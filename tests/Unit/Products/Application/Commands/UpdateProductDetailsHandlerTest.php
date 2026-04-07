<?php

declare(strict_types=1);

use Src\Identity\Domain\DTOs\RoleData;
use Src\Identity\Domain\Ports\RolePort;
use Src\Products\Application\Commands\UpdateProductDetails\UpdateProductDetailsCommand;
use Src\Products\Application\Commands\UpdateProductDetails\UpdateProductDetailsHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductDetailsService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Bus\PaginatedResult;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\Unauthorized;
use Src\Shared\Core\Errors\ValidationFailed;

final class InMemoryProductRepository implements ProductRepositoryPort
{
	public int $saveCalls = 0;

	public ?Product $lastSavedProduct = null;

	/** @param array<int, Product> $products */
	public function __construct(private array $products = []) {}

	public function findById(int $id): ?Product
	{
		return $this->products[$id] ?? null;
	}

	public function findByIdAndPassword(int $id, string $password): ?Product
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function findByIdWithTrashed(int $id): ?Product
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function save(Product $product): Product
	{
		$this->saveCalls++;
		$this->lastSavedProduct = $product;
		$this->products[$product->id] = $product;

		return $product;
	}

	public function delete(int $id): void
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function restore(int $id): void
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function bulkInsert(array $products): void
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function bulkInsertAndReturnIds(array $products, int $chunkSize = 1000): array
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function bulkUpdateNames(array $idToName): void
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function listForUser(array $params): PaginatedResult
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function listForAdmin(array $params): PaginatedResult
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function getUserProductOverview(int $userId): array
	{
		throw new RuntimeException('Not needed in this test.');
	}
}

final class StubRolePort implements RolePort
{
	/** @param array<int, list<string>> $rolesByUserId */
	public function __construct(private array $rolesByUserId = []) {}

	public function getRolesForUser(int $userId): array
	{
		return $this->rolesByUserId[$userId] ?? [];
	}

	public function getPermissionsForUser(int $userId): array
	{
		return [];
	}

	public function hasRole(int $userId, string $role): bool
	{
		return in_array($role, $this->getRolesForUser($userId), true);
	}

	public function assignRole(int $userId, string $role): void
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function removeRole(int $userId, string $role): void
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function listRoles(): array
	{
		return [];
	}

	public function listPermissions(): array
	{
		return [];
	}

	public function findById(int $id): RoleData
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function createRole(string $name, string $guard = 'stateful-api'): RoleData
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function updateRole(int $id, string $name): RoleData
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function deleteRole(int $id): void
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function syncRolePermissions(string $role, array $permissions): void
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function syncRoles(int $userId, array $roles): void
	{
		throw new RuntimeException('Not needed in this test.');
	}

	public function inTransaction(callable $fn): void
	{
		$fn();
	}
}

function makeUpdateDetailsProduct(int $id = 42, ?int $userId = 7): Product
{
	return Product::fromPersistence(
		id: $id,
		productTypeId: 5,
		userId: $userId,
		model: 'ModelA',
		linkedToProductId: null,
		password: 'secret',
		targetUrl: null,
		usage: 0,
		name: 'Before Name',
		description: 'Before description',
		active: true,
		configurationStatus: ConfigurationStatus::from(ConfigurationStatus::ASSIGNED),
		assignedAt: null,
		size: null,
		createdAt: new DateTimeImmutable('2025-01-01T00:00:00+00:00'),
		updatedAt: new DateTimeImmutable('2025-01-01T00:00:00+00:00'),
		deletedAt: null,
	);
}

describe('UpdateProductDetailsHandler', function () {
	it('allows owner to update details and saves exactly once', function (): void {
		$product = makeUpdateDetailsProduct(id: 42, userId: 7);
		$repo = new InMemoryProductRepository([42 => $product]);
		$roles = new StubRolePort();

		$handler = new UpdateProductDetailsHandler($repo, $roles, new ProductDetailsService());

		$result = $handler->handle(new UpdateProductDetailsCommand(
			productId: 42,
			actorUserId: 7,
			name: 'After Name',
			description: null,
			hasName: true,
			hasDescription: false,
		));

		expect($result->name)->toBe('After Name')
			->and($result->description)->toBe('Before description')
			->and($repo->saveCalls)->toBe(1)
			->and($repo->lastSavedProduct?->name->value)->toBe('After Name');
	});

	it('allows developer role to update non-owned product', function (): void {
		$product = makeUpdateDetailsProduct(id: 42, userId: 7);
		$repo = new InMemoryProductRepository([42 => $product]);
		$roles = new StubRolePort([100 => ['developer']]);

		$handler = new UpdateProductDetailsHandler($repo, $roles, new ProductDetailsService());

		$result = $handler->handle(new UpdateProductDetailsCommand(
			productId: 42,
			actorUserId: 100,
			name: null,
			description: 'After description',
			hasName: false,
			hasDescription: true,
		));

		expect($result->description)->toBe('After description')
			->and($repo->saveCalls)->toBe(1);
	});

	it('allows backoffice role to update non-owned product', function (): void {
		$product = makeUpdateDetailsProduct(id: 42, userId: 7);
		$repo = new InMemoryProductRepository([42 => $product]);
		$roles = new StubRolePort([101 => ['backoffice']]);

		$handler = new UpdateProductDetailsHandler($repo, $roles, new ProductDetailsService());

		$result = $handler->handle(new UpdateProductDetailsCommand(
			productId: 42,
			actorUserId: 101,
			name: 'Backoffice Name',
			description: null,
			hasName: true,
			hasDescription: false,
		));

		expect($result->name)->toBe('Backoffice Name')
			->and($repo->saveCalls)->toBe(1);
	});

	it('throws unauthorized when actor is not owner and has no allowed role', function (): void {
		$product = makeUpdateDetailsProduct(id: 42, userId: 7);
		$repo = new InMemoryProductRepository([42 => $product]);
		$roles = new StubRolePort([200 => ['regular']]);

		$handler = new UpdateProductDetailsHandler($repo, $roles, new ProductDetailsService());

		expect(fn (): mixed => $handler->handle(new UpdateProductDetailsCommand(
			productId: 42,
			actorUserId: 200,
			name: 'No permission',
			description: null,
			hasName: true,
			hasDescription: false,
		)))->toThrow(Unauthorized::class, 'product_details_forbidden');

		expect($repo->saveCalls)->toBe(0);
	});

	it('throws not found when product does not exist', function (): void {
		$repo = new InMemoryProductRepository();
		$roles = new StubRolePort();

		$handler = new UpdateProductDetailsHandler($repo, $roles, new ProductDetailsService());

		expect(fn (): mixed => $handler->handle(new UpdateProductDetailsCommand(
			productId: 999,
			actorUserId: 7,
			name: 'Name',
			description: null,
			hasName: true,
			hasDescription: false,
		)))->toThrow(NotFound::class);

		expect($repo->saveCalls)->toBe(0);
	});

	it('rejects empty payload and does not persist', function (): void {
		$product = makeUpdateDetailsProduct(id: 42, userId: 7);
		$repo = new InMemoryProductRepository([42 => $product]);
		$roles = new StubRolePort();

		$handler = new UpdateProductDetailsHandler($repo, $roles, new ProductDetailsService());

		expect(fn (): mixed => $handler->handle(new UpdateProductDetailsCommand(
			productId: 42,
			actorUserId: 7,
			name: null,
			description: null,
			hasName: false,
			hasDescription: false,
		)))->toThrow(ValidationFailed::class, 'product_details_empty_payload');

		expect($repo->saveCalls)->toBe(0);
	});
});
