<?php

declare(strict_types=1);

use Src\Products\Domain\ValueObjects\ProductDescription;

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
