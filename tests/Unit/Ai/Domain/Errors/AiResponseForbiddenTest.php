<?php

declare(strict_types=1);

use Src\Ai\Domain\Errors\AiResponseForbidden;
use Src\Shared\Core\Errors\DomainException;

describe('AiResponseForbidden', function (): void {

    it('is a DomainException', function (): void {
        $error = AiResponseForbidden::forUser(1, 42);

        expect($error)->toBeInstanceOf(DomainException::class);
    });

    it('returns HTTP 403', function (): void {
        $error = AiResponseForbidden::forUser(1, 42);

        expect($error->httpStatus())->toBe(403);
    });

    it('exposes user_id and context_id in context', function (): void {
        $error = AiResponseForbidden::forUser(7, 'abc-review-id');

        expect($error->context())->toMatchArray([
            'user_id'    => 7,
            'context_id' => 'abc-review-id',
        ]);
    });

    it('uses a stable error code', function (): void {
        $error = AiResponseForbidden::forUser(1, 42);

        expect($error->errorCode())->toBe('ai_response_forbidden');
    });

    it('accepts integer and string context identifiers', function (): void {
        $withInt    = AiResponseForbidden::forUser(1, 99);
        $withString = AiResponseForbidden::forUser(1, 'review-xyz');

        expect($withInt->context()['context_id'])->toBe(99);
        expect($withString->context()['context_id'])->toBe('review-xyz');
    });
});
