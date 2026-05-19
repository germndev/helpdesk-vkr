<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - {{ $pageTitle ?? 'Панель' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="helpdesk-body" style="--app-background-image: url('{{ asset('assets/images/app-background.jpg') }}');">
    <header class="helpdesk-header d-flex align-items-center">
        <div class="brand-wrap px-3 d-flex align-items-center">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Заявки" class="brand-image">
            @if(auth()->user()->isAdmin())
                <span class="admin-mark ms-2">/admin</span>
            @endif
        </div>
    </header>

    <div class="helpdesk-shell d-flex">
        <aside class="helpdesk-sidebar d-flex flex-column justify-content-between">
            <nav class="pt-2">
                @foreach($menuItems as $item)
                    @if(($item['type'] ?? 'text') === 'heading')
                        <div class="sidebar-heading">{{ $item['label'] }}</div>
                    @elseif(!empty($item['route']))
                        <a href="{{ route($item['route'], $item['params'] ?? [], false) }}" class="sidebar-item {{ !empty($item['active']) ? 'active' : '' }}">
                            @if(!empty($item['icon']))
                                <x-icon :name="$item['icon']" class="sidebar-icon" />
                            @endif
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @else
                        <div class="sidebar-item {{ !empty($item['active']) ? 'active' : '' }}">
                            @if(!empty($item['icon']))
                                <x-icon :name="$item['icon']" class="sidebar-icon" />
                            @endif
                            <span>{{ $item['label'] }}</span>
                        </div>
                    @endif
                @endforeach
            </nav>

            <div class="sidebar-user border-top p-2">
                <div class="sidebar-user-row">
                    <img src="{{ auth()->user()->avatar_url }}" alt="Аватар" class="sidebar-avatar">
                    <div class="sidebar-user-text">
                        <div class="sidebar-user-name">{{ auth()->user()->display_name }}</div>
                        <div class="sidebar-user-role">{{ auth()->user()->isAdmin() ? 'Администратор' : (auth()->user()->isSupport() ? 'Сотрудник поддержки' : 'Пользователь') }}</div>
                    </div>
                    <button type="button" class="sidebar-logout-button" data-logout-open aria-label="Выйти">
                        <x-icon name="output.svg" class="logout-icon" />
                    </button>
                </div>
            </div>
        </aside>
        <div class="sidebar-resizer" data-sidebar-resizer aria-hidden="true"></div>

        <main class="helpdesk-content flex-grow-1">
            @yield('content')
        </main>
    </div>

    <div class="logout-overlay" data-logout-overlay hidden>
        <div class="logout-dialog" role="dialog" aria-modal="true" aria-labelledby="logoutDialogTitle">
            <h2 class="logout-dialog-title" id="logoutDialogTitle">Подтверждение выхода</h2>
            <p class="logout-dialog-text">Вы уверены, что хотите выйти?</p>
            <div class="logout-dialog-actions">
                <button type="button" class="btn btn-outline-dark" data-logout-close>Отмена</button>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-dark">Выйти</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
