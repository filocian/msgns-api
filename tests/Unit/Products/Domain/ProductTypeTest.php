<?php

declare(strict_types=1);

use Src\Products\Domain\Entities\ProductType;
use Src\Shared\Core\Errors\ValidationFailed;

describe('ProductType Entity', function () {

    // ─── create() factory ───────────────────────────────────────────────────

    it('creates a new product type with correct initial values', function () {
        $pt = ProductType::create(
            code: 'TYPE-A',
            name: 'Type A',
            primaryModel: 'ModelX',
            secondaryModel: null,
        );

        expect($pt->id)->toBe(0)
            ->and($pt->code->value)->toBe('TYPE-A')
            ->and($pt->name)->toBe('Type A')
            ->and($pt->models->primary)->toBe('ModelX')
            ->and($pt->models->secondary)->toBeNull()
            ->and($pt->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($pt->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);
    });

    it('creates a product type with a secondary model', function () {
        $pt = ProductType::create(
            code: 'TYPE-B',
            name: 'Type B',
            primaryModel: 'ModelX',
            secondaryModel: 'ModelY',
        );

        expect($pt->models->secondary)->toBe('ModelY');
    });

    // ─── fromPersistence() factory ───────────────────────────────────────────

    it('rehydrates from persistence with correct values', function () {
        $createdAt = new DateTimeImmutable('2024-01-01T00:00:00Z');
        $updatedAt = new DateTimeImmutable('2024-06-01T12:00:00Z');

        $pt = ProductType::fromPersistence(
            id: 42,
            code: 'LEGACY-CODE',
            name: 'Legacy Type',
            primaryModel: 'OldModel',
            secondaryModel: 'OtherModel',
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        expect($pt->id)->toBe(42)
            ->and($pt->code->value)->toBe('LEGACY-CODE')
            ->and($pt->name)->toBe('Legacy Type')
            ->and($pt->models->primary)->toBe('OldModel')
            ->and($pt->models->secondary)->toBe('OtherModel')
            ->and($pt->createdAt)->toBe($createdAt)
            ->and($pt->updatedAt)->toBe($updatedAt);
    });

    // ─── applyUpdate() — not in use (AC-004) ─────────────────────────────────

    it('updates all fields when the product type is NOT in use (AC-004)', function () {
        $pt = ProductType::fromPersistence(
            id: 1,
            code: 'OLD-CODE',
            name: 'Old Name',
            primaryModel: 'OldPrimary',
            secondaryModel: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
        );

        $pt->applyUpdate(
            isUsed: false,
            code: 'NEW-CODE',
            name: 'New Name',
            primaryModel: 'NewPrimary',
            secondaryModel: 'NewSecondary',
        );

        expect($pt->code->value)->toBe('NEW-CODE')
            ->and($pt->name)->toBe('New Name')
            ->and($pt->models->primary)->toBe('NewPrimary')
            ->and($pt->models->secondary)->toBe('NewSecondary');
    });

    // ─── applyUpdate() — in use, only name changes (AC-005) ──────────────────

    it('allows updating only name when the product type IS in use (AC-005)', function () {
        $pt = ProductType::fromPersistence(
            id: 2,
            code: 'FIXED-CODE',
            name: 'Original Name',
            primaryModel: 'FixedPrimary',
            secondaryModel: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
        );

        $pt->applyUpdate(
            isUsed: true,
            code: null,         // not changing protected field
            name: 'Updated Name',
            primaryModel: null, // not changing protected field
            secondaryModel: null,
        );

        expect($pt->name)->toBe('Updated Name')
            ->and($pt->code->value)->toBe('FIXED-CODE')
            ->and($pt->models->primary)->toBe('FixedPrimary');
    });

    // ─── applyUpdate() — in use, code change rejected (AC-006) ───────────────

    it('throws ValidationFailed when changing code on an in-use product type (AC-006)', function () {
        $pt = ProductType::fromPersistence(
            id: 3,
            code: 'CURRENT-CODE',
            name: 'In-Use Type',
            primaryModel: 'PrimaryModel',
            secondaryModel: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
        );

        $pt->applyUpdate(
            isUsed: true,
            code: 'CHANGED-CODE',
            name: null,
            primaryModel: null,
            secondaryModel: null,
        );
    })->throws(ValidationFailed::class, 'protected_fields_immutable');

    it('throws ValidationFailed with correct context when code is changed on an in-use type (AC-006)', function () {
        $pt = ProductType::fromPersistence(
            id: 4,
            code: 'EXISTING-CODE',
            name: 'In-Use Type',
            primaryModel: 'PrimaryModel',
            secondaryModel: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
        );

        try {
            $pt->applyUpdate(
                isUsed: true,
                code: 'DIFFERENT-CODE',
                name: null,
                primaryModel: null,
                secondaryModel: null,
            );
            expect(false)->toBeTrue('Expected ValidationFailed to be thrown');
        } catch (ValidationFailed $e) {
            expect($e->getMessage())->toBe('protected_fields_immutable');
            $context = $e->context();
            expect($context['fields'])->toContain('code')
                ->and($context['reason'])->toBe('product_type_in_use');
        }
    });

    // ─── applyUpdate() — in use, primary/secondary model change rejected (AC-007) ─

    it('throws ValidationFailed when changing primary_model on an in-use product type (AC-007)', function () {
        $pt = ProductType::fromPersistence(
            id: 5,
            code: 'STABLE-CODE',
            name: 'In-Use Type',
            primaryModel: 'CurrentPrimary',
            secondaryModel: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
        );

        $pt->applyUpdate(
            isUsed: true,
            code: null,
            name: null,
            primaryModel: 'ChangedPrimary',
            secondaryModel: null,
        );
    })->throws(ValidationFailed::class, 'protected_fields_immutable');

    it('throws ValidationFailed when changing secondary_model on an in-use product type (AC-007)', function () {
        $pt = ProductType::fromPersistence(
            id: 6,
            code: 'STABLE-CODE',
            name: 'In-Use Type',
            primaryModel: 'Primary',
            secondaryModel: 'CurrentSecondary',
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
        );

        $pt->applyUpdate(
            isUsed: true,
            code: null,
            name: null,
            primaryModel: null,
            secondaryModel: 'ChangedSecondary',
        );
    })->throws(ValidationFailed::class, 'protected_fields_immutable');

    it('throws ValidationFailed with all changed protected fields listed in context (AC-007)', function () {
        $pt = ProductType::fromPersistence(
            id: 7,
            code: 'CODE-X',
            name: 'Multi-Protected',
            primaryModel: 'PrimaryA',
            secondaryModel: 'SecondaryA',
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
        );

        try {
            $pt->applyUpdate(
                isUsed: true,
                code: 'CODE-Y',
                name: null,
                primaryModel: 'PrimaryB',
                secondaryModel: 'SecondaryB',
            );
            expect(false)->toBeTrue('Expected ValidationFailed to be thrown');
        } catch (ValidationFailed $e) {
            $context = $e->context();
            expect($context['fields'])->toContain('code')
                ->and($context['fields'])->toContain('primary_model')
                ->and($context['fields'])->toContain('secondary_model');
        }
    });

    // ─── applyUpdate() — same-value protected fields should NOT trigger rejection ─

    it('does not reject update when protected fields are sent but unchanged while in use', function () {
        $pt = ProductType::fromPersistence(
            id: 8,
            code: 'SAME-CODE',
            name: 'Same Values Type',
            primaryModel: 'SamePrimary',
            secondaryModel: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
        );

        // Sending the same values should not trigger the protection
        $pt->applyUpdate(
            isUsed: true,
            code: 'SAME-CODE',         // same value
            name: 'Updated Name',
            primaryModel: 'SamePrimary', // same value
            secondaryModel: null,
        );

        expect($pt->name)->toBe('Updated Name')
            ->and($pt->code->value)->toBe('SAME-CODE')
            ->and($pt->models->primary)->toBe('SamePrimary');
    });

    // ─── No delete (AC-008) ──────────────────────────────────────────────────

    it('does not expose a delete method on the entity (AC-008)', function () {
        expect(method_exists(ProductType::class, 'delete'))->toBeFalse()
            ->and(method_exists(ProductType::class, 'softDelete'))->toBeFalse()
            ->and(method_exists(ProductType::class, 'markDeleted'))->toBeFalse();
    });
});
