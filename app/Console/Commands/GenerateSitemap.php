<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectCategory;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap for the website';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sitemap = Sitemap::create();

        // Добавьте статические страницы
        $sitemap->add(Url::create('/'))
            ->add(Url::create('/portfolio-all'))
            ->add(Url::create('/pricing'));


        // Добавьте динамические страницы (например, статьи)
        Project::query()->where('is_active', true)->get()->each(function (Project $project) use ($sitemap) {
            $sitemap->add(Url::create(route('portfolio.show', $project->slug))
                ->setLastModificationDate($project->updated_at));
        });

        // Добавьте динамические страницы категорий проектов, у которых is_active = true
        ProjectCategory::query()->where('is_active', true)->each(function (ProjectCategory $category) use ($sitemap) {
            if ($category->projects()->where('is_active', true)->exists()) {
                $sitemap->add(Url::create(route('portfolio.index', ['id' => $category->id]))
                    ->setLastModificationDate($category->updated_at));
            }
        });

        // Запишите карту сайта в файл
        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap has been generated successfully.');

        return 0;
    }
}
