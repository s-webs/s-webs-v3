<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AiDraftController;
use App\Http\Controllers\Admin\AiSettingsController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\GlobalAssistantController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:5,1'])->name('login.submit');

    Route::middleware(['auth', EnsureAdmin::class])->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [ContentController::class, 'dashboard'])->name('index');
        Route::post('/images', [ImageController::class, 'store'])->name('images.store');
        Route::get('/settings', [AiSettingsController::class, 'show'])->name('settings.show');
        Route::put('/settings', [AiSettingsController::class, 'update'])->name('settings.update');
        Route::delete('/settings/api-key', [AiSettingsController::class, 'destroyKey'])->name('settings.key.destroy');
        Route::get('/admins', [AdminUserController::class, 'index'])->name('admins.index');
        Route::post('/admins', [AdminUserController::class, 'store'])->name('admins.store');
        Route::put('/admins/{admin}', [AdminUserController::class, 'update'])->name('admins.update');
        Route::delete('/admins/{admin}', [AdminUserController::class, 'destroy'])->name('admins.destroy');
        Route::get('/assistant/state', [GlobalAssistantController::class, 'state'])->name('assistant.state');
        Route::get('/assistant/audit', [GlobalAssistantController::class, 'audit'])->name('assistant.audit');
        Route::post('/assistant/seo/plan', [GlobalAssistantController::class, 'planSeo'])->middleware('throttle:10,1')->name('assistant.seo.plan');
        Route::post('/assistant/content/plan', [GlobalAssistantController::class, 'planContent'])->middleware('throttle:10,1')->name('assistant.content.plan');
        Route::get('/assistant/content/targets', [GlobalAssistantController::class, 'contentTargets'])->name('assistant.content.targets');
        Route::post('/assistant/project/plan', [GlobalAssistantController::class, 'planProject'])->middleware('throttle:10,1')->name('assistant.project.plan');
        Route::post('/assistant/actions/{action}/apply', [GlobalAssistantController::class, 'apply'])->name('assistant.actions.apply');
        Route::delete('/assistant/actions/{action}', [GlobalAssistantController::class, 'discard'])->name('assistant.actions.discard');
        Route::post('/ai/drafts', [AiDraftController::class, 'store'])->middleware('throttle:10,1')->name('ai.drafts.store');
        Route::post('/ai/drafts/{draft}/apply', [AiDraftController::class, 'apply'])->name('ai.drafts.apply');
        Route::post('/ai/drafts/{draft}/discard', [AiDraftController::class, 'discard'])->name('ai.drafts.discard');
        Route::get('/ai/audit/{resource}/{id}', [AiDraftController::class, 'audit'])->whereNumber('id')->name('ai.audit');
        Route::get('/{resource}', [ContentController::class, 'index'])->name('resource.index');
        Route::get('/{resource}/create', [ContentController::class, 'create'])->name('resource.create');
        Route::post('/{resource}', [ContentController::class, 'store'])->name('resource.store');
        Route::get('/{resource}/{id}/edit', [ContentController::class, 'edit'])->whereNumber('id')->name('resource.edit');
        Route::put('/{resource}/{id}', [ContentController::class, 'update'])->whereNumber('id')->name('resource.update');
        Route::delete('/{resource}/{id}', [ContentController::class, 'destroy'])->whereNumber('id')->name('resource.destroy');
    });
});
