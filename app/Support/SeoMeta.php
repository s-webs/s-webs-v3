<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Leeto\Seo\Models\Seo;

class SeoMeta
{
    public static function forPage(?Model $entity = null): array
    {
        $path = request()->getPathInfo();
        $entry = Seo::query()->where('url', request()->getRequestUri())->first()
            ?? Seo::query()->where('url', $path)->first();
        $entityTitle = $entity?->getAttribute('name');
        $pick = static fn (?string $value, ?string $fallback = null): ?string => filled($value) ? $value : $fallback;
        $title = $pick($entity?->getAttribute('seo_title'), $pick($entry?->title, $entityTitle ?: 'S-WEBS'));
        $description = $pick($entity?->getAttribute('seo_description'), $entry?->description);
        $keywords = $pick($entity?->getAttribute('seo_keywords'), $entry?->keywords);
        $canonical = $pick($entity?->getAttribute('seo_canonical'), $entry?->canonical ?: request()->url());
        $robots = $entity?->getAttribute('seo_robots') ?: $entry?->robots ?: 'index,follow';
        $ogTitle = $pick($entity?->getAttribute('seo_og_title'), $entry?->og_title ?: $title);
        $ogDescription = $pick($entity?->getAttribute('seo_og_description'), $entry?->og_description ?: $description);
        $image = $pick($entity?->getAttribute('seo_og_image'), $entry?->og_image);
        $imageAlt = $pick($entity?->getAttribute('seo_image_alt'), $entry?->image_alt);
        if ($image && !str_starts_with($image, 'http')) {
            $image = url('/'.ltrim($image, '/'));
        }

        return compact('title', 'description', 'keywords', 'canonical', 'robots', 'ogTitle', 'ogDescription', 'image', 'imageAlt');
    }
}
