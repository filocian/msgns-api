<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Domain\DataTransfer\GenerateProductsInputItem;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Entities\ProductType;
use Src\Products\Domain\Ports\PasswordGeneratorPort;
use Src\Products\Domain\Services\ProductGenerationService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

// ─── Helpers ───────────────────────────────────────────────────────────────────

/**
 * @param array{id?: int, primaryModel?: string, secondaryModel?: string|null, description?: string|null} $overrides
 */
function makeProductType(array $overrides = []): ProductType
{
    return ProductType::fromPersistence(
        id: $overrides['id'] ?? 1,
        code: 'T-001',
        name: 'Test Type',
        primaryModel: $overrides['primaryModel'] ?? 'P-XX',
        secondaryModel: $overrides['secondaryModel'] ?? null,
        createdAt: new DateTimeImmutable(),
        updatedAt: new DateTimeImmutable(),
        description: $overrides['description'] ?? null,
    );
}

// ─── Tests ─────────────────────────────────────────────────────────────────────

describe('ProductGenerationService', function () {

    it('creates the correct number of products for a single-model type (FR-003)', function () {
        /** @var MockInterface&PasswordGeneratorPort $pwGen */
        $pwGen = Mockery::mock(PasswordGeneratorPort::class);
        $pwGen->shouldReceive('generate')->andReturn('PASS123456AB');

        $service = new ProductGenerationService($pwGen);
        $type = makeProductType(['primaryModel' => 'P-XX', 'secondaryModel' => null]);

        $products = $service->buildProducts(
            typeMap: [1 => $type],
            items: [new GenerateProductsInputItem(typeId: 1, quantity: 3)],
            passwordLength: 12,
        );

        expect($products)->toHaveCount(3);
        expect(array_map(fn ($p) => $p->model->value, $products))->each->toBe('P-XX');
    });

    it('creates 2 products per unit for dual-model types, not linked (FR-003)', function () {
        /** @var MockInterface&PasswordGeneratorPort $pwGen */
        $pwGen = Mockery::mock(PasswordGeneratorPort::class);
        $pwGen->shouldReceive('generate')->andReturn('PASS123456AB');

        $service = new ProductGenerationService($pwGen);
        $type = makeProductType([
            'primaryModel' => 'P-GG-IG-RC google',
            'secondaryModel' => 'P-GG-IG-RC instagram',
        ]);

        $products = $service->buildProducts(
            typeMap: [1 => $type],
            items: [new GenerateProductsInputItem(typeId: 1, quantity: 2)],
            passwordLength: 12,
        );

        expect($products)->toHaveCount(4);

        $models = array_map(fn ($p) => $p->model->value, $products);
        expect($models)->toContain('P-GG-IG-RC google');
        expect($models)->toContain('P-GG-IG-RC instagram');

        // None should be linked
        foreach ($products as $product) {
            expect($product->linkedToProductId)->toBeNull();
        }
    });

    it('sets active=true, userId=null on all products (FR-005)', function () {
        /** @var MockInterface&PasswordGeneratorPort $pwGen */
        $pwGen = Mockery::mock(PasswordGeneratorPort::class);
        $pwGen->shouldReceive('generate')->andReturn('PASS123456AB');

        $service = new ProductGenerationService($pwGen);
        $type = makeProductType();

        $products = $service->buildProducts(
            typeMap: [1 => $type],
            items: [new GenerateProductsInputItem(typeId: 1, quantity: 2)],
            passwordLength: 12,
        );

        foreach ($products as $product) {
            expect($product->active)->toBeTrue();
            expect($product->userId)->toBeNull();
            expect($product->linkedToProductId)->toBeNull();
        }
    });

    it('uses item description over ProductType description (FR-006)', function () {
        /** @var MockInterface&PasswordGeneratorPort $pwGen */
        $pwGen = Mockery::mock(PasswordGeneratorPort::class);
        $pwGen->shouldReceive('generate')->andReturn('PASS123456AB');

        $service = new ProductGenerationService($pwGen);
        $type = makeProductType(['description' => 'Type default desc']);

        $products = $service->buildProducts(
            typeMap: [1 => $type],
            items: [new GenerateProductsInputItem(typeId: 1, quantity: 1, description: 'Custom desc')],
            passwordLength: 12,
        );

        expect($products[0]->description?->value)->toBe('Custom desc');
    });

    it('falls back to ProductType description when item has none (FR-006)', function () {
        /** @var MockInterface&PasswordGeneratorPort $pwGen */
        $pwGen = Mockery::mock(PasswordGeneratorPort::class);
        $pwGen->shouldReceive('generate')->andReturn('PASS123456AB');

        $service = new ProductGenerationService($pwGen);
        $type = makeProductType(['description' => 'Fallback desc']);

        $products = $service->buildProducts(
            typeMap: [1 => $type],
            items: [new GenerateProductsInputItem(typeId: 1, quantity: 1)],
            passwordLength: 12,
        );

        expect($products[0]->description?->value)->toBe('Fallback desc');
    });

    it('calls password generator with the configured length (FR-004)', function () {
        /** @var MockInterface&PasswordGeneratorPort $pwGen */
        $pwGen = Mockery::mock(PasswordGeneratorPort::class);
        $pwGen->shouldReceive('generate')
            ->twice()
            ->with(16)
            ->andReturn('ABCDEFGHIJKLMNOP');

        $service = new ProductGenerationService($pwGen);
        $type = makeProductType(['secondaryModel' => 'P-YY']); // dual-model → 2 passwords per unit

        $service->buildProducts(
            typeMap: [1 => $type],
            items: [new GenerateProductsInputItem(typeId: 1, quantity: 1)],
            passwordLength: 16,
        );
    });

    it('buildResult groups products by model and builds redirect URLs correctly (FR-007/008)', function () {
        /** @var MockInterface&PasswordGeneratorPort $pwGen */
        $pwGen = Mockery::mock(PasswordGeneratorPort::class);
        $pwGen->shouldReceive('generate')->andReturn('ABC123');

        $service = new ProductGenerationService($pwGen);
        $type = makeProductType(['primaryModel' => 'P-XX', 'secondaryModel' => null]);

        $products = $service->buildProducts(
            typeMap: [1 => $type],
            items: [new GenerateProductsInputItem(typeId: 1, quantity: 2)],
            passwordLength: 6,
        );

        // Simulate post-insert ID assignment using withAssignedId()
        $hydrated = [];
        foreach ([$products[0], $products[1]] as $i => $product) {
            $withId = $product->withAssignedId(10 + $i);
            $withId->generateDefaultName();
            $hydrated[] = $withId;
        }

        $result = $service->buildResult($hydrated, 'https://app.test.local');

        expect($result->totalCount)->toBe(2);
        expect($result->productsByTypeCode)->toHaveKey('P-XX');
        expect($result->productsByTypeCode['P-XX'])->toHaveCount(2);
        expect($result->productsByTypeCode['P-XX'][0]->redirectUrl)->toBe('https://app.test.local/ABC123');
    });
});
