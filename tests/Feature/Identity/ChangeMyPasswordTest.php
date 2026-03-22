<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = $this->create_user([
        'email'    => 'user@example.com',
        'password' => Hash::make('OldPass123!'),
    ]);
    $this->actingAs($this->user, 'stateful-api');
});

it('returns 204 with correct current password and valid new password', function () {
    $response = $this->patchJson('/api/v2/identity/me/password', [
        'current_password'          => 'OldPass123!',
        'new_password'              => 'NewPass456!',
        'new_password_confirmation' => 'NewPass456!',
    ]);
    $response->assertStatus(204);

    $this->user->refresh();
    expect(Hash::check('NewPass456!', $this->user->password))->toBeTrue();
});

it('returns 422 with incorrect current password', function () {
    $response = $this->patchJson('/api/v2/identity/me/password', [
        'current_password'          => 'WrongPassword!',
        'new_password'              => 'NewPass456!',
        'new_password_confirmation' => 'NewPass456!',
    ]);
    $response->assertStatus(422);
});

it('returns 400 validation error with new_password too short', function () {
    $response = $this->patchJson('/api/v2/identity/me/password', [
        'current_password'          => 'OldPass123!',
        'new_password'              => 'short',
        'new_password_confirmation' => 'short',
    ]);
    $response->assertStatus(400)
             ->assertJsonPath('error.code', 'validation_error');
});

it('returns 400 validation error with mismatched confirmation', function () {
    $response = $this->patchJson('/api/v2/identity/me/password', [
        'current_password'          => 'OldPass123!',
        'new_password'              => 'NewPass456!',
        'new_password_confirmation' => 'DifferentPass!',
    ]);
    $response->assertStatus(400)
             ->assertJsonPath('error.code', 'validation_error');
});

it('has rate limiting middleware (throttle:5,1) on PATCH /me/password', function () {
    $route = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
        ->first(fn (\Illuminate\Routing\Route $r) =>
            $r->uri() === 'api/v2/identity/me/password' && in_array('PATCH', $r->methods())
        );

    expect($route)->not->toBeNull();
    $middleware = $route->middleware();
    expect($middleware)->toContain('throttle:5,1');
});
