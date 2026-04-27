<div class="table-responsive records-table-wrap">
    <table class="table align-middle admin-ticket-table records-table mb-0">
        <thead>
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Приоритет</th>
            <th>Категория</th>
            <th>Ответственный</th>
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
                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="table-link">{{ \Illuminate\Support\Str::limit($ticket->title, 34) }}</a>
                </td>
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
                <td>
                    @if(blank($ticket->assignee?->display_name))
                        <span class="value-missing">Не определен</span>
                    @else
                        {{ $ticket->assignee->display_name }}
                    @endif
                </td>
                <td>{{ $ticket->status_label }}</td>
                <td>{{ $ticket->created_at->format('d.m.Y') }}</td>
                <td class="text-end">
                    <div class="d-inline-flex align-items-center gap-2">
                        <a class="btn btn-sm btn-outline-dark" href="{{ route('admin.tickets.show', $ticket) }}">Открыть</a>
                        <a class="btn btn-sm btn-outline-dark btn-icon-square" href="{{ route('admin.tickets.show', ['ticket' => $ticket, 'edit' => 1]) }}" title="Редактировать">
                            <x-icon name="edit.svg" class="table-icon" />
                        </a>
                        <form action="{{ route('admin.tickets.destroy', $ticket) }}" method="post" onsubmit="return confirm('Удалить заявку?');">
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-sm btn-outline-danger btn-icon-square" title="Удалить">
                                <x-icon name="delete.svg" class="table-icon" />
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center text-secondary py-4">Заявок пока нет</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $tickets->links() }}
</div>
