<?php

declare(strict_types=1);

use Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort;

describe('SubscriptionTypeRepositoryPort contract', function () {
    it('declares an existsByStripeProductId(string): bool method', function () {
        $ref = new ReflectionClass(SubscriptionTypeRepositoryPort::class);

        expect($ref->hasMethod('existsByStripeProductId'))->toBeTrue();

        $method = $ref->getMethod('existsByStripeProductId');
        $params = $method->getParameters();

        expect($params)->toHaveCount(1);
        expect($params[0]->getName())->toBe('stripeProductId');

        $paramType = $params[0]->getType();
        expect($paramType)->toBeInstanceOf(ReflectionNamedType::class);
        expect($paramType->getName())->toBe('string');
        expect($paramType->allowsNull())->toBeFalse();

        $returnType = $method->getReturnType();
        expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
        expect($returnType->getName())->toBe('bool');
    });
});
