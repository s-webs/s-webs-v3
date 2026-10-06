<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Team;
use App\Support\WebpImages;
use App\Support\AiSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Leeto\Seo\Models\Seo;
use Leeto\Seo\Rules\UrlRule;

class ContentController extends Controller
{
    private const TITLES = [
        'categories' => 'Категории портфолио',
        'projects' => 'Проекты',
        'prices' => 'Услуги и цены',
        'teams' => 'Команда',
        'seo' => 'SEO',
    ];

    private const MODELS = [
        'categories' => ProjectCategory::class,
        'projects' => Project::class,
        'prices' => Price::class,
        'teams' => Team::class,
        'seo' => Seo::class,
    ];

    private const IMAGE_FIELDS = [
        'categories' => ['image', 'seo_og_image'],
        'projects' => ['image_main', 'image_preview', 'image_770x500_1', 'image_770x500_2', 'image_370x600', 'image_370x400', 'seo_og_image'],
        'prices' => ['seo_og_image'],
        'teams' => ['image', 'seo_og_image'],
        'seo' => ['og_image'],
    ];

    public function dashboard(Request $request): View|JsonResponse
    {
        $counts = [];
        foreach (self::MODELS as $key => $model) {
            $counts[$key] = $model::query()->count();
        }

        if ($request->expectsJson()) {
            return response()->json(['titles' => self::TITLES, 'counts' => $counts]);
        }

        return view('admin.shell');
    }

    public function index(Request $request, string $resource): View|JsonResponse
    {
        $model = $this->model($resource);

        if ($request->expectsJson()) {
            $query = $model::query()->orderByDesc('id');
            $search = trim((string) $request->query('search', ''));
            if ($search !== '') {
                $column = $resource === 'seo' ? 'url' : 'name';
                $query->where($column, 'like', '%'.$search.'%');
            }
            if (in_array($resource, ['categories', 'projects', 'prices', 'teams'], true)
                && in_array($request->query('status'), ['active', 'inactive'], true)) {
                $column = $resource === 'teams' ? 'active' : 'is_active';
                $query->where($column, $request->query('status') === 'active');
            }

            return response()->json($query->paginate(20));
        }

        return view('admin.shell');
    }

    public function create(string $resource): View|JsonResponse
    {
        $model = $this->model($resource);
        return $this->form($resource, new $model);
    }

    public function edit(string $resource, int $id): View|JsonResponse
    {
        $model = $this->model($resource);
        return $this->form($resource, $model::query()->findOrFail($id));
    }

    public function store(Request $request, string $resource): RedirectResponse|JsonResponse
    {
        $model = $this->model($resource);
        $item = new $model;
        $this->save($request, $resource, $item);

        if ($request->expectsJson()) {
            return response()->json(['item' => $item, 'message' => 'Запись создана.'], 201);
        }

        return redirect()->route('admin.resource.index', $resource)->with('status', 'Запись создана.');
    }

    public function update(Request $request, string $resource, int $id): RedirectResponse|JsonResponse
    {
        $model = $this->model($resource);
        $item = $model::query()->findOrFail($id);
        $this->save($request, $resource, $item);

        if ($request->expectsJson()) {
            return response()->json(['item' => $item, 'message' => 'Запись обновлена.']);
        }

        return redirect()->route('admin.resource.index', $resource)->with('status', 'Запись обновлена.');
    }

    public function destroy(Request $request, string $resource, int $id): RedirectResponse|JsonResponse
    {
        $model = $this->model($resource);
        $model::query()->findOrFail($id)->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Запись удалена.']);
        }

