@php
    $viewer = auth()->user();
    $isUserView = $viewer?->isUser() ?? false;
@endphp

@forelse($ticket->messages as $message)
    @php
        $isOwn = $message->user_id === auth()->id();
        $authorName = $message->user?->display_name ?? 'Пользователь';

        if ($isOwn) {
            $authorName = 'Вы';
        } elseif ($isUserView && $message->user && ! $message->user->isUser()) {
            $authorName = 'Тех. Поддержка';
        }
    @endphp
    <div class="ticket-chat-message {{ $isOwn ? 'is-own' : '' }}">
        <div class="ticket-chat-meta">
            <span class="ticket-chat-author">{{ $authorName }}</span>
            <span class="ticket-chat-time">{{ $message->created_at->format('d.m.Y H:i') }}</span>
        </div>
        @if(trim((string)$message->message) !== '')
            <div class="ticket-chat-text">{{ $message->message }}</div>
        @endif
        @if($message->attachments->isNotEmpty())
            <div class="ticket-chat-files">
                @foreach($message->attachments as $attachment)
                    @php
                        $ext = strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION));
                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']);
                        $fileUrl = '/storage/'.ltrim($attachment->path, '/');
                    @endphp
                    <a class="ticket-chat-file {{ $isImage ? 'is-image' : '' }}" href="{{ $fileUrl }}" target="_blank" rel="noopener">
                        @if($isImage)
                            <img src="{{ $fileUrl }}" alt="{{ $attachment->original_name }}" class="ticket-chat-file-preview">
                        @else
                            <x-icon name="file.svg" class="table-icon" />
                        @endif
                        <span>{{ $attachment->original_name }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@empty
    <p class="text-secondary mb-0">Пока нет сообщений. Напишите первое сообщение в чат.</p>
@endforelse
