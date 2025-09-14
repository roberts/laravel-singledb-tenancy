<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizePrimaryTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (current_tenant_id() !== 1) {
            abort(404);
        }

        return $next($request);
    }
}