        return redirect()->route('admin.resource.index', $resource)->with('status', 'Запись удалена.');
    }

    private function model(string $resource): string
    {
        abort_unless(isset(self::MODELS[$resource]), 404);
        return self::MODELS[$resource];
    }

    private function form(string $resource, Model $item): View|JsonResponse
    {
        $fields = $this->fields($resource);
        $categories = $resource === 'projects'
            ? ProjectCategory::query()->orderBy('name')->pluck('name', 'id')
            : collect();

        if (request()->expectsJson()) {
            return response()->json([
                'resource' => $resource,
                'title' => self::TITLES[$resource],
                'item' => $item,
                'fields' => $fields,
                'categories' => $categories,
                'ai_enabled' => app(AiSettings::class)->configured(),
            ]);
        }

        return view('admin.shell');
    }

    private function fields(string $resource): array
    {
        $fields = match ($resource) {
            'categories' => [
                ['name', 'Название', 'text'], ['order', 'Порядок', 'number'],
                ['is_active', 'Активна', 'checkbox'], ['image', 'Изображение', 'file'],
            ],
            'projects' => [
                ['category_id', 'Категория', 'select'], ['name', 'Название', 'text'],
                ['slug', 'Slug', 'text'], ['description', 'Описание', 'html'],
                ['year', 'Год разработки', 'number'], ['client', 'Клиент', 'text'],
                ['link', 'Ссылка', 'url'], ['order', 'Порядок', 'number'],
                ['is_active', 'Активен', 'checkbox'], ['favorite', 'Избранное', 'checkbox'],
                ['image_main', 'Главное изображение', 'file'], ['image_preview', 'Превью', 'file'],
                ['image_770x500_1', 'Галерея 1', 'file'], ['image_770x500_2', 'Галерея 2', 'file'],
                ['image_370x600', 'Галерея 3', 'file'], ['image_370x400', 'Галерея 4', 'file'],
            ],
            'prices' => [
                ['name', 'Название', 'text'], ['slug', 'Slug', 'text'],
                ['short_description', 'Краткое описание', 'text'],
                ['description', 'Контент', 'html'],
                ['cost', 'Цена', 'number'], ['new_cost', 'Новая цена', 'number'],
                ['options', 'Опции (JSON)', 'textarea'], ['link', 'Ссылка', 'url'],
                ['order', 'Порядок', 'number'],
                ['is_active', 'Активна', 'checkbox'], ['is_popular', 'Популярная', 'checkbox'],
            ],
            'teams' => [
                ['name', 'Имя', 'text'], ['slug', 'Slug', 'text'],
                ['position', 'Должность', 'text'], ['portfolio', 'Портфолио', 'html'],
                ['social', 'Социальные сети (JSON)', 'textarea'], ['order', 'Порядок', 'number'],
                ['active', 'Активен', 'checkbox'], ['image', 'Фото', 'file'],
            ],
            'seo' => [
                ['url', 'URL страницы', 'text'], ['title', 'Title', 'text'],
                ['description', 'Description', 'textarea'], ['keywords', 'Keywords', 'text'],
                ['text', 'SEO текст', 'html'],
            ],
        };

        if ($resource === 'seo') {
            return array_merge($fields, [
                ['canonical', 'Canonical URL', 'url'], ['robots', 'Индексация', 'robots'],
                ['og_title', 'Open Graph title', 'text'], ['og_description', 'Open Graph description', 'textarea'],
                ['og_image', 'Изображение для соцсетей', 'file'], ['image_alt', 'Alt изображения', 'text'],
            ]);
        }

        return array_merge($fields, [
            ['seo_h1', 'Заголовок H1', 'text'],
            ['seo_title', 'SEO title', 'text'], ['seo_description', 'SEO description', 'textarea'],
            ['seo_keywords', 'SEO keywords', 'text'], ['seo_canonical', 'Canonical URL', 'url'],
            ['seo_robots', 'Индексация', 'robots'], ['seo_og_title', 'Open Graph title', 'text'],
            ['seo_og_description', 'Open Graph description', 'textarea'],
            ['seo_og_image', 'Изображение для соцсетей', 'file'], ['seo_image_alt', 'Alt изображения', 'text'],
        ]);
    }

    private function rules(string $resource, ?int $id): array
    {
        $rules = match ($resource) {
            'categories' => [
                'name' => ['required', 'string', 'max:255'],
                'order' => ['required', 'integer'],
                'is_active' => ['required', 'boolean'],
            ],
            'projects' => [
                'category_id' => ['required', 'integer', 'exists:project_categories,id'],
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', Rule::unique('projects', 'slug')->ignore($id)],
                'description' => ['required', 'string'],
                'year' => ['required', 'integer', 'between:1900,2100'],
                'client' => ['required', 'string', 'max:255'],
                'link' => ['nullable', 'url', 'max:255'],
                'order' => ['required', 'integer'],
                'is_active' => ['required', 'boolean'],
                'favorite' => ['required', 'boolean'],
            ],
            'prices' => [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['nullable', 'string', 'max:255', Rule::unique('prices', 'slug')->ignore($id)],
                'short_description' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'cost' => ['required', 'integer', 'min:0'],
                'new_cost' => ['nullable', 'integer', 'min:0'],
                'options' => ['nullable', 'json'],
                'link' => ['nullable', 'url'],
                'seo_title' => ['nullable', 'string', 'max:255'],
                'seo_description' => ['nullable', 'string'],
                'seo_keywords' => ['nullable', 'string'],
                'order' => ['required', 'integer'],
                'is_active' => ['required', 'boolean'],
                'is_popular' => ['required', 'boolean'],
            ],
            'teams' => [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['nullable', 'string', 'max:255', Rule::unique('teams', 'slug')->ignore($id)],
                'position' => ['required', 'string', 'max:255'],
                'portfolio' => ['nullable', 'string'],
                'social' => ['nullable', 'json'],
                'order' => ['required', 'integer'],
                'active' => ['required', 'boolean'],
            ],
            'seo' => [
                'url' => ['required', 'string', 'max:255', new UrlRule, Rule::unique('seo', 'url')->ignore($id)],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'keywords' => ['nullable', 'string'],
                'text' => ['nullable', 'string'],
            ],
        };

        $seoPrefix = $resource === 'seo' ? '' : 'seo_';
        foreach (['canonical', 'og_title', 'image_alt'] as $field) {
            $rules[$seoPrefix.$field] = $field === 'canonical'
                ? ['nullable', 'url', 'max:2048']
                : ['nullable', 'string', 'max:255'];
        }
        foreach (['og_description'] as $field) {
            $rules[$seoPrefix.$field] = ['nullable', 'string'];
        }
        $rules[$seoPrefix.'robots'] = ['nullable', Rule::in(['index,follow', 'noindex,follow', 'noindex,nofollow'])];
        if ($resource !== 'seo') {
            $rules['seo_h1'] = ['nullable', 'string', 'max:255'];
            $rules['seo_title'] = ['nullable', 'string', 'max:255'];
            $rules['seo_description'] = ['nullable', 'string'];
            $rules['seo_keywords'] = ['nullable', 'string'];
        }

        foreach (self::IMAGE_FIELDS[$resource] ?? [] as $field) {
            $rules[$field.'_upload'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
        }

        if ($resource === 'projects' && $id === null) {
            $rules['image_main_upload'][0] = 'required';
            $rules['image_preview_upload'][0] = 'required';
        }

        return $rules;
    }

    private function save(Request $request, string $resource, Model $item): void
    {
        $data = $request->validate($this->rules($resource, $item->exists ? $item->getKey() : null));

        foreach (['options', 'social'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $data[$field] === null ? null : json_decode($data[$field], true);
            }
        }

        foreach (self::IMAGE_FIELDS[$resource] ?? [] as $field) {
            unset($data[$field.'_upload']);
            if ($request->hasFile($field.'_upload')) {
                $directory = str_contains($field, 'og_image') ? 'site-media/seo'
                    : ($resource === 'teams' ? 'site-media/team' : 'site-media/portfolio-projects');
                try {
                    $data[$field] = app(WebpImages::class)->store($request->file($field.'_upload'), $directory);
                } catch (\RuntimeException $exception) {
                    throw ValidationException::withMessages([$field.'_upload' => $exception->getMessage()]);
                }
            }
        }

        $item->forceFill($data)->save();
    }
}
