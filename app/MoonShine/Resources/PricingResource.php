<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Price;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Fields\Json;
use MoonShine\Fields\Number;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Text;
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
            Block::make([
                ID::make()->sortable(),
                Text::make('Название', 'name'),
                Text::make('Краткое описание', 'short_description')->nullable(),
                Number::make('Цена', 'cost')->nullable(),
                Number::make('Новая цена', 'new_cost')->nullable(),
                Json::make('Опции', 'options')
                    ->onlyValue()
                    ->removable(),
                Switcher::make('Акивен', 'is_active')
                    ->default(true),
                Switcher::make('Популярное', 'is_popular')
                    ->default(false),
                Number::make('Сортировка', 'order'),
                Url::make('Url', 'link')->nullable(),
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
