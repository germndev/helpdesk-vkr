<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketClassifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserTicketController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        return view('tickets.create', [
            'pageTitle' => 'Новая заявка',
            'menuItems' => $this->buildUserMenu($user, screen: 'create'),
        ]);
    }

    public function store(Request $request, TicketClassifier $classifier)
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $classification = $classifier->classify(
            $validated['title'],
            $validated['description'],
        );

        $ticket = Ticket::query()->create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => Ticket::STATUS_NEW,
            'priority' => $classification['priority'],
            'category' => $classification['category'],
            'assigned_to' => $classification['assigned_to'],
            'classification_score' => $classification['classification_score'],
            'needs_manual_review' => $classification['needs_manual_review'],
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $ticket->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'path' => $file->store('ticket-attachments', 'public'),
                'size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
            ]);
        }

        return redirect()
            ->route('user.tickets.create')
            ->with([
                'created_ticket_id' => $ticket->id,
                'created_ticket_priority' => $ticket->priority ?? 'Средний',
                'created_ticket_category' => $ticket->category,
                'created_ticket_manual_review' => $ticket->needs_manual_review,
            ]);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if ($ticket->user_id !== $user->id) {
            abort(403, 'У вас нет доступа к этой заявке.');
        }

        $ticket->load(['attachments', 'messages.user', 'messages.attachments']);

        return view('tickets.show', [
            'ticket' => $ticket,
            'pageTitle' => 'Заявка №'.$ticket->id,
            'menuItems' => $this->buildUserMenu($user, $ticket, 'show'),
        ]);
    }

    private function buildUserMenu($user, ?Ticket $currentTicket = null, string $screen = 'create'): array
    {
        $activeTickets = Ticket::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [Ticket::STATUS_NEW, Ticket::STATUS_IN_PROGRESS])
            ->latest('id')
            ->limit(5)
            ->get(['id', 'title']);

        $completedTickets = Ticket::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
            ->latest('id')
            ->limit(5)
            ->get(['id', 'title']);

        return [
            [
                'type' => 'link',
                'label' => 'Новая заявка',
                'icon' => 'newrequest.svg',
                'route' => 'user.tickets.create',
                'active' => $screen === 'create',
            ],
            [
                'type' => 'heading',
                'label' => 'Активные',
            ],
            ...$activeTickets->map(fn (Ticket $ticket) => [
                'type' => 'link',
                'label' => Str::limit($ticket->title, 24),
                'icon' => null,
                'route' => 'user.tickets.show',
                'params' => ['ticket' => $ticket->id],
                'active' => $screen === 'show' && $currentTicket && $currentTicket->id === $ticket->id,
            ])->all(),
            [
                'type' => 'heading',
                'label' => 'Завершенные',
            ],
            ...$completedTickets->map(fn (Ticket $ticket) => [
                'type' => 'link',
                'label' => Str::limit($ticket->title, 24),
                'icon' => null,
                'route' => 'user.tickets.show',
                'params' => ['ticket' => $ticket->id],
                'active' => $screen === 'show' && $currentTicket && $currentTicket->id === $ticket->id,
            ])->all(),
        ];
    }
}
