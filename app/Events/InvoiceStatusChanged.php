<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('invoices-channel'),
            new PrivateChannel("team.{$this->invoice->team_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return ['invoice' => $this->invoice];
    }
}
