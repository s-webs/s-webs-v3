<?php

namespace App\Providers;

use App\Models\ProjectCategory;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.master', function ($view): void {
            $categories = ProjectCategory::query()
                ->where('is_active', true)
                ->whereHas('projects', fn ($query) => $query->where('is_active', true))
                ->orderBy('order')
                ->get();
            $view->with('categories', $categories);
        });
    }
}
