@php
    $url = '';
    use App\Models\WithdrawRequest;
    use App\Models\DispatchOrderItem;
    use App\Models\Order;
    use App\Models\User;
    use App\Models\Claims;
    use App\Models\Setting;
    use App\Models\CustomerSupport;
    use Carbon\Carbon;
    $MyNavBar = \Menu::make('MenuList', function ($menu) use ($url) {
        // Admin / staff panel — trimmed sidebar
        if (
            Auth::user()->user_type == 'admin' ||
            isDispatchHub(Auth::user()) ||
            (Auth::user()->user_type != 'client' && Auth::user()->user_type != 'delivery_man')
        ) {
            // Dashboard
            $menu
                ->add(adminUiSpan('nav.dashboard', __('message.dashboard')), ['route' => 'home'])
                ->prepend('<i class="fas fa-home"></i>')
                ->link->attr(['class' => '']);

            // New Order
            $menu
                ->add(adminUiSpan('nav.new_order', __('message.dispatch')), ['class' => '', 'route' => 'order.create'])
                ->prepend('<i class="fa fa-plus"></i>')
                ->data('permission', 'order-add')
                ->link->attr(['class' => '']);

            $menu
                ->add(adminUiSpan('nav.section.operations', __('message.nav_operations')), ['class' => 'pds-nav-section'])
                ->data('is_section', true)
                ->link->href('javascript:void(0)')
                ->attr(['class' => 'pds-nav-section__link', 'tabindex' => '-1']);

            // Order (group)
            $menu
                ->add(adminUiSpan('nav.order', __('message.order')), ['class' => ''])
                ->prepend('<i class="fa fa-thin fa-file"></i>')
                ->nickname('order')
                ->data('permission', 'order-list')
                ->link->attr(['class' => ''])
                ->href('#order');

            $menu->order
                ->add(adminUiSpan('nav.order_list', __('message.order_list')), [
                    'class' => 'sidebar-layout',
                    'route' => ['order.index', 'orders_type' => 'list'],
                ])
                ->nickname('nav_order_list')
                ->data('permission', 'order-list')
                ->prepend('<i class="fa fa-list"></i>')
                ->link->attr(['class' => '']);

            $preOrderCount = app(\App\Services\DispatchOrderWorkflowService::class)->preOrderCount();
            $preOrderBadgeClass = 'badge badge-pill badge-info p-1 animate__animated animate__flash' . ($preOrderCount > 0 ? '' : ' d-none');
            $preOrderLabel =
                '<span data-admin-text-key="nav.pre_order" data-admin-edit-text="1">' . e(adminUiText('nav.pre_order', __('message.pre_order_list'))) . '</span> ' .
                '<span class="' . $preOrderBadgeClass . '" id="preOrderCount" data-live-badge="preOrderCount">' .
                ($preOrderCount > 0 ? $preOrderCount : '') .
                '</span>';
            $menu->order
                ->add($preOrderLabel, [
                    'class' => 'sidebar-layout',
                    'route' => [
                        'order.index',
                        'orders_type' => 'list',
                        'dispatch_status' => 'pre_order',
                    ],
                ])
                ->nickname('nav_pre_order')
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-clock"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add(adminUiSpan('nav.follow_up', __('message.follow_up')), [
                    'class' => 'sidebar-layout',
                    'route' => 'order.dispatch.to-assign',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-user-plus"></i>')
                ->link->attr(['class' => '']);

            $hubService = app(\App\Services\DispatchHubService::class);
            $authUser = Auth::user();
            $isHubUser = isDispatchHub($authUser);
            $assign100Count = $hubService->poolCount($isHubUser ? (int) $authUser->id : null);
            $assign100BadgeClass = 'badge badge-pill badge-warning p-1 animate__animated animate__flash' . ($assign100Count > 0 ? '' : ' d-none');
            // Header toggle: label only. Count badge lives on the inner Assign 100 child.
            $assign100Label = adminUiSpan('nav.assign_100', __('message.assign_100'));
            $assign100ChildLabel =
                '<span data-admin-text-key="nav.assign_100" data-admin-edit-text="1">' . e(adminUiText('nav.assign_100', __('message.assign_100'))) . '</span> ' .
                '<span class="' . $assign100BadgeClass . '" id="assign100Count" data-live-badge="assign100Count">' .
                ($assign100Count > 0 ? $assign100Count : '') .
                '</span>';

            $menu
                ->add($assign100Label, ['class' => ''])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-users-cog"></i>')
                ->nickname('assign100')
                ->link->attr(['class' => ''])
                ->href('#assign100');

            $addAssign100Child = function () use ($menu, $assign100ChildLabel) {
                $menu->assign100
                    ->add($assign100ChildLabel, [
                        'class' => 'sidebar-layout',
                        'route' => 'order.dispatch.assign-100',
                    ])
                    ->data('permission', 'order-list')
                    ->prepend('<i class="fas fa-users-cog"></i>')
                    ->link->attr(['class' => '']);
            };

            if ($isHubUser) {
                $hubInboxCount = $hubService->inboxCount((int) $authUser->id);
                $hubInboxBadgeClass = 'badge badge-pill badge-info p-1' . ($hubInboxCount > 0 ? '' : ' d-none');
                $hubInboxLabel =
                    '<span data-admin-text-key="nav.from_mdy_to_ygn" data-admin-edit-text="1">' . e(adminUiText('nav.from_mdy_to_ygn', __('message.from_mdy_to_ygn'))) . '</span> ' .
                    '<span class="' . $hubInboxBadgeClass . '" id="hubInboxCount" data-live-badge="hubInboxCount">' .
                    ($hubInboxCount > 0 ? $hubInboxCount : '') .
                    '</span>';
                $menu->assign100
                    ->add($hubInboxLabel, [
                        'class' => 'sidebar-layout',
                        'route' => 'order.dispatch.from-mdy-to-ygn',
                    ])
                    ->data('permission', 'order-list')
                    ->prepend('<i class="fas fa-truck-loading"></i>')
                    ->link->attr(['class' => '']);
                $addAssign100Child();
            } else {
                $addAssign100Child();
                foreach ($hubService->accounts() as $hubAccount) {
                    $inboundCount = $hubService->inboundCount((int) $hubAccount->id);
                    $hubLabelText = $hubService->inboundMenuLabel($hubAccount);
                    $hubNavKey = 'nav.hub.'.(int) $hubAccount->id;
                    $hubInboundBadgeClass = 'badge badge-pill badge-info p-1' . ($inboundCount > 0 ? '' : ' d-none');
                    $hubLabel =
                        '<span data-admin-text-key="'.e($hubNavKey).'" data-admin-edit-text="1">' . e(adminUiText($hubNavKey, $hubLabelText)) . '</span> ' .
                        '<span class="' . $hubInboundBadgeClass . '" id="hubInboundCount-' . (int) $hubAccount->id . '" data-live-badge="hubInboundCount-' . (int) $hubAccount->id . '">' .
                        ($inboundCount > 0 ? $inboundCount : '') .
                        '</span>';
                    $menu->assign100
                        ->add($hubLabel, [
                            'class' => 'sidebar-layout',
                            'route' => ['order.dispatch.from-hub-to-mdy', $hubAccount->id],
                        ])
                        ->data('permission', 'order-list')
                        ->prepend('<i class="fas fa-truck-loading"></i>')
                        ->link->attr(['class' => '']);
                }
            }

            // Message (assigned-item chat) lives in the topbar as a Messenger-style icon.

            $workflow = app(\App\Services\DispatchOrderWorkflowService::class);
            $workflow->healPickupErrorChoicesToCancelled();

            $pickupErrorCount = Order::where('status', 'pickup_error')
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNull('pickup_error_choice')
                        ->orWhereNotIn('pickup_error_choice', ['cancel', 'express']);
                })
                ->count();
            $pickupErrorBadgeClass = 'badge badge-pill badge-danger p-1 animate__animated animate__flash' . ($pickupErrorCount > 0 ? '' : ' d-none');
            $pickupErrorLabel =
                '<span data-admin-text-key="nav.pickup_error" data-admin-edit-text="1">' . e(adminUiText('nav.pickup_error', __('message.pickup_error_order_list'))) . '</span> ' .
                '<span class="' . $pickupErrorBadgeClass . '" id="pickupErrorCount" data-live-badge="pickupErrorCount">' .
                ($pickupErrorCount > 0 ? $pickupErrorCount : '') .
                '</span>';
            $menu->order
                ->add($pickupErrorLabel, [
                    'class' => 'sidebar-layout',
                    'route' => [
                        'order.index',
                        'orders_type' => 'list',
                        'dispatch_status' => 'rider_pick_up_error',
                    ],
                ])
                ->nickname('nav_pickup_error')
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-exclamation-triangle"></i>')
                ->link->attr(['class' => '']);

            $pickupCancelledQuery = Order::query()->whereNull('deleted_at');
            app(\App\Services\DispatchOrderWorkflowService::class)
                ->applyPickupCancelledOrderFilter($pickupCancelledQuery);
            $pickupCancelledCount = $pickupCancelledQuery->count();
            $pickupCancelledBadgeClass = 'badge badge-pill badge-danger p-1 animate__animated animate__flash' . ($pickupCancelledCount > 0 ? '' : ' d-none');
            $pickupCancelledLabel =
                '<span data-admin-text-key="nav.pickup_cancelled" data-admin-edit-text="1">' . e(adminUiText('nav.pickup_cancelled', __('message.pickup_cancelled_order_list'))) . '</span> ' .
                '<span class="' . $pickupCancelledBadgeClass . '" id="pickupCancelledCount" data-live-badge="pickupCancelledCount">' .
                ($pickupCancelledCount > 0 ? $pickupCancelledCount : '') .
                '</span>';
            $menu->order
                ->add($pickupCancelledLabel, [
                    'class' => 'sidebar-layout',
                    'route' => [
                        'order.index',
                        'orders_type' => 'list',
                        'dispatch_status' => 'rider_pick_up_cancelled',
                    ],
                ])
                ->nickname('nav_pickup_cancelled')
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-ban"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add(adminUiSpan('nav.section.partners', __('message.nav_partners')), ['class' => 'pds-nav-section'])
                ->data('is_section', true)
                ->link->href('javascript:void(0)')
                ->attr(['class' => 'pds-nav-section__link', 'tabindex' => '-1']);

            // Rider List
            $menu
                ->add(adminUiSpan('nav.rider_list', __('message.rider_list')), [
                    'route' => 'order.dispatch.rider-list',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-motorcycle"></i>')
                ->link->attr(['class' => '']);

            // Account Creation — right after Rider List (Managers with subadmin-list)
            if (\Illuminate\Support\Facades\Route::has('sub-admin.index')) {
                $menu
                    ->add(adminUiSpan('nav.account_creation', __('message.account_creation')), [
                        'route' => 'sub-admin.index',
                    ])
                    ->data('permission', 'subadmin-list')
                    ->data('nav_pin', true)
                    ->prepend('<i class="fas fa-user-plus"></i>')
                    ->link->attr(['class' => '']);
            }

            // Online Shop List
            $menu
                ->add(adminUiSpan('nav.online_shop', __('message.list_form_title', ['form' => __('message.online_shop')])), [
                    'route' => 'users.index',
                ])
                ->data('permission', 'users-list')
                ->prepend('<i class="fas fa-store"></i>')
                ->link->attr(['class' => '']);

            // Delivery Man
            $menu
                ->add(adminUiSpan('nav.delivery_man', __('message.delivery_man')), [
                    'route' => 'deliveryman.index',
                ])
                ->data('permission', 'deliveryman-list')
                ->prepend('<i class="fa fa-user-tie"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add(adminUiSpan('nav.section.finance', __('message.nav_finance')), ['class' => 'pds-nav-section'])
                ->data('is_section', true)
                ->link->href('javascript:void(0)')
                ->attr(['class' => 'pds-nav-section__link', 'tabindex' => '-1']);

            // Settlement / Finance (group) — pin in header so it stays outside More
            $menu
                ->add(adminUiSpan('nav.settlement', __('message.settlement')), ['class' => ''])
                ->prepend('<i class="fas fa-hand-holding-usd"></i>')
                ->nickname('settlement')
                ->data('permission', 'order-list')
                ->data('nav_pin', true)
                ->link->attr(['class' => ''])
                ->href('#settlement');

            $menu->settlement
                ->add(adminUiSpan('nav.os_list', __('message.os_list')), [
                    'class' => 'sidebar-layout',
                    'route' => 'order.dispatch.os-list',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-store"></i>')
                ->link->attr(['class' => '']);

            $kyoShinScopes = app(\App\Services\KyoShinService::class)->allowedScopeKeys(Auth::user());
            if ($kyoShinScopes !== []) {
                $menu->settlement
                    ->add(adminUiSpan('nav.kyo_shin', __('message.kyo_shin_title')), [
                        'class' => 'sidebar-layout',
                        'route' => 'order.kyo-shin',
                    ])
                    ->data('permission', 'order-list')
                    ->prepend('<i class="fas fa-coins"></i>')
                    ->link->attr(['class' => '']);
            }

            $menu->settlement
                ->add(adminUiSpan('nav.daily_check', __('message.daily_check_list')), [
                    'class' => 'sidebar-layout',
                    'route' => 'order.daily-checklist',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-clipboard-check"></i>')
                ->link->attr(['class' => '']);

            $menu->settlement
                ->add(adminUiSpan('nav.money_transfer', __('message.money_transfer_list')), [
                    'class' => 'sidebar-layout',
                    'route' => 'order.money-transfer',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-exchange-alt"></i>')
                ->link->attr(['class' => '']);

            $menu->settlement
                ->add(adminUiSpan('nav.rider_remit', __('message.rider_remit_title')), [
                    'class' => 'sidebar-layout',
                    'route' => 'order.rider-remit',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-wallet"></i>')
                ->link->attr(['class' => '']);

            $menu->settlement
                ->add(adminUiSpan('nav.cash_payout', __('message.cash_payout_title')), [
                    'class' => 'sidebar-layout',
                    'route' => ['order.cash-payout', 'status' => 'unassigned'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-money-bill-wave"></i>')
                ->link->attr(['class' => '']);

            $menu->settlement
                ->add(adminUiSpan('nav.os_receive', __('message.os_receive_screen_title')), [
                    'class' => 'sidebar-layout',
                    'route' => 'order.os-receive',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-hand-holding-usd"></i>')
                ->link->attr(['class' => '']);

            $menu->settlement
                ->add(adminUiSpan('nav.expenses', __('message.expenses_title')), [
                    'class' => 'sidebar-layout',
                    'route' => 'order.expenses',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-receipt"></i>')
                ->link->attr(['class' => '']);

            $menu->settlement
                ->add(adminUiSpan('nav.expense_summary', __('message.expense_summary_title')), [
                    'class' => 'sidebar-layout',
                    'route' => 'order.expense-summary',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-chart-pie"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add(adminUiSpan('nav.section.admin', __('message.nav_admin')), ['class' => 'pds-nav-section'])
                ->data('is_section', true)
                ->link->href('javascript:void(0)')
                ->attr(['class' => 'pds-nav-section__link', 'tabindex' => '-1']);

            if (\Illuminate\Support\Facades\Route::has('branch.index')) {
                $menu
                    ->add(adminUiSpan('nav.branch', __('message.branch')), [
                        'route' => 'branch.index',
                    ])
                    ->data('permission', 'branch-list')
                    ->prepend('<i class="fas fa-code-branch"></i>')
                    ->link->attr(['class' => '']);
            }

            if (\Illuminate\Support\Facades\Route::has('branch-admins.index')) {
                $menu
                    ->add(adminUiSpan('nav.branch_admins', __('message.sa_branch_admins')), [
                        'route' => 'branch-admins.index',
                    ])
                    ->data('permission', 'branch-admin-list')
                    ->prepend('<i class="fas fa-user-shield"></i>')
                    ->link->attr(['class' => '']);
            }

            if (\Illuminate\Support\Facades\Route::has('delivery-route-locations.index')) {
                $menu
                    ->add(adminUiSpan('nav.delivery_route', __('message.delivery_route_locations_title')), [
                        'route' => 'delivery-route-locations.index',
                    ])
                    ->data('permission', 'delivery-route-list')
                    ->prepend('<i class="fas fa-map-marker-alt"></i>')
                    ->link->attr(['class' => '']);
            }

            // My Salary — every office / admin account can open their own payroll slip
            $menu
                ->add(adminUiSpan('nav.my_salary', __('message.hr_my_salary_title')), [
                    'route' => 'hr.my-salary.index',
                ])
                ->prepend('<i class="fas fa-money-check-alt"></i>')
                ->link->attr(['class' => '']);

            // HR / Payroll (admin) — Late Time Fine / Extra Fine / ရုံးမှခဏယူငွေ → Super Admin only
            $menu
                ->add(adminUiSpan('nav.hr_payroll', __('message.hr_payroll')), ['class' => ''])
                ->prepend('<i class="fas fa-users-cog"></i>')
                ->nickname('hrpayroll')
                ->data('permission', 'hr-payroll-list')
                ->link->attr(['class' => ''])
                ->href('#hrpayroll');

            $menu->hrpayroll
                ->add(adminUiSpan('nav.office_salary', __('message.hr_office_salary_title')), [
                    'class' => 'sidebar-layout',
                    'route' => 'hr.office-salary.index',
                ])
                ->data('permission', 'hr-payroll-list')
                ->prepend('<i class="fas fa-wallet"></i>')
                ->link->attr(['class' => '']);

            $menu->hrpayroll
                ->add(adminUiSpan('nav.rider_salary', __('message.hr_rider_salary_title')), [
                    'class' => 'sidebar-layout',
                    'route' => 'hr.rider-salary.index',
                ])
                ->data('permission', 'hr-payroll-list')
                ->prepend('<i class="fas fa-motorcycle"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add(adminUiSpan('nav.section.system', __('message.nav_system')), ['class' => 'pds-nav-section'])
                ->data('is_section', true)
                ->link->href('javascript:void(0)')
                ->attr(['class' => 'pds-nav-section__link', 'tabindex' => '-1']);

            // Terms & Privacy
            $menu
                ->add(adminUiSpan('nav.terms', __('message.terms_condition')), [
                    'route' => 'term-condition',
                ])
                ->data('permission', 'terms condition')
                ->prepend('<i class="fas fa-file-contract"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add(adminUiSpan('nav.privacy', __('message.privacy_policy')), [
                    'route' => 'privacy-policy',
                ])
                ->data('permission', 'privacy policy')
                ->prepend('<i class="fas fa-user-shield"></i>')
                ->link->attr(['class' => '']);
        }

        if (Auth::user() && Auth::user()->user_type == 'client') {
            $client = Auth()->user();
            $requestCount =
                Order::where('client_id', auth()->id())
                    ->where('status', 'create')
                    ->count() ?? 0;
            $count =
                '<span class="badge badge-pill badge-primary p-1 mr-3 animate__animated animate__flash" id="requestCount">' .
                $requestCount .
                '</span>';

            $menu
                ->add(adminUiSpan('nav.new_order', __('message.dispatch')), ['class' => '', 'route' => 'order.create'])
                ->prepend('<i class="fa fa-plus"></i>')
                ->data('permission', 'order-add')
                ->link->attr(['class' => '']);
            if ($requestCount == 0) {
                $menu
                    ->add(adminUiSpan('nav.order', __('message.order')), ['class' => ''])
                    ->prepend('<i class="fa fa-thin fa-file"></i>')
                    ->nickname('order')
                    ->data('permission', 'order-list')
                    ->link->attr(['class' => ''])
                    ->href('#order');
            } else {
                $menu
                    ->add('<span>' . __('message.order') . ' ' . $count . '</span>', ['class' => ''])
                    ->prepend('<i class="fa fa-thin fa-file"></i>')
                    ->nickname('order')
                    ->data('permission', 'order-list')
                    ->link->attr(['class' => ''])
                    ->href('#order');
            }
            $menu->order
                ->add('<span>' . __('message.all_order') . '</span>', [
                    'class' => 'sidebar-layout', 'route' => 'order.index',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add('<span>' . __('message.schedule_order') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['client-order', 'orders_type' => 'schedule'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa fa-clock-rotate-left"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add('<span>' . __('message.reschedule_order') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['client-order', 'orders_type' => 'reschedule'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa fa-clock-rotate-left"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add('<span>' . __('message.shipped_order') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['shipped-order', 'orders_type' => 'shipped_order'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa fa-clock-rotate-left"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add('<span>' . __('message.today_order') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['client-order', 'orders_type' => 'today'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa fa-clock-rotate-left"></i>')
                ->link->attr(['class' => '']);

            if ($requestCount == 0) {
                $menu->order
                    ->add('<span>' . __('message.pending_order') . '</span>', [
                        'class' => 'sidebar-layout',
                        'route' => ['client-order', 'orders_type' => 'pending'],
                    ])
                    ->data('permission', 'order-list')
                    ->prepend('<i class="fa fa-clock-rotate-left"></i>')
                    ->link->attr(['class' => '']);
            } else {
                $menu->order
                    ->add('<span>' . __('message.pending_order') . ' ' . $count . '</span>', [
                        'class' => 'sidebar-layout',
                        'route' => ['client-order', 'orders_type' => 'pending'],
                    ])
                    ->data('permission', 'order-list')
                    ->prepend('<i class="fa fa-clock-rotate-left"></i>')
                    ->link->attr(['class' => '']);
            }

            $menu->order
                ->add('<span>' . __('message.inprogress_order') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['client-order', 'orders_type' => 'inprogress'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa fa-bars-progress"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add('<span>' . __('message.complete_order') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['client-order', 'orders_type' => 'complete'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa fa-calendar-check"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add('<span>' . __('message.cancel_order') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['client-order', 'orders_type' => 'cancel'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa fa-ban"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.draft') . '</span>', ['route' => 'draft-order'])
                ->prepend('<i class="fa fa-hourglass-half"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.wallet') . '</span>', ['route' => 'clientwallet'])
                ->prepend('<i class="fa fa-wallet"></i>')
                ->link->attr(['class' => '']);

            $requestCount = WithdrawRequest::where('user_id', $client->id)->where('status', 'requested')->count() ?? 0;
            $count =
                '<span class="badge badge-pill badge-primary p-1 mr-3 animate__animated animate__flash" id="requestCount">' .
                $requestCount .
                '</span>';

            if ($requestCount == 0) {
                $menu
                    ->add('<span>' . __('message.withdrawrequest') . '</span>', ['class' => ''])
                    ->prepend('<i class="fas fa-comment-dollar"></i>')
                    ->nickname('withdrawrequest')
                    ->data('permission', 'withdrawrequest-list')
                    ->link->attr(['class' => ''])
                    ->href('#withdrawrequest');
            } else {
                $menu
                    ->add('<span>' . __('message.withdrawrequest') . ' ' . $count . '</span>', ['class' => ''])
                    ->prepend('<i class="fas fa-comment-dollar"></i>')
                    ->nickname('withdrawrequest')
                    ->data('permission', 'withdrawrequest-list')
                    ->link->attr(['class' => ''])
                    ->href('#withdrawrequest');
            }

            $menu->withdrawrequest
                ->add('<span>' . __('message.all') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['withdrawrequest.index', 'withdraw_type' => 'all'],
                ])
                ->data('permission', 'withdrawrequest-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            if ($requestCount == 0) {
                $menu->withdrawrequest
                    ->add('<span>' . __('message.list_form_title', ['form' => __('message.pending')]) . '</span>', [
                        'class' => 'sidebar-layout',
                        'route' => ['withdrawrequest.index', 'withdraw_type' => 'pending'],
                    ])
                    ->data('permission', 'withdrawrequest-list')
                    ->prepend('<i class="fa fa-clock-rotate-left"></i>')
                    ->link->attr(['class' => '']);
            } else {
                $menu->withdrawrequest
                    ->add(
                        '<span>' .
                            __('message.list_form_title', ['form' => __('message.pending')]) .
                            ' ' .
                            $count .
                            '</span>',
                        [
                            'class' => 'sidebar-layout',
                            'route' => ['withdrawrequest.index', 'withdraw_type' => 'pending'],
                        ],
                    )
                    ->data('permission', 'withdrawrequest-list')
                    ->prepend('<i class="fa fa-clock-rotate-left"></i>')
                    ->link->attr(['class' => '']);
            }

            $menu->withdrawrequest
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.approved')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['withdrawrequest.index', 'withdraw_type' => 'approved'],
                ])
                ->data('permission', 'withdrawrequest-list')
                ->prepend('<i class="fa fa-calendar-check"></i>')
                ->link->attr(['class' => '']);

            $menu->withdrawrequest
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.decline')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['withdrawrequest.index', 'withdraw_type' => 'decline'],
                ])
                ->data('permission', 'withdrawrequest-list')
                ->prepend('<i class="fa fa-ban"></i>')
                ->link->attr(['class' => '']);

            $menu->withdrawrequest
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.completed')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['withdrawrequest.index', 'withdraw_type' => 'completed'],
                ])
                ->data('permission', 'withdrawrequest-list')
                ->prepend('<i class="fa-solid fa-circle-check"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.bank_details') . '</span>', ['route' => 'bankdeatils'])
                ->prepend('<i class="fa fa-landmark"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.my_address') . '</span>', ['route' => 'useraddress.index'])
                ->prepend('<i class="fa fa-address-book"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.change_password') . '</span>', ['route' => 'passwordpage'])
                ->prepend('<i class="fa fa-lock"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.app_setting') . '</span>', ['route' => 'appsetting'])
                ->prepend('<i class="fa fa-user-minus"></i>')
                ->link->attr(['class' => '']);
        }
    })->filter(function ($item) {
        return checkMenuRoleAndPermission($item);
    });

    if (optional(request()->route())->getName() === 'order.index') {
        $want = match ((string) request('dispatch_status', '')) {
            'pre_order' => 'nav_pre_order',
            'rider_pick_up_error' => 'nav_pickup_error',
            'rider_pick_up_cancelled' => 'nav_pickup_cancelled',
            default => 'nav_order_list',
        };
        foreach (['nav_order_list', 'nav_pre_order', 'nav_pickup_error', 'nav_pickup_cancelled'] as $nick) {
            try {
                $navItem = $MyNavBar->{$nick} ?? null;
            } catch (\Throwable $e) {
                $navItem = null;
            }
            if (! $navItem) {
                continue;
            }
            $navItem->isActive = $nick === $want;
            if ($nick === $want && method_exists($navItem, 'activate')) {
                $navItem->activate();
            }
        }
    }

    view()->share('MyNavBar', $MyNavBar);
@endphp

{{-- Left sidebar shell kept for menu build / fallback; hidden when body.pds-header-nav. --}}
<div class="mm-sidebar sidebar-default pds-sidebar" aria-hidden="true">
    <div class="mm-sidebar-logo pds-sidebar-brand">
        <a href="{{ route('home') }}" class="header-logo pds-brand-link" title="POINT Delivery Service">
            <img src="{{ getSingleMedia(appSettingData('get'), 'site_logo', null) }}"
                class="img-fluid mode light-img light-logo site_logo_preview pds-brand-logo" alt="">
            <img src="{{ getSingleMedia(appSettingData('get'), 'site_dark_logo', null) }}"
                class="img-fluid mode dark-img darkmode-logo site_dark_logo_preview pds-brand-logo" alt="">
            <span class="pds-brand-copy">
                <span class="pds-brand-copy__name">POINT</span>
                <span class="pds-brand-copy__tag">Delivery Service</span>
            </span>
        </a>
        <button type="button" class="pds-sidebar-toggle wrapper-menu" aria-label="Toggle menu">
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>
    </div>

    <div class="data-scrollbar pds-sidebar-scroll" data-scroll="1">
        <nav class="mm-sidebar-menu">
            <ul id="mm-sidebar-toggle" class="side-menu">
                @include(config('laravel-menu.views.bootstrap-items'), ['items' => $MyNavBar->roots()])
            </ul>
        </nav>
        <div class="pt-5 pb-5"></div>
    </div>
</div>
