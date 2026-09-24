<?php

return [
    'dispatch' => [
        'title_key' => 'sa_screen_dispatch',
        'subtitle_key' => 'sa_screen_dispatch_sub',
        'icon' => 'fa-truck-moving',
        'workspace' => 'order.dispatch.to-assign',
        'links' => [
            ['route' => 'order.dispatch.to-assign', 'label_key' => 'sa_link_to_assign'],
            ['route' => 'order.dispatch.assign-100', 'label_key' => 'sa_link_assign_100'],
            ['route' => 'order.dispatch.assigned-items', 'label_key' => 'sa_link_assigned_items'],
            ['route' => 'order.dispatch.os-list', 'label_key' => 'sa_link_os_list'],
            ['route' => 'order.dispatch.rider-list', 'label_key' => 'sa_link_rider_list'],
        ],
    ],
    'daily-check' => [
        'title_key' => 'sa_screen_daily_check',
        'subtitle_key' => 'sa_screen_daily_check_sub',
        'icon' => 'fa-clipboard-check',
        'workspace' => 'order.daily-checklist',
        'links' => [
            ['route' => 'order.daily-checklist', 'label_key' => 'sa_link_daily_check_list'],
        ],
    ],
    'kyo-shin' => [
        'title_key' => 'sa_screen_kyo_shin',
        'subtitle_key' => 'sa_screen_kyo_shin_sub',
        'icon' => 'fa-coins',
        'workspace' => 'order.kyo-shin',
        'links' => [
            ['route' => 'order.kyo-shin', 'label_key' => 'sa_link_kyo_shin'],
        ],
    ],
    'money-transfer' => [
        'title_key' => 'sa_screen_money_transfer',
        'subtitle_key' => 'sa_screen_money_transfer_sub',
        'icon' => 'fa-exchange-alt',
        'workspace' => 'order.money-transfer',
        'links' => [
            ['route' => 'order.money-transfer', 'label_key' => 'sa_link_money_transfer_sheet'],
        ],
    ],
    'rider-remit' => [
        'title_key' => 'sa_screen_rider_remit',
        'subtitle_key' => 'sa_screen_rider_remit_sub',
        'icon' => 'fa-motorcycle',
        'workspace' => 'order.rider-remit',
        'links' => [
            ['route' => 'order.rider-remit', 'label_key' => 'sa_link_rider_remit_sheet'],
        ],
    ],
    'delivery-route' => [
        'title_key' => 'sa_screen_delivery_route',
        'subtitle_key' => 'sa_screen_delivery_route_sub',
        'icon' => 'fa-map-marker-alt',
        // CRUD is embedded on this screen — no external workspace links.
        'workspace' => null,
        'links' => [],
    ],
    'network' => [
        'title_key' => 'sa_screen_network',
        'subtitle_key' => 'sa_screen_network_sub',
        'icon' => 'fa-sitemap',
        'workspace' => null,
        'links' => [
            ['route' => 'super-admin.branch-admins.index', 'label_key' => 'sa_branch_admins'],
            ['route' => 'super-admin.screens.show', 'label_key' => 'sa_link_delivery_route', 'params' => ['screen' => 'delivery-route', 'tab' => 'from_to']],
            ['route' => 'super-admin.screens.show', 'label_key' => 'sa_screen_rider_remit', 'params' => ['screen' => 'rider-remit']],
            ['route' => 'super-admin.screens.show', 'label_key' => 'sa_screen_late_fine', 'params' => ['screen' => 'late-fine']],
        ],
    ],
    'cash-payout' => [
        'title_key' => 'sa_screen_cash_payout',
        'subtitle_key' => 'sa_screen_cash_payout_sub',
        'icon' => 'fa-money-bill-wave',
        'workspace' => 'order.cash-payout',
        'links' => [
            ['route' => 'order.cash-payout', 'label_key' => 'sa_link_cash_payout', 'params' => ['status' => 'unassigned']],
        ],
    ],
    'os-receive' => [
        'title_key' => 'sa_screen_os_receive',
        'subtitle_key' => 'sa_screen_os_receive_sub',
        'icon' => 'fa-hand-holding-usd',
        'workspace' => 'order.os-receive',
        'links' => [
            ['route' => 'order.os-receive', 'label_key' => 'sa_link_os_receive_pay'],
        ],
    ],
    'expenses' => [
        'title_key' => 'sa_screen_expenses',
        'subtitle_key' => 'sa_screen_expenses_sub',
        'icon' => 'fa-receipt',
        'workspace' => 'order.expenses',
        'links' => [
            ['route' => 'order.expenses', 'label_key' => 'sa_link_expenses_board'],
        ],
    ],
    'expense-summary' => [
        'title_key' => 'sa_screen_expense_summary',
        'subtitle_key' => 'sa_screen_expense_summary_sub',
        'icon' => 'fa-file-invoice-dollar',
        'workspace' => null,
        'links' => [],
    ],
    'late-fine' => [
        'title_key' => 'sa_screen_late_fine',
        'subtitle_key' => 'sa_screen_late_fine_sub',
        'icon' => 'fa-user-clock',
        'workspace' => null,
        'links' => [],
    ],
    'office-salary' => [
        'title_key' => 'sa_screen_office_salary',
        'subtitle_key' => 'sa_screen_office_salary_sub',
        'icon' => 'fa-briefcase',
        'workspace' => null,
        'links' => [],
    ],
    'rider-salary' => [
        'title_key' => 'sa_screen_rider_salary',
        'subtitle_key' => 'sa_screen_rider_salary_sub',
        'icon' => 'fa-motorcycle',
        'workspace' => null,
        'links' => [],
    ],
];
