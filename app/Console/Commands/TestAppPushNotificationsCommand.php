<?php

namespace App\Console\Commands;

use App\Models\OsReceiveSettlement;
use App\Models\User;
use App\Services\AppPushService;
use App\Services\OsSettlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Send every User-App + Rider-App push type to real FCM tokens for QA.
 *
 *   php artisan pds:test-push --from-file=/tmp/pds_push_targets.json --user-id=5 --rider-id=12
 */
class TestAppPushNotificationsCommand extends Command
{
    protected $signature = 'pds:test-push
        {--user-id= : Client user id (OS / User App)}
        {--rider-id= : Delivery man user id (Rider App)}
        {--user-token= : Override FCM token for User App}
        {--rider-token= : Override FCM token for Rider App}
        {--from-file= : JSON with user/rider fcm_token fields}
        {--types= : Comma list of types (default: all)}
        {--dry-run : Print plan only}
        {--delay=1.5 : Seconds between sends}';

    protected $description = 'Send all User App + Rider App push notification types via FCM for testing';

    public function handle(AppPushService $push): int
    {
        $file = $this->option('from-file');
        $fileData = [];
        if ($file && is_file($file)) {
            $fileData = json_decode((string) file_get_contents($file), true) ?: [];
        }

        $userId = (int) ($this->option('user-id') ?: ($fileData['user']['id'] ?? 0));
        $riderId = (int) ($this->option('rider-id') ?: ($fileData['rider']['id'] ?? 0));
        $userToken = trim((string) ($this->option('user-token') ?: ($fileData['user']['fcm_token'] ?? '')));
        $riderToken = trim((string) ($this->option('rider-token') ?: ($fileData['rider']['fcm_token'] ?? '')));

        if ($userId < 1 || $riderId < 1) {
            $this->error('Provide --user-id and --rider-id (or --from-file with ids).');

            return self::FAILURE;
        }

        $creds = $push->credentialsStatus();
        $this->table(['App', 'SA JSON'], [
            ['User App (client)', $creds['user'] ? ($creds['paths']['user'] ?? 'yes') : 'MISSING'],
            ['Rider App (delivery)', $creds['delivery'] ? ($creds['paths']['delivery'] ?? 'yes') : 'MISSING'],
        ]);
        if (! $creds['user'] || ! $creds['delivery']) {
            $this->error('Need both user + delivery Firebase service-account JSON files.');

            return self::FAILURE;
        }

        $user = User::find($userId);
        $rider = User::find($riderId);
        if (! $user || ! $rider) {
            $this->error('User or rider not found in local DB.');

            return self::FAILURE;
        }

        if ($userToken !== '') {
            $user->forceFill(['fcm_token' => $userToken])->save();
            $user->refresh();
        }
        if ($riderToken !== '') {
            $rider->forceFill(['fcm_token' => $riderToken])->save();
            $rider->refresh();
        }

        if (trim((string) $user->fcm_token) === '' || trim((string) $rider->fcm_token) === '') {
            $this->error('Missing fcm_token for user and/or rider. Pass --from-file or --*-token.');

            return self::FAILURE;
        }

        $this->info("User  #{$user->id} {$user->name} ({$user->user_type}) token=".substr((string) $user->fcm_token, 0, 28).'…');
        $this->info("Rider #{$rider->id} {$rider->name} ({$rider->user_type}) token=".substr((string) $rider->fcm_token, 0, 28).'…');

        $filter = array_filter(array_map('trim', explode(',', (string) ($this->option('types') ?: ''))));
        $cases = $this->buildCases($user, $rider);
        if ($filter !== []) {
            $cases = array_values(array_filter($cases, fn ($c) => in_array($c['type'], $filter, true)));
        }

        $this->info('Sending '.count($cases).' FCM pushes…');
        if ($this->option('dry-run')) {
            foreach ($cases as $c) {
                $this->line("  [{$c['app']}] {$c['type']} — {$c['title']}");
            }

            return self::SUCCESS;
        }

        $sendFcm = new \ReflectionMethod(AppPushService::class, 'sendFcm');
        $sendFcm->setAccessible(true);

        $delay = max(0.3, (float) $this->option('delay'));
        $rows = [];
        foreach ($cases as $i => $case) {
            $target = $case['app'] === 'rider' ? $rider : $user;
            $fcmOk = false;
            $err = '';
            try {
                $payload = array_merge([
                    'type' => $case['type'],
                    'subject' => $case['title'],
                    'message' => $case['body'],
                ], $case['extra']);
                // One FCM send per type (device heads-up / system tray).
                $fcmOk = (bool) $sendFcm->invoke($push, $target->fresh(), $case['title'], $case['body'], $payload);
            } catch (\Throwable $e) {
                $err = $e->getMessage();
                Log::warning('pds:test-push failed', ['type' => $case['type'], 'error' => $err]);
            }

            $status = $err !== '' ? 'FAIL' : ($fcmOk ? 'FCM_OK' : 'NO_FCM');
            $rows[] = [$i + 1, $case['app'], $case['type'], $status, $err !== '' ? $err : $case['title']];
            $this->line(sprintf(
                '  %s [%s] %s — %s',
                $status === 'FCM_OK' ? '✓' : ($status === 'NO_FCM' ? '△' : '✗'),
                $case['app'],
                $case['type'],
                $err !== '' ? $err : $case['title']
            ));
            usleep((int) ($delay * 1_000_000));
        }

        $this->newLine();
        $this->table(['#', 'App', 'Type', 'Result', 'Detail'], $rows);
        $fail = count(array_filter($rows, fn ($r) => $r[3] === 'FAIL'));
        $noFcm = count(array_filter($rows, fn ($r) => $r[3] === 'NO_FCM'));
        $this->info("Done. Failures: {$fail}  No-FCM: {$noFcm}");
        $this->comment('Watch flutter_user / flutter_delivery debug logs for onMessage / heads-up.');

        return ($fail > 0 || $noFcm > 0) ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<array{app:string,type:string,title:string,body:string,extra:array}>
     */
    protected function buildCases(User $user, User $rider): array
    {
        $orderId = '999001';
        $itemId = '999002';
        $stamp = now()->format('H:i:s');

        return [
            [
                'app' => 'user',
                'type' => AppPushService::TYPE_ITEM_PENDING,
                'title' => 'Test · Pending',
                'body' => "QA pending item #{$itemId} @ {$stamp}",
                'extra' => ['id' => 'ITEM_'.$itemId, 'order_id' => $orderId, 'item_id' => $itemId, 'app' => 'os'],
            ],
            [
                'app' => 'user',
                'type' => AppPushService::TYPE_ITEM_DELIVERED,
                'title' => 'Test · Delivered',
                'body' => "QA delivered item #{$itemId} @ {$stamp}",
                'extra' => ['id' => 'ITEM_'.$itemId, 'order_id' => $orderId, 'item_id' => $itemId, 'app' => 'os'],
            ],
            [
                'app' => 'user',
                'type' => AppPushService::TYPE_ITEM_MESSAGE,
                'title' => 'Test · Message',
                'body' => "QA item message @ {$stamp}",
                'extra' => [
                    'id' => 'ITEM_MSG_'.$itemId,
                    'order_id' => $orderId,
                    'item_id' => $itemId,
                    'screen' => 'item_message',
                    'app' => 'os',
                ],
            ],
            [
                'app' => 'user',
                'type' => AppPushService::TYPE_PICKUP_READY,
                'title' => 'Test · Assign 100',
                'body' => "QA pickup ready @ {$stamp}",
                'extra' => [
                    'id' => 'ORDER_'.$orderId,
                    'order_id' => $orderId,
                    'item_id' => '',
                    'item_count' => '3',
                    'order_count' => '1',
                    'screen' => 'assign100_today',
                    'app' => 'os',
                ],
            ],
            [
                'app' => 'user',
                'type' => AppPushService::TYPE_OS_APPROVED,
                'title' => 'Account Approved',
                'body' => "User: qa_test\nPass: Test1234 @ {$stamp}",
                'extra' => [
                    'id' => 'OS_'.$user->id,
                    'screen' => 'login',
                    'app' => 'os',
                    'username' => 'qa_test',
                ],
            ],
            [
                'app' => 'user',
                'type' => AppPushService::TYPE_OS_REJECTED,
                'title' => 'Account Rejected',
                'body' => AppPushService::OS_REJECTED_PUSH_BODY,
                'extra' => [
                    'id' => 'OS_REJ_'.$user->id,
                    'screen' => 'login',
                    'app' => 'os',
                    'approval_rejection_reason' => 'QA rejection reason @ '.$stamp,
                ],
            ],
            [
                'app' => 'user',
                'type' => AppPushService::TYPE_KYO_SHIN_GIVEN,
                'title' => 'Test · ကြိုရှင်း',
                'body' => "QA kyo shin given @ {$stamp}",
                'extra' => [
                    'id' => 'KYO_SHIN_QA',
                    'kyo_shin_batch_id' => '1',
                    'screen' => 'kyo_shin',
                    'app' => 'os',
                ],
            ],
            [
                'app' => 'user',
                'type' => AppPushService::TYPE_KYO_SHIN_OVERDUE,
                'title' => 'Test · ကြိုရှင်း Overdue',
                'body' => "QA kyo shin overdue @ {$stamp}",
                'extra' => [
                    'id' => 'KYO_SHIN_OVERDUE_QA',
                    'kyo_shin_batch_id' => '1',
                    'screen' => 'kyo_shin',
                    'app' => 'os',
                ],
            ],
            [
                'app' => 'user',
                'type' => OsSettlementService::NOTIFICATION_TYPE,
                'title' => 'Test · Os ဆီသို့လွှဲရန်',
                'body' => "QA settlement 1,000 MMK @ {$stamp}",
                'extra' => [
                    'id' => 'SETTLEMENT_QA',
                    'settlement_id' => '1',
                    'category' => 'os_settlement',
                    'amount' => '1000',
                    'app' => 'os',
                ],
            ],
            [
                'app' => 'user',
                'type' => OsReceiveSettlement::NOTIFICATION_TYPE,
                'title' => 'Test · ဒလီခပေးချေရရန်',
                'body' => "QA receive pay @ {$stamp}",
                'extra' => [
                    'id' => 'OS_RECEIVE_QA',
                    'receive_id' => '1',
                    'category' => OsReceiveSettlement::NOTIFICATION_TYPE,
                    'app' => 'os',
                ],
            ],
            [
                'app' => 'user',
                'type' => OsReceiveSettlement::NOTIFICATION_APPROVED,
                'title' => 'Test · Payment Received',
                'body' => "QA receive approved @ {$stamp}",
                'extra' => [
                    'id' => 'OS_RECEIVE_OK_QA',
                    'receive_id' => '1',
                    'category' => OsReceiveSettlement::NOTIFICATION_APPROVED,
                    'app' => 'os',
                ],
            ],
            [
                'app' => 'user',
                'type' => OsReceiveSettlement::NOTIFICATION_REJECTED,
                'title' => 'Test · Payment Rejected',
                'body' => "QA receive rejected @ {$stamp}",
                'extra' => [
                    'id' => 'OS_RECEIVE_REJ_QA',
                    'receive_id' => '1',
                    'category' => OsReceiveSettlement::NOTIFICATION_REJECTED,
                    'admin_remark' => 'QA remark',
                    'app' => 'os',
                ],
            ],
            [
                'app' => 'rider',
                'type' => AppPushService::TYPE_PICKUP_ASSIGNED,
                'title' => 'Test · Pickup Assigned',
                'body' => "QA pickup order #{$orderId} @ {$stamp}",
                'extra' => [
                    'id' => 'ORDER_'.$orderId,
                    'order_id' => $orderId,
                    'item_id' => '',
                    'app' => 'delivery',
                ],
            ],
            [
                'app' => 'rider',
                'type' => AppPushService::TYPE_DELIVERY_ASSIGNED,
                'title' => 'Test · Delivery Assigned',
                'body' => "QA delivery item #{$itemId} @ {$stamp}",
                'extra' => [
                    'id' => 'ITEM_'.$itemId,
                    'order_id' => $orderId,
                    'item_id' => $itemId,
                    'app' => 'delivery',
                ],
            ],
        ];
    }
}
