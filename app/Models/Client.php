<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'user_id', 
        'client_code', 
        'client_name', 
        'contact_person', 
        'position', 
        'contact_number', 
        'client_address', 
        'tin_number', 
        'business_style', 
        'client_status'
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}