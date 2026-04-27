@extends('layouts.app')

@section('content')
<div class="p-3 p-md-4">
    <div class="content-card content-card-scroll classification-rule-editor">
        <form method="post" action="{{ $formAction }}" class="classification-rule-form classification-rule-form-layout" data-rule-form>
            @csrf
            @if($formMethod !== 'post')
                @method($formMethod)
            @endif

            <div class="content-card-scroll-body p-3 p-md-4 classification-rule-scroll">
                <h1 class="mb-4">{{ $pageTitle }}</h1>

                <div class="classification-rule-fields">
                    <div class="classification-rule-field">
                        <div class="classification-rule-activity">
                            <label class="form-label mb-0" for="is_active">Активность</label>
                            <div class="form-check form-switch classification-rule-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" aria-label="Активность правила" @checked(old('is_active', $rule->is_active ?? true))>
                            </div>
                        </div>
                    </div>

                    <div class="classification-rule-field classification-rule-field-wide">
                        <label for="name" class="form-label">Название</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $rule->name) }}" class="form-control @error('name') is-invalid @enderror" maxlength="255" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="classification-rule-field classification-rule-field-wide">
                        <label for="responsibles" class="form-label">Ответственные сотрудники</label>
                        <select id="responsibles" name="responsibles[]" class="form-select @error('responsibles') is-invalid @enderror" multiple size="5">
                            @foreach($supportUsers as $supportUser)
                                <option value="{{ $supportUser->id }}" @selected(collect($selectedResponsibles)->contains($supportUser->id) || collect($selectedResponsibles)->contains((string) $supportUser->id))>
                                    {{ $supportUser->display_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Можно выбрать несколько исполнителей. Система выберет того, кто сейчас онлайн и менее загружен.</div>
                        @error('responsibles')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @error('responsibles.*')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <section class="classification-triggers-panel mt-4">
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
                        <h2 class="mb-0 classification-triggers-title">Триггеры</h2>
                        <button type="button" class="btn btn-outline-dark" data-add-trigger>Добавить триггер</button>
                    </div>

                    @error('triggers')
                        <div class="alert alert-danger py-2">{{ $message }}</div>
                    @enderror

                    <div class="classification-triggers-table">
                        <div class="classification-trigger-head">
                            <div>Фраза/слово</div>
                            <div>Вес</div>
                            <div></div>
                        </div>

                        <div data-trigger-list>
                            @foreach($triggerRows as $index => $trigger)
                                <div class="classification-trigger-row" data-trigger-row>
                                    <div>
                                        <input
                                            type="text"
                                            name="triggers[{{ $index }}][phrase]"
                                            value="{{ $trigger['phrase'] ?? '' }}"
                                            class="form-control @error("triggers.$index.phrase") is-invalid @enderror"
                                            maxlength="255"
                                            required
                                            data-trigger-input="phrase"
                                        >
                                        @error("triggers.$index.phrase")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                                        <input
                                            type="number"
                                            name="triggers[{{ $index }}][weight]"
                                            value="{{ $trigger['weight'] ?? 1 }}"
                                            class="form-control @error("triggers.$index.weight") is-invalid @enderror"
                                            min="1"
                                            max="5"
                                            required
                                            data-trigger-input="weight"
                                        >
                                        @error("triggers.$index.weight")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger classification-trigger-remove btn-icon-square" data-remove-trigger aria-label="Удалить триггер" title="Удалить триггер">
                                            <x-icon name="delete.svg" class="table-icon" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>

            <div class="classification-rule-footer">
                <button type="submit" class="btn btn-success">Сохранить</button>
            </div>

            <template data-trigger-template>
                <div class="classification-trigger-row" data-trigger-row>
                    <div>
                        <input type="text" name="" value="" class="form-control" maxlength="255" required data-trigger-input="phrase">
                    </div>
                    <div>
                        <input type="number" name="" value="1" class="form-control" min="1" max="5" required data-trigger-input="weight">
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger classification-trigger-remove btn-icon-square" data-remove-trigger aria-label="Удалить триггер" title="Удалить триггер">
                            <x-icon name="delete.svg" class="table-icon" />
                        </button>
                    </div>
                </div>
            </template>
        </form>
    </div>
</div>
@endsection
