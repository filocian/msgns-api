<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\PermissionRegistrar;
use Src\Ai\Infrastructure\Listeners\AssignFreeAiPermissionListener;
use Src\Identity\Domain\Events\UserActivated;
use Src\Identity\Domain\Permissions\DomainPermissions;

beforeEach(function (): void {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->artisan('rbac:reconcile')->assertExitCode(0);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

describe('AssignFreeAiPermissionListener', function (): void {

    it('assigns ai.free-preview permission when UserActivated event fires', function (): void {
        $user = $this->create_user(['email' => 'activate@test.com']);
        expect($user->hasPermissionTo(DomainPermissions::AI_FREE_PREVIEW))->toBeFalse();

        event(new UserActivated($user->id, 1));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        expect($user->fresh()->hasPermissionTo(DomainPermissions::AI_FREE_PREVIEW))->toBeTrue();
    });

    it('is idempotent — does not error if user already has ai.free-preview', function (): void {
        $user = $this->create_user(['email' => 'idempotent@test.com']);
        $user->givePermissionTo(DomainPermissions::AI_FREE_PREVIEW);

        expect(fn () => event(new UserActivated($user->id, 1)))->not->toThrow(\Throwable::class);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        expect($user->fresh()->hasPermissionTo(DomainPermissions::AI_FREE_PREVIEW))->toBeTrue();
    });

    it('listener is registered and handles UserActivated via the event system', function (): void {
        Event::listen(UserActivated::class, AssignFreeAiPermissionListener::class);

        $user = $this->create_user(['email' => 'listener-registered@test.com']);
        expect($user->hasPermissionTo(DomainPermissions::AI_FREE_PREVIEW))->toBeFalse();

        event(new UserActivated($user->id, 1));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        expect($user->fresh()->hasPermissionTo(DomainPermissions::AI_FREE_PREVIEW))->toBeTrue();
    });
});
