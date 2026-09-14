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
        $rows = $this->kyoShin->osRows($scopeKey, $tab, $fromDay, $toDay);
        $counts = [
            KyoShinService::TAB_OS_LIST => $this->kyoShin->osRows($scopeKey, KyoShinService::TAB_OS_LIST, $fromDay, $toDay)->count(),
            KyoShinService::TAB_ADVANCED_PAID => $this->kyoShin->osRows($scopeKey, KyoShinService::TAB_ADVANCED_PAID, $fromDay, $toDay)->count(),
            KyoShinService::TAB_FINISHED => $this->kyoShin->osRows($scopeKey, KyoShinService::TAB_FINISHED, $fromDay, $toDay)->count(),
        ];

        $pageTitle = __('message.kyo_shin_title');
        $assets = [];

        return view('order.kyo-shin', compact(
            'pageTitle',
            'assets',
            'scopeKey',
            'scopes',
            'tab',
            'summary',
            'rows',
            'counts',
            'fromDay',
            'toDay',
            'fromRaw',
            'toRaw',
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
                $item->kyo_shin_amount = $this->kyoShin->itemAmount($item);
            });
        $rows = $this->kyoShin->osRows($scopeKey, $tab, $fromDay, $toDay);
        $osRow = $rows->firstWhere('id', $osId);
        $osName = $osRow->name ?? ($osId > 0 ? '#'.$osId : __('message.no_os'));
        $pageTitle = __('message.kyo_shin_details');
        $assets = [];
        $canMark = $tab === KyoShinService::TAB_OS_LIST && $items->isNotEmpty();
        $canFinish = $tab === KyoShinService::TAB_ADVANCED_PAID && $items->isNotEmpty();

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
        ));
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
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    protected function resolvedDates(Request $request): array
    {
        $fallback = yangonSettlementDefaultDate('Y-m-d');
        $fromRaw = trim((string) $request->get('from_date', yangonSettlementDefaultDate()));
        $toRaw = trim((string) $request->get('to_date', $fromRaw !== '' ? $fromRaw : yangonSettlementDefaultDate()));
        $fromDay = $this->kyoShin->parseDay($fromRaw, $fallback);
        $toDay = $this->kyoShin->parseDay($toRaw, $fromDay);
        if ($toDay < $fromDay) {
            $toDay = $fromDay;
        }

        return [
            $fromDay,
            $toDay,
            $this->kyoShin->formatDay($fromDay),
            $this->kyoShin->formatDay($toDay),
        ];
    }
}
