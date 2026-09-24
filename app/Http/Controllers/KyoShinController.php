<?php

namespace App\Http\Controllers;

use App\Services\KyoShinService;
use Illuminate\Http\Request;

class KyoShinController extends Controller
{
    public function __construct(
        protected KyoShinService $kyoShin
    ) {}

    public function index(Request $request)
    {
        $this->authorizeList();

        $user = auth()->user();
        $scopeKey = $this->resolvedScope($request);
        $tab = $this->resolvedTab($request);
        [$fromDay, $toDay, $fromRaw, $toRaw] = $this->resolvedDates($request);

        $scopes = collect($this->kyoShin->scopes())
            ->filter(fn ($scope) => $this->kyoShin->actorCanAccess($user, $scope['key']))
            ->values();
        $summary = $this->kyoShin->summary($scopeKey);
        $this->kyoShin->recordDailyLedger($scopeKey, $summary);
        $ledgers = $this->kyoShin->dailyLedgers($scopeKey);
        $showItemList = false;
        $listItems = collect();
        $rows = $this->kyoShin->osRows($scopeKey, $tab, $fromDay, $toDay);
        $counts = [
            KyoShinService::TAB_OS_LIST => $this->kyoShin->osRows($scopeKey, KyoShinService::TAB_OS_LIST, $fromDay, $toDay)->count(),
            KyoShinService::TAB_ADVANCED_PAID => $this->kyoShin->osRows($scopeKey, KyoShinService::TAB_ADVANCED_PAID, $fromDay, $toDay)->count(),
            KyoShinService::TAB_FINISHED => $this->kyoShin->osRows($scopeKey, KyoShinService::TAB_FINISHED, $fromDay, $toDay)->count(),
        ];

        $pageTitle = __('message.kyo_shin_title');
        $assets = [];
        $canEditDue = $this->canEditDue();
        $canCheck = in_array($tab, [
            KyoShinService::TAB_ADVANCED_PAID,
            KyoShinService::TAB_FINISHED,
        ], true);
        $showCheckStatus = $tab === KyoShinService::TAB_OS_LIST || $canCheck;

        return view('order.kyo-shin', compact(
            'pageTitle',
            'assets',
            'scopeKey',
            'scopes',
            'tab',
            'summary',
            'ledgers',
            'rows',
            'listItems',
            'showItemList',
            'counts',
            'fromDay',
            'toDay',
            'fromRaw',
            'toRaw',
            'canEditDue',
            'canCheck',
            'showCheckStatus',
        ));
    }

    public function items(Request $request, int $osId)
    {
        $this->authorizeList();

        $scopeKey = $this->resolvedScope($request);
        $tab = $this->resolvedTab($request);
        [$fromDay, $toDay, $fromRaw, $toRaw] = $this->resolvedDates($request);
        $items = $this->kyoShin->itemsForOs($scopeKey, $tab, $osId, $fromDay, $toDay)
            ->each(function ($item) {
                $item->loadMissing('kyoShinItem.batch');
                $item->kyo_shin_amount = $this->kyoShin->itemAmount($item);
            });
        $rows = $this->kyoShin->osRows($scopeKey, $tab, $fromDay, $toDay);
        $osRow = $rows->firstWhere('id', $osId);
        $osName = $osRow->name ?? ($osId > 0 ? '#'.$osId : __('message.no_os'));
        $pageTitle = __('message.kyo_shin_details');
        $assets = [];
        $canMark = false;
        $canFinish = false;
        $canEditDue = $this->canEditDue();

        return view('order.kyo-shin-items', compact(
            'pageTitle',
            'assets',
            'scopeKey',
            'tab',
            'osId',
            'osName',
            'items',
            'fromDay',
            'toDay',
            'fromRaw',
            'toRaw',
            'canMark',
            'canFinish',
            'canEditDue',
        ));
    }

    public function updateOsDueDate(Request $request, int $osId)
    {
        $this->authorizeEdit();

        $scopeKey = $this->resolvedScope($request);
        $dueDay = $this->parsedDueDay($request);
        $count = $this->kyoShin->updateOsDueFinishedAt($scopeKey, $osId, $dueDay);
        if ($count <= 0) {
            return $this->dueDateResponse(__('message.no_record_found'), 422);
        }

        return $this->dueDateResponse(__('message.kyo_shin_due_updated'), 200, $dueDay);
    }

