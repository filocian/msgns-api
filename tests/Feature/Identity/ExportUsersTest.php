<?php

declare(strict_types=1);

use App\Models\User;

const EXPORT_URL = '/api/v2/identity/admin/users/export';

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->artisan('rbac:reconcile')->assertExitCode(0);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->admin = $this->create_user(['email' => 'admin@export-test.com']);
    $adminRole = \Spatie\Permission\Models\Role::where('name', 'developer')->where('guard_name', 'stateful-api')->first();
    $this->admin->assignRole($adminRole);
    $this->actingAs($this->admin, 'stateful-api');
});

it('exports users as CSV with correct headers and structure', function () {
    $this->create_user(['email' => 'user1@test.com', 'name' => 'User One']);
    $this->create_user(['email' => 'user2@test.com', 'name' => 'User Two']);

    $response = $this->get(EXPORT_URL);

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($response->headers->get('Content-Disposition'))
        ->toContain('attachment; filename="users-export-')
        ->toContain('.csv"');

    $content = $response->streamedContent();

    // Remove BOM
    $content = substr($content, 3);
    $lines = array_filter(explode("\n", trim($content)));

    // Header + admin + 2 users = 4 lines (header + 3 data rows)
    expect(count($lines))->toBe(4);
});

it('emits CSV headers matching spec', function () {
    $response = $this->get(EXPORT_URL);
    $content = $response->streamedContent();

    // Remove BOM
    $content = substr($content, 3);
    $lines = explode("\n", trim($content));
    $headers = str_getcsv($lines[0]);

    expect($headers)->toBe([
        'id', 'name', 'email', 'phone', 'country',
        'default_locale', 'active', 'email_verified',
        'roles', 'created_at', 'updated_at',
    ]);
});

it('renders active field as yes or no', function () {
    $activeUser = $this->create_user(['email' => 'active@test.com', 'active' => true]);
    $inactiveUser = $this->create_user(['email' => 'inactive@test.com', 'active' => false]);

    $response = $this->get(EXPORT_URL);
    $content = substr($response->streamedContent(), 3);
    $lines = array_filter(explode("\n", trim($content)));
    array_shift($lines); // Remove header

    $rows = array_map('str_getcsv', array_values($lines));

    $activeValues = array_column($rows, 6); // active is index 6
    expect($activeValues)->toContain('yes');
    expect($activeValues)->toContain('no');
});

it('renders email_verified as yes or no', function () {
    $verified = $this->create_user([
        'email' => 'verified@test.com',
        'email_verified_at' => now(),
    ]);
    $unverified = $this->create_user([
        'email' => 'unverified@test.com',
        'email_verified_at' => null,
    ]);

    $response = $this->get(EXPORT_URL);
    $content = substr($response->streamedContent(), 3);
    $lines = array_filter(explode("\n", trim($content)));
    array_shift($lines);

    $rows = array_map('str_getcsv', array_values($lines));

    $verifiedValues = array_column($rows, 7); // email_verified is index 7
    expect($verifiedValues)->toContain('yes');
    expect($verifiedValues)->toContain('no');
});

it('renders roles comma-separated and alphabetically sorted', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $user = $this->create_user(['email' => 'multirole@test.com']);
    $devRole = $this->createRole('developer');
    $boRole = $this->createRole('backoffice');
    $user->assignRole($devRole);
    $user->assignRole($boRole);

    $response = $this->get(EXPORT_URL);
    $content = substr($response->streamedContent(), 3);
    $lines = array_filter(explode("\n", trim($content)));
    array_shift($lines);

    $rows = array_map('str_getcsv', array_values($lines));

    $found = false;
    foreach ($rows as $row) {
        if ($row[2] === 'multirole@test.com') {
            expect($row[8])->toBe('backoffice,developer');
            $found = true;
            break;
        }
    }
    expect($found)->toBeTrue();
});

it('filters by search param', function () {
    $this->create_user(['email' => 'alice@test.com', 'name' => 'Alice Smith']);
    $this->create_user(['email' => 'bob@test.com', 'name' => 'Bob Jones']);

    $response = $this->get(EXPORT_URL . '?search=alice');
    $content = substr($response->streamedContent(), 3);
    $lines = array_filter(explode("\n", trim($content)));
    array_shift($lines);

    $rows = array_map('str_getcsv', array_values($lines));
    $emails = array_column($rows, 2);

    expect($emails)->toContain('alice@test.com');
    expect($emails)->not->toContain('bob@test.com');
});

it('filters by active param', function () {
    $this->create_user(['email' => 'active1@test.com', 'active' => true]);
    $this->create_user(['email' => 'active2@test.com', 'active' => true]);
    $this->create_user(['email' => 'inactive1@test.com', 'active' => false]);

    $response = $this->get(EXPORT_URL . '?active=1');
    $content = substr($response->streamedContent(), 3);
    $lines = array_filter(explode("\n", trim($content)));
    array_shift($lines);

    $rows = array_map('str_getcsv', array_values($lines));
    $activeValues = array_column($rows, 6);

    foreach ($activeValues as $val) {
        expect($val)->toBe('yes');
    }
});

