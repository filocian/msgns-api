<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Src\Identity\Domain\Events\UserRegistered;
use Src\Identity\Domain\Ports\VerificationTokenPort;
use Tests\Support\AuthContractAssertions;



it('signs up a new user successfully', function () {
    $response = $this->postWithHeaders('/api/v2/identity/sign-up', [
        'email'           => 'new@example.com',
        'name'            => 'New User',
        'password'        => 'Pass123!',
        'repeat_password' => 'Pass123!',
        'country'         => 'ES',
        'phone'           => '+34123456789',
        'language'        => 'es',
        'user_agent'      => 'feature-test-agent',
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('data.user.email', 'new@example.com')
             ->assertJsonPath('data.user.country', 'ES')
             ->assertJsonPath('data.user.phone', '+34123456789');

    AuthContractAssertions::assertAuthSuccessContract($response->json(), 'signup-success.json');

    $createdUser = User::query()->where('email', 'new@example.com')->firstOrFail();
    expect($createdUser->hasRole('user'))->toBeTrue();

    $this->assertAuthenticated('stateful-api');

    $this->assertDatabaseHas('users', [
        'email' => 'new@example.com',
        'country' => 'ES',
        'phone' => '+34123456789',
        'default_locale' => 'es_ES',
        'user_agent' => 'feature-test-agent',
    ]);
});

it('dispatches registration event on successful signup', function () {
    Event::fake([UserRegistered::class]);

    $this->postWithHeaders('/api/v2/identity/sign-up', [
        'email' => 'event-signup@example.com',
        'name' => 'Event Signup',
        'password' => 'Pass123!',
        'repeat_password' => 'Pass123!',
    ])->assertStatus(200);

    Event::assertDispatched(UserRegistered::class);
});

it('does not fail signup when verification token generation fails', function () {
    $tokenPort = Mockery::mock(VerificationTokenPort::class);
    $tokenPort->shouldReceive('generate')->once()->andThrow(new RuntimeException('token generation failed'));
    $this->app->instance(VerificationTokenPort::class, $tokenPort);

    $response = $this->postWithHeaders('/api/v2/identity/sign-up', [
        'email' => 'verification-failure@example.com',
        'name' => 'Verification Failure',
        'password' => 'Pass123!',
        'repeat_password' => 'Pass123!',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.user.email', 'verification-failure@example.com');
});

it('returns 422 for duplicate email', function () {
    $this->create_user(['email' => 'existing@example.com']);

    $response = $this->postWithHeaders('/api/v2/identity/sign-up', [
        'email'           => 'existing@example.com',
        'name'            => 'Another User',
        'password'        => 'Pass123!',
        'repeat_password' => 'Pass123!',
    ]);
    $response->assertStatus(422);
    AuthContractAssertions::assertAuthErrorContract($response->json(), 'signup-error.json');
    $response->assertJsonPath('error.code', 'email_already_registered');
});

it('maps language to locale with en_UK fallback', function (string|null $language, string $expectedLocale) {
    $response = $this->postWithHeaders('/api/v2/identity/sign-up', [
        'email'           => sprintf('%s@example.com', uniqid('user_', true)),
        'name'            => 'Locale User',
        'password'        => 'Pass123!',
        'repeat_password' => 'Pass123!',
        'language'        => $language,
    ]);

    $response->assertStatus(200);

    $email = $response->json('data.user.email');

    $this->assertDatabaseHas('users', [
        'email' => $email,
        'default_locale' => $expectedLocale,
    ]);
})->with([
    ['ca', 'ca_ES'],
    ['es', 'es_ES'],
    ['fr', 'fr_FR'],
    ['de', 'de_DE'],
    ['it', 'it_IT'],
    ['en', 'en_UK'],
    ['xx', 'en_UK'],
    [null, 'en_UK'],
]);

it('returns 400 for missing required fields', function () {
    $response = $this->postWithHeaders('/api/v2/identity/sign-up', []);

    $response->assertStatus(400)
        ->assertJsonPath('error.code', 'validation_failed');
    AuthContractAssertions::assertAuthErrorContract($response->json(), 'signup-validation-error.json');
});

it('returns 400 for password mismatch', function () {
    $response = $this->postWithHeaders('/api/v2/identity/sign-up', [
        'email'           => 'new@example.com',
        'name'            => 'New User',
        'password'        => 'Pass123!',
        'repeat_password' => 'Different123!',
    ]);
    $response->assertStatus(400)
        ->assertJsonPath('error.code', 'validation_failed');
    AuthContractAssertions::assertAuthErrorContract($response->json(), 'signup-validation-error.json');
});

it('returns 400 for malformed country, phone and language payload', function () {
    $response = $this->postWithHeaders('/api/v2/identity/sign-up', [
        'email'           => 'new@example.com',
        'name'            => 'New User',
        'password'        => 'Pass123!',
        'repeat_password' => 'Pass123!',
        'country'         => 'esp',
        'phone'           => '123456',
        'language'        => 'spa',
    ]);

    $response->assertStatus(400)
        ->assertJsonPath('error.code', 'validation_failed');
    AuthContractAssertions::assertAuthErrorContract($response->json(), 'signup-validation-error.json');
});

it('supports /api/v2/identity/signup compatibility alias', function () {
    $response = $this->postWithHeaders('/api/v2/identity/signup', [
        'email'           => 'alias@example.com',
        'name'            => 'Alias User',
        'password'        => 'Pass123!',
        'repeat_password' => 'Pass123!',
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('data.user.email', 'alias@example.com');
    AuthContractAssertions::assertAuthSuccessContract($response->json(), 'signup-success.json');
});

it('keeps equivalent schema semantics for both v2 signup aliases', function () {
    $signUpPayload = [
        'name' => 'Alias A',
        'email' => sprintf('alias-a-%s@example.com', uniqid()),
        'password' => 'Pass123456!',
        'repeat_password' => 'Pass123456!',
    ];
    $signUpAliasPayload = [
        'name' => 'Alias B',
        'email' => sprintf('alias-b-%s@example.com', uniqid()),
        'password' => 'Pass123456!',
        'repeat_password' => 'Pass123456!',
    ];

    $canonical = $this->postWithHeaders('/api/v2/identity/sign-up', $signUpPayload);
    $alias = $this->postWithHeaders('/api/v2/identity/signup', $signUpAliasPayload);

    $canonical->assertOk();
    $alias->assertOk();

    $canonicalPayload = $canonical->json();
    $aliasPayload = $alias->json();

    AuthContractAssertions::assertAuthSuccessContract($canonicalPayload, 'signup-success.json');
    AuthContractAssertions::assertAuthSuccessContract($aliasPayload, 'signup-success.json');
    AuthContractAssertions::assertContractParityByPaths(
        $canonicalPayload,
        $aliasPayload,
        [
            'data.user.id',
            'data.user.email',
            'data.user.name',
            'data.user.active',
            'data.user.emailVerified',
            'data.user.phone',
            'data.user.country',
            'data.user.hasGoogleLogin',
            'data.user.passwordResetRequired',
            'data.user.createdAt',
        ]
    );
});
