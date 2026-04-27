<?php

namespace App\Http\Controllers;

use App\Models\Ticket;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isUser()) {
            return redirect()->route('user.tickets.create');
        }

        $menuItems = match ($user->role) {
            'admin' => [
                ['type' => 'link', 'label' => 'Главная', 'icon' => 'home.svg', 'active' => true, 'route' => 'dashboard'],
                ['type' => 'link', 'label' => 'Заявки', 'icon' => 'requests.svg', 'route' => 'admin.tickets.index', 'active' => false],
                ['type' => 'link', 'label' => 'Пользователи', 'icon' => 'user.svg', 'route' => 'admin.users.index', 'active' => false],
                ['type' => 'link', 'label' => 'Правила классификации', 'icon' => 'categories.svg', 'route' => 'admin.classification-rules.index', 'active' => false],
            ],
            'support' => [
                ['type' => 'link', 'label' => 'Главная', 'icon' => 'home.svg', 'active' => true, 'route' => 'dashboard'],
                ['type' => 'link', 'label' => 'Заявки', 'icon' => 'requests.svg', 'route' => 'support.tickets.index', 'active' => false],
            ],
            default => [],
        };

        $ticketsScope = Ticket::query();
        if ($user->isSupport()) {
            $ticketsScope->where('assigned_to', $user->id);
        }

        $inProgressCount = (clone $ticketsScope)->where('status', Ticket::STATUS_IN_PROGRESS)->count();
        $completedCount = (clone $ticketsScope)->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])->count();
        $newCount = (clone $ticketsScope)->where('status', Ticket::STATUS_NEW)->count();
        $manualCount = (clone $ticketsScope)
            ->where(function ($query) {
                $query->whereNull('priority')
                    ->orWhere('priority', '');
            })
            ->count();

        return view('dashboard.index', [
            'menuItems' => $menuItems,
            'pageTitle' => 'Главная',
            'stats' => [
                'in_progress' => $inProgressCount,
                'completed' => $completedCount,
                'new' => $newCount,
                'manual' => $manualCount,
            ],
        ]);
    }
}
