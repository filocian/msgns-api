<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException as SpatieUnauthorizedException;
use Spatie\Permission\Middleware\RoleMiddleware as SpatieRoleMiddleware;

/**
 * Wraps Spatie's RoleMiddleware to return a proper JSON 403 response
 * instead of throwing an exception (which the legacy handler maps to 500).
 */
final class RoleMiddleware
{
    public function __construct(private readonly SpatieRoleMiddleware $inner) {}

    public function handle(Request $request, Closure $next, string $role, ?string $guard = null): mixed
    {
        try {
            return $this->inner->handle($request, $next, $role, $guard);
        } catch (SpatieUnauthorizedException) {
            return response()->json(['error' => 'forbidden', 'code' => 'forbidden'], 403);
        }
    }
}
