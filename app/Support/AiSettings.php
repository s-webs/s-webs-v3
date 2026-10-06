<?php

namespace App\Support;

use App\Models\SiteSetting;

class AiSettings
{
    private function value(string $name, mixed $fallback): mixed
    {
        $setting = SiteSetting::query()->find('ai.'.$name);

        return $setting === null ? $fallback : $setting->value;
    }

    public function key(): string
    {
        return (string) $this->value('key', config('ai.key'));
    }

    public function model(): string
    {
        return (string) $this->value('model', config('ai.model'));
    }

    public function enabled(): bool
    {
        return filter_var($this->value('enabled', config('ai.enabled')), FILTER_VALIDATE_BOOLEAN);
    }

    public function configured(): bool
    {
        return $this->enabled() && $this->key() !== '' && $this->model() !== '';
    }

    public function maxOutputTokens(): int
    {
        return (int) $this->value('max_output_tokens', config('ai.max_output_tokens'));
    }

    public function dailyRequestLimit(): int
    {
        return (int) $this->value('daily_request_limit', config('ai.daily_request_limit'));
    }

    public function dailyTokenLimit(): int
    {
        return (int) $this->value('daily_token_limit', config('ai.daily_token_limit'));
    }

    public function set(string $name, string|int|bool $value): void
    {
        SiteSetting::query()->updateOrCreate(['key' => 'ai.'.$name], ['value' => (string) $value]);
    }
}
