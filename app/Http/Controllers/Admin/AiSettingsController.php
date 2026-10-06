<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AiSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class AiSettingsController extends Controller
{
    public function show(Request $request, AiSettings $settings): View|JsonResponse
    {
        if (! $request->expectsJson()) return view('admin.shell');

        return response()->json([
            'has_key' => $settings->key() !== '',
            'model' => $settings->model(),
            'enabled' => $settings->enabled(),
            'max_output_tokens' => $settings->maxOutputTokens(),
            'daily_request_limit' => $settings->dailyRequestLimit(),
            'daily_token_limit' => $settings->dailyTokenLimit(),
        ]);
    }

    public function update(Request $request, AiSettings $settings): JsonResponse
    {
        $data = $request->validate([
            'api_key' => ['nullable', 'string', 'min:20', 'max:512'],
            'model' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'enabled' => ['required', 'boolean'],
            'max_output_tokens' => ['required', 'integer', 'between:256,8000'],
            'daily_request_limit' => ['required', 'integer', 'between:1,1000'],
            'daily_token_limit' => ['required', 'integer', 'between:1000,10000000'],
        ]);

        if ($data['enabled'] && empty($data['api_key']) && $settings->key() === '') {
            throw ValidationException::withMessages(['api_key' => 'Для включения ИИ укажите API-ключ.']);
        }
        if ($data['enabled'] && empty($data['model'])) {
            throw ValidationException::withMessages(['model' => 'Для включения ИИ укажите модель.']);
        }

        foreach (['model', 'enabled', 'max_output_tokens', 'daily_request_limit', 'daily_token_limit'] as $field) {
            $settings->set($field, $data[$field] ?? '');
        }
        if (! empty($data['api_key'])) $settings->set('key', $data['api_key']);
        Log::info('AI settings updated', ['admin_id' => $request->user()->id, 'key_changed' => ! empty($data['api_key'])]);

        return response()->json(['message' => 'Настройки сохранены.', 'has_key' => $settings->key() !== '']);
    }

    public function destroyKey(Request $request, AiSettings $settings): JsonResponse
    {
        $settings->set('key', '');
        $settings->set('enabled', false);
        Log::info('AI API key removed', ['admin_id' => $request->user()->id]);

        return response()->json(['message' => 'API-ключ удалён.', 'has_key' => false]);
    }
}
