<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Price;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Decorations\Column;
use MoonShine\Decorations\Grid;
use MoonShine\Decorations\Tab;
use MoonShine\Decorations\Tabs;
use MoonShine\Fields\Json;
use MoonShine\Fields\Number;
use MoonShine\Fields\Slug;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Text;
use MoonShine\Fields\Textarea;
use MoonShine\Fields\TinyMce;
use MoonShine\Fields\Url;
use MoonShine\Resources\ModelResource;
use MoonShine\Decorations\Block;
use MoonShine\Fields\ID;
use MoonShine\Fields\Field;
use MoonShine\Components\MoonShineComponent;

/**
 * @extends ModelResource<Price>
 */
class PricingResource extends ModelResource
{
    protected string $model = Price::class;

    protected string $title = 'Цены';

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            ID::make()->sortable(),
            Grid::make([
                Column::make([
                    Block::make([
                        Tabs::make([
                            Tab::make('Основная информация', [
                                Text::make('Название', 'name'),
                                Number::make('Цена', 'cost')->nullable(),
                                Number::make('Новая цена', 'new_cost')->nullable(),
                            ]),
                            Tab::make('Опции', [
                                Json::make('Опции', 'options')
                                    ->onlyValue()
                                    ->removable()
                                ->hideOnIndex(),
                            ]),
                            Tab::make('Контент', [
                                TinyMce::make('Контент', 'description')
                                ->hideOnIndex(),
                            ])
                        ]),
                    ])
                ])->columnSpan(8),
                Column::make([
                    Block::make('SEO', [
                        Text::make('Seo title', 'seo_title')
                        ->hideOnIndex(),
                        Textarea::make('Seo description', 'seo_description')
                        ->hideOnIndex(),
                        Text::make('Seo keywords', 'seo_keywords')
                        ->hideOnIndex(),
                    ]),
                    Block::make('Настройки', [
                        Switcher::make('Активен', 'is_active')
                            ->default(true),
                        Switcher::make('Популярное', 'is_popular')
                            ->default(false),
                        Number::make('Сортировка', 'order'),
                        Slug::make('Slug', 'slug')
                        ->from('name')
                        ->unique(),
                    ])->customAttributes([
                        'class' => 'mt-8'
                    ]),
                ])->columnSpan(4)
            ]),
        ];
    }

    /**
     * @param Pricing $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }
}
