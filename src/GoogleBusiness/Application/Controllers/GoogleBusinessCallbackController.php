<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Application\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Src\GoogleBusiness\Application\Commands\ConnectGoogleBusiness\ConnectGoogleBusinessCommand;
use Src\GoogleBusiness\Infrastructure\Services\GoogleBusinessOAuthService;
use Src\Shared\Core\Bus\CommandBus;

final class GoogleBusinessCallbackController extends Controller
{
    public function __construct(
        private readonly GoogleBusinessOAuthService $oauthService,
        private readonly CommandBus $commandBus,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $frontendBaseUrl = config('services.products.v2_front_url') . '/settings/integrations/google-business';

        try {
            // Identify user from session (web guard is disabled — must specify stateful-api guard).
            $user = auth('stateful-api')->user();
            if (! $user) {
                return redirect($frontendBaseUrl . '?error=session_expired');
            }

            // Validate CSRF state.
            $storedState = session()->pull('google_business_oauth_state');
            if (empty($storedState) || $request->query('state') !== $storedState) {
                return redirect($frontendBaseUrl . '?error=oauth_failed');
            }

            // User denied consent.
            if ($request->query('error')) {
                return redirect($frontendBaseUrl . '?error=oauth_failed');
            }

            // Exchange authorization code for tokens.
            $tokens = $this->oauthService->exchangeCodeForTokens((string) $request->query('code'));

            // Fetch Google account ID.
            $googleAccountId = $this->oauthService->fetchGoogleAccountId($tokens['access_token']);

            // Persist the connection.
            $this->commandBus->dispatch(new ConnectGoogleBusinessCommand(
                userId: (int) $user->id,
                googleAccountId: $googleAccountId,
                accessToken: $tokens['access_token'],
                refreshToken: $tokens['refresh_token'],
                expiresIn: $tokens['expires_in'],
            ));

            return redirect($frontendBaseUrl . '?connected=true');
        } catch (\Throwable $e) {
            report($e);
            return redirect($frontendBaseUrl . '?error=oauth_failed');
        }
    }
}
