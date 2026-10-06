<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Админка') — S-WEBS</title>
    <style>
        :root { font-family: system-ui, sans-serif; color: #17202b; background: #f4f6f8; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        header { background: #17202b; color: white; padding: 1rem 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
        header a { color: white; text-decoration: none; }
        nav { display: flex; gap: .8rem; flex-wrap: wrap; }
        nav a { padding: .35rem .5rem; border-radius: .25rem; }
        nav a:hover { background: #344456; }
        main { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: white; border: 1px solid #dbe0e5; border-radius: .6rem; padding: 1.5rem; margin-bottom: 1rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
        .field { margin-bottom: 1rem; }
        label { display: block; font-weight: 600; margin-bottom: .35rem; }
        input:not([type=checkbox]), select, textarea { width: 100%; padding: .65rem; border: 1px solid #b8c2cc; border-radius: .35rem; font: inherit; }
        textarea { min-height: 140px; }
        input[type=checkbox] { width: 1.2rem; height: 1.2rem; vertical-align: middle; }
        .button, button { display: inline-block; background: #175cd3; color: white; border: 0; border-radius: .35rem; padding: .65rem 1rem; text-decoration: none; font: inherit; cursor: pointer; }
        .button.secondary { background: #506071; }
        button.danger { background: #a72832; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .7rem; border-bottom: 1px solid #e2e6ea; }
        .actions { display: flex; gap: .4rem; align-items: center; }
        .alert { padding: .8rem 1rem; border-radius: .35rem; margin-bottom: 1rem; background: #e3f6e9; }
        .error { color: #a72832; font-size: .9rem; }
        small, .muted { color: #536170; }
        img.preview { max-width: 220px; max-height: 130px; display: block; margin: .5rem 0; }
    </style>
</head>
<body>
    <header>
        <strong><a href="{{ route('admin.index') }}">S-WEBS</a></strong>
        @auth
            <nav>
                <a href="{{ route('admin.resource.index', 'categories') }}">Категории</a>
                <a href="{{ route('admin.resource.index', 'projects') }}">Проекты</a>
                <a href="{{ route('admin.resource.index', 'prices') }}">Цены</a>
                <a href="{{ route('admin.resource.index', 'teams') }}">Команда</a>
                <a href="{{ route('admin.resource.index', 'seo') }}">SEO</a>
            </nav>
            <form method="post" action="{{ route('admin.logout') }}" style="margin-left:auto">
                @csrf
                <button type="submit" class="secondary">Выйти</button>
            </form>
        @endauth
    </header>
    <main>
        @if(session('status')) <div class="alert" role="status">{{ session('status') }}</div> @endif
        @yield('content')
    </main>
</body>
</html>
