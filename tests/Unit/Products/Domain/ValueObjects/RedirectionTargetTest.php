<?php

declare(strict_types=1);

use Src\Products\Domain\ValueObjects\RedirectionTarget;
use Src\Products\Domain\ValueObjects\RedirectionType;

describe('RedirectionTarget', function () {
    it('serializes and deserializes external urls', function () {
        $target = RedirectionTarget::externalUrl('https://example.com');

        expect($target->toArray())->toBe([
            'url' => 'https://example.com',
            'type' => 'external_url',
        ])->and(RedirectionTarget::fromArray($target->toArray())->type)->toBe(RedirectionType::EXTERNAL_URL);
    });

    it('supports frontend routes for future resolvers', function () {
        $target = RedirectionTarget::frontendRoute('/product/42/register');

        expect($target->type)->toBe(RedirectionType::FRONTEND_ROUTE)
            ->and(RedirectionTarget::fromArray($target->toArray())->url)->toBe('/product/42/register');
    });
});
