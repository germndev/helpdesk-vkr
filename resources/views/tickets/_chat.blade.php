<section
    class="ticket-chat-side"
    id="ticket-chat"
    data-chat-root
    data-chat-fetch-url="{{ $chatFetchRoute }}"
    data-chat-ticket-id="{{ $ticket->id }}"
>
    <h2 class="h3 mb-3">{{ $chatTitle ?? 'Чат' }}</h2>

    <div class="ticket-chat-body" data-chat-messages>
        @include('tickets._chat_messages', ['ticket' => $ticket])
    </div>

    <div class="ticket-chat-input-wrap">
        <form method="post" action="{{ $chatRoute }}" class="ticket-chat-form" data-chat-form enctype="multipart/form-data">
            @csrf
            <div class="ticket-chat-input-fields">
                <textarea
                    class="form-control @error('message') is-invalid @enderror"
                    name="message"
                    rows="2"
                    maxlength="5000"
                    placeholder="{{ $chatPlaceholder ?? 'Введите сообщение' }}"
                    data-chat-textarea
                >{{ old('message') }}</textarea>
                <input
                    class="form-control form-control-sm @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror"
                    type="file"
                    name="attachments[]"
                    multiple
                    data-chat-files
                >
                <input type="hidden" name="attachments_selected_count" value="0" data-chat-files-count>
            </div>
            <button type="submit" class="btn btn-dark" data-chat-submit>Отправить</button>
        </form>
        @error('message')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        @error('attachments')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        @error('attachments.*')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</section>
