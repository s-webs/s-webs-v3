<?php

use Illuminate\Support\Facades\Route;

Route::get('/info', function () {
    phpinfo();
});
Route::get('/', [\App\Http\Controllers\Pages\HomeController::class, 'index'])->name('home');
Route::get('/portfolio/{slug}', [\App\Http\Controllers\Pages\PortfolioController::class, 'show'])->name('portfolio.show');
Route::get('/portfolio-all', [\App\Http\Controllers\Pages\PortfolioController::class, 'all'])->name('portfolio.all');
Route::get('/portfolio-{id}', [\App\Http\Controllers\Pages\PortfolioController::class, 'index'])->name('portfolio.index');
Route::get('/pricing', [\App\Http\Controllers\Pages\PricingController::class, 'index'])->name('pricing.index');
Route::get('/pricing/{slug}', [\App\Http\Controllers\Pages\PricingController::class, 'show'])->name('pricing.show');
Route::get('/about', [\App\Http\Controllers\Page\AboutController::class, 'index'])->name('about.index');
Route::get('/about/{slug}', [\App\Http\Controllers\Page\AboutController::class, 'show'])->name('about.show');
