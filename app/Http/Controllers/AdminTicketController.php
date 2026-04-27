<?php

namespace App\Http\Controllers;

use App\Models\ClassificationRule;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminTicketController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'assigned_to' => ['nullable', 'array'],
            'assigned_to.*' => ['integer', Rule::exists('users', 'id')],
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
            'priority_missing' => ['nullable', 'boolean'],
        ]);

        $ticketsQuery = Ticket::query()
            ->with(['user', 'assignee'])
            ->when(! empty($filters['assigned_to']), fn ($q) => $q->whereIn('assigned_to', $filters['assigned_to']))
            ->when(! empty($filters['category']), fn ($q) => $q->whereIn('category', $filters['category']))
            ->when(! empty($filters['priority']), fn ($q) => $q->whereIn('priority', $filters['priority']))
            ->when(! empty($filters['status']), fn ($q) => $q->whereIn('status', $filters['status']))
            ->when(($filters['priority_missing'] ?? false), function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('priority')
                        ->orWhere('priority', '');
                });
            });

        $dateSort = $filters['date_sort'] ?? 'desc';
        $tickets = $ticketsQuery
            ->orderBy('created_at', $dateSort)
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $supportUsers = User::query()
            ->whereIn('role', ['admin', 'support'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $categories = Ticket::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $priorities = Ticket::query()
            ->whereNotNull('priority')
            ->distinct()
            ->orderBy('priority')
            ->pluck('priority');

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.tickets._results', [
                    'tickets' => $tickets,
                ])->render(),
            ]);
        }

        return view('admin.tickets.index', [
            'pageTitle' => 'Список заявок',
            'menuItems' => $this->buildAdminMenu('tickets'),
            'tickets' => $tickets,
            'supportUsers' => $supportUsers,
            'categories' => $categories,
            'priorities' => $priorities,
            'filters' => array_merge([
                'assigned_to' => [],
                'category' => [],
                'priority' => [],
                'status' => [],
                'date_sort' => 'desc',
                'priority_missing' => false,
            ], $filters),
        ]);
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['user', 'assignee', 'attachments', 'messages.user', 'messages.attachments']);
        $categories = $this->getAvailableCategories();

        return view('admin.tickets.show', [
            'pageTitle' => 'Заявка №'.$ticket->id,
            'menuItems' => $this->buildAdminMenu('tickets'),
            'ticket' => $ticket,
            'categories' => $categories,
            'categoryAssignees' => $this->buildCategoryAssigneeMap($categories),
            'supportUsers' => User::query()
                ->whereIn('role', ['admin', 'support'])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
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
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'needs_manual_review' => ['required', 'boolean'],
        ]);

        $resolvedAssigneeId = $this->resolveAssigneeByCategory($validated['category'] ?? null);
        if ($resolvedAssigneeId !== null) {
            $validated['assigned_to'] = $resolvedAssigneeId;
        }

        $ticket->update($validated);

        return redirect()
            ->route('admin.tickets.show', $ticket)
            ->with('status_message', 'Заявка обновлена.');
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return redirect()
            ->route('admin.tickets.index')
            ->with('status_message', 'Заявка удалена.');
    }

    public function complete(Ticket $ticket)
    {
        $ticket->update([
            'status' => Ticket::STATUS_CLOSED,
        ]);

        return redirect()
            ->route('admin.tickets.show', $ticket)
            ->with('status_message', 'Заявка закрыта.');
    }

    private function buildAdminMenu(string $active): array
    {
        return [
            ['type' => 'link', 'label' => 'Главная', 'icon' => 'home.svg', 'route' => 'dashboard', 'active' => $active === 'dashboard'],
            ['type' => 'link', 'label' => 'Заявки', 'icon' => 'requests.svg', 'route' => 'admin.tickets.index', 'active' => $active === 'tickets'],
            ['type' => 'link', 'label' => 'Пользователи', 'icon' => 'user.svg', 'route' => 'admin.users.index', 'active' => $active === 'users'],
            ['type' => 'link', 'label' => 'Правила классификации', 'icon' => 'categories.svg', 'route' => 'admin.classification-rules.index', 'active' => $active === 'classification-rules'],
        ];
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

    private function buildCategoryAssigneeMap($categories): array
    {
        $rules = ClassificationRule::query()
            ->active()
            ->with('responsibles')
            ->whereIn('name', $categories)
            ->get()
            ->keyBy('name');

        $map = [];

        foreach ($categories as $category) {
            $rule = $rules->get($category);
            if (! $rule) {
                continue;
            }

            $assigneeId = $this->resolveResponsibleUserId($rule);
            if ($assigneeId !== null) {
                $map[$category] = $assigneeId;
            }
        }

        return $map;
    }

    private function resolveAssigneeByCategory(?string $category): ?int
    {
        $category = trim((string) $category);
        if ($category === '') {
            return null;
        }

        $rule = ClassificationRule::query()
            ->active()
            ->with('responsibles')
            ->where('name', $category)
            ->first();

        if (! $rule) {
            return null;
        }

        return $this->resolveResponsibleUserId($rule);
    }

    private function resolveResponsibleUserId(ClassificationRule $rule): ?int
    {
        $responsibles = $rule->responsibles;

        if ($responsibles->isEmpty()) {
            return null;
        }

        $userIds = $responsibles->pluck('id');
        $activeStatuses = [Ticket::STATUS_NEW, Ticket::STATUS_IN_PROGRESS];
        $urgentPriorities = ['Высокий', 'Критический'];

        $taskStats = Ticket::query()
            ->selectRaw('assigned_to, COUNT(*) as total_active')
            ->selectRaw('SUM(CASE WHEN priority IN (?, ?) THEN 1 ELSE 0 END) as urgent_active', $urgentPriorities)
            ->whereIn('assigned_to', $userIds)
            ->whereIn('status', $activeStatuses)
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        $onlineIds = DB::table('sessions')
            ->whereIn('user_id', $userIds)
            ->where('last_activity', '>=', now()->subMinutes(15)->timestamp)
            ->pluck('user_id')
            ->all();

        return $responsibles
            ->sort(function (User $left, User $right) use ($taskStats, $onlineIds) {
                $leftStats = $taskStats->get($left->id);
                $rightStats = $taskStats->get($right->id);

                $leftOnline = in_array($left->id, $onlineIds, true) ? 1 : 0;
                $rightOnline = in_array($right->id, $onlineIds, true) ? 1 : 0;

                $leftUrgent = (int) ($leftStats->urgent_active ?? 0);
                $rightUrgent = (int) ($rightStats->urgent_active ?? 0);

                $leftTotal = (int) ($leftStats->total_active ?? 0);
                $rightTotal = (int) ($rightStats->total_active ?? 0);

                return [$rightOnline, $leftUrgent, $leftTotal, $left->id]
                    <=>
                    [$leftOnline, $rightUrgent, $rightTotal, $right->id];
            })
            ->first()?->id;
    }

}
