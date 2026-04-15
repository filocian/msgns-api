<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Ai\Application\Services\AiUsageLimitsService;
use Src\Identity\Domain\Permissions\DomainPermissions;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces per-feature AI quota before passing the request downstream.
 *
 * Middleware parameter: product type string ('google_reviews' | 'instagram').
 * Usage: Route::middleware('ai.enforce-usage:google_reviews')
 *
 * Does NOT write usage records — read-only quota check only.
 * Writing is the responsibility of the feature handler.
 *
 * Response codes:
 *   401 — unauthenticated (defensive guard)
 *   403 — authenticated but no AI permission assigned
 *   429 — quota exhausted for the requested product type
 */
final class AiUsageEnforcementMiddleware
{
    /** @var list<string> */
    private const array AI_PERMISSIONS = [
        DomainPermissions::AI_FREE_PREVIEW,
        DomainPermissions::AI_BASIC_MONTHLY,
        DomainPermissions::AI_BASIC_YEARLY,
        DomainPermissions::AI_STANDARD_MONTHLY,
        DomainPermissions::AI_STANDARD_YEARLY,
        DomainPermissions::AI_PREPAID_STARTER,
        DomainPermissions::AI_PREPAID_GROWTH,
        DomainPermissions::AI_PREPAID_PRO,
    ];

    public function __construct(private readonly AiUsageLimitsService $limitsService) {}

    /** @param 'google_reviews'|'instagram' $productType */
    public function handle(Request $request, Closure $next, string $productType): mixed
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->canAny(self::AI_PERMISSIONS)) {
            return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
        }

        if (! $this->limitsService->hasQuota($user, $productType)) {
            return response()->json(['message' => 'AI quota exhausted.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return $next($request);
    }
}
