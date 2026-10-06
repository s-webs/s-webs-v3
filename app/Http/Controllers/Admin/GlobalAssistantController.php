<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantAction;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Support\AiContent;
use App\Support\AiSettings;
use App\Support\AssistantHtml;
use App\Support\AssistantQuota;
use App\Support\OpenAiHtmlEditor;
use App\Support\OpenAiSuggestions;
use App\Support\ProjectVision;
use App\Support\SeoAudit;
use App\Support\WebpImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GlobalAssistantController extends Controller
{
    public function state(Request $request, AiSettings $settings): JsonResponse
    {
        $action = AssistantAction::query()->where('user_id', $request->user()->id)
            ->where('status', 'pending')->where('expires_at', '>', now())
            ->latest()->first();

        return response()->json([
            'enabled' => $settings->configured(),
            'model' => $settings->model(),
            'csrf_token' => csrf_token(),
            'action' => $action ? $this->present($action) : null,
            'categories' => ProjectCategory::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function audit(AiContent $content, SeoAudit $audit): JsonResponse
    {
        return response()->json(['items' => $this->auditItems($content, $audit)]);
    }

    public function contentTargets(AiContent $content): JsonResponse
    {
        $targets = [];
        foreach (['projects', 'prices', 'teams', 'seo'] as $resource) {
            $model = $content->model($resource);
            foreach ($model::query()->orderBy('id')->get() as $record) {
                $path = match ($resource) {
                    'projects' => '/portfolio/'.$record->getAttribute('slug'),
                    'prices' => '/pricing/'.$record->getAttribute('slug'),
                    'teams' => '/about/'.$record->getAttribute('slug'),
                    'seo' => (string) $record->getAttribute('url'),
                };
                $targets[] = [
                    'resource' => $resource, 'id' => $record->getKey(),
                    'field' => $content->htmlField($resource),
                    'name' => $record->getAttribute('name') ?: $record->getAttribute('url') ?: $record->getAttribute('title'),
                    'url' => url($path),
                ];
            }
        }

        return response()->json(['targets' => $targets]);
    }

    public function planSeo(Request $request, AiContent $content, SeoAudit $audit, OpenAiSuggestions $openAi, AiSettings $settings, AssistantQuota $quota): JsonResponse
    {
        abort_unless($settings->configured(), 503, 'Укажите ключ и модель OpenAI в настройках.');
        $data = $request->validate([
            'instruction' => ['nullable', 'string', 'max:1000'],
            'target' => ['nullable', 'string', 'max:255'],
        ]);
        $items = collect($this->auditItems($content, $audit))->sortByDesc('score')->values();
        if (! empty($data['target'])) {
            $target = trim($data['target'], " \t\n\r\0\x0B\"'«»");
            $exact = $items->filter(static fn (array $item): bool =>
                mb_strtolower($item['name']) === mb_strtolower($target)
                || rtrim(mb_strtolower($item['url']), '/') === rtrim(mb_strtolower($target), '/')
                || $item['resource'].':'.$item['id'] === $target);
            $matches = $exact->isNotEmpty() ? $exact : $items->filter(static fn (array $item): bool =>
                str_contains(mb_strtolower($item['name']), mb_strtolower($target)));
            if ($matches->count() > 1) {
                return response()->json([
                    'message' => 'Найдено несколько страниц с таким названием. Выберите нужную.',
                    'choices' => $matches->map(static fn (array $item): array => [
                        'target' => $item['resource'].':'.$item['id'],
                        'name' => $item['name'], 'url' => $item['url'],
                        'resource' => $item['resource'],
                    ])->values()->all(),
                ], 409);
            }
            if ($matches->isEmpty()) {
                return response()->json(['message' => 'Не нашёл страницу с приоритетными SEO-замечаниями по этому названию.'], 422);
            }
            $items = $matches->values();
        } else {
            $items = $items->filter(fn (array $item): bool => collect($item['issues'])->contains(fn (array $issue): bool => in_array($issue['severity'], ['high', 'medium'], true)))->values();
        }
        $total = $items->count();
        $proposals = [];
        $inputTokens = 0;
        $outputTokens = 0;
        foreach ($items as $item) {
            if (count($proposals) >= 3) break;
            $allowed = array_values(array_intersect($content->fields($item['resource']), array_column($item['issues'], 'field')));
            if ($allowed === []) continue;
            $model = $content->model($item['resource']);
            $record = $model::query()->findOrFail($item['id']);
            $snapshot = $content->snapshot($item['resource'], $record);
            $quota->reserve($request->user()->id);
            try {
                $result = $openAi->generate($item['resource'], $snapshot,
                    ($data['instruction'] ?? 'Исправь обнаруженные SEO-недочёты.').' URL: '.$item['url'].' Проблемы: '.implode('; ', array_column($item['issues'], 'message')),
                    $allowed);
            } catch (RuntimeException $exception) {
                if ($proposals === []) return response()->json(['message' => $exception->getMessage()], 503);
                break;
            }
            $suggestions = array_values(array_filter($result['suggestions'], static fn (array $row): bool => trim($row['value']) !== ''));
            if ($suggestions === []) continue;
            $proposals[] = [
                'resource' => $item['resource'], 'id' => $item['id'], 'url' => $item['url'],
                'name' => $item['name'], 'source_hash' => $content->hash($snapshot),
                'updated_at' => $record->updated_at?->toISOString(),
                'suggestions' => $suggestions,
            ];
            $inputTokens += (int) ($result['input_tokens'] ?? 0);
            $outputTokens += (int) ($result['output_tokens'] ?? 0);
        }
        if ($proposals === []) {
            return response()->json(['message' => 'Подходящих текстовых SEO-правок не найдено.'], 422);
        }

        $action = AssistantAction::query()->create([
            'user_id' => $request->user()->id, 'kind' => 'seo',
            'payload' => ['proposals' => $proposals, 'total_candidates' => $total],
            'input_tokens' => $inputTokens, 'output_tokens' => $outputTokens,
            'expires_at' => now()->addDay(),
        ]);

        return response()->json(['action' => $this->present($action)], 201);
    }

    public function planProject(Request $request, WebpImages $images, ProjectVision $vision, AiSettings $settings, AssistantQuota $quota): JsonResponse
    {
        abort_unless($settings->configured(), 503, 'Укажите ключ и модель OpenAI в настройках.');
        $data = $request->validate([
            'instruction' => ['nullable', 'string', 'max:1000'],
            'images' => ['required', 'array', 'min:1', 'max:3'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $quota->reserve($request->user()->id);
        $paths = [];
        $webp = [];
        try {
            foreach ($request->file('images') as $file) {
                $bytes = $images->encode($file->getRealPath());
                $path = 'assistant-uploads/'.$request->user()->id.'/'.Str::uuid().'.webp';
                if (! Storage::disk('local')->put($path, $bytes)) throw new RuntimeException('Не удалось временно сохранить скриншот.');
                $paths[] = $path;
                $webp[] = $bytes;
            }
            $result = $vision->analyze($data['instruction'] ?? '', $webp,
                ProjectCategory::query()->orderBy('name')->pluck('name', 'id')->all());
            $action = AssistantAction::query()->create([
                'user_id' => $request->user()->id, 'kind' => 'project',
                'payload' => ['fields' => $result['fields'], 'image_count' => count($paths)],
                'files' => $paths, 'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'], 'expires_at' => now()->addDay(),
            ]);
        } catch (\Throwable $exception) {
            foreach ($paths as $path) Storage::disk('local')->delete($path);
            if ($exception instanceof RuntimeException) return response()->json(['message' => $exception->getMessage()], 503);
            throw $exception;
        }

        return response()->json(['action' => $this->present($action)], 201);
    }

    public function planContent(Request $request, AiContent $content, AssistantHtml $html, OpenAiHtmlEditor $openAi, AiSettings $settings, AssistantQuota $quota): JsonResponse
    {
        abort_unless($settings->configured(), 503, 'Укажите ключ и модель OpenAI в настройках.');
        $data = $request->validate([
            'resource' => ['required', Rule::in(['projects', 'prices', 'teams', 'seo'])],
            'item_id' => ['required', 'integer', 'min:1'],
            'field' => ['required', 'string'],
            'instruction' => ['required', 'string', 'max:1000'],
            'current_html' => ['nullable', 'string', 'max:12000'],
        ]);
        $resource = $data['resource'];
        $field = $content->htmlField($resource);
        abort_unless($field === $data['field'], 422, 'Это поле нельзя редактировать через помощника.');
        $model = $content->model($resource);
        $record = $model::query()->findOrFail($data['item_id']);
        $original = (string) $record->getAttribute($field);
        if (array_key_exists('current_html', $data) && $original !== ($data['current_html'] ?? '')) {
            throw ValidationException::withMessages(['content' => 'В редакторе есть несохранённые изменения или запись обновилась. Сохраните и откройте страницу снова.']);
        }
        $html->ensureEditable($original);
        $quota->reserve($request->user()->id);
        try {
            $result = $openAi->generate($resource,
                (string) ($record->getAttribute('name') ?: $record->getAttribute('url') ?: $record->getAttribute('title')),
                $field, $original, $data['instruction']);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
        $action = AssistantAction::query()->create([
            'user_id' => $request->user()->id, 'kind' => 'content',
            'payload' => [
                'resource' => $resource, 'id' => $record->getKey(), 'field' => $field,
                'name' => $record->getAttribute('name') ?: $record->getAttribute('url') ?: $record->getAttribute('title'),
                'original_html' => $original, 'proposed_html' => $result['html'],
                'summary' => $result['summary'],
                'source_hash' => $content->hash($content->snapshot($resource, $record)),
                'updated_at' => $record->updated_at?->toISOString(),
            ],
            'input_tokens' => $result['input_tokens'], 'output_tokens' => $result['output_tokens'],
            'expires_at' => now()->addDay(),
        ]);

        return response()->json(['action' => $this->present($action)], 201);
    }

    public function apply(Request $request, AssistantAction $action, AiContent $content): JsonResponse
    {
        abort_unless($action->user_id === $request->user()->id, 403);
        if ($action->status !== 'pending' || $action->expires_at->isPast()) {
            throw ValidationException::withMessages(['action' => 'Предложение устарело. Создайте новое.']);
        }

        if ($action->kind === 'content') {
            $payload = $action->payload;
            DB::transaction(function () use ($action, $content, $payload): void {
                $locked = AssistantAction::query()->lockForUpdate()->findOrFail($action->id);
                $this->assertPending($locked);
                $model = $content->model($payload['resource']);
                $record = $model::query()->lockForUpdate()->findOrFail($payload['id']);
                if ($content->hash($content->snapshot($payload['resource'], $record)) !== $payload['source_hash']
                    || $record->updated_at?->toISOString() !== $payload['updated_at']) {
                    throw ValidationException::withMessages(['action' => 'Страница изменилась. Создайте новое предложение для текста.']);
                }
                $record->forceFill([$payload['field'] => $payload['proposed_html']])->save();
                $locked->forceFill(['status' => 'applied', 'applied_at' => now()])->save();
            });
            return response()->json([
                'message' => 'Текст страницы обновлён.',
                'content' => [
                    'resource' => $payload['resource'], 'id' => $payload['id'],
                    'field' => $payload['field'], 'html' => $payload['proposed_html'],
                ],
            ]);
        }

        if ($action->kind === 'seo') {
            $count = DB::transaction(function () use ($action, $content): int {
                $locked = AssistantAction::query()->lockForUpdate()->findOrFail($action->id);
                $this->assertPending($locked);
                foreach ($locked->payload['proposals'] as $proposal) {
                    $model = $content->model($proposal['resource']);
                    $record = $model::query()->lockForUpdate()->findOrFail($proposal['id']);
                    if ($content->hash($content->snapshot($proposal['resource'], $record)) !== $proposal['source_hash']
                        || $record->updated_at?->toISOString() !== $proposal['updated_at']) {
                        throw ValidationException::withMessages(['action' => 'Одна из страниц изменилась. Проведите SEO-анализ заново.']);
                    }
                    $changes = [];
                    foreach ($proposal['suggestions'] as $suggestion) {
                        $field = $suggestion['field'];
                        if (! in_array($field, $content->fields($proposal['resource']), true)) continue;
                        $value = $content->normalize($proposal['resource'], $field, $suggestion['value']);
                        if (mb_strlen($value) > 12000 || (str_ends_with($field, 'title') && mb_strlen($value) > 255)) {
                            throw ValidationException::withMessages(['action' => 'Одно из предложений слишком длинное.']);
                        }
                        $changes[$field] = $value;
                    }
                    if ($changes !== []) $record->forceFill($changes)->save();
                }
                $locked->forceFill(['status' => 'applied', 'applied_at' => now()])->save();
                return count($locked->payload['proposals']);
            });
            try {
                $sitemapUpdated = Artisan::call('sitemap:generate') === 0;
            } catch (\Throwable) {
                $sitemapUpdated = false;
            }
            return response()->json([
                'message' => 'SEO-правки сохранены для '.$count.' страниц.'.($sitemapUpdated ? ' Карта сайта обновлена.' : ' Карту сайта нужно обновить вручную.'),
                'count' => $count,
            ]);
        }

        abort_unless($action->kind === 'project', 404);
        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('project_categories', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:6000'],
            'year' => ['required', 'integer', 'between:1900,2100'],
            'client' => ['required', 'string', 'max:255'],
            'link' => ['nullable', 'url', 'max:255'],
            'seo_h1' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_image_alt' => ['nullable', 'string', 'max:255'],
            'publish' => ['required', 'boolean'],
        ]);
        $publicPaths = [];
        try {
            $project = DB::transaction(function () use ($action, $content, $data, &$publicPaths): Project {
                $locked = AssistantAction::query()->lockForUpdate()->findOrFail($action->id);
                $this->assertPending($locked);
                foreach ($locked->files ?? [] as $path) {
                    if (! Storage::disk('local')->exists($path)) throw new RuntimeException('Временный скриншот не найден. Загрузите его повторно.');
                    $publicPath = 'site-media/portfolio-projects/'.Str::uuid().'.webp';
                    if (! Storage::disk('public')->put($publicPath, Storage::disk('local')->get($path))) {
                        throw new RuntimeException('Не удалось сохранить изображение проекта.');
                    }
                    $publicPaths[] = $publicPath;
                }
                if ($publicPaths === []) throw new RuntimeException('У проекта нет скриншотов.');
                $base = Str::slug($data['name']) ?: 'project';
                $slug = $base;
                for ($suffix = 2; Project::query()->where('slug', $slug)->exists(); $suffix++) $slug = $base.'-'.$suffix;
                $fields = [
                    'category_id' => $data['category_id'], 'name' => trim($data['name']),
                    'description' => $content->normalize('projects', 'description', $data['description']),
                    'year' => $data['year'], 'client' => trim($data['client']),
                    'link' => $data['link'] ?? null, 'slug' => $slug,
                    'is_active' => $data['publish'], 'favorite' => false, 'order' => 0,
                    'image_main' => 'storage/'.$publicPaths[0], 'image_preview' => 'storage/'.$publicPaths[0],
                    'seo_h1' => $data['seo_h1'] ?? null, 'seo_title' => $data['seo_title'] ?? null,
                    'seo_description' => $data['seo_description'] ?? null,
                    'seo_image_alt' => $data['seo_image_alt'] ?? null,
                ];
                foreach (array_slice($publicPaths, 1, 2) as $index => $path) {
                    $fields['image_770x500_'.($index + 1)] = 'storage/'.$path;
                }
                $project = (new Project)->forceFill($fields);
                $project->save();
                $locked->forceFill(['status' => 'applied', 'applied_at' => now()])->save();
                return $project;
            });
        } catch (\Throwable $exception) {
            foreach ($publicPaths as $path) Storage::disk('public')->delete($path);
            if ($exception instanceof RuntimeException) return response()->json(['message' => $exception->getMessage()], 422);
            throw $exception;
        }
        foreach ($action->files ?? [] as $path) Storage::disk('local')->delete($path);
        $sitemapUpdated = true;
        if ($project->is_active) {
            try {
                $sitemapUpdated = Artisan::call('sitemap:generate') === 0;
            } catch (\Throwable) {
                $sitemapUpdated = false;
            }
        }

        return response()->json([
            'message' => $project->is_active
                ? 'Проект создан и опубликован.'.($sitemapUpdated ? '' : ' Карту сайта нужно обновить вручную.')
                : 'Проект сохранён как черновик.',
            'project' => ['id' => $project->id, 'url' => url('/admin/projects/'.$project->id.'/edit')],
        ]);
    }

    public function discard(Request $request, AssistantAction $action): JsonResponse
    {
        abort_unless($action->user_id === $request->user()->id, 403);
        if ($action->status === 'pending') {
            $action->forceFill(['status' => 'discarded'])->save();
            foreach ($action->files ?? [] as $path) Storage::disk('local')->delete($path);
        }
        return response()->json(['message' => 'Предложение отклонено.']);
    }

    private function assertPending(AssistantAction $action): void
    {
        if ($action->status !== 'pending' || $action->expires_at->isPast()) {
            throw ValidationException::withMessages(['action' => 'Предложение устарело. Создайте новое.']);
        }
    }

    private function present(AssistantAction $action): array
    {
        return [
            'id' => $action->id, 'kind' => $action->kind,
            'payload' => $action->payload, 'expires_at' => $action->expires_at,
        ];
    }

    private function auditItems(AiContent $content, SeoAudit $audit): array
    {
        $items = [];
        foreach (['seo', 'categories', 'projects', 'prices', 'teams'] as $resource) {
            $model = $content->model($resource);
            $query = $model::query()->orderBy('id');
            if (in_array($resource, ['categories', 'projects', 'prices'], true)) $query->where('is_active', true);
            if ($resource === 'teams') $query->where('active', true);
            foreach ($query->get() as $record) {
                $issues = $audit->inspect($resource, $record);
                if ($issues === []) continue;
                $items[] = [
                    'resource' => $resource, 'id' => $record->getKey(),
                    'name' => $record->getAttribute('name') ?: $record->getAttribute('url') ?: '#'.$record->getKey(),
                    'url' => $issues[0]['url'], 'issues' => $issues,
                    'score' => array_sum(array_map(static fn (array $issue): int => match ($issue['severity']) {
                        'high' => 3, 'medium' => 2, 'low' => 1, default => 0,
                    }, $issues)),
                ];
            }
        }
        usort($items, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        return $items;
    }
}
