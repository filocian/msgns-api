<?php

declare(strict_types=1);

use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Support\Facades\DB;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Infrastructure\Persistence\NullProductUsageAdapter;

/**
 * @param array<string, mixed> $overrides
 */
function createMsgCrudProductType(array $overrides = []): int
{
    $uid = uniqid();

    return DB::table('product_types')->insertGetId(array_merge([
        'code' => 'MSG-TYPE-' . $uid,
        'name' => 'Msg Type ' . $uid,
        'image_ref' => 'MSG-TYPE-' . $uid,
        'primary_model' => 'whatsapp',
        'secondary_model' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

/**
 * @param array<string, mixed> $overrides
 */
function createMsgCrudProduct(array $overrides = []): int
{
    $productTypeId = $overrides['product_type_id'] ?? createMsgCrudProductType();

    return DB::table('products')->insertGetId(array_merge([
        'product_type_id' => $productTypeId,
        'user_id' => null,
        'model' => 'whatsapp',
        'linked_to_product_id' => null,
        'password' => 'secret-pass',
        'target_url' => null,
        'usage' => 0,
        'name' => 'Message CRUD Product',
        'description' => null,
        'active' => true,
        'configuration_status' => ConfigurationStatus::COMPLETED,
        'assigned_at' => now(),
        'size' => null,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ], $overrides));
}

function createMsgCrudPhone(int $productId, string $phone = '600000000', string $prefix = '+34'): int
{
    return DB::table('whatsapp_phones')->insertGetId([
        'product_id' => $productId,
        'phone' => $phone,
        'prefix' => $prefix,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function getLocaleId(string $code): int
{
    return (int) DB::table('whatsapp_locales')->where('code', $code)->value('id');
}

function createMsgCrudMessage(int $productId, int $phoneId, string $localeCode, string $message = 'Hello', bool $default = false): int
{
    $localeId = getLocaleId($localeCode);

    return DB::table('whatsapp_messages')->insertGetId([
        'product_id' => $productId,
        'phone_id' => $phoneId,
        'locale_id' => $localeId,
        'message' => $message,
        'default' => $default,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(ProductConfigurationStatusSeeder::class);

    $this->user = $this->create_user(['email' => 'whatsapp-message-crud@example.com']);
    $this->actingAs($this->user, 'stateful-api');

    $this->app->bind(ProductUsagePort::class, NullProductUsageAdapter::class);
});

describe('POST /api/v2/products/{id}/whatsapp/messages', function () {
    it('adds a message to a phone', function () {
        $productId = createMsgCrudProduct();
        $phoneId = createMsgCrudPhone($productId);

        $response = $this->postJson("/api/v2/products/{$productId}/whatsapp/messages", [
            'phone_id' => $phoneId,
            'locale_code' => 'en_US',
            'message' => 'Hello, I want information',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.message.productId', $productId)
            ->assertJsonPath('data.message.phoneId', $phoneId)
            ->assertJsonPath('data.message.localeCode', 'en_US')
            ->assertJsonPath('data.message.message', 'Hello, I want information')
            ->assertJsonPath('data.message.isDefault', false);

        $this->assertDatabaseHas('whatsapp_messages', [
            'product_id' => $productId,
            'phone_id' => $phoneId,
            'message' => 'Hello, I want information',
            'default' => false,
        ]);
    });

    it('returns 422 when locale_code is invalid', function () {
        $productId = createMsgCrudProduct();
        $phoneId = createMsgCrudPhone($productId);

        $this->postJson("/api/v2/products/{$productId}/whatsapp/messages", [
            'phone_id' => $phoneId,
            'locale_code' => 'INVALID_LOCALE',
            'message' => 'Hello',
        ])->assertStatus(422);
    });

    it('returns 409 when locale is duplicated on the same phone', function () {
        $productId = createMsgCrudProduct();
        $phoneId = createMsgCrudPhone($productId);
        createMsgCrudMessage($productId, $phoneId, 'es_ES', 'Existing message');

        $this->postJson("/api/v2/products/{$productId}/whatsapp/messages", [
            'phone_id' => $phoneId,
            'locale_code' => 'es_ES',
            'message' => 'Duplicate locale message',
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'whatsapp_duplicate_locale');
    });

    it('returns 404 when product does not exist', function () {
        $this->postJson('/api/v2/products/999999/whatsapp/messages', [
            'phone_id' => 1,
            'locale_code' => 'en_US',
            'message' => 'Hello',
        ])->assertStatus(404);
    });

    it('returns 404 when phone does not belong to the product', function () {
        $productId1 = createMsgCrudProduct();
        $productId2 = createMsgCrudProduct();
        $phoneId = createMsgCrudPhone($productId2);

        $this->postJson("/api/v2/products/{$productId1}/whatsapp/messages", [
            'phone_id' => $phoneId,
            'locale_code' => 'en_US',
            'message' => 'Hello',
        ])->assertStatus(404);
    });

    it('returns 422 when required fields are missing', function () {
        $productId = createMsgCrudProduct();

        $this->postJson("/api/v2/products/{$productId}/whatsapp/messages", [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createMsgCrudProduct();
        auth()->guard('stateful-api')->logout();

        $this->postJson("/api/v2/products/{$productId}/whatsapp/messages", [
            'phone_id' => 1,
            'locale_code' => 'en_US',
            'message' => 'Hello',
        ])->assertStatus(401);
    });
});

describe('DELETE /api/v2/products/{id}/whatsapp/messages/{messageId}', function () {
    it('removes a non-default message', function () {
        $productId = createMsgCrudProduct();
        $phoneId = createMsgCrudPhone($productId);
        $defaultMsgId = createMsgCrudMessage($productId, $phoneId, 'es_ES', 'Default msg', true);
        $nonDefaultMsgId = createMsgCrudMessage($productId, $phoneId, 'en_US', 'Extra msg', false);

        $this->deleteJson("/api/v2/products/{$productId}/whatsapp/messages/{$nonDefaultMsgId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('whatsapp_messages', ['id' => $nonDefaultMsgId]);
        $this->assertDatabaseHas('whatsapp_messages', ['id' => $defaultMsgId]);
    });

    it('returns 409 when trying to delete a default message (guardrail)', function () {
        $productId = createMsgCrudProduct();
        $phoneId = createMsgCrudPhone($productId);
        $defaultMsgId = createMsgCrudMessage($productId, $phoneId, 'es_ES', 'Default msg', true);

        $this->deleteJson("/api/v2/products/{$productId}/whatsapp/messages/{$defaultMsgId}")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'whatsapp_default_message_removal');

        $this->assertDatabaseHas('whatsapp_messages', ['id' => $defaultMsgId]);
    });

    it('returns 404 when message does not exist', function () {
        $productId = createMsgCrudProduct();

        $this->deleteJson("/api/v2/products/{$productId}/whatsapp/messages/999999")
            ->assertStatus(404);
    });

    it('returns 404 when message belongs to a different product', function () {
        $productId1 = createMsgCrudProduct();
        $productId2 = createMsgCrudProduct();
        $phoneId = createMsgCrudPhone($productId2);
        $msgId = createMsgCrudMessage($productId2, $phoneId, 'es_ES');

        $this->deleteJson("/api/v2/products/{$productId1}/whatsapp/messages/{$msgId}")
            ->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createMsgCrudProduct();
        auth()->guard('stateful-api')->logout();

        $this->deleteJson("/api/v2/products/{$productId}/whatsapp/messages/1")
            ->assertStatus(401);
    });
});

describe('PATCH /api/v2/products/{id}/whatsapp/messages/{messageId}/default', function () {
    it('sets a message as default and clears previous defaults', function () {
        $productId = createMsgCrudProduct();
        $phoneId = createMsgCrudPhone($productId);
        $msg1Id = createMsgCrudMessage($productId, $phoneId, 'es_ES', 'Spanish msg', true);
        $msg2Id = createMsgCrudMessage($productId, $phoneId, 'en_US', 'English msg', false);

        $response = $this->patchJson("/api/v2/products/{$productId}/whatsapp/messages/{$msg2Id}/default");

        $response->assertOk()
            ->assertJsonPath('data.message.id', $msg2Id)
            ->assertJsonPath('data.message.isDefault', true);

        // Previous default should be cleared
        $this->assertDatabaseHas('whatsapp_messages', [
            'id' => $msg1Id,
            'default' => false,
        ]);

        // New default should be set
        $this->assertDatabaseHas('whatsapp_messages', [
            'id' => $msg2Id,
            'default' => true,
        ]);
    });

    it('clears defaults across phones for the same product (product-level scope)', function () {
        $productId = createMsgCrudProduct();
        $phone1Id = createMsgCrudPhone($productId, '600000001', '+34');
        $phone2Id = createMsgCrudPhone($productId, '600000002', '+34');
        $msg1Id = createMsgCrudMessage($productId, $phone1Id, 'es_ES', 'Phone 1 default', true);
        $msg2Id = createMsgCrudMessage($productId, $phone2Id, 'en_US', 'Phone 2 msg', false);

        $this->patchJson("/api/v2/products/{$productId}/whatsapp/messages/{$msg2Id}/default")
            ->assertOk();

        // Default on phone 1 should be cleared (product-level scope)
        $this->assertDatabaseHas('whatsapp_messages', [
            'id' => $msg1Id,
            'default' => false,
        ]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'id' => $msg2Id,
            'default' => true,
        ]);
    });

    it('returns 404 when message does not exist', function () {
        $productId = createMsgCrudProduct();

        $this->patchJson("/api/v2/products/{$productId}/whatsapp/messages/999999/default")
            ->assertStatus(404);
    });

    it('returns 404 when message belongs to a different product', function () {
        $productId1 = createMsgCrudProduct();
        $productId2 = createMsgCrudProduct();
        $phoneId = createMsgCrudPhone($productId2);
        $msgId = createMsgCrudMessage($productId2, $phoneId, 'es_ES');

        $this->patchJson("/api/v2/products/{$productId1}/whatsapp/messages/{$msgId}/default")
            ->assertStatus(404);
    });

    it('returns 401 when unauthenticated', function () {
        $productId = createMsgCrudProduct();
        auth()->guard('stateful-api')->logout();

        $this->patchJson("/api/v2/products/{$productId}/whatsapp/messages/1/default")
            ->assertStatus(401);
    });
});
