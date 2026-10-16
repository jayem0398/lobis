<?php

namespace App\Events;

use App\Models\AppNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;

    public function __construct(AppNotification $notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("App.Models.User.{$this->notification->user_id}"),
            new PrivateChannel("user.{$this->notification->user_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'notification' => [
                'id'             => $this->notification->id,
                'type'           => $this->notification->type,
                'title'          => $this->notification->title,
                'message'        => $this->notification->message,
                'invoice_id'     => $this->notification->invoice_id,
                'invoice_number' => $this->notification->invoice_number,
                'is_read'        => (bool) $this->notification->is_read,
                'created_at'     => $this->notification->created_at?->toISOString() ?? now()->toISOString(),
            ],
        ];
    }
}
