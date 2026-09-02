<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderRemitLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_TYPED = 'typed';
    public const ACTION_SUBMITTED = 'submitted';

    protected $fillable = [
        'remit_date',
        'branch_id',
        'delivery_man_id',
        'actor_id',
        'actor_name',
        'rider_name',
        'action',
        'field',
        'old_value',
        'new_value',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'remit_date' => 'date',
            'branch_id' => 'integer',
            'delivery_man_id' => 'integer',
            'actor_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id', 'id');
    }

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_man_id', 'id');
    }
}
