<?php

declare(strict_types=1);

use Src\Products\Domain\ValueObjects\TargetUrl;
use Src\Shared\Core\Errors\ValidationFailed;

describe('TargetUrl Value Object', function () {

    it('creates a valid URL', function () {
        $vo = TargetUrl::from('https://example.com');

        expect($vo->value)->toBe('https://example.com')
            ->and((string) $vo)->toBe('https://example.com')
            ->and($vo->value())->toBe('https://example.com');
    });

    it('creates a valid URL with path', function () {
        $vo = TargetUrl::from('https://example.com/path/to/resource');

        expect($vo->value)->toBe('https://example.com/path/to/resource');
    });

    it('creates a valid URL with query string', function () {
        $vo = TargetUrl::from('https://example.com?foo=bar');

        expect($vo->value)->toBe('https://example.com?foo=bar');
    });

    it('trims whitespace', function () {
        $vo = TargetUrl::from('  https://example.com  ');

        expect($vo->value)->toBe('https://example.com');
    });

    it('throws on empty string', function () {
        TargetUrl::from('');
    })->throws(ValidationFailed::class, 'target_url_empty');

    it('throws on whitespace-only string', function () {
        TargetUrl::from('   ');
    })->throws(ValidationFailed::class, 'target_url_empty');

    it('throws on invalid URL (no protocol)', function () {
        TargetUrl::from('example.com');
    })->throws(ValidationFailed::class, 'target_url_invalid');

    it('throws on invalid URL (just path)', function () {
        TargetUrl::from('/path/to/resource');
    })->throws(ValidationFailed::class, 'target_url_invalid');

    it('throws on invalid URL (random string)', function () {
        TargetUrl::from('not-a-url');
    })->throws(ValidationFailed::class, 'target_url_invalid');

    it('equals returns true for same value', function () {
        $vo1 = TargetUrl::from('https://example.com');
        $vo2 = TargetUrl::from('https://example.com');

        expect($vo1->equals($vo2))->toBeTrue();
    });

    it('equals returns false for different value', function () {
        $vo1 = TargetUrl::from('https://example.com');
        $vo2 = TargetUrl::from('https://other.com');

        expect($vo1->equals($vo2))->toBeFalse();
    });
});
