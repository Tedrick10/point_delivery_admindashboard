<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\OsSettlementBatch;
use App\Models\OsSettlementDraft;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OsSettlementService
{
    public const NOTIFICATION_TYPE = 'os_settlement';

    public function __construct(
        protected AppPushService $pushService
    ) {}

    /**
     * Completed items for settlement within a date range.
     * Pass null for $osId to include all Online Shops.
     */
    public function completedItemsQuery(?int $osId, string $fromDay, string $toDay)
    {
        return DispatchOrderItem::query()
            ->with(['order.client.city', 'order.city', 'fromBranch', 'toBranch'])
            ->when($osId !== null, function ($query) use ($osId) {
                $query->whereHas('order', function ($q) use ($osId) {
                    if ($osId > 0) {
                        $q->where('client_id', $osId);
                    } else {
                        $q->where(function ($inner) {
                            $inner->whereNull('client_id')->orWhere('client_id', 0);
                        });
                    }
                });
            })
            ->where('status', 'completed')
            ->whereNotNull('admin_completed_at')
            ->whereNull('admin_finished_at')
            ->where(function ($dateQuery) use ($fromDay, $toDay) {
                $dateQuery->whereBetween('received_date', [$fromDay, $toDay])
                    ->orWhere(function ($fallback) use ($fromDay, $toDay) {
                        $fallback->whereNull('received_date')
                            ->where(function ($assigned) use ($fromDay, $toDay) {
                                $assigned->where(function ($q) use ($fromDay, $toDay) {
                                    $q->whereNotNull('assigned_at')
                                        ->whereDate('assigned_at', '>=', $fromDay)
                                        ->whereDate('assigned_at', '<=', $toDay);
                                })->orWhere(function ($q) use ($fromDay, $toDay) {
                                    $q->whereNull('assigned_at')
                                        ->whereDate('created_at', '>=', $fromDay)
                                        ->whereDate('created_at', '<=', $toDay);
                                });
                            });
                    });
            })
            ->orderByDesc('id');
    }

    public function buildSlipRows($items, string $invoiceDate): array
    {
        $rows = [];
        $totals = [
            'os_paid' => 0.0,
            'item_value' => 0.0,
            'deli_amount' => 0.0,
            'os_to_pay' => 0.0,
            'gate_amount' => 0.0,
        ];

        foreach ($items as $slipItem) {
            $osPaid = (float) ($slipItem->os_paid ?? 0);
            $itemValue = (float) ($slipItem->item_value ?? 0);
            $deliAmount = (float) ($slipItem->deli_amount ?? 0);
            $osToPay = $slipItem->displayOsToPay();
            $osToPaySlip = formatDispatchOsToPaySlip($osToPay);
            $date = $slipItem->received_date
                ? Carbon::parse($slipItem->received_date)->format('d-m-Y')
                : $invoiceDate;

            $gateAmount = (float) ($slipItem->gate_amount ?? 0);

            $rows[] = [
                'date' => $date,
                'customer' => $slipItem->customer_name ?: '-',
                'phone' => $slipItem->customer_phone ?: '-',
                'address' => $slipItem->customer_address ?: '-',
                'gate' => number_format($gateAmount),
                'gate_amount' => $gateAmount,
                'os_paid' => $osPaid,
                'item_value' => $itemValue,
                'deli_amount' => $deliAmount,
                'deli_amount_display' => formatDispatchDeliAmount($slipItem, $deliAmount),
                'os_to_pay' => $osToPaySlip['value'],
                'os_to_pay_display' => $osToPaySlip['formatted'],
                'os_to_pay_is_receive' => $osToPaySlip['is_receive'],
            ];

            $totals['os_paid'] += $osPaid;
            $totals['item_value'] += $itemValue;
            $totals['deli_amount'] += $deliAmount;
            $totals['os_to_pay'] += $osToPaySlip['value'];
            $totals['gate_amount'] += $gateAmount;
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    public function resolveSlipSender(User $osClient, $items, string $osName): array
    {
        $sender = [
            'name' => $osName,
            'phone' => normalizeContactNumber((string) ($osClient->contact_number ?? '')) ?: '-',
            'address' => '-',
        ];

        if ($items->isNotEmpty()) {
            $firstOrder = $items->first()->order;
            if ($firstOrder) {
                $resolvedName = resolveDispatchOsName($firstOrder);
                if ($resolvedName !== '-') {
                    $sender['name'] = $resolvedName;
                }
                $phone = resolveDispatchOsPhone($firstOrder);
                $address = resolveDispatchOsAddress($firstOrder);
                if ($phone !== '-') {
                    $sender['phone'] = $phone;
                }
                if ($address !== '-') {
                    $sender['address'] = $address;
                }
            }
        }

        return $sender;
    }

    public function kpayNameFromUser(User $user): string
    {
        $profile = is_array($user->os_profile) ? $user->os_profile : [];

        return trim((string) ($profile['kpay_name'] ?? ''));
    }

    public function kpayNoFromUser(User $user): string
    {
        $profile = is_array($user->os_profile) ? $user->os_profile : [];

        return trim((string) ($profile['kpay_no'] ?? ''));
    }

    public function uploadKpaySlip(int $osId, string $fromDay, string $toDay, UploadedFile $file): OsSettlementDraft
    {
        $dir = $this->storageDir($osId, $fromDay, $toDay);
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }
        $filename = 'kpay-slip-'.Str::random(8).'.'.$ext;
        $path = $file->storeAs($dir, $filename, 'public');

        $draft = OsSettlementDraft::query()->updateOrCreate(
            [
                'os_user_id' => $osId,
                'from_date' => $fromDay,
                'to_date' => $toDay,
            ],
            ['kpay_slip_path' => $path]
        );

        return $draft;
    }

    public function getDraftKpayPath(int $osId, string $fromDay, string $toDay): ?string
    {
        $draft = OsSettlementDraft::query()
            ->where('os_user_id', $osId)
            ->where('from_date', $fromDay)
            ->where('to_date', $toDay)
            ->first();

        return $draft?->kpay_slip_path;
    }

    public function filterItemsBySettlementSide($items, ?string $settlementSide)
    {
        $side = in_array($settlementSide, ['pay', 'receive'], true) ? $settlementSide : null;
        if ($side === null) {
            return $items;
        }

        return $items
            ->filter(static function ($item) use ($side) {
                $amount = (float) $item->displayOsToPay();
                if ($side === 'pay') {
                    return $amount < 0;
                }

                return $amount > 0;
            })
            ->values();
    }

    public function finishOs(
        int $osId,
        string $fromDay,
        string $toDay,
        int $finishedBy,
        array $slipCompany,
        string $osName,
        User $osClient,
        string $deliveryFormat = 'table',
        string $paymentMethod = 'kpay',
        ?string $settlementSide = null
    ): OsSettlementBatch {
        $items = $this->completedItemsQuery($osId, $fromDay, $toDay)->get();
        $items = $this->filterItemsBySettlementSide($items, $settlementSide);
        if ($items->isEmpty()) {
            throw new \RuntimeException(__('message.os_settlement_no_completed_items'));
        }

        $paymentMethod = in_array($paymentMethod, ['kpay', 'cash'], true) ? $paymentMethod : 'kpay';
        $kpayPath = $this->getDraftKpayPath($osId, $fromDay, $toDay);

        // Both Kpay and Cash require an uploaded proof image before Finish.
        if (! $kpayPath || ! Storage::disk('public')->exists($kpayPath)) {
            throw new \RuntimeException(__('message.os_settlement_kpay_slip_required'));
        }

        $toDateRaw = Carbon::parse($toDay)->format('d-m-Y');
        $slipData = $this->buildSlipRows($items, $toDateRaw);
        $slipSender = $this->resolveSlipSender($osClient, $items, $osName);
        $amount = (float) $slipData['totals']['os_to_pay'];
        $itemIds = $items->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        @set_time_limit(120);

        $batch = OsSettlementBatch::create([
            'os_user_id' => $osId,
            'from_date' => $fromDay,
            'to_date' => $toDay,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'settlement_side' => in_array($settlementSide, ['pay', 'receive'], true) ? $settlementSide : null,
            'delivery_format' => $this->normalizeDeliveryFormat($deliveryFormat),
            'kpay_name' => $this->kpayNameFromUser($osClient),
            'kpay_no' => $this->kpayNoFromUser($osClient),
            'kpay_slip_path' => $kpayPath,
            'item_ids' => $itemIds,
            'finished_by' => $finishedBy,
            'finished_at' => now(),
        ]);

        DispatchOrderItem::query()
            ->whereIn('id', $itemIds)
            ->update([
                'admin_finished_at' => now(),
                'admin_updated_at' => now(),
                'updated_at' => now(),
            ]);

        // Keep draft if the other settlement side still has unfinished items for this OS.
        if (! $this->hasUnfinishedCompletedItems($osId, $fromDay, $toDay)) {
            OsSettlementDraft::query()
                ->where('os_user_id', $osId)
                ->where('from_date', $fromDay)
                ->where('to_date', $toDay)
                ->delete();
        }

        $isReceiveSide = $settlementSide === 'receive';
        $receiveId = null;

        if ($isReceiveSide) {
            // Receive row first (no push yet) — push runs after response with file generation.
            try {
                $receive = app(OsReceiveSettlementService::class)->createFromBatch($batch, $finishedBy, false);
                $receiveId = (int) $receive->id;
            } catch (\Throwable $e) {
                Log::warning('os receive settlement create failed', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            try {
                app(MoneyTransferService::class)->recordFromSettlementBatch($batch);
            } catch (\Throwable $e) {
                Log::warning('os settlement money transfer failed', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $batchId = (int) $batch->id;
        $osClientId = ($osId > 0 && $osClient && (int) $osClient->id > 0) ? (int) $osClient->id : 0;

        // Slip files + FCM are slow (especially Finish All × N OS). Do them after the HTTP response.
        dispatch(function () use (
            $batchId,
            $osClientId,
            $isReceiveSide,
            $receiveId,
            $slipCompany,
            $slipSender,
            $slipData,
            $toDateRaw
        ) {
            @set_time_limit(180);
            $freshBatch = OsSettlementBatch::query()->find($batchId);
            if (! $freshBatch) {
                return;
            }

            try {
                app(OsSettlementService::class)->generateLightSettlementFiles(
                    $freshBatch,
                    $slipCompany,
                    $slipSender,
                    $slipData,
                    $toDateRaw
                );
            } catch (\Throwable $e) {
                Log::warning('os settlement light files failed', [
                    'batch_id' => $batchId,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($isReceiveSide) {
                if ($receiveId > 0 && $osClientId > 0) {
                    try {
                        $receive = \App\Models\OsReceiveSettlement::query()->find($receiveId);
                        $osUser = User::query()->find($osClientId);
                        if ($receive && $osUser) {
                            app(OsReceiveSettlementService::class)->notifyPending($osUser, $receive);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('os receive pending notify failed', [
                            'batch_id' => $batchId,
                            'receive_id' => $receiveId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } elseif ($osClientId > 0) {
                try {
                    $osUser = User::query()->find($osClientId);
                    if ($osUser) {
                        app(OsSettlementService::class)->notifyOsSettlement($osUser, $freshBatch->fresh());
                    }
                } catch (\Throwable $e) {
                    Log::warning('os settlement notify failed', [
                        'batch_id' => $batchId,
                        'os_user_id' => $osClientId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            try {
                app(OsSettlementService::class)->generatePdfSettlementFiles(
                    $freshBatch->fresh(),
                    $slipCompany,
                    $slipSender,
                    $slipData,
                    $toDateRaw
                );
            } catch (\Throwable $e) {
                Log::warning('os settlement pdf files failed', [
                    'batch_id' => $batchId,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();

        return $batch->fresh();
    }

    /**
     * Lightweight unfinished check (no eager loads) used after finishing one side.
     */
    public function hasUnfinishedCompletedItems(int $osId, string $fromDay, string $toDay): bool
    {
        return $this->completedItemsQuery($osId, $fromDay, $toDay)->exists();
    }

    public function generateSettlementFiles(
        OsSettlementBatch $batch,
        array $slipCompany,
        array $slipSender,
        array $slipData,
        string $invoiceDate
    ): void {
        $this->generateLightSettlementFiles($batch, $slipCompany, $slipSender, $slipData, $invoiceDate);
        $this->generatePdfSettlementFiles($batch, $slipCompany, $slipSender, $slipData, $invoiceDate);
    }

    protected function settlementViewData(
        array $slipCompany,
        array $slipSender,
        array $slipData,
        string $invoiceDate
    ): array {
        return [
            'slipCompany' => $slipCompany,
            'slipSender' => $slipSender,
            'slipRows' => $slipData['rows'],
            'slipTotals' => $slipData['totals'],
            'slipInvoiceDate' => $invoiceDate,
            'standalone' => true,
        ];
    }

    public function generateLightSettlementFiles(
        OsSettlementBatch $batch,
        array $slipCompany,
        array $slipSender,
        array $slipData,
        string $invoiceDate
    ): void {
        $dir = 'os-settlements/batch-'.$batch->id;
        Storage::disk('public')->makeDirectory($dir);

        $viewData = $this->settlementViewData($slipCompany, $slipSender, $slipData, $invoiceDate);
        $tablePath = $dir.'/slip-table.html';
        Storage::disk('public')->put($tablePath, view('order.os-settlement-slip-table', $viewData)->render());

        $imagePath = $this->generateSlipImage($dir, $slipData, $slipCompany, $slipSender, $invoiceDate);

        $batch->update([
            'slip_table_path' => $tablePath,
            'slip_image_path' => $imagePath,
        ]);
    }

    public function generatePdfSettlementFiles(
        OsSettlementBatch $batch,
        array $slipCompany,
        array $slipSender,
        array $slipData,
        string $invoiceDate
    ): void {
        $dir = 'os-settlements/batch-'.$batch->id;
        Storage::disk('public')->makeDirectory($dir);
        $viewData = $this->settlementViewData($slipCompany, $slipSender, $slipData, $invoiceDate);

        try {
            $pdf = Pdf::loadView('order.os-settlement-slip-pdf', $viewData)->setPaper('a4', 'landscape');
            $pdfPath = $dir.'/slip.pdf';
            Storage::disk('public')->put($pdfPath, $pdf->output());
            $batch->update(['slip_pdf_path' => $pdfPath]);
        } catch (\Throwable $e) {
            Log::warning('os settlement slip pdf failed', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($batch->kpay_slip_path) {
            try {
                $kpayPdfPath = $this->generateKpayPdf($batch, $dir);
                $batch->update(['kpay_pdf_path' => $kpayPdfPath]);
            } catch (\Throwable $e) {
                Log::warning('os settlement kpay pdf failed', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            $combinedPdfPath = $this->generateCombinedPdf($batch, $dir, $viewData);
            $batch->update(['combined_pdf_path' => $combinedPdfPath]);
        } catch (\Throwable $e) {
            Log::warning('os settlement combined pdf failed', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function generateCombinedPdf(OsSettlementBatch $batch, string $dir, array $viewData): string
    {
        $kpayPath = $batch->kpay_slip_path;
        $absolute = $kpayPath ? Storage::disk('public')->path($kpayPath) : null;

        $pdf = Pdf::loadView('order.os-settlement-combined-pdf', array_merge($viewData, [
            'kpayName' => $batch->kpay_name,
            'kpayNo' => $batch->kpay_no,
            'kpayImagePath' => $absolute,
            'amount' => $batch->amount,
        ]))->setPaper('a4', 'landscape');

        $pdfPath = $dir.'/combined.pdf';
        Storage::disk('public')->put($pdfPath, $pdf->output());

        return $pdfPath;
    }

    protected function generateSlipImage(
        string $dir,
        array $slipData,
        array $slipCompany,
        array $slipSender,
        string $invoiceDate
    ): string {
        $rows = $slipData['rows'];
        $lineHeight = 22;
        $headerHeight = 120;
        $tableHeader = 28;
        $rowCount = max(1, count($rows));
        $footerHeight = 28;
        $width = 900;
        $height = $headerHeight + $tableHeader + ($rowCount * $lineHeight) + $footerHeight + 40;

        $img = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 30, 30, 30);
        $red = imagecolorallocate($img, 220, 38, 38);
        $orange = imagecolorallocate($img, 249, 115, 22);
        $gray = imagecolorallocate($img, 120, 120, 120);
        imagefill($img, 0, 0, $white);

        $font = 3;
        $fontBold = 4;
        imagestring($img, $fontBold, 20, 12, (string) ($slipCompany['name'] ?? 'Point Delivery'), $black);
        imagestring($img, $font, 20, 32, (string) ($slipCompany['address'] ?? ''), $gray);
        imagestring($img, $font, 20, 48, (string) ($slipCompany['phone'] ?? ''), $gray);
        imagestring($img, $font, 600, 12, 'INVOICE DATE: '.$invoiceDate, $black);
        imagestring($img, $font, 20, 72, 'Sender: '.($slipSender['name'] ?? ''), $black);
        imagestring($img, $font, 20, 88, 'Phone: '.($slipSender['phone'] ?? ''), $black);

        $y = $headerHeight;
        imagefilledrectangle($img, 10, $y, $width - 10, $y + $tableHeader, $orange);
        $cols = ['NO', 'DATE', 'CUSTOMER', 'PHONE', 'ITEM', 'DELI', 'Gate', 'OSTOPAY'];
        $xPositions = [15, 45, 120, 250, 370, 450, 560, 680];
        foreach ($cols as $i => $label) {
            imagestring($img, $fontBold, $xPositions[$i], $y + 8, $label, $white);
        }

        $y += $tableHeader;
        foreach ($rows as $index => $row) {
            if ($index % 2 === 1) {
                imagefilledrectangle($img, 10, $y, $width - 10, $y + $lineHeight, imagecolorallocate($img, 245, 245, 245));
            }
            $osToPayText = (string) ($row['deli_amount_display'] ?? number_format((float) $row['deli_amount']));
            $osPayDisplay = (string) $row['os_to_pay_display'];
            $osPayColor = ! empty($row['os_to_pay_is_receive']) ? $red : $black;
            imagestring($img, $font, 15, $y + 6, (string) ($index + 1), $black);
            imagestring($img, $font, 45, $y + 6, Str::limit((string) $row['date'], 10, ''), $black);
            imagestring($img, $font, 120, $y + 6, Str::limit((string) $row['customer'], 16, ''), $black);
            imagestring($img, $font, 250, $y + 6, Str::limit((string) $row['phone'], 12, ''), $black);
            imagestring($img, $font, 370, $y + 6, number_format((float) $row['item_value']), $black);
            imagestring($img, $font, 450, $y + 6, Str::limit($osToPayText, 16, ''), $black);
            imagestring($img, $font, 560, $y + 6, Str::limit((string) ($row['gate'] ?? '-'), 10, ''), $black);
            imagestring($img, $font, 680, $y + 6, $osPayDisplay, $osPayColor);
            $y += $lineHeight;
        }

        imagefilledrectangle($img, 10, $y, $width - 10, $y + $footerHeight, $orange);
        $totals = $slipData['totals'];
        $totalOsToPay = (float) $totals['os_to_pay'];
        imagestring($img, $fontBold, 120, $y + 8, 'TOTAL', $white);
        imagestring($img, $fontBold, 370, $y + 8, number_format((float) $totals['item_value']), $white);
        imagestring($img, $fontBold, 450, $y + 8, number_format((float) $totals['deli_amount']), $white);
        imagestring($img, $fontBold, 560, $y + 8, number_format((float) ($totals['gate_amount'] ?? 0)), $white);
        imagestring($img, $fontBold, 680, $y + 8, number_format($totalOsToPay), $totalOsToPay < 0 ? $red : $white);

        $imagePath = $dir.'/slip.png';
        $fullPath = Storage::disk('public')->path($imagePath);
        imagepng($img, $fullPath);
        imagedestroy($img);

        return $imagePath;
    }

    protected function generateKpayPdf(OsSettlementBatch $batch, string $dir): string
    {
        $kpayPath = $batch->kpay_slip_path;
        $absolute = Storage::disk('public')->path($kpayPath);
        $pdf = Pdf::loadView('order.os-settlement-kpay-pdf', [
            'kpayName' => $batch->kpay_name,
            'kpayNo' => $batch->kpay_no,
            'kpayImagePath' => $absolute,
            'amount' => $batch->amount,
        ])->setPaper('a4');
        $pdfPath = $dir.'/kpay.pdf';
        Storage::disk('public')->put($pdfPath, $pdf->output());

        return $pdfPath;
    }

    public function notifyOsSettlement(User $osClient, OsSettlementBatch $batch): void
    {
        $batch->refresh();
        $urls = $this->publicUrls($batch);

        $this->pushService->notifyUser(
            $osClient,
            self::NOTIFICATION_TYPE,
            __('message.os_settlement_notification_title'),
            __('message.os_settlement_notification_body', [
                'amount' => number_format((float) $batch->amount),
                'from' => Carbon::parse($batch->from_date)->format('d-m-Y'),
                'to' => Carbon::parse($batch->to_date)->format('d-m-Y'),
            ]),
            [
                'id' => 'SETTLEMENT_'.$batch->id,
                'settlement_id' => (string) $batch->id,
                'category' => 'os_settlement',
                'delivery_format' => $batch->delivery_format ?? 'table',
                'slip_table_url' => $urls['slip_table_url'],
                'slip_image_url' => $urls['slip_image_url'],
                'slip_pdf_url' => $urls['slip_pdf_url'],
                'kpay_image_url' => $urls['kpay_image_url'],
                'kpay_pdf_url' => $urls['kpay_pdf_url'],
                'combined_pdf_url' => $urls['combined_pdf_url'],
                'amount' => (string) $batch->amount,
                'from_date' => Carbon::parse($batch->from_date)->format('d-m-Y'),
                'to_date' => Carbon::parse($batch->to_date)->format('d-m-Y'),
                'app' => 'os',
            ]
        );
    }

    public function publicUrls(OsSettlementBatch $batch): array
    {
        return [
            'slip_table_url' => $this->urlForPath($batch->slip_table_path),
            'slip_image_url' => $this->urlForPath($batch->slip_image_path),
            'slip_pdf_url' => $this->urlForPath($batch->slip_pdf_path),
            'kpay_image_url' => $this->urlForPath($batch->kpay_slip_path),
            'kpay_pdf_url' => $this->urlForPath($batch->kpay_pdf_path),
            'combined_pdf_url' => $this->urlForPath($batch->combined_pdf_path),
        ];
    }

    public function normalizeDeliveryFormat(?string $format): string
    {
        // Pay slip is table-only (Image / PDF removed).
        return 'table';
    }

    public function slipRowsForBatch(OsSettlementBatch $batch): array
    {
        $itemIds = is_array($batch->item_ids) ? $batch->item_ids : [];
        if ($itemIds === []) {
            return ['rows' => [], 'totals' => [
                'os_paid' => 0.0,
                'item_value' => 0.0,
                'deli_amount' => 0.0,
                'os_to_pay' => 0.0,
            ]];
        }

        $items = DispatchOrderItem::query()
            ->with(['order.client.city', 'order.city', 'fromBranch', 'toBranch'])
            ->whereIn('id', $itemIds)
            ->get()
            ->sortBy(fn ($item) => array_search((int) $item->id, $itemIds, true))
            ->values();

        $invoiceDate = $batch->to_date
            ? Carbon::parse($batch->to_date)->format('d-m-Y')
            : now()->format('d-m-Y');

        return $this->buildSlipRows($items, $invoiceDate);
    }

    public function urlForPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    protected function storageDir(int $osId, string $fromDay, string $toDay): string
    {
        return 'os-settlements/drafts/'.$osId.'_'.$fromDay.'_'.$toDay;
    }
}
