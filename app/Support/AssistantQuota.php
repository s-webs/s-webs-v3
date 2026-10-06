<?php

namespace App\Support;

use App\Models\AiDraft;
use App\Models\AssistantAction;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AssistantQuota
{
    public function __construct(private readonly AiSettings $settings) {}

    public function reserve(int $userId): void
    {
        $key = 'ai-daily-attempts:'.$userId.':'.today()->toDateString();
        $requests = AiDraft::query()->where('user_id', $userId)->whereDate('created_at', today())->count()
            + AssistantAction::query()->where('user_id', $userId)->whereDate('created_at', today())->count();
        if ($requests >= $this->settings->dailyRequestLimit()
            || RateLimiter::tooManyAttempts($key, $this->settings->dailyRequestLimit())) {
            throw ValidationException::withMessages(['message' => 'Дневной лимит ИИ-запросов исчерпан.']);
        }

        $tokens = (int) AiDraft::query()->where('user_id', $userId)->whereDate('created_at', today())->sum('input_tokens')
            + (int) AiDraft::query()->where('user_id', $userId)->whereDate('created_at', today())->sum('output_tokens')
            + (int) AssistantAction::query()->where('user_id', $userId)->whereDate('created_at', today())->sum('input_tokens')
            + (int) AssistantAction::query()->where('user_id', $userId)->whereDate('created_at', today())->sum('output_tokens');
        if ($tokens >= $this->settings->dailyTokenLimit()) {
            throw ValidationException::withMessages(['message' => 'Дневной лимит токенов исчерпан.']);
        }

        RateLimiter::hit($key, 86400);
    }
}
