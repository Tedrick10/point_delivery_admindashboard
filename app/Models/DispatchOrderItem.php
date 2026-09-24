<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DispatchOrderItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'photo_id',
        'pending_photo_id',
        'delivered_photo_id',
        'delivered_type',
        'delivered_at',
        'cust_photo_id',
        'cust_sign_id',
        'received_date',
        'code',
        'status',
        'delivery_locked',
        'return_reassigned',
        'assigned_from_return',
        'return_type',
        'delivery_man_id',
        'hub_user_id',
        'hub_inbox_at',
        'hub_accepted_at',
        'mdy_inbox_at',
        'mdy_accepted_at',
        'assigned_at',
        'admin_updated_at',
        'admin_completed_at',
        'admin_finished_at',
        'rider_remit_at',
        'rider_remit_date',
        'from_branch_id',
        'to_branch_id',
        'city_id',
        'delivery_city',
        'township',
        'item_name',
        'remark',
        'weight',
        'advance_paid',
        'os_paid',
        'item_value',
        'deli_amount',
        'customer_name',
        'customer_phone',
        'customer_address',
        'credit_to',
        'pickup_pay_mode',
        'cust_get',
        'os_to_pay',
        'gate_amount',
        'gate_os_paid',
        'point_amount',
        'agent_amount',
        'agent_expense_branch_id',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'photo_id' => 'integer',
        'pending_photo_id' => 'integer',
        'delivered_photo_id' => 'integer',
        'cust_photo_id' => 'integer',
        'cust_sign_id' => 'integer',
        'delivery_man_id' => 'integer',
        'hub_user_id' => 'integer',
        'delivery_locked' => 'boolean',
        'return_reassigned' => 'boolean',
        'assigned_from_return' => 'boolean',
        'assigned_at' => 'datetime',
        'hub_inbox_at' => 'datetime',
        'hub_accepted_at' => 'datetime',
        'mdy_inbox_at' => 'datetime',
        'mdy_accepted_at' => 'datetime',
        'admin_updated_at' => 'datetime',
        'admin_completed_at' => 'datetime',
        'admin_finished_at' => 'datetime',
        'delivered_at' => 'datetime',
        'rider_remit_at' => 'datetime',
        'rider_remit_date' => 'date',
        'from_branch_id' => 'integer',
        'to_branch_id' => 'integer',
        'city_id' => 'integer',
        'received_date' => 'date',
        'weight' => 'double',
        'advance_paid' => 'double',
        'os_paid' => 'double',
        'item_value' => 'double',
        'deli_amount' => 'double',
        'cust_get' => 'double',
        'os_to_pay' => 'double',
        'gate_amount' => 'double',
        'gate_os_paid' => 'double',
        'point_amount' => 'double',
        'agent_amount' => 'double',
        'agent_expense_branch_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            if ($item->status !== 'completed') {
                return;
            }

            $becameDelivered = ! $item->exists
                || ($item->isDirty('status') && $item->getOriginal('status') !== 'completed');

            if ($becameDelivered) {
                $item->rider_remit_at = null;
                if (empty($item->delivered_at)) {
                    $item->delivered_at = now();
                }
                $item->rider_remit_date = resolveRiderRemitDate(
                    (int) ($item->delivery_man_id ?? 0),
                    $item->delivered_at instanceof \Carbon\Carbon
                        ? $item->delivered_at
                        : null
                );
            }
        });

    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function messages()
    {
        return $this->hasMany(DispatchItemMessage::class, 'dispatch_order_item_id', 'id');
    }

    public function deliveryMan()
    {
        return $this->belongsTo(User::class, 'delivery_man_id', 'id');
    }

    public function kyoShinItem()
    {
        return $this->hasOne(KyoShinItem::class, 'dispatch_order_item_id');
    }

    public function hubUser()
    {
        return $this->belongsTo(User::class, 'hub_user_id', 'id');
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id', 'id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id', 'id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }

    public function photoMedia()
    {
        return $this->belongsTo(Media::class, 'photo_id', 'id');
    }

    public function pendingPhotoMedia()
    {
        return $this->belongsTo(Media::class, 'pending_photo_id', 'id');
    }

    public function pendingRemarks()
    {
        return $this->hasMany(DispatchItemPendingRemark::class, 'dispatch_order_item_id')
            ->orderBy('pending_at')
            ->orderBy('id');
    }

    /**
     * All Pending remark/image entries (history first, else current item fields).
     */
    public function displayPendingRemarks()
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('dispatch_item_pending_remarks')) {
            $this->loadMissing(['pendingRemarks.photoMedia']);
            if ($this->pendingRemarks->isNotEmpty()) {
                return $this->pendingRemarks;
            }
        }

        $remark = trim((string) ($this->remark ?? ''));
        $photoId = (int) ($this->pending_photo_id ?? 0);
        $looksPending = $photoId > 0 || (string) ($this->status ?? '') === 'pending';
        if (! $looksPending || ($remark === '' && $photoId <= 0)) {
            return collect();
        }

        $this->loadMissing('pendingPhotoMedia');
        $row = new DispatchItemPendingRemark([
            'remark' => $remark,
            'photo_id' => $photoId,
            'pending_at' => $this->admin_updated_at ?: $this->updated_at,
            'delivery_man_id' => $this->delivery_man_id,
        ]);
        $row->setRelation('photoMedia', $this->pendingPhotoMedia);

        return collect([$row]);
    }

    public function deliveredPhotoMedia()
    {
        return $this->belongsTo(Media::class, 'delivered_photo_id', 'id');
    }

    public function custPhotoMedia()
    {
        return $this->belongsTo(Media::class, 'cust_photo_id', 'id');
    }

    public function custSignMedia()
    {
        return $this->belongsTo(Media::class, 'cust_sign_id', 'id');
    }

    public static function computeAmounts(float $itemValue, float $deliAmount, float $advancePaid, float $osPaid, string $creditTo = 'customer'): array
    {
        if ($creditTo === 'os') {
            $custGet = $itemValue;
            // Item Value 0 + Os Paid (os_paid > 0): nothing left to settle with OS.
            if ($itemValue <= 0 && $osPaid > 0) {
                $osToPay = 0.0;
            } else {
                // Os Pay: deli may be settled against OS (item − unpaid deli).
                $osToPay = $itemValue - ($deliAmount - $osPaid);
                // Item Value 0 + Os Pay: store positive deli (Point collects from OS).
                if ($itemValue <= 0 && $osToPay < 0) {
                    $osToPay = abs($osToPay);
                }
            }
        } else {
            // Cust Pay: customer pays deli — OS settlement is Item Value only.
            $custGet = $itemValue + $deliAmount;
            $osToPay = $itemValue;
        }

        return [
            'cust_get' => $custGet,
            'os_to_pay' => $osToPay,
        ];
    }

    /**
     * Net gate fee still charged against OS settlement.
     * If OS already paid the gate (Os Paid For Gate), that portion is not
     * cut from Item Value / OSTOPAY.
     *
     * net = gate_amount - gate_os_paid
     * Cust Pay examples:
     * - Gate 1,000, Os Paid For Gate 0 → OSTOPAY = 10,000 − 1,000 = 9,000
     * - Gate 1,000, Os Paid For Gate 1,000 → OSTOPAY = 10,000 (no cut)
     */
    public function netGateCharge(): float
    {
        return round(
            (float) ($this->gate_amount ?? 0) - (float) ($this->gate_os_paid ?? 0),
            2
        );
    }

    /**
     * Base OS to Pay before gate fee (stored item economics only).
     * - Item Value > 0 + Cust Pay → OS gets Item Value only (Deli is NOT deducted).
     * - Item Value > 0 + Os Pay/Os Paid → may net deli via stored os_to_pay.
     * - Item Value = 0 + Cust Pay → 0 (deli belongs to Point Delivery).
     * - Item Value = 0 + Os Paid → 0 (OS already paid deli).
     * - Item Value = 0 + Os Pay → positive deli (Point still collects from OS).
     */
    public function baseDisplayOsToPay(): float
    {
        $itemValue = (float) ($this->item_value ?? 0);
        $osToPay = (float) ($this->os_to_pay ?? 0);
        $deliAmount = (float) ($this->deli_amount ?? 0);
        $payLabel = function_exists('dispatchDeliPayLabel')
            ? dispatchDeliPayLabel($this)
            : 'Cust Pay';

        if ($itemValue > 0) {
            // Cust Pay: customer covers deli — never subtract Deli Amount from Item Value.
            if ($payLabel === 'Cust Pay') {
                return -1 * abs($itemValue);
            }

            // Os Pay / Os Paid: use stored settlement (may already net deli).
            $amount = abs($osToPay != 0.0 ? $osToPay : $itemValue);

            return -1 * $amount;
        }

        // Deli-only Cust Pay or Os Paid: OS to Pay is 0.
        if (in_array($payLabel, ['Cust Pay', 'Os Paid'], true)) {
            return 0.0;
        }

        // Deli-only Os Pay: positive amount Point collects from OS.
        if ($payLabel === 'Os Pay') {
            if ($osToPay != 0.0) {
                return abs($osToPay);
            }
            if ($deliAmount != 0.0) {
                return abs($deliAmount);
            }
        }

        return 0.0;
    }

    /**
     * Admin / settlement OS to Pay after gate fees are cut.
     *
     * Sign convention (unchanged):
     * - negative → Point pays OS
     * - positive → OS pays Point
     *
     * Slip OSTOPAY (pay-to-OS) = ItemValue − (Gate Amount − Os Paid For Gate)
     * display = base + (gate_amount - gate_os_paid)
     * e.g. Cust Pay base -10,000 + Gate 1,000 − OsPaid 1,000 → -10,000
     */
    public function displayOsToPay(): float
    {
        if ((string) ($this->status ?? '') === 'return') {
            $amount = 0.0;
            if ($this->relationLoaded('kyoShinItem') && $this->kyoShinItem) {
                $amount = (float) ($this->kyoShinItem->amount ?? 0);
            }
            if ($amount <= 0) {
                $amount = (float) ($this->item_value ?? 0);
            }

            return round(abs($amount), 2);
        }

        return round($this->baseDisplayOsToPay() + $this->netGateCharge(), 2);
    }

    public function isKyoShinGiven(): bool
    {
        if ($this->relationLoaded('kyoShinItem')) {
            return $this->kyoShinItem !== null;
        }

        return $this->kyoShinItem()->exists();
    }

    /**
     * Parcel was sent back out after Return. Assigned can only go back to Return.
     */
    public function isReturnReassigned(): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'return_reassigned')) {
            return false;
        }

        return (bool) ($this->return_reassigned ?? false);
    }

    /**
     * This Assigned parcel came from Return, so the Returned checkbox stays available.
     */
    public function isAssignedFromReturn(): bool
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'assigned_from_return')) {
            return (bool) ($this->assigned_from_return ?? false) || $this->isReturnReassigned();
        }

        return $this->isReturnReassigned();
    }

    public const RETURN_TYPE_NORMAL = 'normal';

    public const RETURN_TYPE_DELIVERY = 'delivery';

    public function returnType(): ?string
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'return_type')) {
            return null;
        }

        $type = trim((string) ($this->return_type ?? ''));

        return in_array($type, [self::RETURN_TYPE_NORMAL, self::RETURN_TYPE_DELIVERY], true) ? $type : null;
    }

    public function isNormalReturn(): bool
    {
        return $this->returnType() === self::RETURN_TYPE_NORMAL;
    }

    public function isDeliveryReturn(): bool
    {
        return $this->returnType() === self::RETURN_TYPE_DELIVERY;
    }

    /**
     * Return parcel that still collects delivery fee (counts as a paid way).
     */
    public function hasReturnDeliFee(): bool
    {
        if ($this->isNormalReturn()) {
            return false;
        }

        if (! $this->isDeliveryReturn()) {
            return false;
        }

        return (float) ($this->deli_amount ?? 0) > 0;
    }

    /**
     * No-fee Return cycle after Assign: Assigned → Pending → Os Returned only.
     */
    public function isNoFeeOsReturnCycle(): bool
    {
        if (! $this->isAssignedFromReturn() || ! $this->isNormalReturn()) {
            return false;
        }

        return in_array((string) ($this->status ?? ''), ['assigned', 'courier_assigned', 'pending', 'os_returned'], true);
    }

    /**
     * Fee Return after Assign behaves like a normal delivery parcel.
     */
    public function isFeeReturnDeliveryCycle(): bool
    {
        return $this->isDeliveryReturn() && $this->hasReturnDeliFee();
    }

    /**
     * Completed ကြိုရှင်း parcels belong on ကြိုရှင်းသမား ပေးရန်, not Os ဆီသို့ လွှဲရန်.
     * Return + ကြိုရှင်း still counts as receivable from OS.
     */
    public function excludeFromSettlementAmount(): bool
    {
        return (string) ($this->status ?? '') === 'completed' && $this->isKyoShinGiven();
    }

    /**
     * Amount Point already advanced / still owes the ကြိုရှင်း person.
     */
    public function kyoShinPayAmount(): float
    {
        $amount = 0.0;
        if ($this->kyoShinItem) {
            $amount = (float) ($this->kyoShinItem->amount ?? 0);
        }
        if ($amount <= 0) {
            $amount = (float) ($this->item_value ?? 0);
        }

        return round(abs($amount), 2);
    }

    public function settlementOsToPay(): float
    {
        if ($this->excludeFromSettlementAmount()) {
            return 0.0;
        }

        return $this->displayOsToPay();
    }

    public static function generateCode(): string
    {
        $prefix = now()->format('ymd');

        do {
            $code = $prefix . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public static function deliveryCityLabel(?string $cityName): string
    {
        if (!$cityName) {
            return '-';
        }

        foreach (config('dispatch_item_cities.cities', []) as $city) {
            if (($city['name'] ?? '') === $cityName) {
                return $city['name_mm'] ?? $cityName;
            }
        }

        return $cityName;
    }
}
