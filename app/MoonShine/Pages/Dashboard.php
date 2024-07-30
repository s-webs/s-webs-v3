<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Project;
use App\Models\ProjectCategory;
use MoonShine\Decorations\Block;
use MoonShine\Decorations\Column;
use MoonShine\Decorations\Divider;
use MoonShine\Decorations\Grid;
use MoonShine\Decorations\LineBreak;
use MoonShine\Metrics\DonutChartMetric;
use MoonShine\Metrics\LineChartMetric;
use MoonShine\Metrics\ValueMetric;
use MoonShine\Pages\Page;
use MoonShine\Components\MoonShineComponent;

class Dashboard extends Page
{
    /**
     * @return array<string, string>
     */
    public function breadcrumbs(): array
    {
        return [
            '#' => $this->title()
        ];
    }

    public function title(): string
    {
        return $this->title ?: 'Панель управления';
    }

    private function getColorForCategory(string $categoryName): string
    {
        // Замените на реальные имена категорий и цвета
        $colors = [
            'Category 1' => '#ff0000',
            'Category 2' => '#00ff00',
            'Category 3' => '#0000ff',
            // Добавьте больше цветов, если нужно
        ];

        return $colors[$categoryName] ?? '#000000'; // Черный цвет по умолчанию
    }


    /**
     * @return list<MoonShineComponent>
     */
    public function components(): array
    {
        // Получаем данные с группировкой по категориям
        $projectCountsByCategory = Project::query()
            ->selectRaw('category_id, COUNT(*) as project_count')
            ->groupBy('category_id')
            ->pluck('project_count', 'category_id');
        // Подготавливаем данные для DonutChartMetric
        $values = $projectCountsByCategory->mapWithKeys(function ($count, $categoryId) {
            $category = ProjectCategory::query()->find($categoryId);
            return [$category->name => $count];
        })->toArray();


        return [
            Grid::make([
                Column::make([
                    ValueMetric::make('Категории')
                        ->value(ProjectCategory::query()->count()),
                    ValueMetric::make('Проекты')
                        ->value(Project::query()->count())
                        ->customAttributes(['class' => 'mt-8']),
                ])->columnSpan(4),
                Column::make([
                    DonutChartMetric::make('Проекты')
                        ->values($values),
                ])->columnSpan(8)
            ]),
        ];
    }
}
