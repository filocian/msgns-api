<?php

declare(strict_types=1);

use Src\Products\Domain\ValueObjects\SimpleRedirectionModel;

describe('SimpleRedirectionModel', function () {
    it('supports the info model', function () {
        expect(SimpleRedirectionModel::from('info'))->toBe(SimpleRedirectionModel::INFO)
            ->and(SimpleRedirectionModel::INFO->value)->toBe('info')
            ->and(SimpleRedirectionModel::supports('info'))->toBeTrue();
    });

    it('keeps the original simple models', function () {
        $values = array_map(static fn (SimpleRedirectionModel $case): string => $case->value, SimpleRedirectionModel::cases());

        expect($values)->toContain('google', 'instagram', 'youtube', 'tiktok', 'facebook', 'info');
    });
});
