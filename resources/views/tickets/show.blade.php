@extends('layouts.app')

@section('content')
<div class="p-3 p-md-4">
    <div class="content-card content-card-scroll">
        <div class="content-card-scroll-body p-3 p-md-4 ticket-detail-layout">
        <section class="ticket-detail-main">
            <h1 class="mb-3">Заявка №{{ $ticket->id }}</h1>

            <div class="ticket-field">
                <div class="ticket-field-label">Статус</div>
                <div class="ticket-field-value">{{ $ticket->status_label }}</div>
            </div>

            <div class="ticket-field">
                <div class="ticket-field-label">Название</div>
                <div class="ticket-field-value">{{ $ticket->title }}</div>
            </div>

            <div class="ticket-field">
                <div class="ticket-field-label">Описание</div>
                <div class="ticket-field-value">{{ $ticket->description }}</div>
            </div>

            <div class="ticket-field">
                <div class="ticket-field-label">Файл</div>
                @if($ticket->attachments->isNotEmpty())
                    <div class="ticket-attachments">
                        @foreach($ticket->attachments as $attachment)
                            <a class="ticket-attachment-item" href="{{ '/storage/'.ltrim($attachment->path, '/') }}" target="_blank" rel="noopener">
                                <x-icon name="file.svg" class="table-icon" />
                                <span>{{ $attachment->original_name }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="ticket-field-value text-secondary">Нет вложений</div>
                @endif
            </div>

            <div class="ticket-field">
                <div class="ticket-field-label">Приоритет</div>
                <div class="ticket-field-value">{{ $ticket->priority ?? 'Не определен' }}</div>
            </div>
        </section>

        @include('tickets._chat', [
            'ticket' => $ticket,
            'chatTitle' => 'Чат с поддержкой',
            'chatRoute' => route('user.tickets.messages.store', $ticket),
            'chatFetchRoute' => route('user.tickets.messages.index', $ticket),
            'chatPlaceholder' => 'Задайте вопрос',
        ])
        </div>
    </div>
</div>
@endsection
