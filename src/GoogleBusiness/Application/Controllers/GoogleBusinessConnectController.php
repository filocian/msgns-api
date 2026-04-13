<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Application\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Src\GoogleBusiness\Infrastructure\Services\GoogleBusinessOAuthService;

final class GoogleBusinessConnectController extends Controller
{
    public function __construct(
        private readonly GoogleBusinessOAuthService $oauthService,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        session()->put('google_business_oauth_state', $state);

        return redirect()->away($this->oauthService->buildAuthorizationUrl($state));
    }
}
