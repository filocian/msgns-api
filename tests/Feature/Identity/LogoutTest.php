<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;


it('logs out authenticated user', function () {
    $user = $this->create_user(['email' => 'user@example.com']);
    $this->actingAs($user, 'stateful-api');

    $response = $this->postWithHeaders('/api/v2/identity/logout');
    $response->assertStatus(204);
});

it('returns 401 when unauthenticated', function () {
    $response = $this->postWithHeaders('/api/v2/identity/logout');
    $response->assertStatus(401);
});
