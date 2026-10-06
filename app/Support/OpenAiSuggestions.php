<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

class OpenAiSuggestions
{
    public function __construct(private readonly AiSettings $settings) {}

    public function generate(string $resource, array $snapshot, string $instruction, array $allowed): array
    {
        $key = $this->settings->key();
        $model = $this->settings->model();
        if ($key === '' || $model === '') {
            throw new RuntimeException('OpenAI API не настроен: укажите ключ и модель на сервере.');
        }

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'suggestions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'field' => ['type' => 'string'],
                            'value' => ['type' => 'string'],
                            'reason' => ['type' => 'string'],
                        ],
                        'required' => ['field', 'value', 'reason'],
                    ],
                ],
            ],
            'required' => ['suggestions'],
        ];

        $payload = [
            'model' => $model,
            'store' => false,
            'max_output_tokens' => $this->settings->maxOutputTokens(),
            'input' => [
                ['role' => 'system', 'content' => 'Ты редактор сайта S-WEBS. Предлагай только проверяемые правки для разрешённых полей. Не выдумывай клиентов, цены, сроки, результаты и факты. Сохраняй язык исходной записи. SEO-текст должен быть полезным человеку, без переспама. Для HTML-полей верни обычный текст без HTML. Никаких изменений кода или публикации.'],
                ['role' => 'user', 'content' => json_encode([
                    'resource' => $resource,
                    'allowed_fields' => $allowed,
                    'current' => $snapshot,
                    'task' => $instruction,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ],
            'text' => ['format' => [
                'type' => 'json_schema', 'name' => 'content_suggestions',
                'strict' => true, 'schema' => $schema,
            ]],
        ];

        try {
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $response = Http::withToken($key)->acceptJson()->connectTimeout(10)->timeout(45)
                    ->post('https://api.openai.com/v1/responses', $payload);
                if (! in_array($response->status(), [429, 500, 502, 503, 504], true) || $attempt === 3) {
                    break;
                }
                usleep($attempt * 500000);
            }
        } catch (ConnectionException) {
            throw new RuntimeException('Нет соединения с OpenAI API. Повторите запрос позже.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI API временно недоступен (HTTP '.$response->status().').');
        }

        $text = '';
        foreach ($response->json('output', []) as $output) {
            foreach ($output['content'] ?? [] as $content) {
                if (($content['type'] ?? '') === 'refusal') {
                    throw new RuntimeException('Модель отказалась подготовить предложение.');
                }
                if (($content['type'] ?? '') === 'output_text') {
                    $text .= $content['text'] ?? '';
                }
            }
        }

        if ($text === '') {
            throw new RuntimeException('OpenAI API вернул пустой ответ.');
        }

        try {
            $data = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new RuntimeException('OpenAI API вернул некорректный ответ.');
        }

        if (! is_array($data['suggestions'] ?? null)) {
            throw new RuntimeException('OpenAI API вернул ответ без предложений.');
        }

        $suggestions = [];
        foreach ($data['suggestions'] as $row) {
            if (! is_array($row) || ! in_array($row['field'] ?? null, $allowed, true)
                || ! is_string($row['value'] ?? null) || ! is_string($row['reason'] ?? null)) {
                continue;
            }
            $suggestions[$row['field']] = [
                'field' => $row['field'],
                'value' => mb_substr(trim($row['value']), 0, 12000),
                'reason' => mb_substr(strip_tags($row['reason']), 0, 500),
            ];
        }

        return [
            'suggestions' => array_values($suggestions),
            'model' => $model,
            'input_tokens' => $response->json('usage.input_tokens'),
            'output_tokens' => $response->json('usage.output_tokens'),
        ];
    }
}
