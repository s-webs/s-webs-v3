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
        $categories = ProjectCategory::query()
            ->where('is_active', 1)
            ->whereHas('projects')
            ->orderBy('order')
            ->get();
        View::share('categories', $categories);
    }
}
