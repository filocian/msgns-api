<?php

declare(strict_types=1);

use Src\Products\Domain\ValueObjects\ProductName;
use Src\Shared\Core\Errors\ValidationFailed;

describe('ProductName Value Object', function () {

    it('creates a valid product name', function () {
        $vo = ProductName::from('My Product');

        expect($vo->value)->toBe('My Product')
            ->and((string) $vo)->toBe('My Product')
            ->and($vo->value())->toBe('My Product');
    });

    it('trims whitespace', function () {
        $vo = ProductName::from('  My Product  ');

        expect($vo->value)->toBe('My Product');
    });

    it('throws on empty string', function () {
        ProductName::from('');
    })->throws(ValidationFailed::class, 'product_name_empty');

    it('throws on whitespace-only string', function () {
        ProductName::from('   ');
    })->throws(ValidationFailed::class, 'product_name_empty');

    it('throws on too long string', function () {
        $longString = str_repeat('a', ProductName::MAX_LENGTH + 1);
        ProductName::from($longString);
    })->throws(ValidationFailed::class, 'product_name_too_long');

    it('equals returns true for same value', function () {
        $vo1 = ProductName::from('My Product');
        $vo2 = ProductName::from('My Product');

        expect($vo1->equals($vo2))->toBeTrue();
    });

    it('equals returns false for different value', function () {
        $vo1 = ProductName::from('Product A');
        $vo2 = ProductName::from('Product B');

        expect($vo1->equals($vo2))->toBeFalse();
    });
});
