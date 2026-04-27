<?php

namespace App\Http\Controllers;

use App\Models\ClassificationRule;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $filters = $request->validate([
            'category' => ['nullable', 'array'],
            'category.*' => ['string', 'max:255'],
            'priority' => ['nullable', 'array'],
            'priority.*' => ['string', 'max:255'],
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::in([
                Ticket::STATUS_NEW,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_RESOLVED,
                Ticket::STATUS_CLOSED,
            ])],
            'date_sort' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $dateSort = $filters['date_sort'] ?? 'desc';

        $tickets = Ticket::query()
            ->with('user')
            ->where(function ($query) use ($user) {
                $query->where('assigned_to', $user->id)
                    ->orWhereNull('assigned_to');
            })
            ->when(! empty($filters['category']), fn ($q) => $q->whereIn('category', $filters['category']))
            ->when(! empty($filters['priority']), fn ($q) => $q->whereIn('priority', $filters['priority']))
            ->when(! empty($filters['status']), fn ($q) => $q->whereIn('status', $filters['status']))
            ->orderBy('created_at', $dateSort)
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('support.tickets._results', [
                    'tickets' => $tickets,
                ])->render(),
            ]);
        }

        return view('support.tickets.index', [
            'pageTitle' => 'Список заявок',
            'menuItems' => $this->buildSupportMenu('tickets'),
            'tickets' => $tickets,
            'categories' => Ticket::query()
                ->whereNotNull('category')
                ->where(function ($query) use ($user) {
                    $query->where('assigned_to', $user->id)
                        ->orWhereNull('assigned_to');
                })
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
            'priorities' => Ticket::query()
                ->whereNotNull('priority')
                ->where(function ($query) use ($user) {
                    $query->where('assigned_to', $user->id)
                        ->orWhereNull('assigned_to');
                })
                ->distinct()
                ->orderBy('priority')
                ->pluck('priority'),
            'filters' => array_merge([
                'category' => [],
                'priority' => [],
                'status' => [],
                'date_sort' => 'desc',
            ], $filters),
        ]);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->guardAssignedTicket($request, $ticket);

        return view('support.tickets.show', [
            'pageTitle' => 'Заявка №'.$ticket->id,
            'menuItems' => $this->buildSupportMenu('tickets'),
            'ticket' => $ticket->load(['user', 'attachments', 'messages.user', 'messages.attachments']),
            'categories' => $this->getAvailableCategories(),
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->guardAssignedTicket($request, $ticket);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'status' => ['required', Rule::in([
                Ticket::STATUS_NEW,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_RESOLVED,
                Ticket::STATUS_CLOSED,
            ])],
            'priority' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'needs_manual_review' => ['required', 'boolean'],
        ]);

        $ticket->update($validated);

        return redirect()
            ->route('support.tickets.show', $ticket)
            ->with('status_message', 'Заявка обновлена.');
    }

    public function complete(Request $request, Ticket $ticket)
    {
        $this->guardAssignedTicket($request, $ticket);

        $ticket->update([
            'status' => Ticket::STATUS_CLOSED,
        ]);

        return redirect()
            ->route('support.tickets.show', $ticket)
            ->with('status_message', 'Заявка закрыта.');
    }

    private function buildSupportMenu(string $active): array
    {
        return [
            ['type' => 'link', 'label' => 'Главная', 'icon' => 'home.svg', 'route' => 'dashboard', 'active' => $active === 'dashboard'],
            ['type' => 'link', 'label' => 'Заявки', 'icon' => 'requests.svg', 'route' => 'support.tickets.index', 'active' => $active === 'tickets'],
        ];
    }

    private function guardAssignedTicket(Request $request, Ticket $ticket): void
    {
        if ($ticket->assigned_to !== null && $ticket->assigned_to !== $request->user()->id) {
            abort(403, 'У вас нет доступа к этой заявке.');
        }
    }

    private function getAvailableCategories()
    {
        $ruleCategories = ClassificationRule::query()
            ->active()
            ->orderBy('name')
            ->pluck('name');

        $ticketCategories = Ticket::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return $ruleCategories
            ->merge($ticketCategories)
            ->filter(fn ($category) => trim((string) $category) !== '')
            ->unique()
            ->values();
    }

}
