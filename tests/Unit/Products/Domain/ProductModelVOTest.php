<?php

declare(strict_types=1);

use Src\Products\Domain\ValueObjects\ProductModel;
use Src\Shared\Core\Errors\ValidationFailed;

describe('ProductModel Value Object', function () {

    it('creates a valid product model', function () {
        $vo = ProductModel::from('GPT-4');

        expect($vo->value)->toBe('GPT-4')
            ->and((string) $vo)->toBe('GPT-4')
            ->and($vo->value())->toBe('GPT-4');
    });

    it('trims whitespace', function () {
        $vo = ProductModel::from('  GPT-4  ');

        expect($vo->value)->toBe('GPT-4');
    });

    it('throws on empty string', function () {
        ProductModel::from('');
    })->throws(ValidationFailed::class, 'product_model_empty');

    it('throws on whitespace-only string', function () {
        ProductModel::from('   ');
    })->throws(ValidationFailed::class, 'product_model_empty');

    it('throws on too long string', function () {
        $longString = str_repeat('a', ProductModel::MAX_LENGTH + 1);
        ProductModel::from($longString);
    })->throws(ValidationFailed::class, 'product_model_too_long');

    it('equals returns true for same value', function () {
        $vo1 = ProductModel::from('GPT-4');
        $vo2 = ProductModel::from('GPT-4');

        expect($vo1->equals($vo2))->toBeTrue();
    });

    it('equals returns false for different value', function () {
        $vo1 = ProductModel::from('GPT-4');
        $vo2 = ProductModel::from('Claude-3');

        expect($vo1->equals($vo2))->toBeFalse();
    });
});
