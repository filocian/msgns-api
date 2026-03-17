<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\Assert;

final class AuthContractAssertions
{
    public static function assertAuthSuccessContract(array $payload, string $fixtureName): void
    {
        self::assertContractSet($payload, $fixtureName);
    }

    public static function assertAuthErrorContract(array $payload, string $fixtureName): void
    {
        self::assertContractSet($payload, $fixtureName);
    }

    public static function assertV2BaselineParity(array $payload, string $fixtureName): void
    {
        self::assertContractPayload($payload, self::loadFixture('v2-baseline', $fixtureName));
    }

    public static function assertContractSet(array $payload, string $fixtureName): void
    {
        self::assertContractPayload($payload, self::loadFixture('v2-baseline', $fixtureName));
        self::assertContractPayload($payload, self::loadFixture('current-contract', $fixtureName));
    }

    public static function assertContractParityByPaths(array $leftPayload, array $rightPayload, array $paths): void
    {
        foreach ($paths as $path) {
            $leftValue = self::valueAtPath($leftPayload, $path);
            $rightValue = self::valueAtPath($rightPayload, $path);

            Assert::assertSame(
                self::typeOf($leftValue),
                self::typeOf($rightValue),
                sprintf('Type mismatch at "%s" between alias payloads.', $path)
            );
        }
    }

    private static function assertContractPayload(array $payload, array $fixture): void
    {
        Assert::assertArrayHasKey('locked_paths', $fixture);
        Assert::assertIsArray($fixture['locked_paths']);

        foreach ($fixture['locked_paths'] as $path => $expectedType) {
            $value = self::valueAtPath($payload, $path);
            $actualType = self::typeOf($value);
            $expectedTypes = array_map('trim', explode('|', $expectedType));

            Assert::assertContains(
                $actualType,
                $expectedTypes,
                sprintf('Contract mismatch at "%s": expected %s, got %s.', $path, $expectedType, $actualType)
            );
        }
    }

    private static function valueAtPath(array $payload, string $path): mixed
    {
        $segments = explode('.', $path);
        $current = $payload;

        foreach ($segments as $segment) {
            Assert::assertIsArray($current, sprintf('Path "%s" is not traversable at "%s".', $path, $segment));
            Assert::assertArrayHasKey($segment, $current, sprintf('Missing required contract key "%s".', $path));
            $current = $current[$segment];
        }

        return $current;
    }

    private static function typeOf(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_string($value)) {
            return 'string';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_array($value)) {
            return 'array';
        }

        return gettype($value);
    }

    private static function loadFixture(string $version, string $fixtureName): array
    {
        $path = dirname(__DIR__) . '/Fixtures/identity-auth-contracts/' . $version . '/' . $fixtureName;

        Assert::assertFileExists($path, sprintf('Contract fixture not found: %s', $path));

        $decoded = json_decode((string) file_get_contents($path), true);
        Assert::assertIsArray($decoded, sprintf('Invalid contract fixture: %s', $path));

        return $decoded;
    }
}
