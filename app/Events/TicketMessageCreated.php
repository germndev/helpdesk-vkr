<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketMessageCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $ticketId,
        public int $messageId,
        public int $authorId,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('ticket.'.$this->ticketId)];
    }

    public function broadcastAs(): string
    {
        return 'ticket.message.created';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'message_id' => $this->messageId,
            'author_id' => $this->authorId,
        ];
    }
}

