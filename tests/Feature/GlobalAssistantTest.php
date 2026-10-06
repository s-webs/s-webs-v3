<?php

namespace Tests\Feature;

use App\Models\AssistantAction;
use App\Models\Price;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GlobalAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('ai.enabled', true);
        config()->set('ai.key', 'test-api-key');
        config()->set('ai.model', 'gpt-6.1-sol');
    }

    public function test_sitewide_audit_requires_admin_and_lists_affected_urls(): void
    {
        $price = (new Price)->forceFill([
            'name' => 'Тестовая услуга', 'slug' => 'test-service', 'cost' => 1000, 'is_active' => true,
        ]);
        $price->save();
        $this->actingAs(User::factory()->create())->getJson('/admin/assistant/audit')->assertForbidden();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->getJson('/admin/assistant/audit')->assertOk()
            ->assertJsonPath('items.0.url', url('/pricing/test-service'));
    }

    public function test_seo_plan_changes_content_only_after_explicit_apply(): void
    {
        Http::fake(['api.openai.com/*' => Http::response([
            'output' => [['content' => [['type' => 'output_text', 'text' => json_encode(['suggestions' => [
                ['field' => 'seo_title', 'value' => 'Разработка сайтов для бизнеса', 'reason' => 'Понятный заголовок'],
                ['field' => 'seo_description', 'value' => 'Создаём удобные сайты для бизнеса.', 'reason' => 'Описание услуги'],
            ]], JSON_UNESCAPED_UNICODE)]]]],
            'usage' => ['input_tokens' => 120, 'output_tokens' => 50],
        ])]);
        $admin = User::factory()->create(['is_admin' => true]);
        $price = (new Price)->forceFill([
            'name' => 'Разработка сайтов', 'slug' => 'sites', 'cost' => 1000, 'is_active' => true,
        ]);
        $price->save();

        $id = $this->actingAs($admin)->postJson('/admin/assistant/seo/plan', [
            'instruction' => 'Исправь SEO',
        ])->assertCreated()->json('action.id');
        $this->assertNull($price->fresh()->seo_title);
        $this->assertSame('pending', AssistantAction::query()->findOrFail($id)->status);
        $this->postJson('/admin/assistant/actions/'.$id.'/apply')->assertOk();
        $this->assertSame('Разработка сайтов для бизнеса', $price->fresh()->seo_title);
        $this->postJson('/admin/assistant/actions/'.$id.'/apply')->assertUnprocessable();
    }

    public function test_seo_plan_asks_which_page_when_names_repeat(): void
    {
        Http::fake(['api.openai.com/*' => Http::response([
            'output' => [['content' => [['type' => 'output_text', 'text' => json_encode(['suggestions' => [
                ['field' => 'seo_title', 'value' => 'Сайт визитка для бизнеса', 'reason' => 'Ясный заголовок'],
            ]], JSON_UNESCAPED_UNICODE)]]]],
            'usage' => ['input_tokens' => 30, 'output_tokens' => 12],
        ])]);
        $admin = User::factory()->create(['is_admin' => true]);
        $category = (new ProjectCategory)->forceFill(['name' => 'Сайт визитка', 'is_active' => true]);
        $category->save();
        (new Price)->forceFill(['name' => 'Сайт визитка', 'slug' => 'sait-vizitka', 'cost' => 1000, 'is_active' => true])->save();

        $this->actingAs($admin)->postJson('/admin/assistant/seo/plan', [
            'instruction' => 'Улучши SEO страницы «Сайт визитка»', 'target' => 'Сайт визитка',
        ])->assertStatus(409)->assertJsonCount(2, 'choices');
        Http::assertNothingSent();

        $this->postJson('/admin/assistant/seo/plan', [
            'instruction' => 'Улучши SEO страницы «Сайт визитка»', 'target' => 'categories:'.$category->id,
        ])->assertCreated()->assertJsonCount(1, 'action.payload.proposals')
            ->assertJsonPath('action.payload.proposals.0.resource', 'categories');
    }

    public function test_specific_page_can_prepare_lower_priority_seo_fixes(): void
    {
        Http::fake(['api.openai.com/*' => Http::response([
            'output' => [['content' => [['type' => 'output_text', 'text' => json_encode(['suggestions' => [
                ['field' => 'seo_og_title', 'value' => 'Сайт визитка — S-WEBS', 'reason' => 'Заголовок для соцсетей'],
            ]], JSON_UNESCAPED_UNICODE)]]]],
            'usage' => ['input_tokens' => 25, 'output_tokens' => 10],
        ])]);
        $admin = User::factory()->create(['is_admin' => true]);
        $category = (new ProjectCategory)->forceFill([
            'name' => 'Сайт визитка', 'is_active' => true,
            'seo_title' => 'Разработка сайтов визиток',
            'seo_description' => 'Создаём сайты визитки для бизнеса.',
            'seo_h1' => 'Сайты визитки',
        ]);
        $category->save();

        $this->actingAs($admin)->postJson('/admin/assistant/seo/plan', [
            'instruction' => 'Улучши SEO этой страницы', 'target' => 'categories:'.$category->id,
        ])->assertCreated()->assertJsonPath('action.payload.proposals.0.suggestions.0.field', 'seo_og_title');
    }

    public function test_html_editor_content_is_previewed_and_applied_only_after_confirmation(): void
    {
        $original = '<p>Старый <strong>текст</strong> <a href="/portfolio-all">портфолио</a>.</p>';
        Http::fake(['api.openai.com/*' => Http::response([
            'output' => [['content' => [['type' => 'output_text', 'text' => json_encode([
                'html' => '<h2>Новый заголовок</h2><p>Новый <strong>текст</strong> <a href="/portfolio-all" onclick="alert(1)">портфолио</a>.</p><script>alert(1)</script>',
                'summary' => 'Улучшена структура текста.',
            ], JSON_UNESCAPED_UNICODE)]]]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 90],
        ])]);
        $admin = User::factory()->create(['is_admin' => true]);
        $price = (new Price)->forceFill([
            'name' => 'Сайт визитка', 'slug' => 'sait-vizitka', 'cost' => 1000,
            'is_active' => true, 'description' => $original,
        ]);
        $price->save();

        $actionId = $this->actingAs($admin)->postJson('/admin/assistant/content/plan', [
            'resource' => 'prices', 'item_id' => $price->id, 'field' => 'description',
            'instruction' => 'Сделай текст яснее', 'current_html' => $original,
        ])->assertCreated()->assertJsonPath('action.kind', 'content')->json('action.id');
        $this->assertSame($original, $price->fresh()->description);
        $proposal = AssistantAction::query()->findOrFail($actionId)->payload['proposed_html'];
        $this->assertStringContainsString('<h2>Новый заголовок</h2>', $proposal);
        $this->assertStringContainsString('href="/portfolio-all"', $proposal);
        $this->assertStringNotContainsString('onclick', $proposal);
        $this->assertStringNotContainsString('<script', $proposal);

        $this->postJson('/admin/assistant/actions/'.$actionId.'/apply')->assertOk()
            ->assertJsonPath('content.field', 'description');
        $this->assertSame($proposal, $price->fresh()->description);
        $this->postJson('/admin/assistant/actions/'.$actionId.'/apply')->assertUnprocessable();
    }

    public function test_html_editor_rejects_unsaved_changes_and_complex_markup(): void
    {
        Http::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $price = (new Price)->forceFill(['name' => 'Услуга', 'slug' => 'usluga', 'cost' => 1000, 'is_active' => true, 'description' => '<p>Старый текст</p>']);
        $price->save();
        $request = ['resource' => 'prices', 'item_id' => $price->id, 'field' => 'description', 'instruction' => 'Перепиши текст'];

        $this->actingAs($admin)->postJson('/admin/assistant/content/plan', [...$request, 'current_html' => '<p>Несохранённый текст</p>'])->assertUnprocessable();
        $price->forceFill(['description' => '<div class="custom"><p>Старый текст</p></div>'])->save();
        $this->postJson('/admin/assistant/content/plan', [...$request, 'current_html' => $price->description])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_global_chat_can_find_and_prepare_content_without_open_editor(): void
    {
        Http::fake(['api.openai.com/*' => Http::response([
            'output' => [['content' => [['type' => 'output_text', 'text' => json_encode([
                'html' => '<p>Улучшенное описание услуги.</p>',
                'summary' => 'Текст стал понятнее.',
            ], JSON_UNESCAPED_UNICODE)]]]],
            'usage' => ['input_tokens' => 40, 'output_tokens' => 20],
        ])]);
        $price = (new Price)->forceFill([
            'name' => 'Сайт визитка', 'slug' => 'sait-vizitka', 'cost' => 1000,
            'is_active' => true, 'description' => '<p>Описание услуги.</p>',
        ]);
        $price->save();
        $this->getJson('/admin/assistant/content/targets')->assertUnauthorized();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->getJson('/admin/assistant/content/targets')->assertOk()
            ->assertJsonFragment(['resource' => 'prices', 'name' => 'Сайт визитка', 'field' => 'description']);
        $this->postJson('/admin/assistant/content/plan', [
            'resource' => 'prices', 'item_id' => $price->id, 'field' => 'description',
            'instruction' => 'Сделай текст яснее',
        ])->assertCreated()->assertJsonPath('action.kind', 'content');
        $this->assertSame('<p>Описание услуги.</p>', $price->fresh()->description);
    }

    public function test_content_edit_preserves_existing_image_dimensions(): void
    {
        $image = 'http://s-systems.kz/files/1/Portfolio/sites/tts-stud-emhana.s-systems.kz/69e1c2a45eeb1.png';
        $original = '<p><img src="'.$image.'" alt="" width="800" height="533"></p><p>Система учета рабочего времени.</p>';
        Http::fake(['api.openai.com/*' => Http::response([
            'output' => [['content' => [['type' => 'output_text', 'text' => json_encode([
                'html' => '<p><img src="'.$image.'" alt=""></p><p>Система учёта рабочего времени сотрудников.</p>',
                'summary' => 'Текст стал яснее.',
            ], JSON_UNESCAPED_UNICODE)]]]],
            'usage' => ['input_tokens' => 60, 'output_tokens' => 40],
        ])]);
        $category = (new ProjectCategory)->forceFill(['name' => 'Приложения', 'is_active' => true]);
        $category->save();
        $project = (new Project)->forceFill([
            'name' => 'Studenttik emhana / TTS (Time tracking system)',
            'slug' => 'studenttik-emhana-tts-time-tracking-system',
            'category_id' => $category->id, 'description' => $original, 'is_active' => true,
            'year' => 2026, 'client' => 'Тестовый клиент',
            'image_main' => 'main.webp', 'image_preview' => 'preview.webp',
        ]);
        $project->save();

        $actionId = $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->postJson('/admin/assistant/content/plan', [
                'resource' => 'projects', 'item_id' => $project->id, 'field' => 'description',
                'instruction' => 'Сделай текст яснее', 'current_html' => $original,
            ])->assertCreated()->json('action.id');
        $proposal = AssistantAction::query()->findOrFail($actionId)->payload['proposed_html'];
        $this->assertStringContainsString('width="800"', $proposal);
        $this->assertStringContainsString('height="533"', $proposal);
        $this->assertSame($original, $project->fresh()->description);
    }

    public function test_screenshot_project_is_drafted_and_saved_only_after_review(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $fields = [
            'name' => 'Сайт для кафе', 'description' => 'Современный сайт кафе.',
            'year' => '', 'client' => '', 'link' => '', 'seo_h1' => 'Сайт для кафе',
            'seo_title' => 'Сайт для кафе — портфолио S-WEBS',
            'seo_description' => 'Дизайн сайта кафе.', 'seo_image_alt' => 'Главная страница сайта кафе',
        ];
        Http::fake(['api.openai.com/*' => Http::response([
            'output' => [['content' => [['type' => 'output_text', 'text' => json_encode($fields, JSON_UNESCAPED_UNICODE)]]]],
            'usage' => ['input_tokens' => 300, 'output_tokens' => 80],
        ])]);
        $admin = User::factory()->create(['is_admin' => true]);
        $category = (new ProjectCategory)->forceFill(['name' => 'Сайты', 'is_active' => true]);
        $category->save();

        $id = $this->actingAs($admin)->withHeaders(['Accept' => 'application/json'])
            ->post('/admin/assistant/project/plan', [
                'instruction' => 'Оформи проект по скриншоту',
                'images' => [UploadedFile::fake()->image('screen.png', 800, 600)],
            ])->assertCreated()->assertJsonPath('action.payload.fields.name', 'Сайт для кафе')->json('action.id');
        $this->assertDatabaseCount('projects', 0);
        $action = AssistantAction::query()->findOrFail($id);
        Storage::disk('local')->assertExists($action->files[0]);

        $this->postJson('/admin/assistant/actions/'.$id.'/apply', [
            ...$fields, 'category_id' => $category->id, 'year' => 2026,
            'client' => 'Кафе', 'publish' => false,
        ])->assertOk()->assertJsonPath('message', 'Проект сохранён как черновик.');
        $project = Project::query()->firstOrFail();
        $this->assertFalse((bool) $project->is_active);
        $this->assertSame('Сайт для кафе', $project->name);
        $this->assertStringEndsWith('.webp', $project->image_main);
        Storage::disk('public')->assertExists(substr($project->image_main, strlen('storage/')));
        Storage::disk('local')->assertMissing($action->files[0]);
    }
}
