<?php

declare(strict_types=1);

use Src\Identity\Domain\Permissions\DomainPermissions;
use Illuminate\Support\Facades\DB;

function createGenerationHistoryRecord(array $overrides = []): int
{
    return DB::table('generation_history')->insertGetId(array_merge([
        'generated_at' => '2026-04-02 12:30:00',
        'total_count' => 10,
        'summary' => json_encode([[ 
            'type_code' => 'QR_BASIC',
            'type_name' => 'QR Basic',
            'quantity' => 10,
            'size' => null,
            'description' => null,
        ]], JSON_THROW_ON_ERROR),
        'excel_blob' => 'xlsx-binary',
        'generated_by_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $this->user = $this->create_user(['email' => 'history@example.com']);
    $permission = $this->createPermission(DomainPermissions::PRODUCT_GENERATION);
    $this->user->givePermissionTo($permission);
    $this->actingAs($this->user, 'stateful-api');
});

describe('GET /api/v2/products/generations', function () {
    it('returns 200 with paginated generation history', function () {
        createGenerationHistoryRecord();

        $response = $this->getJson('/api/v2/products/generations');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']])
            ->assertJsonPath('meta.total', 1);
    });

    it('respects page and per_page query parameters and returns correct meta', function () {
        foreach (range(1, 20) as $index) {
            createGenerationHistoryRecord(['generated_at' => sprintf('2026-04-02 %02d:00:00', $index % 24)]);
        }

        $response = $this->getJson('/api/v2/products/generations?per_page=10&page=1');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.current_page', 1);
    });

    it('returns most recent record first', function () {
        $olderId = createGenerationHistoryRecord(['generated_at' => '2026-04-02 10:00:00']);
        $newerId = createGenerationHistoryRecord(['generated_at' => '2026-04-02 13:00:00']);

        $response = $this->getJson('/api/v2/products/generations');

        expect($response->json('data.0.id'))->toBe($newerId)
            ->and($response->json('data.1.id'))->toBe($olderId);
    });

    it('converts generated_at to requested timezone', function () {
        createGenerationHistoryRecord(['generated_at' => '2026-04-02 12:30:00']);

        $response = $this->getJson('/api/v2/products/generations?timezone=Europe/Madrid');

        $response->assertOk()
            ->assertJsonPath('data.0.generated_at', '2026-04-02T14:30:00+02:00');
    });

    it('returns UTC format when timezone is not provided', function () {
        createGenerationHistoryRecord(['generated_at' => '2026-04-02 12:30:00']);

        $response = $this->getJson('/api/v2/products/generations');

        $response->assertOk()
            ->assertJsonPath('data.0.generated_at', '2026-04-02T12:30:00+00:00');
    });

    it('returns 401 when no Bearer token provided', function () {
        auth()->guard('stateful-api')->logout();

        $this->getJson('/api/v2/products/generations')->assertStatus(401);
    });

    it('returns 403 when user lacks PRODUCT_GENERATION permission', function () {
        $user = $this->create_user(['email' => 'no-permission@example.com']);
        $this->actingAs($user, 'stateful-api');

        $this->getJson('/api/v2/products/generations')->assertStatus(403);
    });

    it('returns 422 for invalid timezone string', function () {
        $this->getJson('/api/v2/products/generations?timezone=Invalid/Zone')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['timezone']);
    });

    it('returns 422 for per_page exceeding 50', function () {
        $this->getJson('/api/v2/products/generations?per_page=100')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    });

    it('returns 422 for per_page=0', function () {
        $this->getJson('/api/v2/products/generations?per_page=0')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    });

    it('returns 200 with empty data array when no history records exist', function () {
        $this->getJson('/api/v2/products/generations')
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data');
    });

    it('includes generated_by object with id and email when user exists', function () {
        createGenerationHistoryRecord(['generated_by_id' => $this->user->id]);

        $response = $this->getJson('/api/v2/products/generations');

        $response->assertOk()
            ->assertJsonPath('data.0.generated_by.id', $this->user->id)
            ->assertJsonPath('data.0.generated_by.email', 'history@example.com');
    });

    it('returns null generated_by when user has been deleted', function () {
        $generatedBy = $this->create_user(['email' => 'deleted-history-user@example.com']);
        createGenerationHistoryRecord(['generated_by_id' => $generatedBy->id]);
        $generatedBy->delete();

        $response = $this->getJson('/api/v2/products/generations');

        $response->assertOk()
            ->assertJsonPath('data.0.generated_by', null);
    });

    it('omits excel_blob from response', function () {
        createGenerationHistoryRecord();

        $response = $this->getJson('/api/v2/products/generations');

        expect($response->json('data.0'))->not->toHaveKey('excel_blob');
    });

    it('returns empty data when page exceeds last_page', function () {
        foreach (range(1, 5) as $index) {
            createGenerationHistoryRecord(['generated_at' => sprintf('2026-04-02 0%d:00:00', $index)]);
        }

        $response = $this->getJson('/api/v2/products/generations?per_page=15&page=3');

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.current_page', 3)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 1);
    });
});

describe('GET /api/v2/products/generations/{id}/download', function () {
    it('returns 200 with correct headers and raw excel blob bytes', function () {
        $id = createGenerationHistoryRecord(['excel_blob' => 'xlsx-binary']);

        $response = $this->get('/api/v2/products/generations/' . $id . '/download');

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->and($response->headers->get('Content-Disposition'))->toBe('attachment; filename="generation-' . $id . '.xlsx"')
            ->and($response->getContent())->toBe('xlsx-binary');
    });

    it('returns 404 for non existent generation id', function () {
        $this->get('/api/v2/products/generations/9999/download')->assertStatus(404);
    });

    it('returns 401 when no Bearer token provided', function () {
        auth()->guard('stateful-api')->logout();

        $this->getJson('/api/v2/products/generations/1/download')->assertStatus(401);
    });

    it('returns 403 when user lacks PRODUCT_GENERATION permission', function () {
        $id = createGenerationHistoryRecord();
        $user = $this->create_user(['email' => 'no-download-permission@example.com']);
        $this->actingAs($user, 'stateful-api');

        $this->get('/api/v2/products/generations/' . $id . '/download')->assertStatus(403);
    });

    it('returns 404 for non numeric id', function () {
        $this->get('/api/v2/products/generations/not-a-number/download')->assertStatus(404);
    });
});
