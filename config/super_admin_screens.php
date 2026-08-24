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
        'workspace' => 'order.expense-summary',
        'links' => [
            ['route' => 'order.expense-summary', 'label_key' => 'sa_link_summary_table'],
        ],
    ],
];
