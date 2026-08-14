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
        //Admin Dashboard

        if (
            Auth::user()->user_type == 'admin' ||
            (Auth::user()->user_type != 'client' && Auth::user()->user_type != 'delivery_man')
        ) {
          //  $menu
            //     ->add('<span>' . __('message.view_site') . '</span>', ['route' => 'frontend-section'])
            //     ->prepend('<i class="fa fa-arrow-left"></i>')
            //     ->link->attr(['class' => '']);

            // --- Overview ---
            $menu
                ->add('<span>' . __('message.dashboard') . '</span>', ['route' => 'home'])
                ->prepend('<i class="fas fa-home"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.dispatch') . '</span>', ['class' => '', 'route' => 'order.create'])
                ->prepend('<i class="fa fa-plus"></i>')
                ->data('permission', 'order-add')
                ->link->attr(['class' => '']);

            // --- Orders ---
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
                    '<span class="badge badge-pill badge-secondary p-1">' .
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

            $menu
                ->add('<span>' . __('message.print_label') . '</span>', ['class' => ''])
                ->prepend('<i class="fa-solid fa-print"></i>')
                ->nickname('orderprint')
                ->link->attr(['class' => ''])
                ->href('#orderprint');

            $menu->orderprint
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.print_label')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'orderprint-datatable',
                ])
                ->data('permission', 'country-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.high_demanding_areas') . '</span>', ['route' => 'high_demanding_areas'])
                ->prepend('<i class="fa fa-thumbtack"></i>')
                ->link->attr(['class' => '']);

            $requestCount = Claims::where('status', 'pending')->count() ?? 0;
            $claimsCountBadge =
                '<span class="badge badge-pill badge-primary p-1 mr-3 animate__animated animate__flash" id="requestCount">' .
                $requestCount .
                '</span>';

            if ($requestCount == 0) {
                $menu
                    ->add('<span>' . __('message.claims_management') . '</span>', ['class' => ''])
                    ->prepend('<i class="fa-solid fa-clipboard-question"></i>')
                    ->nickname('claims_management')
                    ->data('permission', 'claims-list')
                    ->link->attr(['class' => ''])
                    ->href('#claims');
            } else {
                $menu
                    ->add('<span>' . __('message.claims_management') . ' ' . $claimsCountBadge . '</span>', ['class' => ''])
                    ->prepend('<i class="fa-solid fa-clipboard-question"></i>')
                    ->nickname('claims_management')
                    ->data('permission', 'claims-list')
                    ->link->attr(['class' => ''])
                    ->href('#claims');
            }

            $menu->claims_management
                ->add('<span>' . __('message.all') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['claims.index', 'claims_type' => 'all'],
                ])
                ->data('permission', 'claims-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            if ($requestCount == 0) {
                $menu->claims_management
                    ->add('<span>' . __('message.list_form_title', ['form' => __('message.pending')]) . '</span>', [
                        'class' => 'sidebar-layout',
                        'route' => ['claims.index', 'claims_type' => 'pending'],
                    ])
                    ->data('permission', 'claims-list')
                    ->prepend('<i class="fa fa-clock-rotate-left"></i>')
                    ->link->attr(['class' => '']);
            } else {
                $menu->claims_management
                    ->add(
                        '<span>' .
                            __('message.list_form_title', ['form' => __('message.pending')]) .
                            ' ' .
                            $claimsCountBadge .
                            '</span>',
                        ['class' => 'sidebar-layout', 'route' => ['claims.index', 'claims_type' => 'pending']],
                    )
                    ->data('permission', 'claims-list')
                    ->prepend('<i class="fa fa-clock-rotate-left"></i>')
                    ->link->attr(['class' => '']);
            }

            $menu->claims_management
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.approved')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['claims.index', 'claims_type' => 'approved'],
                ])
                ->data('permission', 'claims-list')
                ->prepend('<i class="fa fa-calendar-check"></i>')
                ->link->attr(['class' => '']);

            $menu->claims_management
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.reject')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['claims.index', 'claims_type' => 'reject'],
                ])
                ->data('permission', 'claims-list')
                ->prepend('<i class="fa-solid fa-xmark"></i>')
                ->link->attr(['class' => '']);

            $menu->claims_management
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.close')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['claims.index', 'claims_type' => 'close'],
                ])
                ->data('permission', 'claims-list')
                ->prepend('<i class="fa fa-ban"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.emergency') . '</span>', ['class' => '', 'route' => 'emergency.index'])
                ->prepend('<i class="fa fa-cart-plus"></i>')
                ->data('permission', 'emergency-list')
                ->link->attr(['class' => '']);

            // $menu->add('<span>'.__('message.shipped_order').'</span>', ['route' => ['shipped-order','orders_type'=>'shipped_order']])
            //     ->prepend('<i class="fa-solid fa-clipboard-list"></i>')
            //     ->link->attr(['class' => '']);

            // --- Rider List / Online Shopping / Branch (grouped) ---
            $clientPendingCount = User::where('user_type', 'client')
                ->where('status', 1)
                ->where(function ($query) {
                    $query->whereNull('email_verified_at')->orWhereNull('otp_verify_at');
                })
                ->where(function ($query) {
                    $query->whereNotNull('email_verified_at')->orWhereNotNull('otp_verify_at');
                })
                ->count();

            $menu
                ->add('<span>' . __('message.partner_directory') . '</span>', ['class' => ''])
                ->prepend('<i class="fas fa-sitemap"></i>')
                ->nickname('partner_directory')
                ->link->attr(['class' => ''])
                ->href('#partner_directory');

            $menu->partner_directory
                ->add('<span>' . __('message.rider_list') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'order.dispatch.rider-list',
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fas fa-motorcycle"></i>')
                ->link->attr(['class' => '']);

            $menu->partner_directory
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.online_shop')]) . '</span>', [
                    'class' => request()->is('users') || request()->is('users?*') ? 'sidebar-layout active' : 'sidebar-layout',
                    'route' => 'users.index',
                ])
                ->data('permission', 'users-list')
                ->prepend('<i class="fas fa-store"></i>')
                ->link->attr(['class' => '']);

            $menu->partner_directory
                ->add('<span>' . __('message.active_list_form_title', ['form' => __('message.online_shop')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['user.status', 'active'],
                ])
                ->data('permission', 'users-list')
                ->prepend('<i class="fa fa-user-check"></i>')
                ->link->attr(['class' => '']);

            $menu->partner_directory
                ->add('<span>' . __('message.inactive_list_form_title', ['form' => __('message.online_shop')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['user.status', 'inactive'],
                ])
                ->data('permission', 'users-list')
                ->prepend('<i class="fa fa-user-clock"></i>')
                ->link->attr(['class' => '']);

            if ($clientPendingCount == 0) {
                $menu->partner_directory
                    ->add(
                        '<span>' . __('message.pending_list_form_title', ['form' => __('message.online_shop')]) . '</span>',
                        ['class' => 'sidebar-layout', 'route' => ['user.status', 'status' => 'pending']],
                    )
                    ->data('permission', 'users-list')
                    ->prepend('<i class="fa fa-user-pen"></i>')
                    ->link->attr(['class' => '']);
            } else {
                $count =
                    '<span class="badge badge-pill badge-primary p-1 animate__animated animate__flash" id="requestCount">' .
                    $clientPendingCount .
                    '</span>';
                $menu->partner_directory
                    ->add(
                        '<span>' .
                            __('message.pending_list_form_title', ['form' => __('message.online_shop')]) .
                            ' ' .
                            $count .
                            '</span>',
                        ['class' => 'sidebar-layout', 'route' => ['user.status', 'status' => 'pending']],
                    )
                    ->data('permission', 'users-list')
                    ->prepend('<i class="fa fa-user-pen"></i>')
                    ->link->attr(['class' => '']);
            }

            $menu->partner_directory
                ->add('<span>' . __('message.add_form_title', ['form' => __('message.online_shop')]) . '</span>', [
                    'class' => request()->is('users/create') || request()->is('users/*/edit') ? 'sidebar-layout active' : 'sidebar-layout',
                    'route' => 'users.create',
                ])
                ->data('permission', 'users-add')
                ->prepend('<i class="fa fa-plus-square"></i>')
                ->link->attr(['class' => '']);

            $menu->partner_directory
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.branch')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'branch.index',
                ])
                ->data('permission', 'branch-list')
                ->prepend('<i class="fas fa-code-branch"></i>')
                ->link->attr(['class' => '']);

            $menu->partner_directory
                ->add('<span>' . __('message.add_form_title', ['form' => __('message.branch')]) . '</span>', [
                    'class' => request()->is('branch/create') || request()->is('branch/*/edit') ? 'sidebar-layout active' : 'sidebar-layout',
                    'route' => 'branch.create',
                ])
                ->data('permission', ['branch-add', 'branch-edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);


            // --- People ---
            $deliveryManPendingCount = User::where('user_type', 'delivery_man')
                ->where('status', 1)
                ->where(function ($query) {
                    $query
                        ->whereNull('email_verified_at')
                        ->orWhereNull('otp_verify_at')
                        ->orWhereNull('document_verified_at');
                })
                ->where(function ($query) {
                    $query
                        ->whereNotNull('email_verified_at')
                        ->orWhereNotNull('otp_verify_at')
                        ->orWhereNotNull('document_verified_at');
                })
                ->count();
            if ($deliveryManPendingCount == 0) {
                $menu
                    ->add('<span>' . __('message.delivery_man') . '</span>', ['class' => ''])
                    ->prepend('<i class="fa fa-user-tie"></i>')
                    ->nickname('delivery_man')
                    ->data('permission', 'deliveryman-list')
                    ->link->attr(['class' => ''])
                    ->href('#delivery_man');
            } else {
                $count =
                    '<span class="badge badge-pill badge-primary p-1 mr-3 animate__animated animate__flash" id="requestCount">' .
                    $deliveryManPendingCount .
                    '</span>';
                $menu
                    ->add('<span>' . __('message.delivery_man') . ' ' . $count . '</span>', ['class' => ''])
                    ->prepend('<i class="fa fa-user-tie"></i>')
                    ->nickname('delivery_man')
                    ->data('permission', 'deliveryman-list')
                    ->link->attr(['class' => ''])
                    ->href('#delivery_man');
            }

            $menu->delivery_man
                ->add('<span>' . __('message.delivery_man') . '</span>', [
                    'class' => request()->is('delivery_man/*/add') ? 'sidebar-layout active' : 'sidebar-layout',
                    'route' => 'deliveryman.index',
                ])
                ->data('permission', ['deliveryman-add', 'deliveryman-edit'])
                ->prepend('<i class="fa fa-user-tie"></i>')
                ->link->attr(['class' => '']);

            $menu->delivery_man
                ->add(
                    '<span>' . __('message.active_list_form_title', ['form' => __('message.delivery_man')]) . '</span>',
                    ['class' => 'sidebar-layout', 'route' => ['deliveryman.pending', 'active']],
                )
                ->data('permission', 'deliveryman-list')
                ->prepend('<i class="fa fa-user-check"></i>')
                ->link->attr(['class' => '']);

            $menu->delivery_man
                ->add(
                    '<span>' .
                        __('message.inactive_list_form_title', ['form' => __('message.delivery_man')]) .
                        '</span>',
                    ['class' => 'sidebar-layout', 'route' => ['deliveryman.pending', 'inactive']],
                )
                ->data('permission', 'deliveryman-list')
                ->prepend('<i class="fa fa-user-clock"></i>')
                ->link->attr(['class' => '']);

            if ($deliveryManPendingCount == 0) {
                $menu->delivery_man
                    ->add(
                        '<span>' .
                            __('message.pending_list_form_title', ['form' => __('message.delivery_man')]) .
                            '</span>',
                        ['class' => 'sidebar-layout', 'route' => ['deliveryman.pending', 'status' => 'pending']],
                    )
                    ->data('permission', 'deliveryman-list')
                    ->prepend('<i class="fa fa-user-pen"></i>')
                    ->link->attr(['class' => '']);
            } else {
                $count =
                    '<span class="badge badge-pill badge-primary p-1  animate__animated animate__flash" id="requestCount">' .
                    $deliveryManPendingCount .
                    '</span>';
                $menu->delivery_man
                    ->add(
                        '<span>' .
                            __('message.pending_list_form_title', ['form' => __('message.delivery_man')]) .
                            ' ' .
                            $count .
                            '</span>',
                        ['class' => 'sidebar-layout', 'route' => ['deliveryman.pending', 'status' => 'pending']],
                    )
                    ->data('permission', 'deliveryman-list')
                    ->prepend('<i class="fa fa-user-pen"></i>')
                    ->link->attr(['class' => '']);
            }

            $menu->delivery_man
                ->add('<span>' . __('message.deliverymandocument') . '</span>', [
                    'class' => request()->is('delivery_man/*/add') ? 'sidebar-layout active' : 'sidebar-layout',
                    'route' => 'deliverymandocument.index',
                ])
                ->data('permission', ['deleverymandocument-add', 'deleverymandocument-edit'])
                ->prepend('<i class="fa fa-id-card"></i>')
                ->link->attr(['class' => '']);

            $menu->delivery_man
                ->add('<span>' . __('message.require_document') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'document.index',
                ])
                ->data('permission', 'document-add')
                ->prepend('<i class="fa fa-file"></i>')
                ->link->attr(['class' => '']);
            $menu->delivery_man
                ->add('<span>' . __('message.delivery_man_location') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'deliveryman-location',
                ])
                ->data('permission', 'delivery_boy_location-add')
                ->prepend('<i class="fa fa-thumbtack"></i>')
                ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.delivery_man_payout_reports').'</span>', ['route' => 'deliveryman-payout-reports.index'])
            ->prepend('<i class="fas fa-hotel"></i>')
            ->nickname('faq')
            ->data('permission', 'delivery-man-payout-reports-list')
            ->link->attr(['class' => '', 'title' => __('message.delivery_man_payout_reports'), 'data-toggle' => 'tooltip', 'data-placement' => 'right']);

            $menu
                ->add('<span>' . __('message.sub_admin') . '</span>', ['class' => ''])
                ->prepend('<i class="fa-solid fa-users"></i>')
                ->nickname('sub_admin')
                ->data('permission', ['subadmin-list', 'role-list', 'permission-list'])
                ->link->attr(['class' => ''])
                ->href('#sub_admin');

            $menu->sub_admin
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.sub_admin')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'sub-admin.index',
                ])
                ->data('permission', 'subadmin-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->sub_admin
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.role')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'role.index',
                ])
                ->data('permission', 'role-list')
                ->prepend('<i class="fas fa-user-tag"></i>')
                ->link->attr(['class' => '']);

            $menu->sub_admin
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.permission')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'permission.index',
                ])
                ->data('permission', 'permission-list')
                ->prepend('<i class="fas fa-key"></i>')
                ->link->attr(['class' => '']);

            // --- Location & Catalog ---
            $menu
                ->add('<span>' . __('message.country') . '</span>', ['class' => ''])
                ->prepend('<i class="fa-sharp fa fa-globe"></i>')
                ->nickname('country')
                ->data('permission', 'country-list')
                ->link->attr(['class' => ''])
                ->href('#country');

            $menu->country
                ->add('<span>' . __('message.add_form_title', ['form' => __('message.country')]) . '</span>', [
                    'class' => request()->is('country/*/edit') ? 'sidebar-layout active' : 'sidebar-layout',
                    'route' => 'country.create',
                ])
                ->data('permission', ['country-add', 'country-edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

            $menu->country
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.country')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'country.index',
                ])
                ->data('permission', 'country-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.city_delivery_fees') . '</span>', ['class' => ''])
                ->prepend('<i class="fa fa-city"></i>')
                ->nickname('city')
                ->data('permission', 'city-list')
                ->link->attr(['class' => ''])
                ->href('#city');

            $menu->city
                ->add('<span>' . __('message.add_form_title', ['form' => __('message.city')]) . '</span>', [
                    'class' => request()->is('country/*/edit') ? 'sidebar-layout active' : 'sidebar-layout',
                    'route' => 'city.create',
                ])
                ->data('permission', ['city-add', 'city-edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

            $menu->city
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.city')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'city.index',
                ])
                ->data('permission', 'city-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.vehicle') . '</span>', ['class' => ''])
                ->prepend('<i class="fa fa-car"></i>')
                ->nickname('vehicle')
                ->data('permission', 'vehicle-list')
                ->link->attr(['class' => ''])
                ->href('#vehicle');

            $menu->vehicle
                ->add('<span>' . __('message.add_form_title', ['form' => __('message.vehicle')]) . '</span>', [
                    'class' => request()->is('country/*/edit') ? 'sidebar-layout active' : 'sidebar-layout',
                    'route' => 'vehicle.create',
                ])
                ->data('permission', ['vehicle-add', 'vehicle-edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

            $menu->vehicle
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.vehicle')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'vehicle.index',
                ])
                ->data('permission', 'vehicle-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.extracharge') . '</span>', ['class' => ''])
                ->prepend('<i class="fas fa-comment-dollar"></i>')
                ->nickname('extracharge')
                ->data('permission', 'extracharge-list')
                ->link->attr(['class' => ''])
                ->href('#extracharge');

            $menu->extracharge
                ->add('<span>' . __('message.add_form_title', ['form' => __('message.extracharge')]) . '</span>', [
                    'class' => request()->is('country/*/edit') ? 'sidebar-layout active' : 'sidebar-layout',
                    'route' => 'extracharge.create',
                ])
                ->data('permission', ['extracharge-add', 'extracharge-edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

            $menu->extracharge
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.extracharges')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'extracharge.index',
                ])
                ->data('permission', 'extracharge-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.parceltype') . '</span>', ['class' => ''])
                ->prepend('<i class="fa-sharp fa fa-box"></i>')
                ->nickname('staticdata')
                ->data('permission', 'staticdata-list')
                ->link->attr(['class' => ''])
                ->href('#staticdata');

            $menu->staticdata
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.parceltype')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'staticdata.index',
                ])
                ->data('permission', 'staticdata-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.courier_companies') . '</span>', ['class' => ''])
                ->prepend('<i class="fa-sharp fa fa-box"></i>')
                ->nickname('courier_companies')
                ->data('permission', 'couriercompanies-list')
                ->link->attr(['class' => ''])
                ->href('#courier_companies');

            $menu->courier_companies
                ->add(
                    '<span>' . __('message.list_form_title', ['form' => __('message.courier_companies')]) . '</span>',
                    ['class' => 'sidebar-layout', 'route' => 'couriercompanies.index'],
                )
                ->data('permission', 'couriercompanies-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            // --- Finance ---
            $menu
                ->add('<span>' . __('message.payment') . '</span>', ['class' => ''])
                ->prepend('<i class="fa-solid fa-money-check-dollar"></i>')
                ->nickname('payment')
                ->data('permission', 'payment-list')
                ->link->attr(['class' => ''])
                ->href('#payment');

            $menu->payment
                ->add('<span>' . __('message.online_payment') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['payment-datatable', 'payment_type' => 'online'],
                ])
                ->data('permission', 'payment-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->payment
                ->add('<span>' . __('message.cash_payment') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['payment-datatable', 'payment_type' => 'cash'],
                ])
                ->data('permission', 'payment-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->payment
                ->add('<span>' . __('message.wallet_payment') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['payment-datatable', 'payment_type' => 'wallet'],
                ])
                ->data('permission', 'payment-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $requestCount = WithdrawRequest::where('status', 'requested')->count() ?? 0;
            if ($requestCount == 0) {
                $menu
                    ->add('<span>' . __('message.withdrawrequest') . '</span>', ['class' => ''])
                    ->prepend('<i class="fas fa-comment-dollar"></i>')
                    ->nickname('withdrawrequest')
                    ->data('permission', 'withdrawrequest-list')
                    ->link->attr(['class' => ''])
                    ->href('#withdrawrequest');
            } else {
                $count =
                    '<span class="badge badge-pill badge-primary p-1 mr-3 animate__animated animate__flash" id="requestCount">' .
                    $requestCount .
                    '</span>';
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

            $requestCount = WithdrawRequest::where('status', 'requested')->count() ?? 0;
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
                $count =
                    '<span class="badge badge-pill badge-primary p-1 mr-3 animate__animated animate__flash" id="requestCount">' .
                    $requestCount .
                    '</span>';
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
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.cencel')]) . '</span>', [
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

            /* Push Notification hidden
            $menu
                ->add('<span>' . __('message.pushnotification') . '</span>', ['class' => ''])
                ->prepend('<i class="fas fa-bullhorn"></i>')
                ->nickname('pushnotification')
                ->data('permission', 'push notification-list')
                ->link->attr(['class' => ''])
                ->href('#pushnotification');

            $menu->pushnotification
                ->add(
                    '<span>' . __('message.list_form_title', ['form' => __('message.pushnotification')]) . '</span>',
                    ['class' => 'sidebar-layout', 'route' => 'pushnotification.index'],
                )
                ->data('permission', 'push notification-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->pushnotification
                ->add('<span>' . __('message.send_pushnotification') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'pushnotification.create',
                ])
                ->data('permission', ['push notification-add', 'push notification-edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);
            */

            $menu
                ->add('<span>' . __('message.report') . '</span>', ['class' => ''])
                ->prepend('<i class="fa fa-address-book"></i>')
                ->nickname('report')
                ->data('permission', 'report')
                ->link->attr(['class' => ''])
                ->href('#report');

            $menu->report
                ->add('<span>' . __('message.admin_earning_report') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'report-adminEarning',
                ])
                ->data('permission', 'admin_earning_report')
                ->prepend('<i class="fas fa-file-contract"></i>')
                ->link->attr(['class' => '']);

            $menu->report
                ->add('<span>' . __('message.deliveryman_earning_report') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'report-deliverymanEarning',
                ])
                ->data('permission', 'deliveryman_earning_report')
                ->prepend('<i class="fas fa-file-contract"></i>')
                ->link->attr(['class' => '']);

            $menu->report
                ->add('<span>' . __('message.delivery_man_wise_report') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'report-of-deliveryman',
                ])
                ->data('permission', 'delivery_man_wise_report')
                ->prepend('<i class="fas fa-file-contract"></i>')
                ->link->attr(['class' => '']);

            $menu->report
                ->add('<span>' . __('message.user_wise_report') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'report-of-user',
                ])
                ->data('permission', 'user_wise_report')
                ->prepend('<i class="fas fa-file-contract"></i>')
                ->link->attr(['class' => '']);

            $menu->report
                ->add('<span>' . __('message.city_wise_report') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'report-of-city',
                ])
                ->data('permission', 'city_wise_report')
                ->prepend('<i class="fas fa-file-contract"></i>')
                ->link->attr(['class' => '']);

            $menu->report
                ->add('<span>' . __('message.country_wise_report') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'report-of-country',
                ])
                ->data('permission', 'country_wise_report')
                ->prepend('<i class="fas fa-file-contract"></i>')
                ->link->attr(['class' => '']);

            $menu->report
                ->add('<span>' . __('message.order_report') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'order-of-report',
                ])
                ->data('permission', 'order_report')
                ->prepend('<i class="fas fa-file-contract"></i>')
                ->link->attr(['class' => '']);

            /* Mail Template hidden
            $menu
                ->add('<span>' . __('message.mail_templated') . '</span>', ['class' => ''])
                ->prepend('<i class="fa-solid fa-envelope-open-text"></i>')
                ->nickname('ordermail')
                ->data('permission', 'order-list')
                ->link->attr(['class' => ''])
                ->href('#ordermail');

            $menu->ordermail
                ->add('<span>' . __('message.create_mail') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordermail.index', 'mails_type' => 'create'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordermail
                ->add('<span>' . __('message.active_mail') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordermail.index', 'mails_type' => 'active'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordermail
                ->add('<span>' . __('message.arrived_mail') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordermail.index', 'mails_type' => 'courier_arrived'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordermail
                ->add('<span>' . __('message.pickup_up_mail') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordermail.index', 'mails_type' => 'courier_picked_up'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordermail
                ->add('<span>' . __('message.return_mail') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordermail.index', 'mails_type' => 'return'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordermail
                ->add('<span>' . __('message.cancel_mail') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordermail.index', 'mails_type' => 'cancelled'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordermail
                ->add('<span>' . __('message.assigned_mail') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordermail.index', 'mails_type' => 'courier_assigned'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordermail
                ->add('<span>' . __('message.shipped_mail') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordermail.index', 'mails_type' => 'shipped'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordermail
                ->add('<span>' . __('message.completed_mail') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordermail.index', 'mails_type' => 'completed'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordermail
                ->add('<span>' . __('message.departed_mail') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordermail.index', 'mails_type' => 'courier_departed'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordermail
                ->add('<span>' . __('message.reschedule_mail') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordermail.index', 'mails_type' => 'reschedule'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordermail
                ->add('<span>' . __('message.otp_verification_mail') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['otpVerify_template', 'mails_type' => 'otp_verification_mail'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordermail
                ->add('<span>' . __('message.self_billing_statement') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['otpVerify_template', 'mails_type' => 'self_billing_statement'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);
            */

            /* SMS Templated hidden
            $menu
                ->add('<span>' . __('message.sms_templated') . '</span>', ['class' => ''])
                ->prepend('<i class="fa-solid fa-envelope-open-text"></i>')
                ->nickname('ordersms')
                ->data('permission', 'order-list')
                ->link->attr(['class' => ''])
                ->href('#ordersms');

            $menu->ordersms
                ->add('<span>' . __('message.order_confirmation') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordersms.index', 'sms_type' => 'order_confirmation'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordersms
                ->add('<span>' . __('message.you_have_parcel') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordersms.index', 'sms_type' => 'you_have_parcel'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordersms
                ->add('<span>' . __('message.out_for_delivery') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordersms.index', 'sms_type' => 'out_for_delivery'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordersms
                ->add('<span>' . __('message.delivered_successfully') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordersms.index', 'sms_type' => 'delivered_successfully'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordersms
                ->add('<span>' . __('message.delivery_attempt_failed') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordersms.index', 'sms_type' => 'delivery_attempt_failed'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordersms
                ->add('<span>' . __('message.new_delivery_assignment') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordersms.index', 'sms_type' => 'new_delivery_assignment'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordersms
                ->add('<span>' . __('message.pickup_verification_code') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordersms.index', 'sms_type' => 'pickup_verification_code'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordersms
                ->add('<span>' . __('message.delivery_verification_code') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordersms.index', 'sms_type' => 'delivery_verification_code'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);

            $menu->ordersms
                ->add('<span>' . __('message.emergency_sms') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['ordersms.index', 'sms_type' => 'emergency'],
                ])
                ->data('permission', 'order-list')
                ->prepend('<i class="fa-solid fa-inbox"></i>')
                ->link->attr(['class' => '']);
            */

            /* Reference program hidden
            $menu
                ->add('<span>' . __('message.reference_program') . '</span>', ['route' => 'reference-list'])
                ->prepend('<i class="fa-solid fa-handshake-simple"></i>')
                ->link->attr(['class' => '']);
            */

            $supportCount = CustomerSupport::count() ?? 0;
            $supportBadge =
                $supportCount > 0
                    ? '<span class="badge badge-pill badge-primary p-1 mr-3 animate__animated animate__flash">' .
                        $supportCount .
                        '</span>'
                    : '';

            $menu
                ->add('<span>' . __('message.customer_support') . ' ' . $supportBadge . '</span>', [
                    'class' => '',
                    'route' => 'customersupport.index',
                ])
                ->prepend('<i class="fa fa-headset"></i>')
                ->data('permission', 'customersupport-list')
                ->link->attr(['class' => '']);

            /* Coupon hidden
            $menu
                ->add('<span>' . __('message.coupon') . '</span>', ['class' => ''])
                ->prepend('<i class="fa-solid fa-ticket"></i>')
                ->nickname('coupon')
                ->data('permission', 'coupon-list')
                ->link->attr(['class' => ''])
                ->href('#city');

            $menu->coupon
                ->add('<span>' . __('message.add_form_title', ['form' => __('message.coupon')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'coupon.create',
                ])
                ->data('permission', ['coupon-add', 'coupon-edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

            $menu->coupon
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.coupon')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'coupon.index',
                ])
                ->data('permission', 'city-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);
            */

            // --- Marketing & Features ---
            $menu
                ->add('<span>' . __('message.marketing') . '</span>', ['class' => ''])
                ->prepend('<i class="fas fa-ad"></i>')
                ->nickname('marketing')
                ->data('permission', 'advertisement-list')
                ->link->attr(['class' => ''])
                ->href('#marketing');

            $menu->marketing
                ->add('<span>' . __('message.advertisement') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'advertisement.index',
                ])
                ->data('permission', 'advertisement-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->marketing
                ->add('<span>' . __('message.welcome_promotion') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'welcome-promotion.index',
                ])
                ->data('permission', 'welcome-promotion-list')
                ->prepend('<i class="fas fa-gift"></i>')
                ->link->attr(['class' => '']);

            $menu->marketing
                ->add('<span>' . __('message.shop_showcase') . '</span>', ['class' => ''])
                ->prepend('<i class="fas fa-store"></i>')
                ->nickname('shop_showcase')
                ->data('permission', 'shop-banner-list')
                ->link->attr(['class' => ''])
                ->href('#shop_showcase');

            $menu->shop_showcase
                ->add('<span>' . __('message.shop_banners') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'shop-banner.index',
                ])
                ->data('permission', 'shop-banner-list')
                ->prepend('<i class="fas fa-images"></i>')
                ->link->attr(['class' => '']);

            $menu->shop_showcase
                ->add('<span>' . __('message.shop_categories') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'shop-category.index',
                ])
                ->data('permission', 'shop-category-list')
                ->prepend('<i class="fas fa-th-large"></i>')
                ->link->attr(['class' => '']);

            $menu->shop_showcase
                ->add('<span>' . __('message.shop_products') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'shop-product.index',
                ])
                ->data('permission', 'shop-product-list')
                ->prepend('<i class="fas fa-box"></i>')
                ->link->attr(['class' => '']);

            $menu->marketing
                ->add('<span>' . __('message.order_chat_monitoring') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'order-chat.index',
                ])
                ->data('permission', 'order-chat-list')
                ->prepend('<i class="fas fa-comments"></i>')
                ->link->attr(['class' => '']);

            $menu->marketing
                ->add('<span>' . __('message.pushnotification') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'pushnotification.index',
                ])
                ->data('permission', 'push notification-list')
                ->prepend('<i class="fas fa-bullhorn"></i>')
                ->link->attr(['class' => '']);

            // --- System & Content ---
            $menu
                ->add('<span>' . __('message.pages') . '</span>', ['class' => ''])
                ->prepend('<i class="fas fa-file"></i>')
                ->nickname('pages')
                ->data('permission', 'pages')
                ->link->attr(['class' => ''])
                ->href('#pages');

            $menu->pages
                ->add('<span>' . __('message.list') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'pages.index',
                ])
                ->data('permission', 'page List')
                ->prepend('<i class="fas fa-file-contract"></i>')
                ->link->attr(['class' => '']);

            $menu->pages
                ->add('<span>' . __('message.terms_condition') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'term-condition',
                ])
                ->data('permission', 'terms condition')
                ->prepend('<i class="fas fa-file-contract"></i>')
                ->link->attr(['class' => '']);

            $menu->pages
                ->add('<span>' . __('message.privacy_policy') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'privacy-policy',
                ])
                ->data('permission', 'privacy policy')
                ->prepend('<i class="fas fa-user-shield"></i>')
                ->link->attr(['class' => '']);

            $menu->pages->add('<span>'.__('message.billing_statement').'</span>', ['class' => 'sidebar-layout' ,'route' => 'billing-statement'])
                ->data('permission', 'billing-statement')
                ->prepend('<i class="fas fa-user-shield"></i>')
                ->link->attr(['class' => '']);

            $menu
                ->add('<span>' . __('message.app_language_setting') . '</span>', ['class' => ''])
                ->prepend('<i class="fa fa-language"></i>')
                ->nickname('app_language_setting')
                ->data('permission', '')
                ->link->attr(['class' => ''])
                ->href('#app_language_setting');

            $menu->app_language_setting
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.screen')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'screen.index',
                ])
                ->data('permission', 'screen-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->app_language_setting
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.default_keyword')]) . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'defaultkeyword.index',
                ])
                ->data('permission', 'defaultkeyword-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->app_language_setting
                ->add('<span>' . __('message.list_form_title', ['form' => __('message.language')]) . '</span>', [
                    'class' =>
                        request()->is('languagelist/*/edit') || request()->is('languagelist/create')
                            ? 'sidebar-layout active'
                            : 'sidebar-layout',
                    'route' => 'languagelist.index',
                ])
                ->data('permission', 'languagelist-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->app_language_setting
                ->add(
                    '<span>' .
                        __('message.list_form_title', ['form' => __('message.language_with_keyword')]) .
                        '</span>',
                    ['class' => 'sidebar-layout', 'route' => 'languagewithkeyword.index'],
                )
                ->data('permission', 'languagewithkeyword-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->app_language_setting
                ->add(
                    '<span>' .
                        __('message.list_form_title', ['form' => __('message.bulk_import_langugage_data')]) .
                        '</span>',
                    ['class' => 'sidebar-layout', 'route' => 'bulk.language.data'],
                )
                ->data('permission', 'bulkimport-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            if (isSidebarMenuVisible('website_section')) {
            $menu
                ->add('<span>' . __('message.website_section') . '</span>', ['class' => ''])
                ->prepend('<i class="fas fa-globe-asia"></i>')
                ->nickname('website_section')
                ->data('permission', 'website_section list')
                ->link->attr(['class' => ''])
                ->href('#website_section');

            $menu->website_section
                ->add('<span>' . __('message.infromation') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['frontend.website.form', 'app_content'],
                ])
                ->data('permission', 'information list')
                ->prepend('<i class="fas fa-file-alt"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section
                ->add('<span>' . __('message.whydelivery') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'whydelivery.index',
                ])
                ->data('permission', 'whydelivery list')
                ->prepend('<i class="fas fa-truck"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section
                ->add('<span>' . __('message.app_overview') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['app-overview.index'],
                ])
                ->data('permission', 'app overview list')
                ->prepend('<i class="fa-solid fa-eye"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section
                ->add('<span>' . __('message.downloandapp') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['frontend.website.form', 'download_app'],
                ])
                ->data('permission', 'downloandapp list')
                ->prepend('<i class="fas fa-download"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section
                ->add('<span>' . __('message.client_testimonial') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['client-testimonial.index'],
                ])
                ->data('permission', 'client testimonial list')
                ->prepend('<i class="fa-solid fa-shapes"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section
                ->add('<span>' . __('message.courier_recruitment_section') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['frontend.website.form', 'courier_recruitment_section'],
                ])
                ->data('permission', 'courierrecruitmentsection list')
                ->prepend('<i class="fa-solid fa-boxes-stacked"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section
                ->add('<span>' . __('message.delivery_job') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['frontend.website.form', 'delivery_job'],
                ])
                ->data('permission', 'deliveryjob list')
                ->prepend('<i class="fas fa-location-arrow"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section
                ->add('<span>' . __('message.deliverypartner') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'deliverypartner.index',
                ])
                ->data('permission', 'deliverypartner list')
                ->prepend('<i class="fas fa-user-nurse"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section
                ->add('<span>' . __('message.document_verification') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['document-verification.index'],
                ])
                ->data('permission', 'document verification list')
                ->prepend('<i class="fa-solid fa-file-invoice"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section
                ->add('<span>' . __('message.delivery_man_section') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['delivery-man-section.index'],
                ])
                ->data('permission', 'app overview list')
                ->prepend('<i class="fa-solid fa-person"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section
                ->add('<span>' . __('message.deliver_your_way') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'deliver-your-way.index',
                ])
                ->data('permission', 'deliveryourway list')
                ->prepend('<i class="fa-solid fa-truck-ramp-box"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section
                ->add('<span>' . __('message.contactinfo') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['frontend.website.form', 'contact_us'],
                ])
                ->data('permission', 'contactinfo list')
                ->prepend('<i class="fas fa-id-badge"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section
                ->add('<span>' . __('message.aboutus') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['frontend.website.form', 'about_us'],
                ])
                ->data('permission', 'aboutus list')
                ->prepend('<i class="fas fa-exclamation"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section
                ->add('<span>' . __('message.walkthrough') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => 'walkthrough.index',
                ])
                ->data('permission', 'walkthrough list')
                ->prepend('<i class="fas fa-tv"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section
                ->add('<span>' . __('message.order_status') . '</span>', [
                    'class' => 'sidebar-layout',
                    'route' => ['website.section.order.status'],
                ])
                ->data('permission', 'order status list')
                ->prepend('<i class="fa-solid fa-box"></i>')
                ->link->attr(['class' => '']);
            }

            if (isSidebarMenuVisible('general_settings')) {
            $menu
                ->add('<span>' . __('message.general_settings') . '</span>', ['route' => 'setting.index'])
                ->prepend('<i class="fas fa-cog"></i>')
                ->nickname('setting')
                ->data('permission', 'system setting');
            }

            // Always visible for admins — mobile apps sync API IP from here.
            $menu
                ->add('<span>' . __('message.api_server_settings') . '</span>', [
                    'route' => ['setting.index', 'page' => 'api-server-setting'],
                ])
                ->prepend('<i class="fas fa-network-wired"></i>')
                ->nickname('api_server_setting')
                ->data('permission', 'system setting')
                ->link->attr(['class' => '']);

            if (isSidebarMenuVisible('rest_api')) {
            $menu
                ->add('<span>' . __('message.rest_api') . '</span>', ['route' => 'rest-api.index'])
                ->prepend('<i class="fa-solid fa-repeat"></i>')
                ->link->attr(['class' => '']);
            }

            // --- Security ---
            $menu->add('<span class="item-name">'.__('message.admin_login_history').'</span>', ['route' => 'admin_login_history.index'])
                  ->prepend('<i class="fas fa-laptop"></i>')
                  ->link->attr([ 'class' => request()->routeIs('admin_login_history.index') ? 'nav-link active' : 'nav-link' ]);

            $menu->add('<span>'.__('message.active_sessions').'</span>', ['route' => 'admin-login-device.index'])
            ->prepend('<i class="fas fa-sign-out-alt"></i>')
            ->nickname('admin_login_history')
            ->link->attr(['class' => '', 'title' => __('message.active_sessions'), 'data-toggle' => 'tooltip', 'data-placement' => 'right']);
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
