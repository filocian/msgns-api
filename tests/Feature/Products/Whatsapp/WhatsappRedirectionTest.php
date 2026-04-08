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
function createWhatsappRedirProductType(array $overrides = []): int
{
    $uid = uniqid();

    return DB::table('product_types')->insertGetId(array_merge([
        'code' => 'RD-TYPE-' . $uid,
        'name' => 'Redir Type ' . $uid,
        'image_ref' => 'RD-TYPE-' . $uid,
        'primary_model' => 'whatsapp',
        'secondary_model' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

/**
 * @param array<string, mixed> $overrides
 */
function createWhatsappRedirProduct(array $overrides = []): int
{
    $productTypeId = $overrides['product_type_id'] ?? createWhatsappRedirProductType();

    return DB::table('products')->insertGetId(array_merge([
        'product_type_id' => $productTypeId,
        'user_id' => null,
        'model' => 'whatsapp',
        'linked_to_product_id' => null,
        'password' => 'secret',
        'target_url' => null,
        'usage' => 0,
        'name' => 'Redirection Product',
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

function createWhatsappRedirPhone(int $productId, string $phone = '600000000', string $prefix = '+34'): int
{
    return DB::table('whatsapp_phones')->insertGetId([
        'product_id' => $productId,
        'phone' => $phone,
        'prefix' => $prefix,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function getRedirLocaleId(string $code): int
{
    return (int) DB::table('whatsapp_locales')->where('code', $code)->value('id');
}

function createWhatsappRedirMessage(int $productId, int $phoneId, string $localeCode, string $message = 'Hello', bool $default = false): int
{
    $localeId = getRedirLocaleId($localeCode);

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
    $this->seed(ProductConfigurationStatusSeeder::class);

    $this->app->bind(ProductUsagePort::class, NullProductUsageAdapter::class);
});

describe('WhatsApp redirection resolution via GET /api/v2/products/{id}/{password}/redirection-target', function () {
    it('resolves by default message when one is set', function () {
        $productId = createWhatsappRedirProduct();
        $phoneId = createWhatsappRedirPhone($productId, '612345678', '+34');
        createWhatsappRedirMessage($productId, $phoneId, 'es_ES', 'Hola, quiero info', true);
        createWhatsappRedirMessage($productId, $phoneId, 'en_US', 'Hello, I want info', false);

        $response = $this->getJson("/api/v2/products/{$productId}/secret/redirection-target");

        $response->assertOk()
            ->assertJsonPath('data.type', 'external_url');

        $url = $response->json('data.url');
        expect($url)->toContain('https://api.whatsapp.com/send/')
            ->and($url)->toContain('phone=34612345678')
            ->and($url)->toContain('text=' . urlencode('Hola, quiero info'));
    });

    it('resolves by locale match when no default is set', function () {
        $productId = createWhatsappRedirProduct();
        $phoneId = createWhatsappRedirPhone($productId, '611111111', '+34');
        createWhatsappRedirMessage($productId, $phoneId, 'es_ES', 'Mensaje espanol', false);
        createWhatsappRedirMessage($productId, $phoneId, 'en_US', 'English message', false);

        $response = $this->getJson(
            "/api/v2/products/{$productId}/secret/redirection-target",
            ['Accept-Language' => 'en-US,en;q=0.9']
        );

        $response->assertOk();

        $url = $response->json('data.url');
        expect($url)->toContain('text=' . urlencode('English message'));
    });

    it('resolves to the first message as fallback when no default and no locale match', function () {
        $productId = createWhatsappRedirProduct();
        $phoneId = createWhatsappRedirPhone($productId, '622222222', '+49');
        createWhatsappRedirMessage($productId, $phoneId, 'de_DE', 'German fallback', false);
        createWhatsappRedirMessage($productId, $phoneId, 'fr_FR', 'French message', false);

        $response = $this->getJson(
            "/api/v2/products/{$productId}/secret/redirection-target",
            ['Accept-Language' => 'ja-JP']
        );

        $response->assertOk();

        $url = $response->json('data.url');
        // Fallback: first message by ID order
        expect($url)->toContain('text=' . urlencode('German fallback'));
    });

    it('returns 422 when product has no messages configured', function () {
        $productId = createWhatsappRedirProduct();

        $this->getJson("/api/v2/products/{$productId}/secret/redirection-target")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'product_missing_target_url');
    });

    it('returns 404 when product does not exist or password is wrong', function () {
        $productId = createWhatsappRedirProduct();

        $this->getJson("/api/v2/products/{$productId}/wrong-password/redirection-target")
            ->assertStatus(404);
    });

    it('returns 422 when product is not active', function () {
        $productId = createWhatsappRedirProduct(['active' => false]);

        $this->getJson("/api/v2/products/{$productId}/secret/redirection-target")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'product_not_active');
    });

    it('returns 422 when product configuration is incomplete', function () {
        $productId = createWhatsappRedirProduct(['configuration_status' => ConfigurationStatus::ASSIGNED]);

        $this->getJson("/api/v2/products/{$productId}/secret/redirection-target")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'product_incomplete_configuration');
    });

    it('prefers default over locale match', function () {
        $productId = createWhatsappRedirProduct();
        $phone1Id = createWhatsappRedirPhone($productId, '633333333', '+34');
        $phone2Id = createWhatsappRedirPhone($productId, '644444444', '+34');
        createWhatsappRedirMessage($productId, $phone1Id, 'es_ES', 'Spanish locale match', false);
        createWhatsappRedirMessage($productId, $phone2Id, 'en_US', 'Default English', true);

        $response = $this->getJson(
            "/api/v2/products/{$productId}/secret/redirection-target",
            ['Accept-Language' => 'es-ES,es;q=0.9']
        );

        $response->assertOk();

        $url = $response->json('data.url');
        // Default takes priority over locale match
        expect($url)->toContain('text=' . urlencode('Default English'))
            ->and($url)->toContain('phone=34644444444');
    });
});
