@extends('layouts.app')

@section('content')
<div class="p-3 p-md-4">
    <div class="content-card content-card-scroll">
        <div class="content-card-scroll-body p-3 p-md-4 ticket-detail-layout">
        <section class="ticket-detail-main">
            @if(session('status_message'))
                <div class="alert alert-success py-2">{{ session('status_message') }}</div>
            @endif

            <form id="admin-ticket-delete-form" action="{{ route('admin.tickets.destroy', $ticket) }}" method="post">
                @csrf
                @method('delete')
            </form>

            <form id="admin-ticket-complete-form" action="{{ route('admin.tickets.complete', $ticket) }}" method="post">
                @csrf
            </form>

            <form method="post" action="{{ route('admin.tickets.update', $ticket) }}" data-inline-edit-form data-inline-start-edit="{{ request()->boolean('edit') ? '1' : '0' }}">
                @csrf
                @method('put')

                <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                    <h1 class="mb-0">Заявка №{{ $ticket->id }}</h1>
                    <div class="d-flex align-items-center gap-2">
                        @if($ticket->status !== 'closed')
                            <button type="submit" class="btn btn-success" form="admin-ticket-complete-form">
                                Закрыть заявку
                            </button>
                        @endif
                        <span class="edit-mode-badge" data-inline-edit-badge hidden>Режим редактирования</span>
                        <button type="button" class="btn btn-sm btn-outline-dark btn-icon-square" data-inline-edit-toggle title="Редактировать">
                            <x-icon name="edit.svg" class="table-icon" />
                        </button>
                    </div>
                </div>

                <div class="ticket-field">
                    <label class="ticket-field-label" for="status">Статус</label>
                    <select id="status" name="status" class="form-select ticket-input-static" data-inline-edit-input disabled>
                        <option value="new" @selected(old('status', $ticket->status) === 'new')>Новая</option>
                        <option value="in_progress" @selected(old('status', $ticket->status) === 'in_progress')>В работе</option>
                        <option value="resolved" @selected(old('status', $ticket->status) === 'resolved')>Выполнена</option>
                        <option value="closed" @selected(old('status', $ticket->status) === 'closed')>Закрыта</option>
                    </select>
                </div>

                <div class="ticket-field">
                    <label class="ticket-field-label" for="title">Название</label>
                    <input id="title" name="title" type="text" class="form-control ticket-input-static" value="{{ old('title', $ticket->title) }}" data-inline-edit-input disabled>
                </div>

                <div class="ticket-field">
                    <label class="ticket-field-label" for="description">Описание</label>
                    <textarea id="description" name="description" rows="4" class="form-control ticket-input-static" data-inline-edit-input disabled>{{ old('description', $ticket->description) }}</textarea>
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
                    <label class="ticket-field-label" for="priority">Приоритет</label>
                    <select id="priority" name="priority" class="form-select ticket-input-static" data-inline-edit-input disabled>
                        <option value="" @selected(old('priority', $ticket->priority) === null || old('priority', $ticket->priority) === '')>Не определен</option>
                        <option value="Низкий" @selected(old('priority', $ticket->priority) === 'Низкий')>Низкий</option>
                        <option value="Средний" @selected(old('priority', $ticket->priority) === 'Средний')>Средний</option>
                        <option value="Высокий" @selected(old('priority', $ticket->priority) === 'Высокий')>Высокий</option>
                        <option value="Критический" @selected(old('priority', $ticket->priority) === 'Критический')>Критический</option>
                    </select>
                </div>

                <div class="ticket-field">
                    <label class="ticket-field-label" for="category">Категория</label>
                    <select id="category" name="category" class="form-select ticket-input-static" data-inline-edit-input data-category-select disabled>
                        <option value="">Не определена</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" @selected(old('category', $ticket->category) === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ticket-field">
                    <label class="ticket-field-label" for="assigned_to">Ответственный</label>
                    <select id="assigned_to" name="assigned_to" class="form-select ticket-input-static" data-inline-edit-input disabled>
                        <option value="">Не назначен</option>
                        @foreach($supportUsers as $support)
                            <option value="{{ $support->id }}" @selected((string)old('assigned_to', $ticket->assigned_to) === (string)$support->id)>{{ $support->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ticket-field">
                    <label class="ticket-field-label" for="needs_manual_review">Требует ручной обработки</label>
                    <select id="needs_manual_review" name="needs_manual_review" class="form-select ticket-input-static" data-inline-edit-input disabled>
                        <option value="1" @selected((string)old('needs_manual_review', (int)$ticket->needs_manual_review) === '1')>Да</option>
                        <option value="0" @selected((string)old('needs_manual_review', (int)$ticket->needs_manual_review) === '0')>Нет</option>
                    </select>
                </div>

                <div class="ticket-field mb-0">
                    <div class="ticket-field-label">Дата обращения</div>
                    <div class="ticket-field-value">{{ $ticket->created_at->format('d.m.Y') }}</div>
                </div>

                <div class="inline-edit-actions form-action-dock" data-inline-edit-actions>
                    <button type="submit" class="btn btn-outline-danger d-flex align-items-center gap-2" form="admin-ticket-delete-form" onclick="return confirm('Удалить заявку?');" data-inline-edit-action disabled>
                        <x-icon name="delete.svg" class="table-icon" />
                        <span>Удалить</span>
                    </button>
                    <button type="submit" class="btn btn-success" data-inline-edit-action disabled>Сохранить</button>
                </div>

                @if($errors->hasAny(['title', 'description', 'status', 'priority', 'category', 'assigned_to', 'needs_manual_review']))
                    <script>
                        window.__forceInlineEdit = true;
                    </script>
                @endif
            </form>
        </section>

        @include('tickets._chat', [
            'ticket' => $ticket,
            'chatTitle' => 'Чат с ['.$ticket->user->display_name.']',
            'chatRoute' => route('admin.tickets.messages.store', $ticket),
            'chatFetchRoute' => route('admin.tickets.messages.index', $ticket),
            'chatPlaceholder' => 'Напишите ответ',
        ])
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const categorySelect = document.querySelector('[data-category-select]');
        const assigneeSelect = document.querySelector('#assigned_to');
        if (!categorySelect || !assigneeSelect) {
            return;
        }

        const categoryAssignees = @json($categoryAssignees ?? []);

        categorySelect.addEventListener('change', () => {
            const category = categorySelect.value ?? '';
            const assigneeId = categoryAssignees[category];
            if (!assigneeId) {
                return;
            }

            assigneeSelect.value = String(assigneeId);
        });
    })();
</script>
@endpush
