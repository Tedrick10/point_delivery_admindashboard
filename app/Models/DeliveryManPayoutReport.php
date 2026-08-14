<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DeliveryManPayoutReport extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'delivery_man_id',
        'week_start_date',
        'week_end_date',
        'total_trips',
        'total_fare',
        'total_commission',
        'payout_amount',
        'status',
        'generated_at',
        'paid_at',
        'driver_tips',
        'is_mail_sent',
        'payment_method',
        'transaction_reference',
    ];

    protected $casts = [
        'delivery_man_id' => 'integer',
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'total_trips' => 'integer',
        'driver_tips' => 'integer',
        'is_mail_sent' => 'boolean',
        'total_fare' => 'double',
        'total_commission' => 'double',
        'payout_amount' => 'double',
        'generated_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'delivery_man_id', 'id');
    }
}
