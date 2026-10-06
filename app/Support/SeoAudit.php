<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class SeoAudit
{
    public function inspect(string $resource, Model $item): array
    {
        $prefix = $resource === 'seo' ? '' : 'seo_';
        $title = trim((string) $item->getAttribute($prefix.'title'));
        $description = trim((string) $item->getAttribute($prefix.'description'));
        $h1 = $resource === 'seo' ? trim((string) $item->getAttribute('title')) : trim((string) $item->getAttribute('seo_h1'));
        $canonical = trim((string) $item->getAttribute($prefix.'canonical'));
        $robots = (string) $item->getAttribute($prefix.'robots');
        $ogTitle = trim((string) $item->getAttribute($prefix.'og_title'));
        $ogDescription = trim((string) $item->getAttribute($prefix.'og_description'));
        $ogImage = trim((string) $item->getAttribute($prefix.'og_image'));
        $alt = trim((string) $item->getAttribute($prefix.'image_alt'));
        $path = match ($resource) {
            'categories' => '/portfolio-'.$item->getKey(),
            'projects' => '/portfolio/'.$item->getAttribute('slug'),
            'prices' => '/pricing/'.$item->getAttribute('slug'),
            'teams' => '/about/'.$item->getAttribute('slug'),
            'seo' => (string) $item->getAttribute('url'),
            default => '/',
        };
        $url = url($path);
        $issues = [];
        $add = static function (string $field, string $severity, string $message) use (&$issues, $url): void {
            $issues[] = compact('field', 'severity', 'message', 'url');
        };

        if ($title === '') $add($prefix.'title', 'high', 'Не заполнен SEO title.');
        elseif (mb_strlen($title) > 60) $add($prefix.'title', 'medium', 'SEO title длиннее 60 символов.');
        elseif ($item->newQuery()->where($prefix.'title', $title)->where($item->getKeyName(), '!=', $item->getKey())->exists()) $add($prefix.'title', 'medium', 'SEO title повторяется в другой записи этого раздела.');
        if ($description === '') $add($prefix.'description', 'high', 'Не заполнено SEO description.');
        elseif (mb_strlen($description) > 160) $add($prefix.'description', 'medium', 'SEO description длиннее 160 символов.');
        elseif ($item->newQuery()->where($prefix.'description', $description)->where($item->getKeyName(), '!=', $item->getKey())->exists()) $add($prefix.'description', 'medium', 'SEO description повторяется в другой записи этого раздела.');
        if ($h1 === '') $add($resource === 'seo' ? 'title' : 'seo_h1', 'medium', 'Не задан заголовок H1.');
        if ($canonical !== '' && filter_var($canonical, FILTER_VALIDATE_URL) === false) $add($prefix.'canonical', 'high', 'Некорректный canonical URL.');
        if (str_contains($robots, 'noindex')) $add($prefix.'robots', 'info', 'Страница закрыта от индексации.');
        if ($ogTitle === '') $add($prefix.'og_title', 'low', 'Не задан Open Graph title.');
        if ($ogDescription === '') $add($prefix.'og_description', 'low', 'Не задан Open Graph description.');
        if ($ogImage === '') $add($prefix.'og_image', 'low', 'Не задано изображение Open Graph.');
        elseif (! preg_match('~^https?://~i', $ogImage) && ! is_file(public_path(ltrim($ogImage, '/')))) $add($prefix.'og_image', 'high', 'Локальное изображение Open Graph не найдено.');
        if ($ogImage !== '' && $alt === '') $add($prefix.'image_alt', 'low', 'Не задан alt для изображения соцсетей.');

        return $issues;
    }
}
