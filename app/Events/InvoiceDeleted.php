<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceDeleted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $id;
    public $invoice_number;

    public function __construct($id, $invoice_number = null)
    {
        $this->id = $id;
        $this->invoice_number = $invoice_number;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('invoices-channel'),
            new PrivateChannel('admin-channel'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
        ];
    }
}
