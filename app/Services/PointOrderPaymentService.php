<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PointOrderPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PointOrderPaymentService
{
    public function __construct(
        protected PointWalletService $points,
        protected AppPushService $push
    ) {}

    /**
     * User/OS pays an order with points. Points are debited immediately and held
     * until Admin approve → Super Admin confirm (or refunded on reject).
     */
    public function create(User $user, int $orderId, ?float $pointsAmount = null, ?string $remark = null): PointOrderPayment
    {
        $order = Order::query()->find($orderId);
        if (! $order) {
            throw new InvalidArgumentException(__('message.not_found_entry', ['name' => __('message.order')]));
        }

        $clientId = (int) ($order->client_id ?? 0);
        if ($clientId !== (int) $user->id && ($user->user_type ?? '') === 'client') {
            throw new InvalidArgumentException(__('message.demo_permission_denied'));
        }

        $amount = $pointsAmount !== null ? (float) $pointsAmount : (float) ($order->total_amount ?? 0);
        if ($amount <= 0) {
            throw new InvalidArgumentException(__('message.point_amount_invalid'));
        }

        $existing = PointOrderPayment::query()
            ->where('order_id', $orderId)
            ->whereIn('status', [
                PointOrderPayment::STATUS_PENDING_ADMIN,
                PointOrderPayment::STATUS_PENDING_SUPER_ADMIN,
                PointOrderPayment::STATUS_CONFIRMED,
            ])
            ->exists();

        if ($existing) {
            throw new InvalidArgumentException(__('message.point_payment_already_exists'));
        }

        if ($this->points->balance((int) $user->id) < $amount) {
            throw new InvalidArgumentException(__('message.point_insufficient_balance'));
        }

        $payment = DB::transaction(function () use ($user, $order, $orderId, $amount, $remark) {
            $row = PointOrderPayment::create([
                'user_id' => (int) $user->id,
                'order_id' => $orderId,
                'points' => round($amount, 2),
                'status' => PointOrderPayment::STATUS_PENDING_ADMIN,
                'remark' => $remark,
            ]);

            $this->points->debit(
                (int) $user->id,
                $amount,
                'order_payment',
                (int) $user->id,
                __('message.point_order_payment_hold', ['order' => $orderId]),
                [
                    'order_id' => $orderId,
                    'reference_id' => $row->id,
                    'reference_type' => PointOrderPayment::class,
                ]
            );

            $paymentRecord = Payment::updateOrCreate(
                ['order_id' => $orderId],
                [
                    'client_id' => (int) ($order->client_id ?? $user->id),
                    'datetime' => now(),
                    'total_amount' => $amount,
                    'payment_type' => 'points',
                    'payment_status' => 'pending',
                    'received_by' => 'admin',
                ]
            );

            $order->payment_id = $paymentRecord->id;
            $order->payment_type = 'points';
            $order->save();

            return $row->fresh(['user', 'order']);
        });

        $this->notifyAdminsPending($payment);

        return $payment;
    }

    public function approveByAdmin(PointOrderPayment $payment, User $admin, ?string $remark = null): PointOrderPayment
    {
        if ($payment->status !== PointOrderPayment::STATUS_PENDING_ADMIN) {
            throw new InvalidArgumentException(__('message.point_payment_invalid_status'));
        }

        $payment->status = PointOrderPayment::STATUS_PENDING_SUPER_ADMIN;
        $payment->admin_approved_by = (int) $admin->id;
        $payment->admin_approved_at = now();
        if ($remark !== null && $remark !== '') {
            $payment->remark = trim(($payment->remark ? $payment->remark."\n" : '').$remark);
        }
        $payment->save();

        $this->notifySuperAdminsForConfirm($payment->fresh(['user', 'order']));

        return $payment;
    }

    public function confirmBySuperAdmin(PointOrderPayment $payment, User $superAdmin, ?string $remark = null): PointOrderPayment
    {
        if ($payment->status !== PointOrderPayment::STATUS_PENDING_SUPER_ADMIN) {
            throw new InvalidArgumentException(__('message.point_payment_invalid_status'));
        }

        return DB::transaction(function () use ($payment, $superAdmin, $remark) {
            $payment->status = PointOrderPayment::STATUS_CONFIRMED;
            $payment->super_admin_confirmed_by = (int) $superAdmin->id;
            $payment->super_admin_confirmed_at = now();
            if ($remark !== null && $remark !== '') {
                $payment->remark = trim(($payment->remark ? $payment->remark."\n" : '').$remark);
            }
            $payment->save();

            if ($payment->order_id) {
                Payment::query()
                    ->where('order_id', $payment->order_id)
                    ->where('payment_type', 'points')
                    ->update(['payment_status' => 'paid']);
            }

            $this->notifyUserConfirmed($payment->fresh(['user', 'order']));

            return $payment;
        });
    }

    public function reject(PointOrderPayment $payment, User $actor, ?string $remark = null): PointOrderPayment
    {
        if (! in_array($payment->status, [
            PointOrderPayment::STATUS_PENDING_ADMIN,
            PointOrderPayment::STATUS_PENDING_SUPER_ADMIN,
        ], true)) {
            throw new InvalidArgumentException(__('message.point_payment_invalid_status'));
        }

        return DB::transaction(function () use ($payment, $actor, $remark) {
            $refundLabel = $payment->isOsReceive()
                ? ('OsR#'.(int) $payment->os_receive_settlement_id)
                : (string) $payment->order_id;

            $this->points->credit(
                (int) $payment->user_id,
                (float) $payment->points,
                $payment->isOsReceive() ? 'os_receive_refund' : 'order_payment_refund',
                (int) $actor->id,
                __('message.point_order_payment_refund', ['order' => $refundLabel]),
                [
                    'order_id' => $payment->order_id,
                    'reference_id' => $payment->id,
                    'reference_type' => PointOrderPayment::class,
                    'data' => [
                        'os_receive_settlement_id' => $payment->os_receive_settlement_id,
                    ],
                ]
            );

            $payment->status = PointOrderPayment::STATUS_REJECTED;
            $payment->rejected_by = (int) $actor->id;
            $payment->rejected_at = now();
            if ($remark !== null && $remark !== '') {
                $payment->remark = trim(($payment->remark ? $payment->remark."\n" : '').$remark);
            }
            $payment->save();

            if ($payment->order_id) {
                Payment::query()
                    ->where('order_id', $payment->order_id)
                    ->where('payment_type', 'points')
                    ->update(['payment_status' => 'failed']);
            }

            return $payment;
        });
    }

    public function notifyAdminsPendingPublic(PointOrderPayment $payment): void
    {
        $this->notifyAdminsPending($payment);
    }

    protected function notifyAdminsPending(PointOrderPayment $payment): void
    {
        $admins = User::query()
            ->where('user_type', 'admin')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 1)->orWhere('status', '1');
            })
            ->get();

        $ref = $payment->isOsReceive()
            ? ('OsR#'.(int) $payment->os_receive_settlement_id)
            : (string) $payment->order_id;

        $subject = __('message.point_noti_admin_approve_title');
        $message = __('message.point_noti_admin_approve_body', [
            'order' => $ref,
            'points' => formatPointNotifyNumber($payment->points),
            'name' => optional($payment->user)->name,
        ]);

        foreach ($admins as $admin) {
            $this->push->notifyUser(
                $admin,
                'point_payment_pending_admin',
                $subject,
                $message,
                [
                    'id' => 'POINT_PAY_'.$payment->id,
                    'category' => 'points',
                    'order_id' => (string) ($payment->order_id ?? ''),
                    'os_receive_settlement_id' => (string) ($payment->os_receive_settlement_id ?? ''),
                    'point_payment_id' => (string) $payment->id,
                ]
            );
        }
    }

    protected function notifySuperAdminsForConfirm(PointOrderPayment $payment): void
    {
        $subject = __('message.point_noti_super_confirm_title');
        $message = __('message.point_noti_super_confirm_body', [
            'order' => $payment->order_id,
            'points' => formatPointNotifyNumber($payment->points),
            'name' => optional($payment->user)->name,
        ]);

        foreach (getSuperAdminUsers() as $sa) {
            $this->push->notifyUser(
                $sa,
                'point_payment_pending_super_admin',
                $subject,
                $message,
                [
                    'id' => 'POINT_PAY_'.$payment->id,
                    'category' => 'points',
                    'order_id' => (string) $payment->order_id,
                    'point_payment_id' => (string) $payment->id,
                ]
            );
        }
    }

    protected function notifyUserConfirmed(PointOrderPayment $payment): void
    {
        $user = $payment->user;
        if (! $user) {
            return;
        }

        $this->push->notifyUser(
            $user,
            'point_payment_confirmed',
            __('message.point_noti_user_confirmed_title'),
            __('message.point_noti_user_confirmed_body', [
                'order' => $payment->order_id,
                'points' => formatPointNotifyNumber($payment->points),
            ]),
            [
                'id' => 'POINT_PAY_'.$payment->id,
                'order_id' => (string) $payment->order_id,
                'point_payment_id' => (string) $payment->id,
                'app' => 'os',
            ]
        );
    }
}
