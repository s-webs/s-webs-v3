<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\ProjectCategory;
use Illuminate\Http\Request;
use Leeto\Seo\Seo;

class PricingController extends Controller
{
    public function index()
    {
        $prices = Price::query()->where('is_active', true)->get();
        return view('pages.pricing.index', compact('prices'));
    }

    public function show(String $slug)
    {
        $categories = ProjectCategory::query()
            ->where('is_active', 1)
            ->whereHas('projects')
            ->orderBy('order')
            ->get();

        $item = Price::query()->where('slug', $slug)->firstOrFail();
        $prices = Price::query()->where('is_active', true)->orderBy('created_at', 'desc')->limit(5)->get();
        Seo::title($item->seo_title ?? $item->name);
        Seo::description($item->seo_description ?? 'Хотите создать сайт? Давайте сделаем это!');
        Seo::keywords($item->seo_keywords ?? 'создать сайт, разработать сайт, сколько стоит сайт, создать сайт цена, создать сайт в шымкенте');
        return view('pages.pricing.show', compact('item', 'categories', 'prices'));
    }
}
