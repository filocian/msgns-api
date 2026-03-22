<?php

declare(strict_types=1);

use Tests\Support\AuthContractAssertions;

it('keeps signup aliases compatible for v2 success payload locks', function () {
    $canonical = $this->postWithHeaders('/api/v2/identity/sign-up', [
        'name' => 'Canonical User',
        'email' => sprintf('canon-%s@example.com', uniqid()),
        'password' => 'Pass123456!',
        'repeat_password' => 'Pass123456!',
    ]);

    $alias = $this->postWithHeaders('/api/v2/identity/signup', [
        'name' => 'Alias User',
        'email' => sprintf('alias-%s@example.com', uniqid()),
        'password' => 'Pass123456!',
        'repeat_password' => 'Pass123456!',
    ]);

    $canonical->assertOk();
    $alias->assertOk();

    AuthContractAssertions::assertV2BaselineParity($canonical->json(), 'signup-success.json');
    AuthContractAssertions::assertV2BaselineParity($alias->json(), 'signup-success.json');
});

it('keeps signup aliases compatible for v2 domain-error payload locks', function () {
    $this->create_user(['email' => 'compat-duplicate@example.com']);

    $canonical = $this->postWithHeaders('/api/v2/identity/sign-up', [
        'email' => 'compat-duplicate@example.com',
        'name' => 'Duplicate User',
        'password' => 'Pass123456!',
        'repeat_password' => 'Pass123456!',
    ]);

    $alias = $this->postWithHeaders('/api/v2/identity/signup', [
        'email' => 'compat-duplicate@example.com',
        'name' => 'Duplicate Alias User',
        'password' => 'Pass123456!',
        'repeat_password' => 'Pass123456!',
    ]);

    $canonical->assertStatus(422);
    $alias->assertStatus(422);

    AuthContractAssertions::assertV2BaselineParity($canonical->json(), 'signup-error.json');
    AuthContractAssertions::assertV2BaselineParity($alias->json(), 'signup-error.json');
});

it('keeps login success payload compatible with v2 baseline', function () {
    $this->create_user([
        'email' => 'compat-login@example.com',
        'password' => 'Pass123456!',
        'country' => 'ES',
        'phone' => '+34123456789',
    ]);

    $response = $this->postWithHeaders('/api/v2/identity/login', [
        'email' => 'compat-login@example.com',
        'password' => 'Pass123456!',
    ]);

    $response->assertOk();

    AuthContractAssertions::assertV2BaselineParity($response->json(), 'login-success.json');
});

it('keeps login domain-error payload compatible with v2 baseline', function () {
    $response = $this->postWithHeaders('/api/v2/identity/login', [
        'email' => 'missing-user@example.com',
        'password' => 'WrongPassword123!',
    ]);

    $response->assertStatus(422);

    AuthContractAssertions::assertV2BaselineParity($response->json(), 'login-error.json');
});
