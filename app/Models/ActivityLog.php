<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'description',
        'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, string $description, ?User $user = null): self
    {
        $userObj = $user ?? auth()->user();
        return self::create([
            'user_id'    => $userObj ? $userObj->id : null,
            'user_name'  => $userObj ? $userObj->name : 'System',
            'action'     => $action,
            'description'=> $description,
            'ip_address' => request()->ip(),
        ]);
    }
}
