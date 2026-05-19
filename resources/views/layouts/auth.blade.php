<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Вход</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="helpdesk-body" style="--app-background-image: url('{{ asset('assets/images/app-background.jpg') }}');">
    <header class="helpdesk-header d-flex align-items-center">
        <div class="brand-wrap px-3 d-flex align-items-center">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Заявки" class="brand-image">
        </div>
    </header>

    <main class="helpdesk-auth-main d-flex align-items-center justify-content-center">
        @yield('content')
    </main>
</body>
</html>
