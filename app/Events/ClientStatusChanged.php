<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin-channel'),
        ];
    }

    public function broadcastWith(): array
    {
        return ['client' => $this->client];
    }
}
