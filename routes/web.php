<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\Pages\HomeController::class, 'index'])->name('home');
Route::get('/portfolio/{slug}', [\App\Http\Controllers\Pages\PortfolioController::class, 'show'])->name('portfolio.show');
Route::get('/portfolio-all', [\App\Http\Controllers\Pages\PortfolioController::class, 'all'])->name('portfolio.all');
Route::get('/portfolio-{id}', [\App\Http\Controllers\Pages\PortfolioController::class, 'index'])->name('portfolio.index');
