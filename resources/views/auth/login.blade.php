@extends('layouts.auth')

@section('content')
<div class="auth-card card border-0 shadow">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <h1 class="h2 mb-0">Вход</h1>
            @if ($errors->any())
                <div class="alert alert-danger py-2 px-3 mb-0 small">{{ $errors->first() }}</div>
            @endif
        </div>

        <form action="{{ route('login.attempt') }}" method="post">
            @csrf
            <div class="mb-3">
                <label for="login" class="form-label">Логин</label>
                <input id="login" type="text" name="login" value="{{ old('login') }}" class="form-control @error('login') is-invalid @enderror" required autofocus>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Пароль</label>
                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-dark px-4">Войти</button>
            </div>
        </form>
    </div>
</div>
@endsection
