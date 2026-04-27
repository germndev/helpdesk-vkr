<?php

namespace App\Http\Controllers;

use App\Events\TicketMessageCreated;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class TicketMessageController extends Controller
{
    public function userIndex(Request $request, Ticket $ticket): JsonResponse
    {
        $this->ensureUserAccess($request, $ticket);

        return $this->messagesJson($ticket);
    }

    public function supportIndex(Request $request, Ticket $ticket): JsonResponse
    {
        $this->ensureSupportAccess($request, $ticket);

        return $this->messagesJson($ticket);
    }

    public function adminIndex(Ticket $ticket): JsonResponse
    {
        return $this->messagesJson($ticket);
    }

    public function userStore(Request $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        $this->ensureUserAccess($request, $ticket);

        $this->storeMessage($request, $ticket);

        if ($request->ajax()) {
            return $this->messagesJson($ticket);
        }

        return back()->withFragment('ticket-chat');
    }

    public function supportStore(Request $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        $this->ensureSupportAccess($request, $ticket);

        $this->storeMessage($request, $ticket);

        if ($request->ajax()) {
            return $this->messagesJson($ticket);
        }

        return back()->withFragment('ticket-chat');
    }

    public function adminStore(Request $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        $this->storeMessage($request, $ticket);

        if ($request->ajax()) {
            return $this->messagesJson($ticket);
        }

        return back()->withFragment('ticket-chat');
    }

    private function storeMessage(Request $request, Ticket $ticket): void
    {
        $validated = $request->validate(
            [
                'message' => ['nullable', 'string', 'max:5000'],
                'attachments' => ['nullable', 'array', 'max:5'],
                'attachments.*' => ['file', 'max:10240'],
                'attachments_selected_count' => ['nullable', 'integer', 'min:0', 'max:5'],
            ],
            [
                'attachments.max' => 'Можно прикрепить не более 5 файлов за одно сообщение.',
                'attachments.*.uploaded' => 'Файл не загрузился. Проверьте размер файла и лимит PHP.',
                'attachments.*.max' => 'Максимальный размер одного файла: 10 МБ.',
                'attachments.*.file' => 'Можно прикреплять только файлы.',
            ],
        );

        $messageText = trim((string) ($validated['message'] ?? ''));
        
        $rawFiles = (array) $request->file('attachments', []);
        $files = [];

        foreach ($rawFiles as $rawFile) {
            if (! $rawFile) {
                continue;
            }

            if (! $rawFile->isValid()) {
                throw ValidationException::withMessages([
                    'attachments' => 'Файл не загрузился. Проверьте размер файла и лимит PHP.',
                ]);
            }

            $files[] = $rawFile;
        }

        $selectedCount = (int) ($validated['attachments_selected_count'] ?? 0);
        if ($selectedCount > 0 && count($files) === 0) {
            throw ValidationException::withMessages([
                'attachments' => 'Файл не загрузился. Проверьте размер файла и лимит PHP.',
            ]);
        }

        if (count($files) > 5) {
            throw ValidationException::withMessages([
                'attachments' => 'Можно прикрепить не более 5 файлов за одно сообщение.',
            ]);
        }

        if ($messageText === '' && count($files) === 0) {
            throw ValidationException::withMessages([
                'message' => 'Введите сообщение или прикрепите файл.',
            ]);
        }

        $message = $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'message' => $messageText,
        ]);

        foreach ($files as $file) {
            $size = $file->getSize() ?: 0;

            $message->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'path' => $this->storeAttachment($file),
                'size' => $size,
            ]);
        }

        try {
            broadcast(new TicketMessageCreated($ticket->id, $message->id, (int) $request->user()->id));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function storeAttachment(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension === '') {
            $extension = 'bin';
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $targetDirectory = public_path('storage/ticket-messages');

        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $file->move($targetDirectory, $filename);

        return 'ticket-messages/'.$filename;
    }

    private function messagesJson(Ticket $ticket): JsonResponse
    {
        $ticket->load(['messages.user', 'messages.attachments']);

        return response()->json([
            'html' => view('tickets._chat_messages', ['ticket' => $ticket])->render(),
        ]);
    }

    private function ensureUserAccess(Request $request, Ticket $ticket): void
    {
        if ($ticket->user_id !== $request->user()->id) {
            abort(403, 'У вас нет доступа к этой заявке.');
        }
    }

    private function ensureSupportAccess(Request $request, Ticket $ticket): void
    {
        if ($ticket->assigned_to !== $request->user()->id) {
            abort(403, 'У вас нет доступа к этой заявке.');
        }
    }
}
