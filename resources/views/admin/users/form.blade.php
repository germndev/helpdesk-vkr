@extends('layouts.app')

@section('content')
<div class="p-3 p-md-4">
    <div class="content-card content-card-scroll">
        <div class="content-card-scroll-body p-3 p-md-4">
        <h1 class="mb-4">{{ $pageTitle }}</h1>

        @if(session('status_message'))
            <div class="alert alert-success py-2">{{ session('status_message') }}</div>
        @endif

        <form method="post" action="{{ $formAction }}" class="admin-user-form" enctype="multipart/form-data">
            @csrf
            @if($formMethod !== 'post')
                @method($formMethod)
            @endif

            <div class="admin-user-layout mb-4">
                <div class="admin-user-avatar-col">
                    <label for="avatar" class="admin-user-avatar-label">
                        <img src="{{ $userEntity->avatar_url }}" alt="Аватар" class="admin-user-avatar-preview" id="avatar-preview">
                        <span class="admin-user-avatar-badge">
                            <x-icon name="edit.svg" />
                        </span>
                    </label>
                    <input id="avatar" type="file" name="avatar" class="form-control d-none @error('avatar') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,.gif,.bmp,image/*" data-avatar-input>
                    @error('avatar')
                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <div class="admin-user-fields">
                    <div class="admin-user-grid-two">
                        <div>
                            <label for="last_name" class="form-label">Фамилия</label>
                            <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $userEntity->last_name) }}" class="form-control @error('last_name') is-invalid @enderror" maxlength="255" required>
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="first_name" class="form-label">Имя</label>
                            <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $userEntity->first_name) }}" class="form-control @error('first_name') is-invalid @enderror" maxlength="255" required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="admin-user-stack-field">
                        <label for="login" class="form-label">Логин</label>
                        <input id="login" name="login" type="text" value="{{ old('login', $userEntity->login) }}" class="form-control @error('login') is-invalid @enderror" maxlength="255" required>
                        @error('login')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="admin-user-stack-field">
                        <label for="password" class="form-label">{{ $isEdit ? 'Новый пароль (необязательно)' : 'Пароль' }}</label>
                        <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" {{ $isEdit ? '' : 'required' }}>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="admin-user-stack-field">
                        <label for="role" class="form-label">Права доступа</label>
                        <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" size="3" required>
                            <option value="user" @selected(old('role', $userEntity->role ?: 'user') === 'user')>Пользователь</option>
                            <option value="support" @selected(old('role', $userEntity->role) === 'support')>Сотрудник</option>
                            <option value="admin" @selected(old('role', $userEntity->role) === 'admin')>Администратор</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-action-dock">
                <button type="submit" class="btn btn-success admin-user-submit">Сохранить</button>
                @if($isEdit)
                    <button type="submit" class="btn btn-outline-danger" form="delete-user-form">Удалить пользователя</button>
                @endif
            </div>
        </form>

        @if($isEdit)
            <form id="delete-user-form" method="post" action="{{ route('admin.users.destroy', $userEntity) }}" onsubmit="return confirm('Удалить пользователя?');" class="d-none">
                @csrf
                @method('delete')
            </form>
        @endif
        </div>
    </div>
</div>
<script>
(() => {
    const input = document.querySelector('[data-avatar-input]');
    const preview = document.getElementById('avatar-preview');
    if (!input || !preview) {
        return;
    }

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) {
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        preview.onload = () => URL.revokeObjectURL(objectUrl);
    });
})();
</script>
@endsection

