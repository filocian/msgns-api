<?php

declare(strict_types=1);

namespace Src\Instagram\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Src\Instagram\Domain\Models\UserInstagramConnection;

final class InstagramCallbackController extends Controller
{
    private const string API_VERSION = 'v21.0';
    private const string GRAPH_URL   = 'https://graph.facebook.com';
    private const int    TIMEOUT     = 10;

    public function __invoke(Request $request): RedirectResponse
    {
        $frontendBase = config('services.products.v2_front_url') . '/settings/integrations/instagram';

        try {
            // Verify user is authenticated via session.
            $user = Auth::guard('stateful-api')->user();
            if (! $user) {
                return redirect($frontendBase . '?error=unauthenticated');
            }

            // Validate state.
            $storedState = session()->pull('instagram_oauth_state');
            if (empty($storedState) || $request->query('state') !== $storedState) {
                return redirect($frontendBase . '?error=state_mismatch');
            }

            // Validate code presence.
            $code = $request->query('code');
            if (empty($code)) {
                return redirect($frontendBase . '?error=missing_code');
            }

            // Step 1: Exchange code for short-lived token (POST).
            try {
                $shortLivedResponse = Http::timeout(self::TIMEOUT)
                    ->asForm()
                    ->post(self::GRAPH_URL . '/' . self::API_VERSION . '/oauth/access_token', [
                        'client_id'     => config('services.meta.app_id'),
                        'client_secret' => config('services.meta.app_secret'),
                        'redirect_uri'  => config('services.meta.redirect_uri'),
                        'code'          => $code,
                    ]);
            } catch (ConnectionException) {
                return redirect($frontendBase . '?error=api_error');
            }

            if ($shortLivedResponse->failed()) {
                return redirect($frontendBase . '?error=token_exchange_failed');
            }

            $shortLivedToken = (string) $shortLivedResponse->json('access_token');

            // Step 2: Exchange short-lived token for long-lived token (GET).
            try {
                $longLivedResponse = Http::timeout(self::TIMEOUT)->get(self::GRAPH_URL . '/oauth/access_token', [
                    'grant_type'        => 'fb_exchange_token',
                    'client_id'         => config('services.meta.app_id'),
                    'client_secret'     => config('services.meta.app_secret'),
                    'fb_exchange_token' => $shortLivedToken,
                ]);
            } catch (ConnectionException) {
                return redirect($frontendBase . '?error=api_error');
            }

            if ($longLivedResponse->failed()) {
                return redirect($frontendBase . '?error=token_exchange_failed');
            }

            $longLivedToken = (string) $longLivedResponse->json('access_token');
            $expiresIn      = (int) $longLivedResponse->json('expires_in');

            // Step 3: Fetch Facebook Pages.
            try {
                $pagesResponse = Http::timeout(self::TIMEOUT)->get(
                    self::GRAPH_URL . '/' . self::API_VERSION . '/me/accounts',
                    ['access_token' => $longLivedToken]
                );
            } catch (ConnectionException) {
                return redirect($frontendBase . '?error=api_error');
            }

            if ($pagesResponse->failed()) {
                return redirect($frontendBase . '?error=api_error');
            }

            $pages = $pagesResponse->json('data') ?? [];
            if (empty($pages)) {
                return redirect($frontendBase . '?error=no_business_account');
            }

            $pageId = (string) ($pages[0]['id'] ?? '');

            // Step 4: Fetch Instagram Business Account from first page.
            try {
                $igAccountResponse = Http::timeout(self::TIMEOUT)
                    ->withToken($longLivedToken)
                    ->get(self::GRAPH_URL . '/' . self::API_VERSION . '/' . $pageId, [
                        'fields' => 'instagram_business_account',
                    ]);
            } catch (ConnectionException) {
                return redirect($frontendBase . '?error=api_error');
            }

            if ($igAccountResponse->failed()) {
                return redirect($frontendBase . '?error=api_error');
            }

            $igAccount = $igAccountResponse->json('instagram_business_account');
            if (empty($igAccount['id'])) {
                return redirect($frontendBase . '?error=no_business_account');
            }

            $igUserId = (string) $igAccount['id'];

            // Step 5: Fetch Instagram username (optional).
            $username = null;
            try {
                $usernameResponse = Http::timeout(self::TIMEOUT)
                    ->withToken($longLivedToken)
                    ->get(self::GRAPH_URL . '/' . self::API_VERSION . '/' . $igUserId, [
                        'fields' => 'username',
                    ]);

                if ($usernameResponse->successful()) {
                    $username = $usernameResponse->json('username');
                }
            } catch (ConnectionException) {
                // Username is optional — do not fail the entire flow.
            }

            // Step 6: Store connection.
            UserInstagramConnection::updateOrCreate(
                ['user_id' => (int) $user->id],
                [
                    'instagram_user_id'   => $igUserId,
                    'instagram_username'  => $username,
                    'page_id'             => $pageId,
                    'access_token'        => $longLivedToken,
                    'expires_at'          => now()->addSeconds($expiresIn),
                ]
            );

            return redirect($frontendBase . '?connected=true');

        } catch (\Throwable $e) {
            report($e);
            return redirect($frontendBase . '?error=api_error');
        }
    }
}
