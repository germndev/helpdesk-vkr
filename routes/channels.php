<?php

use App\Models\Ticket;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('ticket.{ticketId}', function ($user, int $ticketId) {
    $ticket = Ticket::query()->find($ticketId);

    if (! $ticket) {
        return false;
    }

    if ($user->isAdmin()) {
        return true;
    }

    if ($user->isSupport()) {
        return (int) $ticket->assigned_to === (int) $user->id;
    }

    return (int) $ticket->user_id === (int) $user->id;
});
