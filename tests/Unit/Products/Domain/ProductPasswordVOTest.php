<?php

declare(strict_types=1);

use Src\Products\Domain\ValueObjects\ProductPassword;
use Src\Shared\Core\Errors\ValidationFailed;

describe('ProductPassword Value Object', function () {

    it('creates a valid product password', function () {
        $vo = ProductPassword::from('secret123');

        expect($vo->value)->toBe('secret123')
            ->and((string) $vo)->toBe('secret123')
            ->and($vo->value())->toBe('secret123');
    });

    it('trims whitespace', function () {
        $vo = ProductPassword::from('  secret123  ');

        expect($vo->value)->toBe('secret123');
    });

    it('throws on empty string', function () {
        ProductPassword::from('');
    })->throws(ValidationFailed::class, 'product_password_empty');

    it('throws on whitespace-only string', function () {
        ProductPassword::from('   ');
    })->throws(ValidationFailed::class, 'product_password_empty');

    it('equals returns true for same value', function () {
        $vo1 = ProductPassword::from('secret123');
        $vo2 = ProductPassword::from('secret123');

        expect($vo1->equals($vo2))->toBeTrue();
    });

    it('equals returns false for different value', function () {
        $vo1 = ProductPassword::from('secret123');
        $vo2 = ProductPassword::from('different');

        expect($vo1->equals($vo2))->toBeFalse();
    });
});
