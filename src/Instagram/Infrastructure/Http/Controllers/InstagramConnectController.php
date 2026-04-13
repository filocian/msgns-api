<?php

declare(strict_types=1);

namespace Src\Instagram\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

final class InstagramConnectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $state = Str::random(40);
        session()->put('instagram_oauth_state', $state);

        $url = 'https://www.facebook.com/' . 'v21.0/dialog/oauth?' . http_build_query([
            'client_id'     => config('services.meta.app_id'),
            'redirect_uri'  => config('services.meta.redirect_uri'),
            'scope'         => 'instagram_basic,instagram_content_publish,pages_read_engagement',
            'response_type' => 'code',
            'state'         => $state,
        ]);

        return redirect()->away($url);
    }
}
