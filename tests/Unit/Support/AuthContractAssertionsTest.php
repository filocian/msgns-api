<?php

declare(strict_types=1);

use PHPUnit\Framework\ExpectationFailedException;
use Tests\Support\AuthContractAssertions;

function authSuccessPayloadFixture(): array
{
    return [
        'data' => [
            'user' => [
                'id' => 1,
                'email' => 'contract@example.com',
                'name' => 'Contract User',
                'active' => true,
                'emailVerified' => false,
                'phone' => '+34123456789',
                'country' => 'ES',
                'hasGoogleLogin' => false,
                'passwordResetRequired' => false,
                'createdAt' => '2026-01-01T00:00:00Z',
            ],
        ],
    ];
}

it('fails when a required locked key is removed', function () {
    $payload = authSuccessPayloadFixture();
    unset($payload['data']['user']['createdAt']);

    expect(fn() => AuthContractAssertions::assertAuthSuccessContract($payload, 'signup-success.json'))
        ->toThrow(ExpectationFailedException::class, 'data.user.createdAt');
});

it('fails when a required locked key is renamed', function () {
    $payload = authSuccessPayloadFixture();
    $payload['data']['user']['email_address'] = $payload['data']['user']['email'];
    unset($payload['data']['user']['email']);

    expect(fn() => AuthContractAssertions::assertAuthSuccessContract($payload, 'signup-success.json'))
        ->toThrow(ExpectationFailedException::class, 'data.user.email');
});

it('fails when a required locked key type changes', function () {
    $payload = authSuccessPayloadFixture();
    $payload['data']['user']['id'] = '1';

    expect(fn() => AuthContractAssertions::assertAuthSuccessContract($payload, 'signup-success.json'))
        ->toThrow(ExpectationFailedException::class, 'expected integer, got string');
});

it('allows additive fields outside locked paths', function () {
    $payload = authSuccessPayloadFixture();
    $payload['data']['user']['avatarUrl'] = 'https://example.com/avatar.png';

    AuthContractAssertions::assertAuthSuccessContract($payload, 'signup-success.json');

    expect(true)->toBeTrue();
});
