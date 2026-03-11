<?php

declare(strict_types=1);



it('returns current user when authenticated', function () {
    $user = $this->create_user(['email' => 'user@example.com']);
    $this->actingAs($user, 'stateful-api');

    $response = $this->getJson('/api/v2/identity/me');
    $response->assertStatus(200)
             ->assertJsonPath('data.email', 'user@example.com');
});

it('returns 401 when unauthenticated', function () {
    $response = $this->getJson('/api/v2/identity/me');
    $response->assertStatus(401);
});
