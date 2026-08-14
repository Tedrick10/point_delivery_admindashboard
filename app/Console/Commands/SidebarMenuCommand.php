<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SidebarMenuCommand extends Command
{
    protected $signature = 'sidebar:menu {action : hide, show, or list} {menus?* : Menu keys e.g. website_section rest_api}';

    protected $description = 'Hide or show admin sidebar drawer menu items';

    protected array $allowedMenus = [
        'website_section' => 'Website Section',
        'general_settings' => 'General Setting',
        'rest_api' => 'Rest Api',
    ];

    public function handle(): int
    {
        $action = strtolower((string) $this->argument('action'));

        return match ($action) {
            'hide' => $this->hideMenus(),
            'show' => $this->showMenus(),
            'list' => $this->listMenus(),
            default => $this->invalidAction($action),
        };
    }

    protected function hideMenus(): int
    {
        $menus = $this->resolveMenuKeys();
        if ($menus === null) {
            return self::FAILURE;
        }

        $hidden = array_values(array_unique(array_merge(hiddenSidebarMenus(), $menus)));
        setHiddenSidebarMenus($hidden);

        $this->info('Hidden sidebar menus: ' . implode(', ', $menus));
        $this->line('Refresh the admin dashboard to see changes.');

        return self::SUCCESS;
    }

    protected function showMenus(): int
    {
        $menus = $this->resolveMenuKeys();
        if ($menus === null) {
            return self::FAILURE;
        }

        $hidden = array_values(array_diff(hiddenSidebarMenus(), $menus));
        setHiddenSidebarMenus($hidden);

        $this->info('Visible sidebar menus: ' . implode(', ', $menus));
        $this->line('Refresh the admin dashboard to see changes.');

        return self::SUCCESS;
    }

    protected function listMenus(): int
    {
        $hidden = hiddenSidebarMenus();

        $this->info('Sidebar menu visibility:');
        foreach ($this->allowedMenus as $key => $label) {
            $status = in_array($key, $hidden, true) ? 'hidden' : 'visible';
            $this->line("- {$label} ({$key}): {$status}");
        }

        return self::SUCCESS;
    }

    protected function resolveMenuKeys(): ?array
    {
        $menus = $this->argument('menus') ?: [];

        if (empty($menus)) {
            $this->error('Please provide at least one menu key.');
            $this->line('Allowed keys: ' . implode(', ', array_keys($this->allowedMenus)));

            return null;
        }

        $invalid = array_values(array_diff($menus, array_keys($this->allowedMenus)));
        if (!empty($invalid)) {
            $this->error('Invalid menu key(s): ' . implode(', ', $invalid));
            $this->line('Allowed keys: ' . implode(', ', array_keys($this->allowedMenus)));

            return null;
        }

        return $menus;
    }

    protected function invalidAction(string $action): int
    {
        $this->error("Unknown action: {$action}");
        $this->line('Usage examples:');
        $this->line('  php artisan sidebar:menu hide website_section rest_api general_settings');
        $this->line('  php artisan sidebar:menu show website_section');
        $this->line('  php artisan sidebar:menu list');

        return self::FAILURE;
    }
}
