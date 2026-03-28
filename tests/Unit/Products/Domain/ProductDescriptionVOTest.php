<?php

declare(strict_types=1);

use Src\Products\Domain\ValueObjects\ProductDescription;
use Src\Shared\Core\Errors\ValidationFailed;

describe('ProductDescription Value Object', function () {

    it('creates from a non-null string', function () {
        $vo = ProductDescription::from('A test description');

        expect($vo->value)->toBe('A test description')
            ->and((string) $vo)->toBe('A test description')
            ->and($vo->value())->toBe('A test description');
    });

    it('trims whitespace', function () {
        $vo = ProductDescription::from('  A test description  ');

        expect($vo->value)->toBe('A test description');
    });

    it('creates from null', function () {
        $vo = ProductDescription::from(null);

        expect($vo->value)->toBeNull()
            ->and((string) $vo)->toBe('')
            ->and($vo->value())->toBeNull();
    });

    it('throws on empty string', function () {
        expect(fn () => ProductDescription::from(''))
            ->toThrow(ValidationFailed::class, 'product_description_empty');
    });

    it('throws on whitespace-only string', function () {
        expect(fn () => ProductDescription::from('   '))
            ->toThrow(ValidationFailed::class, 'product_description_empty');
    });

    it('throws on string exceeding max length', function () {
        $longString = str_repeat('a', 501);

        expect(fn () => ProductDescription::from($longString))
            ->toThrow(ValidationFailed::class, 'product_description_too_long');
    });

    it('accepts string within limit', function () {
        $maxString = str_repeat('a', 500);
        $vo = ProductDescription::from($maxString);

        expect($vo->value)->toBe($maxString);
    });

    it('equals returns true for same value', function () {
        $vo1 = ProductDescription::from('desc');
        $vo2 = ProductDescription::from('desc');

        expect($vo1->equals($vo2))->toBeTrue();
    });

    it('equals returns false for different value', function () {
        $vo1 = ProductDescription::from('desc1');
        $vo2 = ProductDescription::from('desc2');

        expect($vo1->equals($vo2))->toBeFalse();
    });

    it('equals returns true when both null', function () {
        $vo1 = ProductDescription::from(null);
        $vo2 = ProductDescription::from(null);

        expect($vo1->equals($vo2))->toBeTrue();
    });

    it('equals returns false when one is null and other is not', function () {
        $vo1 = ProductDescription::from('desc');
        $vo2 = ProductDescription::from(null);

        expect($vo1->equals($vo2))->toBeFalse();
    });
});
