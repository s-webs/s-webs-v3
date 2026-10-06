<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Панель управления — S-WEBS</title>
    @vite('resources/js/admin.js')
</head>
<body>
<div id="admin-app"><main class="loading-shell">Панель управления S-WEBS загружается…</main></div>
</body>
</html>
