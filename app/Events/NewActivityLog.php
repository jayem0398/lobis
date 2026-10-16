<?php

namespace App\Events;

use App\Models\ActivityLog;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewActivityLog implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $log;

    public function __construct(ActivityLog $log)
    {
        $this->log = $log;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin-channel'),
        ];
    }

    public function broadcastWith(): array
    {
        return ['log' => $this->log];
    }
}
