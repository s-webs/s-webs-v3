<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\AssistantAction;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('admin:create {email}', function (string $email): void {
    $name = $this->ask('Имя администратора');
    $password = $this->secret('Пароль (не менее 12 символов)');

    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $name || ! $password || mb_strlen($password) < 12) {
        $this->error('Укажите корректный email, имя и пароль длиной от 12 символов.');
        return;
    }

    $user = User::firstOrNew(['email' => $email]);
    $user->name = $name;
    $user->password = Hash::make($password);
    $user->is_admin = true;
    $user->save();

    $this->info('Администратор создан или обновлён.');
})->purpose('Создать администратора сайта');

Artisan::command('assistant:prune', function (): void {
    AssistantAction::query()->where('expires_at', '<', now())->chunkById(100, function ($actions): void {
        foreach ($actions as $action) {
            foreach ($action->files ?? [] as $path) Storage::disk('local')->delete($path);
            $action->delete();
        }
    });
    $this->info('Просроченные предложения и временные скриншоты удалены.');
})->purpose('Удалить просроченные предложения помощника')->daily();
