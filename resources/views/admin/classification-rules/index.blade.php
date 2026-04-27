@extends('layouts.app')

@section('content')
<div class="p-3 p-md-4">
    <div class="content-card p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <h1 class="mb-0">Правила классификации</h1>
            <a href="{{ route('admin.classification-rules.create') }}" class="btn btn-outline-dark classification-rules-add-button">
                Добавить правило
            </a>
        </div>

        @if(session('status_message'))
            <div class="alert alert-success py-2">{{ session('status_message') }}</div>
        @endif

        <div class="classification-rules-grid">
            @forelse($rules as $rule)
                <a href="{{ route('admin.classification-rules.edit', $rule) }}" class="classification-rule-card">
                    <div class="classification-rule-card-title">{{ $rule->name }}</div>
                </a>
            @empty
                <div class="classification-rule-empty">Правила пока не добавлены.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
