<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Super Admin UI text sync (auth + same-origin only; avoids host/cookie CSRF flakes)
        'super-admin/ui-text',
        'super-admin/ui-text/*',
        'super-admin/admin-ui-text',
        'super-admin/admin-ui-text/*',
    ];
}
