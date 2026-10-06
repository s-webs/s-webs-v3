<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if (! $request->expectsJson()) return view('admin.shell');

        return response()->json(['admins' => User::query()->where('is_admin', true)
            ->orderBy('id')->get(['id', 'name', 'email', 'created_at'])]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:12', 'max:255'],
        ]);
        $admin = User::query()->create([...$data, 'is_admin' => true]);

        return response()->json(['admin' => $admin->only(['id', 'name', 'email', 'created_at']), 'message' => 'Администратор создан.'], 201);
    }

    public function update(Request $request, User $admin): JsonResponse
    {
        abort_unless($admin->is_admin, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:12', 'max:255'],
        ]);
        if (empty($data['password'])) unset($data['password']);
        $admin->fill($data)->save();

        return response()->json(['admin' => $admin->only(['id', 'name', 'email', 'created_at']), 'message' => 'Администратор обновлён.']);
    }

    public function destroy(Request $request, User $admin): JsonResponse
    {
        abort_unless($admin->is_admin, 404);
        if ($admin->is($request->user())) {
            throw ValidationException::withMessages(['admin' => 'Нельзя удалить собственный аккаунт.']);
        }

        DB::transaction(function () use ($admin): void {
            $admins = User::query()->where('is_admin', true)->lockForUpdate()->pluck('id');
            if ($admins->count() <= 1) {
                throw ValidationException::withMessages(['admin' => 'Нельзя удалить последнего администратора.']);
            }
            if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $admin->id)->delete();
            }
            $admin->delete();
        });

        return response()->json(['message' => 'Администратор удалён.']);
    }
}
