@extends('layouts.app')

@section('content')
<div class="p-3 p-md-4">
    <div class="content-card p-3 p-md-4 position-relative">
        <div class="ticket-form-wrap">
            <h1 class="mb-4">Оставить заявку</h1>

            <form action="{{ route('user.tickets.store') }}" method="post" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="title" class="form-label">Название</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" maxlength="255" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Описание</label>
                    <textarea id="description" name="description" rows="5" class="form-control @error('description') is-invalid @enderror" maxlength="5000" required>{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="attachments" class="form-label">Файл</label>
                    <input id="attachments" name="attachments[]" type="file" class="form-control @error('attachments.*') is-invalid @enderror" multiple>
                    @error('attachments.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-dark ticket-submit-btn">Отправить</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('created_ticket_id'))
    @php
        $priority = session('created_ticket_priority', 'Средний');
        $priorityOrder = ['Низкий', 'Средний', 'Высокий', 'Критический'];
        $activePriorityIndex = array_search($priority, $priorityOrder, true);
        $activePriorityIndex = $activePriorityIndex === false ? 1 : $activePriorityIndex;
    @endphp
    <div class="success-overlay" data-success-overlay>
        <div class="success-dialog">
            <h2 class="h4 mb-2">Ваша заявка №{{ session('created_ticket_id') }} принята в работу!</h2>
            <div class="text-secondary">Приоритет</div>
            <div class="fw-semibold">{{ $priority }}</div>
            <div class="priority-meter mt-3" aria-hidden="true">
                @foreach($priorityOrder as $segmentIndex => $segment)
                    <span class="priority-meter-segment {{ $segmentIndex <= $activePriorityIndex ? 'active '.\Illuminate\Support\Str::slug($segment) : '' }}"></span>
                @endforeach
            </div>
            <button type="button" class="btn btn-dark px-4" data-success-close>Спасибо!</button>
        </div>
    </div>
@endif
@endsection
