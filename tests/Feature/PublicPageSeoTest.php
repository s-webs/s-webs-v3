<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPageSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_listing_uses_page_seo_when_team_social_items_are_rendered(): void
    {
        Team::query()->forceCreate([
            'name' => 'Тестовый участник',
            'position' => 'Дизайнер',
            'slug' => 'test-designer',
            'active' => true,
            'social' => [['link' => 'https://example.test', 'icon' => 'icon-link']],
        ]);

        $this->get('/about')->assertOk()->assertSee('<title>S-WEBS</title>', false);
    }
}
