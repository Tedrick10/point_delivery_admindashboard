<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable implements HasMedia
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use InteractsWithMedia;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username', 'user_type', 'country_id', 'city_id', 'branch_id', 'address', 'contact_number',
        'daily_contact_number', 'daily_contact_date', 'email_verified_at',
        'player_id', 'latitude', 'longitude', 'status', 'rider_work_on', 'rider_work_off_date', 'last_notification_seen' , 'login_type', 'uid', 'fcm_token', 'otp_verify_at'
        ,'app_version', 'last_location_update_at', 'app_source','last_actived_at','document_verified_at' ,'is_autoverified_document',
        'is_autoverified_email','is_autoverified_mobile','vehicle_id','referral_code','partner_referral_code','flag','apple_user_identifier',
        'is_vip', 'welcome_orders_used', 'is_temp_password', 'created_by_admin', 'is_dispatch_hub', 'is_mdy_return',
        'hub_parent_id', 'os_profile',
        'approval_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'country_id' => 'integer',
        'city_id' => 'integer',
        'branch_id' => 'integer',
        'status' => 'integer',
        'rider_work_on' => 'boolean',
        'rider_work_off_date' => 'date',
        'otp_verify_at' => 'datetime',
        'document_verified_at' => 'datetime',
        'last_location_update_at'   => 'datetime',
        'daily_contact_date' => 'date',
        'os_profile' => 'array',
        'is_dispatch_hub' => 'boolean',
        'is_mdy_return' => 'boolean',
        'hub_parent_id' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */

    // protected $appends = [
    //     'profile_photo_url',
    // ];


    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    public static function approvalStatuses(): array
    {
        return [
            self::APPROVAL_PENDING,
            self::APPROVAL_APPROVED,
            self::APPROVAL_REJECTED,
        ];
    }

    public function isClientApprovalApproved(): bool
    {
        if ($this->user_type !== 'client') {
            return true;
        }

        return ($this->approval_status ?? self::APPROVAL_APPROVED) === self::APPROVAL_APPROVED
            && (int) $this->status === 1;
    }

    /**
     * Sync approval_status with login-active status for OS clients.
     */
    public function applyApprovalStatus(string $approvalStatus): void
    {
        $approvalStatus = in_array($approvalStatus, self::approvalStatuses(), true)
            ? $approvalStatus
            : self::APPROVAL_PENDING;

        $this->approval_status = $approvalStatus;
        $this->status = $approvalStatus === self::APPROVAL_APPROVED ? 1 : 0;
    }

    public function routeNotificationForOneSignal()
    {
        return $this->player_id;
    }

    public function routeNotificationForFcm($notification)
    {
        return $this->fcm_token;
    }

    public static function userCount($user_type = null)
    {
        $query = self::query();
        $query->when($user_type, function($q) use($user_type){
            return $q->where('user_type',$user_type);
        });
        return $query->count();
    }

    public function scopeAdmin($query) {
        return $query->where('user_type', 'admin');
    }

    public function country(){
        return $this->belongsTo(Country::class, 'country_id','id');
    }

    public function city(){
        return $this->belongsTo(City::class, 'city_id','id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function hrStaff()
    {
        return $this->hasOne(HrStaff::class, 'user_id', 'id');
    }

    public function order(){
        return $this->hasMany(Order::class,'client_id','id')->withTrashed();
    }
    // public function customersupport(){
    //     return $this->hasMany(CustomerSupport::class,'user_id','id');
    // }

    public function deliveryManOrder(){
        return $this->hasMany(Order::class,'delivery_man_id','id')->withTrashed();
    }

    /**
     * Rider App daily phone — deprecated. Admin contact_number is the source of truth.
     * Kept for backward-compatible API fields; always treated as satisfied.
     */
    public function hasDailyContactForToday(): bool
    {
        return true;
    }

    /**
     * Phone shown on Pick Up / Delivery assign for riders — admin profile contact_number.
     */
    public function riderAssignedPhone(): ?string
    {
        $phone = trim((string) ($this->contact_number ?? ''));

        return $phone !== '' ? $phone : null;
    }

    public function isRiderWorkOn(): bool
    {
        $today = now('Asia/Yangon')->toDateString();
        if (! ($this->rider_work_on ?? true)) {
            $offDate = $this->rider_work_off_date
                ? $this->rider_work_off_date->toDateString()
                : null;
            if ($offDate === null || $offDate < $today) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Toggle display: Off for today (work_on false with today's off date).
     */
    public function displaysWorkOff(): bool
    {
        return ! $this->isRiderWorkOn();
    }

    /**
     * Riders available for Pick Up / Delivery assign today (On only).
     */
    public function scopeAvailableForAssign($query)
    {
        try {
            app(\App\Services\RiderWorkStatusService::class)->resetExpiredOffRiders();
        } catch (\Throwable $e) {
            // Keep assign list usable even if reset fails.
        }

        return $query->where('rider_work_on', true);
    }

    public function isDispatchHub(): bool
    {
        return (int) ($this->is_dispatch_hub ?? 0) === 1;
    }

    public function isMdyReturn(): bool
    {
        return (int) ($this->is_mdy_return ?? 0) === 1;
    }

    public function scopeDispatchHubs($query)
    {
        return $query->where('user_type', 'delivery_man')->where('is_dispatch_hub', 1);
    }

    public function scopeExcludeDispatchHubs($query)
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('users', 'is_dispatch_hub')) {
            return $query;
        }

        return $query->where(function ($inner) {
            $inner->whereNull('is_dispatch_hub')->orWhere('is_dispatch_hub', 0);
        });
    }

    /**
     * MDY / admin Rider List: hubs + regular riders.
     * Hub panels: only last-mile riders created on that hub.
     */
    public function scopeVisibleOnAdminRiderList($query, ?self $viewer = null)
    {
        $viewer = $viewer ?? auth()->user();
        if (! \Illuminate\Support\Facades\Schema::hasColumn('users', 'is_dispatch_hub')) {
            return $query;
        }

        if ($viewer && (int) ($viewer->is_dispatch_hub ?? 0) === 1) {
            return $query->where('hub_parent_id', (int) $viewer->id)
                ->where(function ($inner) {
                    $inner->whereNull('is_dispatch_hub')->orWhere('is_dispatch_hub', 0);
                });
        }

        return $query->where(function ($outer) {
            $outer->where('is_dispatch_hub', 1)
                ->orWhere(function ($regular) {
                    $regular->where(function ($hubFlag) {
                        $hubFlag->whereNull('is_dispatch_hub')->orWhere('is_dispatch_hub', 0);
                    });
                    if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'hub_parent_id')) {
                        $regular->where(function ($parent) {
                            $parent->whereNull('hub_parent_id')->orWhere('hub_parent_id', 0);
                        });
                    }
                });
        });
    }

    public function hubParent()
    {
        return $this->belongsTo(self::class, 'hub_parent_id');
    }

    public function hubDeliveryMen()
    {
        return $this->hasMany(self::class, 'hub_parent_id');
    }

    public function deliveryManDocument(){
        return $this->hasMany(DeliveryManDocument::class,'delivery_man_id', 'id')->withTrashed();
    }

    public function userBankAccount() {
        return $this->hasOne(UserBankAccount::class, 'user_id', 'id');
    }

    public function userWallet() {
        return $this->hasOne(Wallet::class, 'user_id', 'id');
    }

    public function userWithdraw(){
        return $this->hasMany(WithdrawRequest::class, 'user_id', 'id');
    }

    public function userAddress() {
        return $this->hasMany(UserAddress::class, 'user_id', 'id');
    }

    public function userNotification(){
        return $this->hasMany(Notification::class, 'notifiable_id', 'id');
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'client_id');
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id','id');
    }
    public function claims(){
        return $this->hasMany(Claims::class,'client_id','id');
    }

    public function rating(){
        return $this->hasMany(Ratings::class, 'review_user_id', 'id');
    }

    protected static function boot(){
        parent::boot();
        static::deleted(function ($row) {
            switch ($row->user_type) {
                case 'client':
                    $row->order()->delete();
                    if($row->forceDeleting === true)
                    {
                        $row->order()->forceDelete();
                        $row->userAddress()->delete();
                        $row->userNotification()->delete();
                    }
                    break;
                case 'delivery_man':
                    if($row->forceDeleting === true){
                        $row->deliveryManOrder()->update(['delivery_man_id' => NULL ]);
                        $row->userNotification()->delete();
                    }
                    break;
                default:
                    # code...
                    break;
            }
        });
        static::restoring(function($row) {
            $row->order()->withTrashed()->restore();
        });

        // static::created(function ($row) {
        //     if(SettingData('email_verification', 'email_verification')) {
        //         $row->notify(new EmailVerification());
        //     }
        // });
    }

    public function getPayment()
    {
        return $this->hasManyThrough(
            Payment::class,
            Order::class,
            'delivery_man_id',
            'order_id',
            'id',
            'id'
        )->where('payment_status','paid');
    }

    public function userWalletHistory(){
        return $this->hasMany(WalletHistory::class, 'user_id', 'id');
    }
    public function deliveryManEarning(){
        return $this->hasMany(WalletHistory::class, 'user_id', 'id');
    }
}
