<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Price;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_access_content(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');

        $ordinaryUser = User::factory()->create();
        $this->actingAs($ordinaryUser)->get('/admin')->assertForbidden();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Панель управления');
    }

    public function test_administrator_can_sign_in_and_out(): void
    {
        User::factory()->create(['email' => 'admin@example.test', 'password' => 'test-password-123', 'is_admin' => true]);

        $this->post('/admin/login', ['email' => 'admin@example.test', 'password' => 'test-password-123'])
            ->assertRedirect('/admin');
        $this->assertAuthenticated();

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_regular_user_cannot_sign_in_to_admin(): void
    {
        User::factory()->create(['email' => 'user@example.test', 'password' => 'test-password-123', 'is_admin' => false]);

        $this->post('/admin/login', ['email' => 'user@example.test', 'password' => 'test-password-123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_administrator_can_be_provisioned_from_console(): void
    {
        $this->artisan('admin:create admin@example.test')
            ->expectsQuestion('Имя администратора', 'Администратор')
            ->expectsQuestion('Пароль (не менее 12 символов)', 'secure-password-123')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'admin@example.test', 'is_admin' => 1]);
    }

    public function test_administrator_can_create_project_with_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $category = (new ProjectCategory)->forceFill(['name' => 'Сайты', 'order' => 1, 'is_active' => true]);
        $category->save();

        $this->actingAs($admin)->post('/admin/projects', [
            'category_id' => $category->id,
            'name' => 'Новый сайт',
            'slug' => 'new-site',
            'description' => '<p>Описание</p>',
            'year' => 2026,
            'client' => 'Клиент',
            'order' => 1,
            'is_active' => 1,
            'favorite' => 0,
            'image_main_upload' => UploadedFile::fake()->image('main.png', 2, 2),
            'image_preview_upload' => UploadedFile::fake()->image('preview.png', 2, 2),
        ])->assertRedirect('/admin/projects');

        $project = Project::query()->where('slug', 'new-site')->firstOrFail();
        $this->assertStringStartsWith('storage/site-media/portfolio-projects/', $project->image_main);
        Storage::disk('public')->assertExists(substr($project->image_main, strlen('storage/')));
        $this->assertStringStartsWith('storage/site-media/portfolio-projects/', $project->image_preview);
        $this->assertStringEndsWith('.webp', $project->image_main);
        $image = Storage::disk('public')->get(substr($project->image_main, strlen('storage/')));
        $this->assertSame('image/webp', getimagesizefromstring($image)['mime']);
    }

    public function test_invalid_editor_image_is_rejected_without_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->postJson('/admin/images', [
            'image' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain'),
        ])->assertUnprocessable();
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_administrator_can_use_all_content_sections(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        foreach (['categories', 'projects', 'prices', 'teams', 'seo'] as $resource) {
            $this->actingAs($admin)->get('/admin/'.$resource)->assertOk();
            $this->get('/admin/'.$resource.'/create')->assertOk();
        }

        $this->post('/admin/prices', [
            'name' => 'Разработка сайта', 'slug' => 'website', 'cost' => 1000,
            'options' => '["Дизайн","Разработка"]', 'order' => 1,
            'is_active' => 1, 'is_popular' => 0,
        ])->assertRedirect('/admin/prices');
        $this->assertSame(['Дизайн', 'Разработка'], Price::query()->where('slug', 'website')->firstOrFail()->options);

        $this->post('/admin/teams', [
            'name' => 'Иван', 'position' => 'Разработчик', 'slug' => 'ivan',
            'social' => '[{"icon":"fab fa-github","link":"https://github.com"}]',
            'order' => 1, 'active' => 1,
        ])->assertRedirect('/admin/teams');
        $this->assertCount(1, Team::query()->where('slug', 'ivan')->firstOrFail()->social);

        $this->post('/admin/seo', ['url' => '/about', 'title' => 'О нас'])
            ->assertRedirect('/admin/seo');
        $this->assertDatabaseHas('seo', ['url' => '/about', 'title' => 'О нас']);
    }

    public function test_reactive_admin_exposes_seo_fields_and_publishes_meta_tags(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = (new ProjectCategory)->forceFill(['name' => 'Сайты', 'order' => 1, 'is_active' => true]);
        $category->save();

        $this->actingAs($admin)->getJson('/admin/categories/'.$category->id.'/edit')
            ->assertOk()->assertSee('seo_title');

        $this->postJson('/admin/categories/'.$category->id, [
            '_method' => 'PUT', 'name' => 'Сайты', 'order' => 1, 'is_active' => 1,
            'seo_title' => 'Разработка сайтов — S-WEBS',
            'seo_description' => 'Портфолио студии по разработке сайтов.',
            'seo_h1' => 'Наши сайты',
            'seo_robots' => 'noindex,follow',
            'seo_canonical' => 'https://s-systems.kz/portfolio-'.$category->id,
            'seo_og_title' => 'Сайты S-WEBS',
        ])->assertOk();

        $this->get('/portfolio-'.$category->id)->assertOk()
            ->assertSee('<title>Разработка сайтов — S-WEBS</title>', false)
            ->assertSee('content="noindex,follow"', false)
            ->assertSee('https://s-systems.kz/portfolio-'.$category->id, false)
            ->assertSee('Наши сайты');

        $this->actingAs($admin)->postJson('/admin/seo', [
            'url' => '/about', 'title' => 'О студии S-WEBS',
            'description' => 'Знакомьтесь с нашей командой.',
            'robots' => 'index,follow', 'og_title' => 'Наша команда',
            'image_alt' => 'Команда S-WEBS',
        ])->assertCreated();
        $this->get('/about')->assertOk()
            ->assertSee('<title>О студии S-WEBS</title>', false)
            ->assertSee('content="Наша команда"', false);
    }

    public function test_html_fields_keep_existing_markup_and_render_on_public_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $html = '<h2>Тестовый заголовок</h2><p><strong>Форматирование</strong> и <a href="/about">ссылка</a>.</p>';
        $this->actingAs($admin)->getJson('/admin/prices/create')
            ->assertOk()->assertJsonPath('fields.3.2', 'html');

        $this->postJson('/admin/prices', [
            'name' => 'Тестовая услуга', 'slug' => 'test-service', 'cost' => 1000,
            'order' => 1, 'is_active' => 1, 'is_popular' => 0,
            'description' => $html,
        ])->assertCreated();

        $price = Price::query()->where('slug', 'test-service')->firstOrFail();
        $this->assertSame($html, $price->description);
        $this->getJson('/admin/prices/'.$price->id.'/edit')->assertOk()->assertJsonPath('item.description', $html);
        $this->get('/pricing/test-service')->assertOk()->assertSee($html, false);
    }
}
