@extends('layouts.app')

@section('content')
<div class="p-3 p-md-4">
    <div class="content-card p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <h1 class="mb-0">Список пользователей</h1>
            <a href="{{ route('admin.users.create') }}" class="btn btn-outline-dark">
                Добавить пользователя
            </a>
        </div>

        @if(session('status_message'))
            <div class="alert alert-success py-2">{{ session('status_message') }}</div>
        @endif

        <div class="users-table-wrap records-table-wrap">
            <table class="table users-table records-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Фамилия</th>
                        <th>Логин</th>
                        <th>Доступ</th>
                        <th class="text-end">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr
                            class="users-row-clickable"
                            onclick="if (!event.target.closest('a,button,form,input,select,label')) { window.location='{{ route('admin.users.edit', $user) }}'; }"
                        >
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->first_name }}</td>
                            <td>{{ $user->last_name }}</td>
                            <td>{{ $user->login }}</td>
                            <td>{{ $user->isAdmin() ? 'Администратор' : ($user->isSupport() ? 'Сотрудник' : 'Пользователь') }}</td>
                            <td class="text-end">
                                <div class="users-actions">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-dark">
                                        Изменить
                                    </a>
                                    <form method="post" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Удалить пользователя?');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Пользователи не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
