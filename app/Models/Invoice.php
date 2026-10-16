<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{   
    /**
     * Explicitly bind to the primary database connection.
     */
    protected $connection = 'mysql';

    protected $fillable = [
        'invoice_number', 
        'client_id', 
        'team_id', 
        'user_id', 
        'issue_date', 
        'due_date', 
        'service_period_start',
        'service_period_end',
        'payment_terms',
        'tax_type',
        'has_withholding_tax',
        'withholding_tax_amount',
        'delivery_address',
        'delivery_contact_person',
        'delivery_position',
        'delivery_phone',
        'subtotal', 
        'tax_amount', 
        'discount_amount', 
        'total_amount', 
        'notes', 
        'status',
        'approved_by',
        'edit_count',
        'deletion_requested',
        'deletion_reason'
    ];

    /**
     * Cast attributes to native types.
     */
    protected $casts = [
        'issue_date'             => 'date:Y-m-d',
        'due_date'               => 'date:Y-m-d',
        'service_period_start'   => 'date:Y-m-d',
        'service_period_end'     => 'date:Y-m-d',
        'has_withholding_tax'    => 'boolean',
        'deletion_requested'     => 'boolean',
        'withholding_tax_amount' => 'decimal:2',
        'subtotal'               => 'decimal:2',
        'tax_amount'             => 'decimal:2',
        'discount_amount'        => 'decimal:2',
        'total_amount'           => 'decimal:2',
        'edit_count'             => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}