it('filters by role param', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $dev = $this->create_user(['email' => 'dev@test.com']);
    $devRole = $this->createRole('developer');
    $dev->assignRole($devRole);

    $bo = $this->create_user(['email' => 'bo@test.com']);
    $boRole = $this->createRole('backoffice');
    $bo->assignRole($boRole);

    $response = $this->get(EXPORT_URL . '?role=backoffice');
    $content = substr($response->streamedContent(), 3);
    $lines = array_filter(explode("\n", trim($content)));
    array_shift($lines);

    $rows = array_map('str_getcsv', array_values($lines));
    $emails = array_column($rows, 2);

    expect($emails)->toContain('bo@test.com');
    expect($emails)->not->toContain('dev@test.com');
});

it('filters by date range', function () {
    $old = $this->create_user(['email' => 'old@test.com']);
    $old->forceFill(['created_at' => '2026-01-01 12:00:00'])->save();

    $mid = $this->create_user(['email' => 'mid@test.com']);
    $mid->forceFill(['created_at' => '2026-02-15 12:00:00'])->save();

    $new = $this->create_user(['email' => 'new@test.com']);
    $new->forceFill(['created_at' => '2026-03-20 12:00:00'])->save();

    $response = $this->get(EXPORT_URL . '?created_from=2026-02-01&created_to=2026-02-28');
    $content = substr($response->streamedContent(), 3);
    $lines = array_filter(explode("\n", trim($content)));
    array_shift($lines);

    $rows = array_map('str_getcsv', array_values($lines));
    $emails = array_column($rows, 2);

    expect($emails)->toContain('mid@test.com');
    expect($emails)->not->toContain('old@test.com');
    expect($emails)->not->toContain('new@test.com');
});

it('applies combined filters with AND logic', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $alice = $this->create_user(['email' => 'alice.dev@test.com', 'name' => 'Alice Dev', 'active' => true]);
    $devRole = $this->createRole('developer');
    $alice->assignRole($devRole);

    $bob = $this->create_user(['email' => 'bob.dev@test.com', 'name' => 'Bob Dev', 'active' => false]);
    $bob->assignRole($devRole);

    $response = $this->get(EXPORT_URL . '?search=alice&active=1&role=developer');
    $content = substr($response->streamedContent(), 3);
    $lines = array_filter(explode("\n", trim($content)));
    array_shift($lines);

    $rows = array_map('str_getcsv', array_values($lines));
    $emails = array_column($rows, 2);

    expect($emails)->toContain('alice.dev@test.com');
    expect($emails)->not->toContain('bob.dev@test.com');
});

it('returns header only for empty result', function () {
    $response = $this->get(EXPORT_URL . '?search=nonexistent-user-xyz');

    $response->assertStatus(200);
    $content = substr($response->streamedContent(), 3);
    $lines = array_filter(explode("\n", trim($content)));

    // Only header row
    expect(count($lines))->toBe(1);
});

it('returns 401 for unauthenticated requests', function () {
    // Reset auth
    app('auth')->forgetGuards();

    $response = $this->withHeaders(['Accept' => 'application/json'])
                      ->get(EXPORT_URL);

    $response->assertStatus(401);
});

it('returns 403 for unauthorized users', function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $regular = $this->create_user(['email' => 'regular@test.com']);
    $this->actingAs($regular, 'stateful-api');

    $response = $this->getJson(EXPORT_URL);

    $response->assertStatus(403);
});

it('returns 400 for invalid role', function () {
    $response = $this->getJson(EXPORT_URL . '?role=nonexistent');
    $response->assertStatus(400)
             ->assertJsonPath('error.code', 'validation_error')
             ->assertJsonPath('error.context.errors.role.0', fn ($v) => str_contains($v, 'role'));
});

it('returns 400 for invalid date range', function () {
    $response = $this->getJson(EXPORT_URL . '?created_from=2026-03-20&created_to=2026-03-10');
    $response->assertStatus(400)
             ->assertJsonPath('error.code', 'validation_error')
             ->assertJsonPath('error.context.errors.created_to.0', fn ($v) => str_contains($v, 'created_to') || str_contains($v, 'created from'));
});

it('returns 400 for invalid date format', function () {
    $response = $this->getJson(EXPORT_URL . '?created_from=not-a-date');
    $response->assertStatus(400)
             ->assertJsonPath('error.code', 'validation_error')
             ->assertJsonPath('error.context.errors.created_from.0', fn ($v) => str_contains($v, 'created_from') || str_contains($v, 'created from'));
});

it('protects against CSV injection', function () {
    $this->create_user(['email' => 'inject@test.com', 'name' => '=CMD("calc")']);

    $response = $this->get(EXPORT_URL);
    $content = substr($response->streamedContent(), 3);
    $lines = array_filter(explode("\n", trim($content)));
    array_shift($lines);

    $rows = array_map('str_getcsv', array_values($lines));

    $found = false;
    foreach ($rows as $row) {
        if ($row[2] === 'inject@test.com') {
            expect($row[1])->toBe("'=CMD(\"calc\")");
            $found = true;
            break;
        }
    }
    expect($found)->toBeTrue();
});

it('prepends UTF-8 BOM to response', function () {
    $response = $this->get(EXPORT_URL);
    $content = $response->streamedContent();

    $bom = substr($content, 0, 3);
    expect($bom)->toBe("\xEF\xBB\xBF");
});
