<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminSettingsAndUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_settings_and_admin_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/admin/settings')->assertForbidden();
        $this->getJson('/admin/admins')->assertForbidden();
        $this->putJson('/admin/settings', [])->assertForbidden();
        $this->postJson('/admin/admins', [])->assertForbidden();
    }

    public function test_api_key_is_encrypted_and_never_returned(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $key = 'sk-test-secret-key-that-must-stay-private';
        $payload = [
            'api_key' => $key, 'model' => 'test-model', 'enabled' => true,
            'max_output_tokens' => 1600, 'daily_request_limit' => 30, 'daily_token_limit' => 100000,
        ];

        $this->actingAs($admin)->putJson('/admin/settings', $payload)
            ->assertOk()->assertDontSee($key);
        $this->getJson('/admin/settings')->assertOk()->assertJsonPath('has_key', true)
            ->assertJsonMissing(['api_key' => $key])->assertDontSee($key);
        $stored = DB::table('site_settings')->where('key', 'ai.key')->value('value');
        $this->assertNotSame($key, $stored);
        $this->assertStringNotContainsString($key, $stored);

        $this->putJson('/admin/settings', [...$payload, 'api_key' => ''])->assertOk();
        $this->assertSame($stored, DB::table('site_settings')->where('key', 'ai.key')->value('value'));
        $this->deleteJson('/admin/settings/api-key')->assertOk()->assertJsonPath('has_key', false);
        $this->getJson('/admin/settings')->assertJsonPath('enabled', false)->assertJsonPath('has_key', false);
    }

    public function test_admin_can_create_edit_and_delete_other_admin(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $created = $this->actingAs($owner)->postJson('/admin/admins', [
            'name' => 'Редактор', 'email' => 'editor@example.test', 'password' => 'long-password-123',
        ])->assertCreated()->assertDontSee('long-password-123')->json('admin.id');
        $other = User::query()->findOrFail($created);
        $this->assertTrue($other->is_admin);
        $this->assertTrue(Hash::check('long-password-123', $other->password));

        $this->putJson('/admin/admins/'.$created, [
            'name' => 'Новый редактор', 'email' => 'editor@example.test', 'password' => '',
        ])->assertOk();
        $this->assertSame('Новый редактор', $other->fresh()->name);
        $this->assertTrue(Hash::check('long-password-123', $other->fresh()->password));
        $this->getJson('/admin/admins')->assertOk()->assertDontSee('password');

        $this->deleteJson('/admin/admins/'.$owner->id)->assertUnprocessable();
        $this->deleteJson('/admin/admins/'.$created)->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $created]);
    }

    public function test_last_admin_and_short_password_are_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->postJson('/admin/admins', [
            'name' => 'Другой', 'email' => 'other@example.test', 'password' => 'short',
        ])->assertUnprocessable();
        $this->deleteJson('/admin/admins/'.$admin->id)->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
