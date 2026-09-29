<?php

namespace App\Http\Middleware;

use App\Services\MyanmarTextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Normalize Zawgyi Myanmar free-text in request payloads to Unicode before controllers run.
 */
class NormalizeMyanmarText
{
    public function __construct(
        protected MyanmarTextService $myanmarText
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            $payload = $request->all();
            if ($payload !== []) {
                $normalized = $this->myanmarText->normalizeValue($payload);
                if (is_array($normalized)) {
                    $request->replace($normalized);
                }
            }
        }

        return $next($request);
    }
}
