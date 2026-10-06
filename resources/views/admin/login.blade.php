@extends('admin.layout')
@section('title', 'Вход')
@section('content')
    <div class="card" style="max-width:440px;margin:auto">
        <h1>Вход в админку</h1>
        <form method="post" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>@error('email')<p class="error">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="password">Пароль</label><input id="password" name="password" type="password" required>@error('password')<p class="error">{{ $message }}</p>@enderror</div>
            <button type="submit">Войти</button>
        </form>
    </div>
@endsection
