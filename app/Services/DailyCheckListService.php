<?php

namespace App\Services;

use App\Models\DailyCheckInvoice;
use App\Models\DispatchOrderItem;
use App\Models\OsSettlementBatch;
use App\Models\Profofpictures;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DailyCheckListService
{
    public function parseDate(string $value): Carbon
    {
        try {
            return Carbon::createFromFormat('d-m-Y', trim($value))->startOfDay();
        } catch (\Throwable $e) {
            return Carbon::parse($value)->startOfDay();
        }
    }

    /**
     * @return Collection<int, object>
     */
    public function listRows(
        string $fromDay,
        string $toDay,
        string $mode,
        ?int $branchId,
        ?int $osId,
        ?int $riderId
    ): Collection {
        $modes = $mode === 'all'
            ? [DailyCheckInvoice::PARTY_OS, DailyCheckInvoice::PARTY_RIDER]
            : [$mode === 'rider' ? DailyCheckInvoice::PARTY_RIDER : DailyCheckInvoice::PARTY_OS];

        $rows = collect();
        $pendingGroups = [];

        foreach ($modes as $partyType) {
            $items = $this->baseItemsQuery($fromDay, $toDay, $branchId)
                ->when($partyType === DailyCheckInvoice::PARTY_OS, function ($q) use ($osId) {
                    // OS only after ငွေရှင်းတမ်း Finish (admin_finished_at).
                    $q->whereNotNull('admin_finished_at');
                    $q->whereHas('order');
                    if ($osId !== null) {
                        if ($osId > 0) {
                            $q->whereHas('order', fn ($oq) => $oq->where('client_id', $osId));
                        } else {
                            $q->whereHas('order', function ($oq) {
                                $oq->where(function ($inner) {
                                    $inner->whereNull('client_id')->orWhere('client_id', 0);
                                });
                            });
                        }
                    }
                })
                ->when($partyType === DailyCheckInvoice::PARTY_RIDER, function ($q) use ($riderId) {
                    // Completed stays on Rider even after Finish; OS is additive.
                    $q->whereNotNull('delivery_man_id');
                    if ($riderId !== null && $riderId > 0) {
                        $q->where('delivery_man_id', $riderId);
                    }
                })
                ->with(['order.client.city', 'order.client', 'toBranch', 'fromBranch', 'deliveryMan.city'])
                ->get();

            $grouped = $items->groupBy(function (DispatchOrderItem $item) use ($partyType) {
                $day = $this->itemDay($item);
                $partyId = $partyType === DailyCheckInvoice::PARTY_OS
                    ? (int) ($item->order?->client_id ?? 0)
                    : (int) ($item->delivery_man_id ?? 0);

                return $partyType.'|'.$partyId.'|'.$day;
            });

            foreach ($grouped as $groupItems) {
                /** @var Collection<int, DispatchOrderItem> $groupItems */
                $first = $groupItems->first();
                $day = $this->itemDay($first);
                if ($day < $fromDay || $day > $toDay) {
                    continue;
                }
                $partyId = $partyType === DailyCheckInvoice::PARTY_OS
                    ? (int) ($first->order?->client_id ?? 0)
                    : (int) ($first->delivery_man_id ?? 0);

                $invoice = $this->ensureInvoice($partyType, $partyId, $day, $groupItems);
                $pendingGroups[] = [$invoice, $groupItems, $partyType];
            }
        }

        $methodByItemId = $this->loadOsPaymentMethodByItemId(
            collect($pendingGroups)->flatMap(fn ($g) => $g[1]->pluck('id'))->map(fn ($id) => (int) $id)->unique()->values()
        );

        foreach ($pendingGroups as [$invoice, $groupItems, $partyType]) {
            $rows->push($this->mapRow($invoice, $groupItems, $partyType, $methodByItemId));
        }

        return $rows
            ->sortByDesc(fn ($row) => $row->received_date_raw.'-'.$row->invoice_no)
            ->values();
    }

    public function ensureInvoice(
        string $partyType,
        int $partyUserId,
        string $receivedDay,
        ?Collection $items = null
    ): DailyCheckInvoice {
        $branchId = null;
        if ($items && $items->isNotEmpty()) {
            $branchId = $items
                ->map(fn (DispatchOrderItem $i) => $i->to_branch_id ?: $i->from_branch_id)
                ->filter()
                ->countBy()
                ->sortDesc()
                ->keys()
                ->first();
            $branchId = $branchId ? (int) $branchId : null;
        }

        $invoice = DailyCheckInvoice::query()->firstOrCreate(
            [
                'party_type' => $partyType,
                'party_user_id' => $partyUserId,
                'received_date' => $receivedDay,
            ],
            [
                'invoice_no' => $this->generateInvoiceNo(),
                'branch_id' => $branchId,
                'created_by' => auth()->id(),
                'item_ids' => $items
                    ? $items->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
                    : [],
            ]
        );

        if ($items && $items->isNotEmpty()) {
            $invoice->item_ids = $items->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
            if ($branchId && (int) $invoice->branch_id !== $branchId) {
                $invoice->branch_id = $branchId;
            }
            if ($invoice->isDirty()) {
                $invoice->save();
            }
        }

        return $invoice->loadMissing(['partyUser.city', 'partyUser.userBankAccount', 'branch', 'remittedByUser', 'createdByUser']);
    }

    public function findInvoiceOrFail(int $id): DailyCheckInvoice
    {
        return DailyCheckInvoice::query()
            ->with(['partyUser.city', 'partyUser.userBankAccount', 'branch', 'remittedByUser', 'createdByUser'])
            ->findOrFail($id);
    }

    /**
     * @return Collection<int, DispatchOrderItem>
     */
    public function itemsForInvoice(DailyCheckInvoice $invoice): Collection
    {
        $ids = is_array($invoice->item_ids) ? $invoice->item_ids : [];
        if ($ids === []) {
            return $this->reloadItemsForInvoice($invoice);
        }

        $items = DispatchOrderItem::query()
            ->with(['order.client.city', 'toBranch', 'fromBranch', 'deliveryMan', 'photoMedia', 'custPhotoMedia', 'custSignMedia'])
            ->whereIn('id', $ids)
            ->when($invoice->isOs(), fn ($q) => $q->whereNotNull('admin_finished_at'))
            ->get()
            ->sortBy(fn ($item) => array_search((int) $item->id, array_map('intval', $ids), true) ?: 9999)
            ->values();

        if ($items->isEmpty()) {
            return $this->reloadItemsForInvoice($invoice);
        }

        return $items;
    }

    public function updateRemittedDate(DailyCheckInvoice $invoice, string $dateRaw): DailyCheckInvoice
    {
        $day = $this->parseDate($dateRaw)->toDateString();
        $invoice->remitted_date = $day;
        $invoice->remitted_by = auth()->id();
        $invoice->save();

        return $invoice->fresh(['partyUser.city', 'partyUser.userBankAccount', 'branch', 'remittedByUser']);
    }

    public function updateRemittedPhoto(DailyCheckInvoice $invoice, UploadedFile $file): DailyCheckInvoice
    {
        $dir = 'daily-check/remitted/'.$invoice->id;
        if ($invoice->remitted_photo_path && Storage::disk('public')->exists($invoice->remitted_photo_path)) {
            Storage::disk('public')->delete($invoice->remitted_photo_path);
        }

        $path = $file->store($dir, 'public');
        $invoice->remitted_photo_path = $path;
        $invoice->remitted_by = auth()->id();
        $invoice->save();

        return $invoice->fresh(['partyUser.city', 'partyUser.userBankAccount', 'branch', 'remittedByUser']);
    }

    public function partyDisplayName(DailyCheckInvoice $invoice): string
    {
        if ($invoice->party_user_id <= 0) {
            return $invoice->isOs() ? __('message.no_os') : __('message.no_record_found');
        }

        $user = $invoice->partyUser;
        $name = trim((string) ($user?->name ?? ''));
        if ($name === '') {
            $name = '#'.$invoice->party_user_id;
        }
        $city = trim((string) ($user?->city?->name ?? ''));
        if ($city !== '') {
            $name .= ' ('.$city.')';
        }

        return $name;
    }

    public function companyInfo(): array
    {
        $app = appSettingData('get');
        $companyName = SettingData('order_invoice', 'company_name')
            ?: ($app->site_name ?? null)
            ?: 'Point Delivery';
        $companyPhone = SettingData('order_invoice', 'company_contact_number')
            ?: ($app->contact_number ?? null)
            ?: ($app->help_support_number ?? null)
            ?: '09400080670, 09402578059';
        $companyAddress = SettingData('order_invoice', 'company_address')
            ?: ($app->site_description ?? null)
            ?: '62A, 104A*105.';
        $companyEmail = SettingData('order_invoice', 'company_email')
            ?: ($app->site_email ?? null)
            ?: 'point@gmail.com';

        $logoUrl = SettingData('order_invoice', 'company_logo');
        if (! $logoUrl) {
            $logoSetting = \App\Models\Setting::where('type', 'order_invoice')->where('key', 'company_logo')->first();
            if ($logoSetting) {
                $logoUrl = getSingleMedia($logoSetting, 'company_logo', null);
            }
        }
        if (! $logoUrl && $app) {
            $logoUrl = getSingleMedia($app, 'site_logo', null);
        }

        return [
            'name' => $companyName,
            'phone' => $companyPhone,
            'address' => $companyAddress,
            'email' => $companyEmail,
            'logo' => $logoUrl,
        ];
    }

    /**
     * Store Check Detail Action media.
     * type: order_photo | cust_photo | cust_sign
     */
    public function storeItemActionMedia(DispatchOrderItem $item, string $type, UploadedFile $file): array
    {
        $map = [
            'order_photo' => ['column' => 'photo_id', 'prof_type' => 'check_order_photo', 'relation' => 'photoMedia'],
            'cust_photo' => ['column' => 'cust_photo_id', 'prof_type' => 'photocust', 'relation' => 'custPhotoMedia'],
            'cust_sign' => ['column' => 'cust_sign_id', 'prof_type' => 'signcust', 'relation' => 'custSignMedia'],
        ];

        if (! isset($map[$type])) {
            throw new \InvalidArgumentException('Invalid media type');
        }

        $meta = $map[$type];
        $profpicture = Profofpictures::create([
            'order_id' => $item->order_id,
            'type' => $meta['prof_type'],
        ]);
        $profpicture->addMedia($file)->toMediaCollection('prof_file');
        $media = $profpicture->getMedia('prof_file')->first();
        $mediaId = $media ? (int) $media->id : 0;

        if ($mediaId > 0) {
            $item->{$meta['column']} = $mediaId;
            $item->save();
        }

        $item->load($meta['relation']);
        $url = mediaAbsoluteUrl($item->{$meta['relation']});

        return [
            'type' => $type,
            'media_id' => $mediaId,
            'url' => $url,
        ];
    }

    public function itemActionMediaUrls(DispatchOrderItem $item): array
    {
        $item->loadMissing(['photoMedia', 'custPhotoMedia', 'custSignMedia']);

        return [
            'order_photo_url' => mediaAbsoluteUrl($item->photoMedia),
            'cust_photo_url' => mediaAbsoluteUrl($item->custPhotoMedia),
            'cust_sign_url' => mediaAbsoluteUrl($item->custSignMedia),
        ];
    }

    /**
     * OS Daily Check invoices for a list date (by invoice received_date).
     * Used by Summary Income Card — does not re-filter by admin_completed_at window.
     *
     * @return Collection<int, object>
     */
    public function osInvoiceRowsForDate(string $day, ?int $branchId = null): Collection
    {
        // Live OS rows for the day (settled only) — keeps Summary Income in sync.
        return $this->listRows($day, $day, DailyCheckInvoice::PARTY_OS, $branchId, null, null);
    }

    protected function baseItemsQuery(string $fromDay, string $toDay, ?int $branchId)
    {
        // Completed onwards. Invoice day matches Rider ငွေအပ်:
        // first Completed on C → C−1; later Completed that day → C.
        $boundsStart = dailyCheckListDayBounds($fromDay)['start'];
        $boundsEnd = dailyCheckListDayBounds($toDay)['end'];

        return DispatchOrderItem::query()
            ->where('status', 'completed')
            ->whereNotNull('admin_completed_at')
            ->where('admin_completed_at', '>=', $boundsStart)
            ->where('admin_completed_at', '<', $boundsEnd)
            ->when($branchId && $branchId > 0, function ($q) use ($branchId) {
                applyDestinationBranchFilter($q, $branchId);
            });
    }

    protected function reloadItemsForInvoice(DailyCheckInvoice $invoice): Collection
    {
        $day = $invoice->received_date?->toDateString();
        if (! $day) {
            return collect();
        }

        $items = $this->baseItemsQuery($day, $day, null)
            ->when($invoice->isOs(), function ($q) use ($invoice) {
                $q->whereNotNull('admin_finished_at');
                $partyId = (int) $invoice->party_user_id;
                if ($partyId > 0) {
                    $q->whereHas('order', fn ($oq) => $oq->where('client_id', $partyId));
                } else {
                    $q->whereHas('order', function ($oq) {
                        $oq->where(function ($inner) {
                            $inner->whereNull('client_id')->orWhere('client_id', 0);
                        });
                    });
                }
            })
            ->when($invoice->isRider(), function ($q) use ($invoice) {
                $q->where('delivery_man_id', (int) $invoice->party_user_id);
            })
            ->with(['order.client.city', 'toBranch', 'fromBranch', 'deliveryMan', 'photoMedia', 'custPhotoMedia', 'custSignMedia'])
            ->get()
            ->filter(fn (DispatchOrderItem $item) => $this->itemDay($item) === $day)
            ->values();

        $invoice->item_ids = $items->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $invoice->save();

        return $items->values();
    }

    protected function mapRow(
        DailyCheckInvoice $invoice,
        Collection $items,
        string $partyType,
        ?Collection $methodByItemId = null
    ): object {
        $advance = 0.0;
        $osPaid = 0.0;
        $osToPay = 0.0;
        $itemValue = 0.0;
        $custGet = 0.0;
        $gate = 0.0;
        $modifiedAt = null;

        foreach ($items as $item) {
            $advance += (float) ($item->advance_paid ?? 0);
            $osPaid += (float) ($item->os_paid ?? 0);
            $itemValue += (float) ($item->item_value ?? 0);
            $custGet += (float) ($item->cust_get ?? 0);
            $gate += (float) ($item->gate_amount ?? 0);
            $osToPay += $item->displayOsToPay();
            $ts = $item->admin_finished_at ?: $item->admin_completed_at ?: $item->updated_at;
            if ($ts && ($modifiedAt === null || $ts->gt($modifiedAt))) {
                $modifiedAt = $ts;
            }
        }

        // Deli fee = collected Total − amount paid to OS − gate.
        // os_to_pay is signed (negative = Point pays OS).
        $deli = round($custGet + $osToPay - $gate, 2);

        // Rider Amount = collected Cust Get. OS Amount also uses rider-collected Cust Get
        // (KBZ Pay / Cash Pay from settlement). OsToPay stays the remittance figure.
        $amount = $custGet;
        // Production greens Payment when amount == osPayment (settled).
        $osPayment = $invoice->remitted_date ? $amount : 0.0;
        $paymentInfo = $this->partyBankPaymentInfo($invoice, $amount);
        // Payment type (KBZ / Cash) after ငွေရှင်းတမ်း — known settlement method only.
        $paySplit = $this->resolveKpayCashAmounts($items, $methodByItemId);
        $osToPayDisplay = $this->formatAmountWithPayMethod($osToPay, $paySplit);

        $userName = $invoice->remittedByUser?->name
            ?: $invoice->createdByUser?->name
            ?: (auth()->user()?->name ?? '-');

        return (object) [
            'id' => $invoice->id,
            'invoice_no' => $invoice->invoice_no,
            'party_type' => $partyType,
            'party_user_id' => (int) $invoice->party_user_id,
            'name' => $this->partyDisplayName($invoice),
            'received_date' => $invoice->received_date?->format('d-m-Y') ?? '-',
            'received_date_raw' => $invoice->received_date?->toDateString() ?? '',
            'item_count' => $items->count(),
            'advance_paid' => $advance,
            'os_paid' => $osPaid,
            'deli_amount' => $deli,
            'item_value' => $itemValue,
            'cust_get' => $custGet,
            'gate' => $gate,
            'os_to_pay' => $osToPay,
            'amount' => $amount,
            'kpay_amount' => $paySplit['kpay'],
            'cash_amount' => $paySplit['cash'],
            'amount_display' => number_format($amount),
            'amount_html' => e(number_format($amount)),
            'os_to_pay_display' => $osToPayDisplay['plain'],
            'os_to_pay_html' => $osToPayDisplay['html'],
            'os_payment' => $osPayment,
            'payment_matched' => abs($amount - $osPayment) < 0.001,
            'bank_name_list' => $paymentInfo['bank_name_list'],
            'amount_list' => $paymentInfo['amount_list'],
            'user_name' => $userName,
            'modified_date' => $modifiedAt ? $modifiedAt->timezone('Asia/Yangon')->format('d-m-Y H:i:s') : '-',
            'remitted_date' => $invoice->remitted_date?->format('d-m-Y') ?? '',
            'remitted_photo_url' => $invoice->remittedPhotoUrl(),
            'has_remitted_photo' => (bool) $invoice->remitted_photo_path,
            'branch_id' => (int) ($invoice->branch_id ?? 0),
            'branch_name' => $invoice->branch?->name ?? '-',
        ];
    }

    /**
     * Map dispatch item id → settlement payment method (kpay|cash).
     *
     * @param  Collection<int, int>  $itemIds
     * @param  Collection<int, int>  $osUserIds
     * @return Collection<int, string>
     */
    protected function loadOsPaymentMethodByItemId(Collection $itemIds, ?Collection $osUserIds = null): Collection
    {
        $itemIds = $itemIds->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values();
        if ($itemIds->isEmpty()) {
            return collect();
        }

        $wanted = array_fill_keys($itemIds->all(), true);
        $map = [];
        $osUserIds = ($osUserIds ?? collect())
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        // Newest batch wins when an item appears in more than one settlement.
        OsSettlementBatch::query()
            ->whereNotNull('item_ids')
            ->when($osUserIds->isNotEmpty(), fn ($q) => $q->whereIn('os_user_id', $osUserIds->all()))
            ->orderByDesc('id')
            ->get(['id', 'payment_method', 'item_ids'])
            ->each(function (OsSettlementBatch $batch) use (&$map, $wanted) {
                $method = in_array((string) $batch->payment_method, ['kpay', 'cash'], true)
                    ? (string) $batch->payment_method
                    : 'kpay';
                foreach ((array) ($batch->item_ids ?? []) as $rawId) {
                    $id = (int) $rawId;
                    if (! isset($wanted[$id]) || isset($map[$id])) {
                        continue;
                    }
                    $map[$id] = $method;
                }
            });

        return collect($map);
    }

    /**
     * Split OsToPay into KBZ Pay / Cash Pay portions from settlement payment_method.
     * Only known settlement methods count — unmatched stays unlabeled (plain amount).
     *
     * @param  Collection<int, DispatchOrderItem>  $items
     * @param  Collection<int, string>|null  $methodByItemId
     * @return array{kpay: float, cash: float}
     */
    protected function resolveKpayCashAmounts(
        Collection $items,
        ?Collection $methodByItemId = null
    ): array {
        $kpay = 0.0;
        $cash = 0.0;
        $methodByItemId = $methodByItemId ?? collect();

        foreach ($items as $item) {
            $portion = (float) $item->displayOsToPay();
            $method = $methodByItemId->get((int) $item->id);
            if ($method === 'kpay') {
                $kpay += $portion;
            } elseif ($method === 'cash') {
                $cash += $portion;
            }
        }

        return ['kpay' => round($kpay, 2), 'cash' => round($cash, 2)];
    }

    /**
     * Amount cell: "21,000 (KBZ Pay)" / "21,000 (Cash Pay)" — no extra columns.
     *
     * @param  array{kpay: float, cash: float}  $paySplit
     * @return array{plain: string, html: string}
     */
    protected function formatAmountWithPayMethod(float $amount, array $paySplit): array
    {
        $fmt = static function (float $value): string {
            return abs($value - round($value)) < 0.001
                ? number_format($value, 0, '.', ',')
                : number_format($value, 2, '.', ',');
        };

        $kpay = (float) ($paySplit['kpay'] ?? 0);
        $cash = (float) ($paySplit['cash'] ?? 0);
        $hasKpay = abs($kpay) >= 0.001;
        $hasCash = abs($cash) >= 0.001;

        $lines = [];
        if ($hasKpay && $hasCash) {
            $lines[] = ['amount' => $fmt($kpay), 'label' => __('message.kbz_pay')];
            $lines[] = ['amount' => $fmt($cash), 'label' => __('message.cash_payment')];
        } elseif ($hasKpay) {
            $lines[] = ['amount' => $fmt($amount), 'label' => __('message.kbz_pay')];
        } elseif ($hasCash) {
            $lines[] = ['amount' => $fmt($amount), 'label' => __('message.cash_payment')];
        } else {
            $lines[] = ['amount' => $fmt($amount), 'label' => ''];
        }

        $plainParts = [];
        $htmlParts = [];
        foreach ($lines as $line) {
            $plainParts[] = $line['label'] !== ''
                ? $line['amount'].' ('.$line['label'].')'
                : $line['amount'];
            $htmlParts[] = formatDeliAmountPartsHtml($line['amount'], $line['label']);
        }

        return [
            'plain' => implode(' / ', $plainParts),
            'html' => count($htmlParts) === 1
                ? $htmlParts[0]
                : '<span class="pds-daily-check-amount-stack">'.implode('', $htmlParts).'</span>',
        ];
    }

    /**
     * Production Bank List dialog fields for Payment(Rider/Os).
     *
     * @return array{bank_name_list: string, amount_list: string}
     */
    public function partyBankPaymentInfo(DailyCheckInvoice $invoice, float $amount = 0.0): array
    {
        $invoice->loadMissing(['partyUser.userBankAccount']);
        $user = $invoice->partyUser;
        if (! $user) {
            return [
                'bank_name_list' => '-',
                'amount_list' => number_format($amount),
            ];
        }

        $lines = [];
        $amounts = [];

        if ($invoice->isOs()) {
            $settlement = app(OsSettlementService::class);
            $kpayName = $settlement->kpayNameFromUser($user);
            $kpayNo = $settlement->kpayNoFromUser($user);
            if ($kpayName !== '' || $kpayNo !== '') {
                $lines[] = 'KBZ Pay'.($kpayName !== '' ? ' — '.$kpayName : '');
                $amounts[] = $kpayNo !== '' ? $kpayNo : number_format($amount);
            }
        }

        $bank = $user->userBankAccount;
        if ($bank) {
            $bankName = trim((string) ($bank->bank_name ?? ''));
            $holder = trim((string) ($bank->account_holder_name ?? ''));
            $acct = trim((string) ($bank->account_number ?? ''));
            $label = $bankName !== '' ? $bankName : 'Bank';
            if ($holder !== '') {
                $label .= ' — '.$holder;
            }
            $lines[] = $label;
            $amounts[] = $acct !== '' ? $acct : number_format($amount);
        }

        if ($lines === []) {
            $lines[] = $user->name ?: '-';
            $amounts[] = number_format($amount);
        }

        return [
            'bank_name_list' => implode("\n", $lines),
            'amount_list' => implode("\n", $amounts),
        ];
    }

    public function itemStatusLabel(DispatchOrderItem $item): string
    {
        return app(DispatchOrderWorkflowService::class)->clientItemStatusLabel($item);
    }

    /**
     * Production Show Slip columns for Daily Check List:
     * No, Date, Name, Phone, Township, Os Paid, Advance, Item Value, Amount, CustPaid, Balance
     *
     * @param  Collection<int, DispatchOrderItem>  $items
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, float>}
     */
    public function buildShowSlipRows(Collection $items, string $invoiceDate): array
    {
        $rows = [];
        $totals = [
            'os_paid' => 0.0,
            'advance_paid' => 0.0,
            'item_value' => 0.0,
            'amount' => 0.0,
            'cust_paid' => 0.0,
            'balance' => 0.0,
        ];

        foreach ($items as $item) {
            $osPaid = (float) ($item->os_paid ?? 0);
            $advance = (float) ($item->advance_paid ?? 0);
            $itemValue = (float) ($item->item_value ?? 0);
            $amount = (float) ($item->cust_get ?? 0);
            if (abs($amount) < 0.001) {
                $amount = abs($item->displayOsToPay());
            }
            $custPaid = 0.0;
            $balance = $amount - $custPaid;
            $date = $item->received_date
                ? Carbon::parse($item->received_date)->format('d-m-Y')
                : $invoiceDate;
            $township = trim((string) ($item->township ?: $item->delivery_city ?: ''));

            $rows[] = [
                'date' => $date,
                'name' => $item->customer_name ?: '-',
                'phone' => $item->customer_phone ?: '-',
                'township' => $township !== '' ? $township : '-',
                'os_paid' => $osPaid,
                'advance_paid' => $advance,
                'item_value' => $itemValue,
                'amount' => $amount,
                'cust_paid' => $custPaid,
                'balance' => $balance,
            ];

            $totals['os_paid'] += $osPaid;
            $totals['advance_paid'] += $advance;
            $totals['item_value'] += $itemValue;
            $totals['amount'] += $amount;
            $totals['cust_paid'] += $custPaid;
            $totals['balance'] += $balance;
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    protected function itemDay(DispatchOrderItem $item): string
    {
        $at = $item->admin_completed_at ?: $item->admin_finished_at ?: $item->updated_at;

        return dailyCheckListDateForItem($item, $at ? Carbon::parse($at) : null)->toDateString();
    }

    protected function generateInvoiceNo(): string
    {
        do {
            $no = now('Asia/Yangon')->format('ymdHis').str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
        } while (DailyCheckInvoice::query()->where('invoice_no', $no)->exists());

        return $no;
    }
}
