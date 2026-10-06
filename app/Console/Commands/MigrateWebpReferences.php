<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateWebpReferences extends Command
{
    protected $signature = 'images:migrate-webp {--write : Обновить БД после сухого прогона} {--manifest= : Путь к manifest JSON}';

    protected $description = 'Проверить или заменить локальные ссылки на изображения из WebP manifest';

    private const COLUMNS = [
        'project_categories' => ['image', 'seo_og_image'],
        'projects' => ['image_main', 'image_preview', 'image_770x500_1', 'image_770x500_2', 'image_370x600', 'image_370x400', 'seo_og_image', 'description'],
        'prices' => ['seo_og_image', 'description'],
        'teams' => ['image', 'seo_og_image', 'portfolio'],
        'seo' => ['og_image', 'text'],
    ];

    public function handle(): int
    {
        $manifestPath = $this->option('manifest') ?: storage_path('app/webp-manifest.json');
        if (! is_file($manifestPath)) {
            $this->error('Manifest не найден: '.$manifestPath);
            return self::FAILURE;
        }
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (! is_array($manifest['files'] ?? null)) {
            $this->error('Manifest имеет неверный формат.');
            return self::FAILURE;
        }

        $mapping = [];
        foreach ($manifest['files'] as $file) {
            if (! in_array($file['status'] ?? '', ['ready', 'already-exists', 'refresh'], true)) continue;
            $from = $file['from'] ?? '';
            $to = $file['to'] ?? '';
            if (! str_starts_with($from, '/') || ! str_starts_with($to, '/')) continue;
            if (! is_file(public_path(ltrim($to, '/')))) continue;
            $mapping[$from] = $to;
            $mapping[ltrim($from, '/')] = ltrim($to, '/');
            if (str_starts_with($from, '/site-media/')) {
                $mapping['storage'.$from] = ltrim($to, '/');
                $mapping['/storage'.$from] = $to;
            }
        }
        if ($mapping === []) {
            $this->warn('Готовых WebP-файлов по manifest не найдено.');
            return self::FAILURE;
        }

        $changedRows = 0;
        $changedFields = 0;
        $changes = [];
        $backup = [];
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) continue;
            $columns = array_values(array_filter($columns, static fn (string $column): bool => Schema::hasColumn($table, $column)));
            DB::table($table)->select(array_merge(['id'], $columns))->orderBy('id')->chunkById(100, function ($rows) use ($table, $columns, $mapping, &$changedRows, &$changedFields, &$changes, &$backup): void {
                foreach ($rows as $row) {
                    $updates = [];
                    foreach ($columns as $column) {
                        $old = $row->$column;
                        if (! is_string($old) || $old === '') continue;
                        $new = in_array($column, ['description', 'portfolio', 'text'], true)
                            ? $this->replaceHtmlImages($old, $mapping)
                            : $this->replaceUrl($old, $mapping);
                        if ($new !== $old) $updates[$column] = $new;
                    }
                    if ($updates === []) continue;
                    $changedRows++;
                    $changedFields += count($updates);
                    $this->line($table.' #'.$row->id.': '.implode(', ', array_keys($updates)));
                    $changes[] = compact('table', 'updates') + ['id' => $row->id];
                    $backup[] = ['table' => $table, 'id' => $row->id, 'values' => array_intersect_key((array) $row, $updates)];
                }
            });
        }

        if ($this->option('write') && $changes !== []) {
            $backupPath = storage_path('app/webp-db-backup-'.now()->format('Ymd-His').'.json');
            if (file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false) {
                $this->error('Не удалось сохранить резервную копию изменяемых значений БД.');
                return self::FAILURE;
            }
            DB::transaction(function () use ($changes): void {
                foreach ($changes as $change) {
                    DB::table($change['table'])->where('id', $change['id'])->update($change['updates']);
                }
            });
            $this->info('Копия старых значений: '.$backupPath);
        }

        $this->info(($this->option('write') ? 'Обновлено' : 'Будет обновлено').": {$changedRows} записей, {$changedFields} полей.");
        return self::SUCCESS;
    }

    private function replaceHtmlImages(string $html, array $mapping): string
    {
        return preg_replace_callback('~(<img\b[^>]*?\b(?:src|data-src)\s*=\s*)(["\'])(.*?)\2~is',
            fn (array $match): string => $match[1].$match[2].$this->replaceUrl($match[3], $mapping).$match[2],
            $html) ?? $html;
    }

    private function replaceUrl(string $value, array $mapping): string
    {
        $parsed = parse_url($value);
        if ($parsed === false) return $value;
        if (isset($parsed['host']) && strcasecmp($parsed['host'], (string) parse_url(config('app.url'), PHP_URL_HOST)) !== 0) return $value;
        $path = $parsed['path'] ?? '';
        if (! isset($mapping[$path])) return $value;
        return str_replace($path, $mapping[$path], $value);
    }
}
