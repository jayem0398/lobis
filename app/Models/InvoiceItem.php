<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'item_name', 'quantity', 'price', 'tax', 'discount', 'total'
    ];

    // Relationship: Ang Item na ito ay nabibilang sa isang Invoice
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}