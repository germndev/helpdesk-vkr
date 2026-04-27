@extends('layouts.app')

@section('content')
<div class="p-3 p-md-4">
    <div class="content-card p-4">
        <h1 class="mb-4">{{ $pageTitle }}</h1>

        @if(auth()->user()->isAdmin() || auth()->user()->isSupport())
            @php($isAdmin = auth()->user()->isAdmin())
            <div class="row g-3">
                <div class="col-md-6">
                    <{{ $isAdmin ? 'a' : 'div' }} class="stat-card stat-card-link" @if($isAdmin) href="{{ route('admin.tickets.index', ['status' => ['in_progress']]) }}" @endif>
                        <div class="stat-title">В работе</div>
                        <div class="stat-value">{{ $stats['in_progress'] }}</div>
                    </{{ $isAdmin ? 'a' : 'div' }}>
                </div>
                <div class="col-md-6">
                    <{{ $isAdmin ? 'a' : 'div' }} class="stat-card stat-card-success stat-card-link" @if($isAdmin) href="{{ route('admin.tickets.index', ['status' => ['resolved', 'closed']]) }}" @endif>
                        <div class="stat-title">Завершенные заявки</div>
                        <div class="stat-value">{{ $stats['completed'] }}</div>
                    </{{ $isAdmin ? 'a' : 'div' }}>
                </div>
                <div class="col-md-6">
                    <{{ $isAdmin ? 'a' : 'div' }} class="stat-card stat-card-info stat-card-link" @if($isAdmin) href="{{ route('admin.tickets.index', ['status' => ['new']]) }}" @endif>
                        <div class="stat-title">Новые</div>
                        <div class="stat-value">{{ $stats['new'] }}</div>
                    </{{ $isAdmin ? 'a' : 'div' }}>
                </div>
                <div class="col-md-6">
                    <{{ $isAdmin ? 'a' : 'div' }} class="stat-card stat-card-danger stat-card-link" @if($isAdmin) href="{{ route('admin.tickets.index', ['priority_missing' => 1]) }}" @endif>
                        <div class="stat-title">Приоритет не определен</div>
                        <div class="stat-value">{{ $stats['manual'] }}</div>
                    </{{ $isAdmin ? 'a' : 'div' }}>
                </div>
            </div>
        @else
            <p class="text-secondary mb-0">Каркас пользовательской/сотруднической части готов. На следующем шаге подключим экран заявок и форму создания по макету.</p>
        @endif
    </div>
</div>
@endsection