    public function updateItemDueDate(Request $request, int $itemId)
    {
        $this->authorizeEdit();

        $dueDay = $this->parsedDueDay($request);
        $count = $this->kyoShin->updateItemDueFinishedAt($itemId, $dueDay, auth()->user());
        if ($count <= 0) {
            return $this->dueDateResponse(__('message.no_record_found'), 422);
        }

        return $this->dueDateResponse(__('message.kyo_shin_due_updated'), 200, $dueDay);
    }

    public function markAdvancedPaid(Request $request, int $osId)
    {
        $this->authorizeList();

        $scopeKey = $this->resolvedScope($request);
        [$fromDay, $toDay] = array_slice($this->resolvedDates($request), 0, 2);
        $items = $this->kyoShin->itemsForOs($scopeKey, KyoShinService::TAB_OS_LIST, $osId, $fromDay, $toDay);
        $count = $this->kyoShin->markAdvancedPaid($scopeKey, $items, auth()->user());

        return redirect()
            ->route('order.kyo-shin', [
                'scope' => $scopeKey,
                'tab' => KyoShinService::TAB_ADVANCED_PAID,
                'from_date' => $this->kyoShin->formatDay($fromDay),
                'to_date' => $this->kyoShin->formatDay($toDay),
            ])
            ->withSuccess(__('message.kyo_shin_marked', ['count' => $count]));
    }

    public function markChecked(Request $request)
    {
        $this->authorizeList();

        $scopeKey = $this->resolvedScope($request);
        $tab = $this->resolvedTab($request);
        if (! in_array($tab, [
            KyoShinService::TAB_ADVANCED_PAID,
            KyoShinService::TAB_FINISHED,
        ], true)) {
            return redirect()
                ->route('order.kyo-shin', [
                    'scope' => $scopeKey,
                    'tab' => $tab,
                    'from_date' => $request->get('from_date', ''),
                    'to_date' => $request->get('to_date', ''),
                ])
                ->withErrors(__('message.kyo_shin_check_none'));
        }

        $osIds = array_map('intval', (array) $request->input('os_ids', []));
        $count = $this->kyoShin->markChecked($scopeKey, $tab, $osIds, auth()->user());
        $redirect = redirect()->route('order.kyo-shin', [
            'scope' => $scopeKey,
            'tab' => $tab,
            'from_date' => $request->get('from_date', ''),
            'to_date' => $request->get('to_date', ''),
        ]);

        if ($count <= 0) {
            return $redirect->withErrors(__('message.kyo_shin_check_none'));
        }

        return $redirect->withSuccess(__('message.kyo_shin_checked', ['count' => $count]));
    }

    public function sendToOs(Request $request, int $osId)
    {
        $this->authorizeList();

        $scopeKey = $this->resolvedScope($request);
        [$fromDay, $toDay] = array_slice($this->resolvedDates($request), 0, 2);
        $itemIds = array_map('intval', (array) $request->input('item_ids', []));
        $fromItems = (string) $request->input('from', '') === 'items';
        if ($fromItems && $itemIds === []) {
            return redirect()
                ->route('order.kyo-shin.items', [
                    'osId' => $osId,
                    'scope' => $scopeKey,
                    'tab' => KyoShinService::TAB_FINISHED,
                    'from_date' => $request->get('from_date', ''),
                    'to_date' => $request->get('to_date', ''),
                ])
                ->withErrors(__('message.kyo_shin_send_to_os_none'));
        }

        $count = $this->kyoShin->notifyOsReturn($scopeKey, $osId, $fromDay, $toDay, $itemIds);
        $redirect = $fromItems
            ? redirect()->route('order.kyo-shin.items', [
                'osId' => $osId,
                'scope' => $scopeKey,
                'tab' => KyoShinService::TAB_FINISHED,
                'from_date' => $request->get('from_date', ''),
                'to_date' => $request->get('to_date', ''),
            ])
            : redirect()->route('order.kyo-shin', [
                'scope' => $scopeKey,
                'tab' => KyoShinService::TAB_FINISHED,
                'from_date' => $request->get('from_date', ''),
                'to_date' => $request->get('to_date', ''),
            ]);

        if ($count <= 0) {
            return $redirect->withErrors(__('message.kyo_shin_return_notify_none'));
        }

        return $redirect->withSuccess(__('message.kyo_shin_return_notified', ['count' => $count]));
    }

