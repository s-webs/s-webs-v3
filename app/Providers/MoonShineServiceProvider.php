<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Resources\PricingResource;
use App\MoonShine\Resources\ProjectCategoryResource;
use App\MoonShine\Resources\ProjectResource;
use App\MoonShine\Resources\SeoResource;
use App\MoonShine\Resources\TeamResource;
use MoonShine\Providers\MoonShineApplicationServiceProvider;
use MoonShine\MoonShine;
use MoonShine\Menu\MenuGroup;
use MoonShine\Menu\MenuItem;
use MoonShine\Resources\MoonShineUserResource;
use MoonShine\Resources\MoonShineUserRoleResource;
use MoonShine\Contracts\Resources\ResourceContract;
use MoonShine\Menu\MenuElement;
use MoonShine\Pages\Page;
use Closure;

class MoonShineServiceProvider extends MoonShineApplicationServiceProvider
{
    /**
     * @return list<ResourceContract>
     */
    protected function resources(): array
    {
        return [];
    }

    /**
     * @return list<Page>
     */
    protected function pages(): array
    {
        return [];
    }

    /**
     * @return Closure|list<MenuElement>
     */
    protected function menu(): array
    {
        return [
            MenuGroup::make('Портфолио', [
                MenuItem::make('Категории', new ProjectCategoryResource()),
                MenuItem::make('Проекты', new ProjectResource())
            ]),

            MenuItem::make('Цены', new PricingResource()),
            MenuItem::make('SEO', new SeoResource()),
            MenuItem::make('Команда', new TeamResource()),

            MenuItem::make('Документация MoonShine', 'https://moonshine-laravel.com/docs')
                ->badge(fn() => 'Check')
                ->blank(),

            MenuGroup::make(static fn() => __('moonshine::ui.resource.system'), [
                MenuItem::make(
                    static fn() => __('moonshine::ui.resource.admins_title'),
                    new MoonShineUserResource()
                ),
                MenuItem::make(
                    static fn() => __('moonshine::ui.resource.role_title'),
                    new MoonShineUserRoleResource()
                ),
            ]),
        ];
    }

    /**
     * @return Closure|array{css: string, colors: array, darkColors: array}
     */
    protected function theme(): array
    {
        return [];
    }
}
