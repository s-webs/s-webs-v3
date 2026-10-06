<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Price;
use App\Models\Team;
use Leeto\Seo\Models\Seo;
use Illuminate\Console\Command;
use XMLWriter;

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
        $writer = new XMLWriter();
        $writer->openURI(public_path('sitemap.xml'));
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ([route('home'), route('portfolio.all'), route('pricing.index'), route('about.index')] as $url) {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            if (!Seo::query()->where('url', $path)->where('robots', 'like', 'noindex%')->exists()) {
                $this->addUrl($writer, $url);
            }
        }

        Project::query()->where('is_active', true)->where('seo_robots', 'not like', 'noindex%')->orderBy('id')->chunk(100, function ($projects) use ($writer): void {
            foreach ($projects as $project) {
                $this->addUrl($writer, route('portfolio.show', $project->slug), $project->updated_at?->toAtomString());
            }
        });

        ProjectCategory::query()->where('is_active', true)
            ->where('seo_robots', 'not like', 'noindex%')
            ->whereHas('projects', fn ($query) => $query->where('is_active', true))
            ->orderBy('id')->chunk(100, function ($categories) use ($writer): void {
                foreach ($categories as $category) {
                    $this->addUrl($writer, route('portfolio.index', $category->id), $category->updated_at?->toAtomString());
                }
            });

        Price::query()->where('is_active', true)->whereNotNull('slug')
            ->where('seo_robots', 'not like', 'noindex%')->orderBy('id')->chunk(100, function ($prices) use ($writer): void {
                foreach ($prices as $price) {
                    $this->addUrl($writer, route('pricing.show', $price->slug), $price->updated_at?->toAtomString());
                }
            });

        Team::query()->where('active', true)->whereNotNull('slug')
            ->where('seo_robots', 'not like', 'noindex%')->orderBy('id')->chunk(100, function ($teams) use ($writer): void {
                foreach ($teams as $team) {
                    $this->addUrl($writer, route('about.show', $team->slug), $team->updated_at?->toAtomString());
                }
            });

        $writer->endElement();
        $writer->endDocument();
        $writer->flush();

        $this->info('Sitemap has been generated successfully.');

        return 0;
    }

    private function addUrl(XMLWriter $writer, string $url, ?string $modified = null): void
    {
        $writer->startElement('url');
        $writer->writeElement('loc', $url);
        if ($modified !== null) {
            $writer->writeElement('lastmod', $modified);
        }
        $writer->endElement();
    }
}
