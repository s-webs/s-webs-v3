<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProjectCategory;

use MoonShine\Decorations\Tab;
use MoonShine\Decorations\Tabs;
use MoonShine\Fields\Image;
use MoonShine\Fields\Number;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Text;
use MoonShine\Resources\ModelResource;
use MoonShine\Decorations\Block;
use MoonShine\Fields\ID;
use MoonShine\Fields\Field;
use MoonShine\Components\MoonShineComponent;

/**
 * @extends ModelResource<ProjectCategory>
 */
class ProjectCategoryResource extends ModelResource
{
    protected string $model = ProjectCategory::class;

    protected string $title = 'Категории проектов';

    protected string $column = 'name';

    protected bool $editInModal = true;
    protected bool $createInModal = true;

    public function redirectAfterSave(): string
    {
        return '/crm/resource/project-category-resource/index-page';
    }

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            ID::make()->sortable(),
            Block::make([
                Text::make('Название', 'name'),
                Image::make('Изображение', 'image')->nullable(),
                Number::make('Сортировка', 'order')->default(1),
                Switcher::make('Активировать', 'is_active')
                    ->default(true)
            ])
        ];
    }

    protected function resolveOrder(): static
    {
        $this->sortColumn = 'order';
        $this->sortDirection = 'ASC';

        return parent::resolveOrder();
    }

    /**
     * @param ProjectCategory $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }
}
