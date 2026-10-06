<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ProjectVision
{
    private const FIELDS = [
        'name', 'description', 'year', 'client', 'link',
        'seo_h1', 'seo_title', 'seo_description', 'seo_image_alt',
    ];

    public function __construct(private readonly AiSettings $settings) {}

    public function analyze(string $instruction, array $webpImages, array $categories): array
    {
        $properties = [];
        foreach (self::FIELDS as $field) $properties[$field] = ['type' => 'string'];
        $content = [['type' => 'input_text', 'text' => json_encode([
            'instruction' => $instruction,
            'categories' => $categories,
            'task' => 'Подготовь карточку проекта для портфолио по скриншотам и словам пользователя. Если факт нельзя прочитать или вывести надёжно, оставь поле пустым. Не выдумывай клиента, год, ссылку, результаты или технологии. Описание — обычный текст на русском, SEO-поля без переспама.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]];
        foreach ($webpImages as $bytes) {
            $content[] = [
                'type' => 'input_image',
                'image_url' => 'data:image/webp;base64,'.base64_encode($bytes),
                'detail' => 'high',
            ];
        }

        try {
            $response = Http::withToken($this->settings->key())->acceptJson()->connectTimeout(10)->timeout(90)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $this->settings->model(),
                    'store' => false,
                    'max_output_tokens' => $this->settings->maxOutputTokens(),
                    'input' => [
                        ['role' => 'system', 'content' => 'Ты редактор портфолио S-WEBS. Извлекай только проверяемые сведения со скриншотов и из запроса. Не добавляй придуманные факты.'],
                        ['role' => 'user', 'content' => $content],
                    ],
                    'text' => ['format' => [
                        'type' => 'json_schema', 'name' => 'portfolio_project', 'strict' => true,
                        'schema' => ['type' => 'object', 'additionalProperties' => false, 'properties' => $properties, 'required' => self::FIELDS],
                    ]],
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Нет соединения с OpenAI API. Повторите запрос позже.');
        }
        if (! $response->successful()) {
            throw new RuntimeException('OpenAI API временно недоступен (HTTP '.$response->status().').');
        }

        $text = '';
        foreach ($response->json('output', []) as $output) {
            foreach ($output['content'] ?? [] as $part) {
                if (($part['type'] ?? '') === 'refusal') throw new RuntimeException('Модель отказалась обработать скриншоты.');
                if (($part['type'] ?? '') === 'output_text') $text .= $part['text'] ?? '';
            }
        }
        try {
            $data = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new RuntimeException('OpenAI API вернул некорректный ответ.');
        }
        if (! is_array($data)) throw new RuntimeException('OpenAI API вернул некорректный ответ.');
        $fields = [];
        foreach (self::FIELDS as $field) {
            $value = $data[$field] ?? '';
            $fields[$field] = is_string($value) ? mb_substr(trim(strip_tags($value)), 0, $field === 'description' ? 6000 : 255) : '';
        }

        return [
            'fields' => $fields,
            'model' => $this->settings->model(),
            'input_tokens' => $response->json('usage.input_tokens'),
            'output_tokens' => $response->json('usage.output_tokens'),
        ];
    }
}
