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
            (Auth::user()->user_type != 'client' && Auth::user()->user_type != 'delivery_man')
        ) {
            // Dashboard
            $menu
                ->add('<span>' . __('message.dashboard') . '</span>', ['route' => 'home'])
                ->prepend('<i class="fas fa-home"></i>')
                ->link->attr(['class' => '']);

            // New Order
            $menu
                ->add('<span>' . __('message.dispatch') . '</span>', ['class' => '', 'route' => 'order.create'])
                ->prepend('<i class="fa fa-plus"></i>')
                ->data('permission', 'order-add')
                ->link->attr(['class' => '']);

            // Order (group)
            $menu
                ->add('<span>' . __('message.order') . '</span>', ['class' => ''])
                ->prepend('<i class="fa fa-thin fa-file"></i>')
                ->nickname('order')
                ->data('permission', 'order-list')
                ->link->attr(['class' => ''])
                ->href('#order');

            $menu->order
                ->add('<span>' . __('message.order_list') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['order.index', 'orders_type' => 'list'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa fa-list"></i>')
                ->link->attr(['class' => '']);

            $preOrderCount = app(\App\Services\DispatchOrderWorkflowService::class)->preOrderCount();
            $preOrderLabel = '<span>' . __('message.pre_order_list') . '</span>';
            if ($preOrderCount > 0) {
                $preOrderLabel =
                    '<span>' . __('message.pre_order_list') . ' ' .
                    '<span class="badge badge-pill badge-info p-1 animate__animated animate__flash">' .
                    $preOrderCount .
                    '</span></span>';
            }
            $menu->order
                ->add($preOrderLabel, [
                    'class' => 'sidebar-layout',
                    'route' => [
                        'order.index',
                        'orders_type' => 'list',
                        'dispatch_status' => 'pre_order',
                    ],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-clock"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add('<span>' . __('message.follow_up') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'order.dispatch.to-assign',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-user-plus"></i>')
                ->link->attr(['class' => '']);

            $assign100Count = DispatchOrderItem::where('status', 'assigned')
                ->whereHas('order', function ($q) {
                    $q->whereIn('status', ['courier_picked_up', 'courier_departed', 'completed']);
                })
                ->count();

            if ($assign100Count == 0) {
                $menu->order
                    ->add('<span>' . __('message.assign_100') . '</span>', [
                        'class' => 'sidebar-layout',
                        'route' => 'order.dispatch.assign-100',
                    ])
                    ->data('permission', 'order-list')
                    ->prepend('<i class="fas fa-users-cog"></i>')
                    ->link->attr(['class' => '']);
            } else {
                $assign100Badge =
                    '<span class="badge badge-pill badge-warning p-1 animate__animated animate__flash" id="assign100Count">' .
                    $assign100Count .
                    '</span>';
                $menu->order
                    ->add('<span>' . __('message.assign_100') . ' ' . $assign100Badge . '</span>', [
                        'class' => 'sidebar-layout',
                        'route' => 'order.dispatch.assign-100',
                    ])
                    ->data('permission', 'order-list')
                    ->prepend('<i class="fas fa-users-cog"></i>')
                    ->link->attr(['class' => '']);
            }

            $assignedItemCount = DispatchOrderItem::query()
                ->whereHas('messages', function ($q) {
                    $q->where('sender_type', 'client')->whereNull('read_at');
                })
                ->count();
            if ($assignedItemCount == 0) {
                $menu->order
                    ->add('<span>' . __('message.assigned_item_list') . '</span>', [
                        'class' => 'sidebar-layout',
                        'route' => 'order.dispatch.assigned-items',
                    ])
                    ->data('permission', 'order-list')
                    ->prepend('<i class="fas fa-clipboard-check"></i>')
                    ->link->attr(['class' => '']);
            } else {
                $assignedBadge =
                    '<span class="badge badge-pill badge-info p-1" id="assignedItemListCount">' .
                    $assignedItemCount .
                    '</span>';
                $menu->order
                    ->add('<span>' . __('message.assigned_item_list') . ' ' . $assignedBadge . '</span>', [
                        'class' => 'sidebar-layout',
                        'route' => 'order.dispatch.assigned-items',
                    ])
                    ->data('permission', 'order-list')
                    ->prepend('<i class="fas fa-clipboard-check"></i>')
                    ->link->attr(['class' => '']);
            }

            $menu->order
                ->add('<span>' . __('message.os_list') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'order.dispatch.os-list',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-store"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add('<span>' . __('message.daily_check_list') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'order.daily-checklist',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-clipboard-check"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add('<span>' . __('message.money_transfer_list') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'order.money-transfer',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-exchange-alt"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add('<span>' . __('message.rider_remit_title') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'order.rider-remit',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-wallet"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add('<span>' . __('message.cash_payout_title') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['order.cash-payout', 'status' => 'unassigned'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-money-bill-wave"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add('<span>' . __('message.os_receive_screen_title') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'order.os-receive',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-hand-holding-usd"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add('<span>' . __('message.expenses_title') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'order.expenses',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-receipt"></i>')
                ->link->attr(['class' => '']);

            $menu->order
                ->add('<span>' . __('message.expense_summary_title') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'order.expense-summary',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-chart-pie"></i>')
                ->link->attr(['class' => '']);

            $workflow = app(\App\Services\DispatchOrderWorkflowService::class);
            $workflow->healPickupErrorChoicesToCancelled();

            $pickupErrorCount = Order::where('status', 'pickup_error')
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNull('pickup_error_choice')
                        ->orWhereNotIn('pickup_error_choice', ['cancel', 'express']);
                })
                ->count();
            $pickupErrorLabel = '<span>' . __('message.pickup_error_order_list') . '</span>';
            if ($pickupErrorCount > 0) {
                $pickupErrorLabel =
                    '<span>' . __('message.pickup_error_order_list') . ' ' .
                    '<span class="badge badge-pill badge-danger p-1 animate__animated animate__flash">' .
                    $pickupErrorCount .
                    '</span></span>';
            }
            $menu->order
                ->add($pickupErrorLabel, [
                    'class' => 'sidebar-layout',
                    'route' => [
                        'order.index',
                        'orders_type' => 'list',
                        'dispatch_status' => 'rider_pick_up_error',
                    ],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-exclamation-triangle"></i>')
                ->link->attr(['class' => '']);

            $pickupCancelledCount = Order::where('status', 'cancelled')
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNotNull('pickup_error_at')
                        ->orWhereIn('pickup_error_choice', ['cancel', 'express'])
                        ->orWhere('reason', 'like', '%pickup error%')
                        ->orWhere('reason', 'like', '%User cancelled after pickup error%')
                        ->orWhere('reason', 'like', '%User chose express after pickup error%');
                })
                ->count();
            $pickupCancelledLabel = '<span>' . __('message.pickup_cancelled_order_list') . '</span>';
            if ($pickupCancelledCount > 0) {
                $pickupCancelledLabel =
                    '<span>' . __('message.pickup_cancelled_order_list') . ' ' .
                    '<span class="badge badge-pill badge-danger p-1 animate__animated animate__flash">' .
                    $pickupCancelledCount .
                    '</span></span>';
            }
            $menu->order
                ->add($pickupCancelledLabel, [
                    'class' => 'sidebar-layout',
                    'route' => [
                        'order.index',
                        'orders_type' => 'list',
                        'dispatch_status' => 'rider_pick_up_cancelled',
                    ],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-ban"></i>')
                ->link->attr(['class' => '']);

            // Rider List
            $menu
                ->add('<span>' . __('message.rider_list') . '</span>', [
                    'route' => 'order.dispatch.rider-list',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-motorcycle"></i>')
                ->link->attr(['class' => '']);

            // Online Shop List
            $menu
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.online_shop')]) . '</span>', [
                    'class' => request()->is('users') || request()->is('users?*') ? 'active' : '',
                    'route' => 'users.index',
                ])
                ->data('permission', 'users-list')
                ->prepend('<i class="fas fa-store"></i>')
                ->link->attr(['class' => '']);

            // Delivery Man
            $menu
                ->add('<span>' . __('message.delivery_man') . '</span>', [
                    'route' => 'deliveryman.index',
                ])
                ->data('permission', 'deliveryman-list')
                ->prepend('<i class="fa fa-user-tie"></i>')
                ->link->attr(['class' => '']);

            // My Salary — visible to office accounts (own salary)
            $menu
                ->add('<span>' . __('message.hr_my_salary_title') . '</span>', [
                    'route' => 'hr.my-salary.index',
                ])
                ->prepend('<i class="fas fa-wallet"></i>')
                ->link->attr(['class' => '']);

            // HR / Payroll (admin)
            $menu
                ->add('<span>' . __('message.hr_payroll') . '</span>', ['class' => ''])
                ->prepend('<i class="fas fa-users-cog"></i>')
                ->nickname('hrpayroll')
                ->data('permission', 'hr-payroll-list')
                ->link->attr(['class' => ''])
                ->href('#hrpayroll');

            $menu->hrpayroll
                ->add('<span>' . __('message.hr_late_fine_title') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'hr.late-fine.index',
                ])
                ->data('permission', 'hr-payroll-list')
                ->prepend('<i class="fas fa-user-clock"></i>')
                ->link->attr(['class' => '']);

            $menu->hrpayroll
                ->add('<span>' . __('message.hr_office_salary_title') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'hr.office-salary.index',
                ])
                ->data('permission', 'hr-payroll-list')
                ->prepend('<i class="fas fa-wallet"></i>')
                ->link->attr(['class' => '']);

            $menu->hrpayroll
                ->add('<span>' . __('message.hr_rider_salary_title') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'hr.rider-salary.index',
                ])
                ->data('permission', 'hr-payroll-list')
                ->prepend('<i class="fas fa-motorcycle"></i>')
                ->link->attr(['class' => '']);

            // Account Creation
            $menu
                ->add('<span>' . __('message.account_creation') . '</span>', [
                    'route' => 'sub-admin.index',
                ])
                ->data('permission', 'subadmin-list')
                ->prepend('<i class="fas fa-user-plus"></i>')
                ->link->attr(['class' => '']);

            // Roles & Permission (combined)
            $menu
                ->add('<span>' . __('message.roles_and_permission') . '</span>', [
                    'route' => 'permission.index',
                ])
                ->data('permission', 'permission-list')
                ->prepend('<i class="fas fa-user-shield"></i>')
                ->link->attr(['class' => '']);

            // Terms & Privacy
            $menu
                ->add('<span>' . __('message.terms_condition') . '</span>', [
                    'route' => 'term-condition',
                ])
                ->data('permission', 'terms condition')
                ->prepend('<i class="fas fa-file-contract"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.privacy_policy') . '</span>', [
                    'route' => 'privacy-policy',
                ])
                ->data('permission', 'privacy policy')
                ->prepend('<i class="fas fa-user-shield"></i>')
                ->link->attr(['class' => '']);

            // API Server IP
            $menu
                ->add('<span>' . __('message.api_server_settings') . '</span>', [
                    'route' => ['setting.index', 'page' => 'api-server-setting'],
                ])
                ->prepend('<i class="fas fa-network-wired"></i>')
                ->nickname('api_server_setting')
                ->data('permission', 'system setting')
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
                ->add('<span>' . __('message.dispatch') . '</span>', ['class' => '', 'route' => 'order.create'])
                ->prepend('<i class="fa fa-plus"></i>')
                ->data('permission', 'order-add')
                ->link->attr(['class' => '']);
            if ($requestCount == 0) {
                $menu
                    ->add('<span>' . __('message.order') . '</span>', ['class' => ''])
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
    view()->share('MyNavBar', $MyNavBar);
@endphp

<div class="mm-sidebar sidebar-default pds-sidebar">
    <div class="mm-sidebar-logo d-flex align-items-center justify-content-between pds-sidebar-brand">
        <a href="{{ route('home') }}" class="header-logo pds-brand-link">
            <span class="pds-brand-mark">
                <img src="{{ getSingleMedia(appSettingData('get'), 'site_logo', null) }}"
                    class="img-fluid mode light-img rounded-normal light-logo site_logo_preview pds-brand-logo" alt="logo">
                <img src="{{ getSingleMedia(appSettingData('get'), 'site_dark_logo', null) }}"
                    class="img-fluid mode dark-img rounded-normal darkmode-logo site_dark_logo_preview pds-brand-logo" alt="dark-logo">
            </span>
        </a>
        <div class="side-menu-bt-sidebar pds-sidebar-toggle">
            <i class="fas fa-bars wrapper-menu"></i>
        </div>
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