    public function markReceived(Request $request, int $osId)
    {
        $this->authorizeList();

        $scopeKey = $this->resolvedScope($request);
        $tab = $this->resolvedTab($request);
        [$fromDay, $toDay] = array_slice($this->resolvedDates($request), 0, 2);
        $itemIds = array_map('intval', (array) $request->input('item_ids', []));
        $redirect = redirect()->route('order.kyo-shin.items', [
            'osId' => $osId,
            'scope' => $scopeKey,
            'tab' => $tab,
            'from_date' => $request->get('from_date', ''),
            'to_date' => $request->get('to_date', ''),
        ]);

        if ($itemIds === []) {
            return $redirect->withErrors(__('message.kyo_shin_received_none'));
        }

        $count = $this->kyoShin->markItemsReceived(
            $scopeKey,
            $tab,
            $osId,
            $itemIds,
            auth()->user(),
            $fromDay,
            $toDay
        );

        if ($count <= 0) {
            return $redirect->withErrors(__('message.kyo_shin_received_none'));
        }

        return $redirect->withSuccess(__('message.kyo_shin_received_done', ['count' => $count]));
    }

    public function markFinished(Request $request, int $osId)
    {
        $this->authorizeList();

        $scopeKey = $this->resolvedScope($request);
        [$fromDay, $toDay] = array_slice($this->resolvedDates($request), 0, 2);
        $items = $this->kyoShin->itemsForOs($scopeKey, KyoShinService::TAB_ADVANCED_PAID, $osId, $fromDay, $toDay);
        $count = $this->kyoShin->markFinished($scopeKey, $items, auth()->user());

        return redirect()
            ->route('order.kyo-shin', [
                'scope' => $scopeKey,
                'tab' => KyoShinService::TAB_FINISHED,
                'from_date' => $this->kyoShin->formatDay($fromDay),
                'to_date' => $this->kyoShin->formatDay($toDay),
            ])
            ->withSuccess(__('message.kyo_shin_finished', ['count' => $count]));
    }

    protected function authorizeList(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->can('order-list') || $this->kyoShin->allowedScopeKeys($user) === []) {
            abort(403, __('message.demo_permission_denied'));
        }
    }

    protected function authorizeEdit(): void
    {
        $this->authorizeList();
        if (! $this->canEditDue()) {
            abort(403, __('message.demo_permission_denied'));
        }
    }

    protected function canEditDue(): bool
    {
        $user = auth()->user();

        return (bool) ($user && $user->can('order-edit'));
    }

    protected function parsedDueDay(Request $request): string
    {
        $raw = trim((string) $request->input('due_finished_at', ''));
        if ($raw === '') {
            abort(response()->json(['message' => __('message.kyo_shin_due_required')], 422));
        }

        return $this->kyoShin->parseDay($raw);
    }

    protected function dueDateResponse(string $message, int $status, ?string $dueDay = null)
    {
        $payload = ['message' => $message];
        if ($dueDay) {
            $payload['due_finished_at'] = $this->kyoShin->formatDay($dueDay);
        }

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json($payload, $status);
        }

        $redirect = redirect()->back();

        return $status >= 400
            ? $redirect->withErrors($message)
            : $redirect->withSuccess($message);
    }

    protected function resolvedScope(Request $request): string
    {
        $user = auth()->user();
        $requested = trim((string) $request->get('scope', ''));
        if ($requested !== '' && $this->kyoShin->actorCanAccess($user, $requested)) {
            return $requested;
        }

        $default = $this->kyoShin->defaultScopeKey($user);
        if (! $default) {
            abort(403, __('message.demo_permission_denied'));
        }

        return $default;
    }

    protected function resolvedTab(Request $request): string
    {
        $tab = trim((string) $request->get('tab', KyoShinService::TAB_OS_LIST));
        $allowed = [
            KyoShinService::TAB_OS_LIST,
            KyoShinService::TAB_ADVANCED_PAID,
            KyoShinService::TAB_FINISHED,
        ];

        return in_array($tab, $allowed, true) ? $tab : KyoShinService::TAB_OS_LIST;
    }

    /**
     * Empty From/To means All. User picks dates only when they want to filter.
     *
     * @return array{0: ?string, 1: ?string, 2: string, 3: string}
     */
    protected function resolvedDates(Request $request): array
    {
        $fromRaw = trim((string) $request->get('from_date', ''));
        $toRaw = trim((string) $request->get('to_date', ''));
        $fromDay = $fromRaw !== '' ? $this->kyoShin->parseDay($fromRaw) : null;
        $toDay = $toRaw !== '' ? $this->kyoShin->parseDay($toRaw) : null;
        if ($fromDay && $toDay && $toDay < $fromDay) {
            $toDay = $fromDay;
            $toRaw = $this->kyoShin->formatDay($toDay);
        }

        return [
            $fromDay,
            $toDay,
            $fromDay ? $this->kyoShin->formatDay($fromDay) : '',
            $toDay ? $this->kyoShin->formatDay($toDay) : '',
        ];
    }
}
