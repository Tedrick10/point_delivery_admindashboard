<?php

namespace App\Http\Controllers;

use App\Exports\DailyCheckListExport;
use App\Exports\DailyCheckListOverallExport;
use App\Models\Branch;
use App\Models\DailyCheckInvoice;
use App\Models\DispatchOrderItem;
use App\Models\User;
use App\Services\DailyCheckListService;
use App\Services\OsSettlementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DailyCheckListController extends Controller
{
    public function index(Request $request, DailyCheckListService $service)
    {
        if (! auth()->user()->can('order-list')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $yangonToday = now('Asia/Yangon')->format('d-m-Y');
        $fromDateRaw = trim((string) $request->get('from_date', $yangonToday));
        $toDateRaw = trim((string) $request->get('to_date', $fromDateRaw));
        $fromDay = $service->parseDate($fromDateRaw)->toDateString();
        $toDay = $service->parseDate($toDateRaw)->toDateString();

        $mode = strtolower(trim((string) $request->get('mode', 'os')));
        if (! in_array($mode, ['os', 'rider', 'all'], true)) {
            $mode = 'os';
        }

        $branchFilter = $request->get('branch_id', 'all');
        $branchId = $branchFilter === 'all' || $branchFilter === '' || $branchFilter === null
            ? null
            : (int) $branchFilter;

        $loginUser = auth()->user();
        $branches = Branch::query()->where('status', 1)->orderBy('name')->get(['id', 'name']);
        if (! in_array((string) ($loginUser->user_type ?? ''), ['admin', 'demo_admin'], true)
            && (int) ($loginUser->branch_id ?? 0) > 0
        ) {
            $branches = $branches->where('id', (int) $loginUser->branch_id)->values();
            $branchId = (int) $loginUser->branch_id;
            $branchFilter = (string) $branchId;
        }

        $osFilter = $request->get('os_id', 'all');
        $osId = null;
        if ($mode === 'os' && $osFilter !== 'all' && $osFilter !== '') {
            $osId = (int) $osFilter;
        }

        $riderFilter = $request->get('rider_id', 'all');
        $riderId = null;
        if ($mode === 'rider' && $riderFilter !== 'all' && $riderFilter !== '') {
            $riderId = (int) $riderFilter;
        }

        $rows = $service->listRows($fromDay, $toDay, $mode, $branchId, $osId, $riderId);

        $osOptions = User::query()
            ->where('user_type', 'client')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $riderOptions = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $pageTitle = __('message.daily_check_list');
        $assets = [];
        $filterFromDate = $fromDateRaw;
        $filterToDate = $toDateRaw;

        return view('order.daily-check-list', compact(
            'pageTitle',
            'assets',
            'rows',
            'filterFromDate',
            'filterToDate',
            'mode',
            'branchFilter',
            'branches',
            'osFilter',
            'osOptions',
            'riderFilter',
            'riderOptions'
        ));
    }

    public function detail(int $invoice, DailyCheckListService $service)
    {
        if (! auth()->user()->can('order-list')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $invoiceModel = $service->findInvoiceOrFail($invoice);
        $items = $service->itemsForInvoice($invoiceModel);

        $html = view('order.partials._daily-check-detail', [
            'invoice' => $invoiceModel,
            'items' => $items,
            'partyName' => $service->partyDisplayName($invoiceModel),
            'canEdit' => auth()->user()->can('order-edit'),
        ])->render();

        return response()->json(['html' => $html]);
    }

    public function storeItemActionMedia(Request $request, int $item, DailyCheckListService $service)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $request->validate([
            'type' => 'required|in:order_photo,cust_photo,cust_sign',
            'photo' => 'required|image|max:10240',
        ]);

        $itemModel = DispatchOrderItem::query()->findOrFail($item);
        $result = $service->storeItemActionMedia(
            $itemModel,
            (string) $request->input('type'),
            $request->file('photo')
        );

        return response()->json([
            'message' => __('message.updated_successfully') ?? 'Updated',
            'type' => $result['type'],
            'url' => $result['url'],
            'media_id' => $result['media_id'],
        ]);
    }

    public function slip(int $invoice, DailyCheckListService $service)
    {
        if (! auth()->user()->can('order-list')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $invoiceModel = $service->findInvoiceOrFail($invoice);
        $items = $service->itemsForInvoice($invoiceModel);
        $invoiceDate = $invoiceModel->received_date?->format('d-m-Y') ?? now('Asia/Yangon')->format('d-m-Y');
        $slipData = $service->buildShowSlipRows($items, $invoiceDate);

        $sender = [
            'name' => $service->partyDisplayName($invoiceModel),
            'phone' => normalizeContactNumber((string) ($invoiceModel->partyUser?->contact_number ?? '')) ?: '-',
            'address' => $invoiceModel->partyUser?->address ?: '-',
        ];

        $html = view('order.partials._daily-check-slip', [
            'slipInvoiceDate' => $invoiceDate,
            'slipCompany' => $service->companyInfo(),
            'slipSender' => $sender,
            'slipRows' => $slipData['rows'],
            'slipTotals' => $slipData['totals'],
            'invoiceNo' => $invoiceModel->invoice_no,
        ])->render();

        return response()->json([
            'html' => $html,
            'invoice_no' => $invoiceModel->invoice_no,
        ]);
    }

    public function updateRemitDate(Request $request, int $invoice, DailyCheckListService $service)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $request->validate([
            'remitted_date' => 'required|string',
        ]);

        $invoiceModel = $service->findInvoiceOrFail($invoice);
        $updated = $service->updateRemittedDate($invoiceModel, (string) $request->input('remitted_date'));

        return response()->json([
            'message' => __('message.updated_successfully') ?? 'Updated',
            'remitted_date' => $updated->remitted_date?->format('d-m-Y'),
        ]);
    }

    public function updateRemitPhoto(Request $request, int $invoice, DailyCheckListService $service)
    {
        if (! auth()->user()->can('order-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $request->validate([
            'photo' => 'required|image|max:10240',
        ]);

        $invoiceModel = $service->findInvoiceOrFail($invoice);
        $updated = $service->updateRemittedPhoto($invoiceModel, $request->file('photo'));

        return response()->json([
            'message' => __('message.updated_successfully') ?? 'Updated',
            'remitted_photo_url' => $updated->remittedPhotoUrl(),
        ]);
    }

    public function pdf(int $invoice, DailyCheckListService $service, OsSettlementService $settlementService)
    {
        if (! auth()->user()->can('order-list')) {
            abort(403);
        }

        $invoiceModel = $service->findInvoiceOrFail($invoice);
        $items = $service->itemsForInvoice($invoiceModel);
        $invoiceDate = $invoiceModel->received_date?->format('d-m-Y') ?? now('Asia/Yangon')->format('d-m-Y');
        $slipData = $settlementService->buildSlipRows($items, $invoiceDate);

        $pdf = Pdf::loadView('order.daily-check-invoice-pdf', [
            'invoice' => $invoiceModel,
            'partyName' => $service->partyDisplayName($invoiceModel),
            'slipCompany' => $service->companyInfo(),
            'slipRows' => $slipData['rows'],
            'slipTotals' => $slipData['totals'],
            'slipInvoiceDate' => $invoiceDate,
            'remittedOnly' => false,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('invoice-'.$invoiceModel->invoice_no.'.pdf');
    }

    public function remittedPdf(int $invoice, DailyCheckListService $service, OsSettlementService $settlementService)
    {
        if (! auth()->user()->can('order-list')) {
            abort(403);
        }

        $invoiceModel = $service->findInvoiceOrFail($invoice);
        $items = $service->itemsForInvoice($invoiceModel);
        $invoiceDate = $invoiceModel->received_date?->format('d-m-Y') ?? now('Asia/Yangon')->format('d-m-Y');
        $slipData = $settlementService->buildSlipRows($items, $invoiceDate);

        $pdf = Pdf::loadView('order.daily-check-invoice-pdf', [
            'invoice' => $invoiceModel,
            'partyName' => $service->partyDisplayName($invoiceModel),
            'slipCompany' => $service->companyInfo(),
            'slipRows' => $slipData['rows'],
            'slipTotals' => $slipData['totals'],
            'slipInvoiceDate' => $invoiceDate,
            'remittedOnly' => true,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('remitted-invoice-'.$invoiceModel->invoice_no.'.pdf');
    }

    public function excelInvoice(int $invoice, DailyCheckListService $service)
    {
        if (! auth()->user()->can('order-list')) {
            abort(403);
        }

        $invoiceModel = $service->findInvoiceOrFail($invoice);
        $items = $service->itemsForInvoice($invoiceModel);

        return Excel::download(
            new DailyCheckListOverallExport(collect([(object) [
                'invoice' => $invoiceModel,
                'party_name' => $service->partyDisplayName($invoiceModel),
                'items' => $items,
            ]])),
            'invoice-'.$invoiceModel->invoice_no.'.xlsx'
        );
    }

    public function exportExcel(Request $request, DailyCheckListService $service)
    {
        if (! auth()->user()->can('order-list')) {
            abort(403);
        }

        $rows = $this->filteredRows($request, $service);
        $mode = strtolower(trim((string) $request->get('mode', 'os')));
        if (! in_array($mode, ['os', 'rider', 'all'], true)) {
            $mode = 'os';
        }

        return Excel::download(new DailyCheckListExport($rows, $mode), 'dailychecklist.xlsx');
    }

    public function exportExcelOverall(Request $request, DailyCheckListService $service)
    {
        if (! auth()->user()->can('order-list')) {
            abort(403);
        }

        $rows = $this->filteredRows($request, $service);
        $payload = $rows->map(function ($row) use ($service) {
            $invoice = $service->findInvoiceOrFail((int) $row->id);

            return (object) [
                'invoice' => $invoice,
                'party_name' => $row->name,
                'items' => $service->itemsForInvoice($invoice),
            ];
        });

        return Excel::download(new DailyCheckListOverallExport($payload), 'dailychecklistoverall.xlsx');
    }

    protected function filteredRows(Request $request, DailyCheckListService $service)
    {
        $yangonToday = now('Asia/Yangon')->format('d-m-Y');
        $fromDateRaw = trim((string) $request->get('from_date', $yangonToday));
        $toDateRaw = trim((string) $request->get('to_date', $fromDateRaw));
        $fromDay = $service->parseDate($fromDateRaw)->toDateString();
        $toDay = $service->parseDate($toDateRaw)->toDateString();

        $mode = strtolower(trim((string) $request->get('mode', 'os')));
        if (! in_array($mode, ['os', 'rider', 'all'], true)) {
            $mode = 'os';
        }

        $branchFilter = $request->get('branch_id', 'all');
        $branchId = ($branchFilter === 'all' || $branchFilter === '' || $branchFilter === null)
            ? null
            : (int) $branchFilter;

        $osFilter = $request->get('os_id', 'all');
        $osId = ($mode === 'os' && $osFilter !== 'all' && $osFilter !== '') ? (int) $osFilter : null;

        $riderFilter = $request->get('rider_id', 'all');
        $riderId = ($mode === 'rider' && $riderFilter !== 'all' && $riderFilter !== '') ? (int) $riderFilter : null;

        return $service->listRows($fromDay, $toDay, $mode, $branchId, $osId, $riderId);
    }
}
