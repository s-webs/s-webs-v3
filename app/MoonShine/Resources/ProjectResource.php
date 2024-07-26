<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\Project;

use MoonShine\Decorations\Tab;
use MoonShine\Decorations\Tabs;
use MoonShine\Enums\PageType;
use MoonShine\Fields\Image;
use MoonShine\Fields\Number;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Slug;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Text;
use MoonShine\Fields\Textarea;
use MoonShine\Fields\Url;
use MoonShine\Resources\ModelResource;
use MoonShine\Decorations\Block;
use MoonShine\Fields\ID;
use MoonShine\Fields\Field;
use MoonShine\Components\MoonShineComponent;

/**
 * @extends ModelResource<Project>
 */
class ProjectResource extends ModelResource
{
    protected string $model = Project::class;

    protected string $title = 'Проекты';

    protected bool $editInModal = true;
    protected bool $createInModal = true;

    public function redirectAfterSave(): string
    {
        return '/crm/resource/project-resource/index-page';
    }

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Block::make([
                ID::make()->sortable(),
                Tabs::make([
                    Tab::make('Основная информация', [
                        BelongsTo::make('Категория', 'category', resource: new ProjectCategoryResource()),
                        Text::make('Название', 'name'),
                        Textarea::make('Описание', 'description')
                            ->hideOnIndex(),
                        Number::make('Год разработки', 'year'),
                        Text::make('Клиент', 'client')
                            ->hideOnIndex(),
                        Url::make('Ссылка', 'link')
                            ->nullable()
                        ->hideOnIndex(),
                        Switcher::make('Активно', 'is_active'),
                        Switcher::make('Избранное', 'favorite'),
                        Number::make('Сортировка', 'order'),
                        Slug::make('Slug', 'slug')
                            ->from('name')
                            ->hideOnIndex()
                    ]),
                    Tab::make('Изображения', [
                        Image::make('Главное изображение', 'image_main')
                            ->disk('public')
                            ->dir('site-media/portfolio-projects')
                            ->removable()
                            ->hideOnIndex(),
                        Image::make('Превью', 'image_preview')
                            ->disk('public')
                            ->dir('site-media/portfolio-projects')
                            ->removable()
                            ->hideOnIndex(),
                        Image::make('image_770x500_1')
                            ->disk('public')
                            ->dir('site-media/portfolio-projects')
                            ->removable()
                            ->hideOnIndex(),
                        Image::make('image_770x500_2')
                            ->disk('public')
                            ->dir('site-media/portfolio-projects')
                            ->removable()
                            ->hideOnIndex(),
                        Image::make('image_370x600')
                            ->disk('public')
                            ->dir('site-media/portfolio-projects')
                            ->removable()
                            ->hideOnIndex(),
                        Image::make('image_370x400')
                            ->disk('public')
                            ->dir('site-media/portfolio-projects')
                            ->removable()
                            ->hideOnIndex(),
                    ]),
                ])
            ]),
        ];
    }

    protected function resolveOrder(): static
    {
        $this->sortColumn = 'order';
        $this->sortDirection = 'DESC';

        return parent::resolveOrder();
    }

    /**
     * @param Project $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }
}
