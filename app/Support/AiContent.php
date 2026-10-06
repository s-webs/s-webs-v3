<?php

namespace App\Support;

use App\Models\Price;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Leeto\Seo\Models\Seo;

class AiContent
{
    private const MODELS = [
        'categories' => ProjectCategory::class,
        'projects' => Project::class,
        'prices' => Price::class,
        'teams' => Team::class,
        'seo' => Seo::class,
    ];

    private const FIELDS = [
        'categories' => ['name', 'seo_h1', 'seo_title', 'seo_description', 'seo_keywords', 'seo_og_title', 'seo_og_description', 'seo_image_alt'],
        'projects' => ['name', 'description', 'seo_h1', 'seo_title', 'seo_description', 'seo_keywords', 'seo_og_title', 'seo_og_description', 'seo_image_alt'],
        'prices' => ['name', 'short_description', 'description', 'seo_h1', 'seo_title', 'seo_description', 'seo_keywords', 'seo_og_title', 'seo_og_description', 'seo_image_alt'],
        'teams' => ['position', 'portfolio', 'seo_h1', 'seo_title', 'seo_description', 'seo_keywords', 'seo_og_title', 'seo_og_description', 'seo_image_alt'],
        'seo' => ['title', 'description', 'keywords', 'text', 'og_title', 'og_description', 'image_alt'],
    ];

    private const HTML_FIELDS = [
        'projects' => 'description',
        'prices' => 'description',
        'teams' => 'portfolio',
        'seo' => 'text',
    ];

    public function htmlField(string $resource): ?string
    {
        return self::HTML_FIELDS[$resource] ?? null;
    }

    public function model(string $resource): string
    {
        abort_unless(isset(self::MODELS[$resource]), 404);

        return self::MODELS[$resource];
    }

    public function fields(string $resource): array
    {
        $this->model($resource);

        return self::FIELDS[$resource];
    }

    public function snapshot(string $resource, Model|array $item): array
    {
        $source = $item instanceof Model ? $item->getAttributes() : $item;
        $result = [];
        foreach ($this->fields($resource) as $field) {
            $result[$field] = mb_substr((string) ($source[$field] ?? ''), 0, 12000);
        }

        return $result;
    }

    public function hash(array $snapshot): string
    {
        return hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function normalize(string $resource, string $field, string $value): string
    {
        $value = trim($value);
        if (($resource === 'projects' || $resource === 'prices') && $field === 'description'
            || $resource === 'teams' && $field === 'portfolio'
            || $resource === 'seo' && $field === 'text') {
            $paragraphs = preg_split('/\R{2,}/u', $value);
            return implode('', array_map(static fn (string $paragraph): string => '<p>'.nl2br(e(trim($paragraph))).'</p>', $paragraphs));
        }

        return strip_tags($value);
    }
}
