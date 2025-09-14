<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Middleware;

use Closure;
use Illuminate\Http\Request;
use Roberts\LaravelSingledbTenancy\Services\SmartFallback;
use Symfony\Component\HttpFoundation\Response;

class AuthorizePrimaryTenant
{
    public function __construct(protected SmartFallback $smartFallback)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->smartFallback->isFallback()) {
            return $next($request);
        }

        if (current_tenant_id() !== 1) {
            abort(404);
        }

        return $next($request);
    }
}
