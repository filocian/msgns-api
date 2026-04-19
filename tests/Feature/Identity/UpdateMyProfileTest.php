<?php

declare(strict_types=1);

beforeEach(function () {
    $this->user = $this->create_user(['email' => 'user@example.com']);
    $this->actingAs($this->user, 'stateful-api');
});

it('updates profile with partial fields (name + phone)', function () {
    $response = $this->patchJson('/api/v2/identity/me', [
        'name'  => 'New Name',
        'phone' => '+1 555-9999',
    ]);
    $response->assertStatus(200)
             ->assertJsonPath('data.name', 'New Name')
             ->assertJsonPath('data.phone', '+1 555-9999')
             ->assertJsonPath('data.email', 'user@example.com');
});

it('updates profile with default_locale change', function () {
    $response = $this->patchJson('/api/v2/identity/me', [
        'default_locale' => 'es',
    ]);
    $response->assertStatus(200)
             ->assertJsonPath('data.defaultLocale', 'es');
});

it('returns 400 validation error with invalid default_locale', function () {
    $response = $this->patchJson('/api/v2/identity/me', [
        'default_locale' => 'xx',
    ]);
    $response->assertStatus(400)
             ->assertJsonPath('error.code', 'validation_failed');
});

it('returns 400 validation error with empty body', function () {
    $response = $this->patchJson('/api/v2/identity/me', []);
    $response->assertStatus(400)
             ->assertJsonPath('error.code', 'validation_failed');
});

it('response shape matches UserResource with defaultLocale', function () {
    $response = $this->patchJson('/api/v2/identity/me', [
        'name' => 'Updated',
    ]);
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'data' => [
                     'id',
                     'email',
                     'name',
                     'active',
                     'emailVerified',
                     'phone',
                     'country',
                     'hasGoogleLogin',
                     'passwordResetRequired',
                     'defaultLocale',
                     'createdAt',
                 ],
             ]);
});
