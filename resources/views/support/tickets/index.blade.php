@extends('layouts.app')

@section('content')
<div class="p-3 p-md-4">
    <div class="content-card p-3 p-md-4">
        <h1 class="mb-3">Список заявок</h1>

        <form method="get" class="admin-ticket-filters mb-2" data-ticket-filter-form>
            <div class="multi-filter" data-multi-filter>
                <button type="button" class="form-select text-start" data-multi-filter-toggle>Категория</button>
                <div class="multi-filter-menu" data-multi-filter-menu>
                    @foreach($categories as $category)
                        <label class="multi-filter-option">
                            <input type="checkbox" name="category[]" value="{{ $category }}" @checked(collect($filters['category'])->contains($category))>
                            <span>{{ $category }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="multi-filter" data-multi-filter>
                <button type="button" class="form-select text-start" data-multi-filter-toggle>Приоритет</button>
                <div class="multi-filter-menu" data-multi-filter-menu>
                    @foreach($priorities as $priority)
                        <label class="multi-filter-option">
                            <input type="checkbox" name="priority[]" value="{{ $priority }}" @checked(collect($filters['priority'])->contains($priority))>
                            <span>{{ $priority }}</span>
                        </label>
                    @endforeach
                    <label class="multi-filter-option">
                        <input type="checkbox" name="priority[]" value="__missing" @checked(collect($filters['priority'])->contains('__missing'))>
                        <span>Не определен</span>
                    </label>
                </div>
            </div>

            <div class="multi-filter" data-multi-filter>
                <button type="button" class="form-select text-start" data-multi-filter-toggle>Статус</button>
                <div class="multi-filter-menu" data-multi-filter-menu>
                    <label class="multi-filter-option"><input type="checkbox" name="status[]" value="new" @checked(collect($filters['status'])->contains('new'))><span>Новая</span></label>
                    <label class="multi-filter-option"><input type="checkbox" name="status[]" value="in_progress" @checked(collect($filters['status'])->contains('in_progress'))><span>В работе</span></label>
                    <label class="multi-filter-option"><input type="checkbox" name="status[]" value="resolved" @checked(collect($filters['status'])->contains('resolved'))><span>Выполнена</span></label>
                    <label class="multi-filter-option"><input type="checkbox" name="status[]" value="closed" @checked(collect($filters['status'])->contains('closed'))><span>Закрыта</span></label>
                </div>
            </div>

            <label class="sort-control">
                <x-icon name="filter.svg" class="table-icon" />
                <select class="form-select" name="date_sort" data-sort-select>
                    <option value="desc" @selected($filters['date_sort'] === 'desc')>По дате: новые</option>
                    <option value="asc" @selected($filters['date_sort'] === 'asc')>По дате: старые</option>
                </select>
            </label>
        </form>

        <div class="filter-hint mb-3">Фильтры применяются автоматически. Можно выбрать несколько значений в каждом фильтре.</div>

        <div data-ticket-results>
            @include('support.tickets._results', ['tickets' => $tickets])
        </div>
    </div>
</div>
@endsection
