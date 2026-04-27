<div class="table-responsive records-table-wrap">
    <table class="table align-middle admin-ticket-table records-table mb-0">
        <thead>
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Пользователь</th>
            <th>Приоритет</th>
            <th>Категория</th>
            <th>Статус</th>
            <th>Дата</th>
            <th class="text-end">Действия</th>
        </tr>
        </thead>
        <tbody>
        @forelse($tickets as $ticket)
            <tr>
                <td>{{ $ticket->id }}</td>
                <td>
                    <a href="{{ route('support.tickets.show', $ticket) }}" class="table-link">{{ \Illuminate\Support\Str::limit($ticket->title, 40) }}</a>
                </td>
                <td>{{ $ticket->user->display_name }}</td>
                <td>
                    @if(blank($ticket->priority))
                        <span class="priority-missing">Не определен</span>
                    @else
                        {{ $ticket->priority }}
                    @endif
                </td>
                <td>
                    @if(blank($ticket->category))
                        <span class="value-missing">Не определен</span>
                    @else
                        {{ $ticket->category }}
                    @endif
                </td>
                <td>{{ $ticket->status_label }}</td>
                <td>{{ $ticket->created_at->format('d.m.Y') }}</td>
                <td class="text-end">
                    <div class="d-inline-flex align-items-center gap-2">
                        <a class="btn btn-sm btn-outline-dark" href="{{ route('support.tickets.show', $ticket) }}">Открыть</a>
                        <a class="btn btn-sm btn-outline-dark btn-icon-square" href="{{ route('support.tickets.show', ['ticket' => $ticket, 'edit' => 1]) }}" title="Редактировать">
                            <x-icon name="edit.svg" class="table-icon" />
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center text-secondary py-4">Нет заявок, назначенных вам</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $tickets->links() }}
</div>
