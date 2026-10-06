<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiDraft;
use App\Support\AiContent;
use App\Support\AiSettings;
use App\Support\OpenAiSuggestions;
use App\Support\SeoAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AiDraftController extends Controller
{
    public function audit(string $resource, int $id, AiContent $content, SeoAudit $audit): JsonResponse
    {
        $model = $content->model($resource);
        $item = $model::query()->findOrFail($id);

        return response()->json(['issues' => $audit->inspect($resource, $item)]);
    }

    public function store(Request $request, AiContent $content, OpenAiSuggestions $openAi, AiSettings $settings): JsonResponse
    {
        abort_unless($settings->configured(), 503, 'ИИ-помощник отключён или не настроен.');
        $data = $request->validate([
            'resource' => ['required', Rule::in(['categories', 'projects', 'prices', 'teams', 'seo'])],
            'item_id' => ['nullable', 'integer', 'min:1'],
            'instruction' => ['required', 'string', 'max:1000'],
            'current' => ['nullable', 'array'],
            'current.*' => ['nullable', 'string', 'max:12000'],
        ]);

        $todayCount = AiDraft::query()->where('user_id', $request->user()->id)
            ->whereDate('created_at', today())->count();
        if ($todayCount >= $settings->dailyRequestLimit()) {
            throw ValidationException::withMessages(['instruction' => 'Дневной лимит запросов исчерпан.']);
        }
        $usedTokens = (int) AiDraft::query()->where('user_id', $request->user()->id)
            ->whereDate('created_at', today())->sum('input_tokens')
            + (int) AiDraft::query()->where('user_id', $request->user()->id)
                ->whereDate('created_at', today())->sum('output_tokens');
        if ($usedTokens >= $settings->dailyTokenLimit()) {
            throw ValidationException::withMessages(['instruction' => 'Дневной лимит токенов исчерпан.']);
        }
        $rateKey = 'ai-daily-attempts:'.$request->user()->id.':'.today()->toDateString();
        if (RateLimiter::tooManyAttempts($rateKey, $settings->dailyRequestLimit())) {
            throw ValidationException::withMessages(['instruction' => 'Дневной лимит запросов исчерпан.']);
        }
        RateLimiter::hit($rateKey, 86400);

        $resource = $data['resource'];
        $model = $content->model($resource);
        $item = isset($data['item_id']) ? $model::query()->findOrFail($data['item_id']) : null;
        $snapshot = $content->snapshot($resource, $item ?? ($data['current'] ?? []));
        if (strlen(json_encode($snapshot)) > 30000) {
            throw ValidationException::withMessages(['current' => 'Слишком большой контекст для ИИ.']);
        }

        try {
            $result = $openAi->generate($resource, $snapshot, $data['instruction'], $content->fields($resource));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        $draft = AiDraft::query()->create([
            'user_id' => $request->user()->id,
            'resource' => $resource,
            'item_id' => $item?->getKey(),
            'source_hash' => $content->hash($snapshot),
            'suggestions' => $result['suggestions'],
            'model' => $result['model'],
            'input_tokens' => $result['input_tokens'],
            'output_tokens' => $result['output_tokens'],
            'status' => 'pending',
        ]);

        return response()->json(['draft' => $draft], 201);
    }

    public function apply(Request $request, AiDraft $draft, AiContent $content, AiSettings $settings): JsonResponse
    {
        abort_unless($settings->configured(), 503, 'ИИ-помощник отключён или не настроен.');
        abort_unless($draft->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['required', 'string', Rule::in($content->fields($draft->resource))],
        ]);
        $fields = array_unique($data['fields']);
        $proposed = collect($draft->suggestions)->keyBy('field');
        foreach ($fields as $field) {
            if (! $proposed->has($field)) {
                throw ValidationException::withMessages(['fields' => 'Предложение не содержит выбранное поле.']);
            }
        }

        if ($draft->item_id === null) {
            throw ValidationException::withMessages(['fields' => 'Новая запись применяется через форму создания.']);
        }

        $item = DB::transaction(function () use ($draft, $content, $fields, $proposed) {
            $lockedDraft = AiDraft::query()->lockForUpdate()->findOrFail($draft->id);
            $model = $content->model($lockedDraft->resource);
            $item = $model::query()->lockForUpdate()->findOrFail($lockedDraft->item_id);
            if ($lockedDraft->status !== 'pending' || $content->hash($content->snapshot($lockedDraft->resource, $item)) !== $lockedDraft->source_hash) {
                throw ValidationException::withMessages(['fields' => 'Запись изменилась. Обновите её и создайте новое предложение.']);
            }

            $changes = [];
            foreach ($fields as $field) {
                $value = $content->normalize($lockedDraft->resource, $field, $proposed[$field]['value']);
                $max = in_array($field, ['name', 'position', 'short_description', 'title', 'seo_title', 'seo_h1', 'seo_og_title', 'og_title', 'seo_image_alt', 'image_alt'], true) ? 255 : 12000;
                if (mb_strlen($value) > $max) {
                    throw ValidationException::withMessages(['fields' => 'Значение поля '.$field.' слишком длинное.']);
                }
                $changes[$field] = $value;
            }

            $item->forceFill($changes)->save();
            $lockedDraft->forceFill(['status' => 'applied', 'applied_at' => now()])->save();
            return $item;
        });

        return response()->json(['item' => $item, 'message' => 'Выбранные поля применены.']);
    }

    public function discard(Request $request, AiDraft $draft): JsonResponse
    {
        abort_unless($draft->user_id === $request->user()->id, 403);
        $draft->forceFill(['status' => 'discarded'])->save();

        return response()->json(['message' => 'Предложение отклонено.']);
    }
}
