<?php

namespace Tests\Feature;

use App\Models\AiDraft;
use App\Models\Price;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('ai.enabled', true);
        config()->set('ai.key', 'test-api-key');
        config()->set('ai.model', 'test-model');
    }

    public function test_only_admin_can_generate_or_audit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/admin/ai/drafts', [
            'resource' => 'prices', 'instruction' => 'Предложи title',
        ])->assertForbidden();
        $this->getJson('/admin/ai/audit/prices/1')->assertForbidden();
    }

    public function test_admin_reviews_and_applies_selected_fields(): void
    {
        Http::fake(['api.openai.com/*' => Http::response([
            'output' => [['content' => [['type' => 'output_text', 'text' => json_encode(['suggestions' => [
                ['field' => 'seo_title', 'value' => 'Разработка сайтов в Казахстане', 'reason' => 'Описывает услугу'],
                ['field' => 'seo_description', 'value' => 'Создаём сайты для бизнеса.', 'reason' => 'Краткое описание'],
            ]], JSON_UNESCAPED_UNICODE)]]]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 40],
        ])]);
        $admin = User::factory()->create(['is_admin' => true]);
        $price = (new Price)->forceFill(['name' => 'Разработка сайтов', 'cost' => 1000, 'seo_title' => 'Старый title']);
        $price->save();

        $draftId = $this->actingAs($admin)->postJson('/admin/ai/drafts', [
            'resource' => 'prices', 'item_id' => $price->id, 'instruction' => 'Улучши SEO',
        ])->assertCreated()->assertJsonPath('draft.status', 'pending')->json('draft.id');

        $this->assertSame('Старый title', $price->fresh()->seo_title);
        $this->postJson('/admin/ai/drafts/'.$draftId.'/apply', ['fields' => ['seo_title']])
            ->assertOk()->assertJsonPath('item.seo_title', 'Разработка сайтов в Казахстане');
        $this->assertNull($price->fresh()->seo_description);
        $this->assertSame('applied', AiDraft::query()->findOrFail($draftId)->status);
        $this->postJson('/admin/ai/drafts/'.$draftId.'/apply', ['fields' => ['seo_title']])->assertUnprocessable();
    }

    public function test_changed_record_cannot_be_overwritten_by_old_draft(): void
    {
        Http::fake(['api.openai.com/*' => Http::response([
            'output' => [['content' => [['type' => 'output_text', 'text' => '{"suggestions":[{"field":"seo_title","value":"Новый title","reason":"Короче"}]}']]]],
        ])]);
        $admin = User::factory()->create(['is_admin' => true]);
        $price = (new Price)->forceFill(['name' => 'Услуга', 'cost' => 1000]);
        $price->save();
        $draftId = $this->actingAs($admin)->postJson('/admin/ai/drafts', [
            'resource' => 'prices', 'item_id' => $price->id, 'instruction' => 'SEO title',
        ])->assertCreated()->json('draft.id');

        $price->forceFill(['seo_title' => 'Правка редактора'])->save();
        $this->postJson('/admin/ai/drafts/'.$draftId.'/apply', ['fields' => ['seo_title']])->assertUnprocessable();
        $this->assertSame('Правка редактора', $price->fresh()->seo_title);
    }

    public function test_invalid_model_output_does_not_create_draft(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['output' => [['content' => [['type' => 'output_text', 'text' => 'not-json']]]]])]);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->postJson('/admin/ai/drafts', [
            'resource' => 'prices', 'instruction' => 'Напиши описание', 'current' => ['name' => 'Услуга'],
        ])->assertStatus(503);
        $this->assertDatabaseCount('ai_drafts', 0);
    }

    public function test_seo_audit_reports_page_url_and_actionable_fields(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $price = (new Price)->forceFill([
            'name' => 'Тестовая услуга', 'slug' => 'test-service', 'cost' => 1000,
            'seo_robots' => 'noindex,follow', 'seo_canonical' => 'invalid-url',
        ]);
        $price->save();

        $issues = $this->actingAs($admin)->getJson('/admin/ai/audit/prices/'.$price->id)
            ->assertOk()->json('issues');
        $this->assertContains('seo_title', array_column($issues, 'field'));
        $this->assertContains('seo_canonical', array_column($issues, 'field'));
        $this->assertContains('seo_robots', array_column($issues, 'field'));
        $this->assertSame(url('/pricing/test-service'), $issues[0]['url']);
    }
}
