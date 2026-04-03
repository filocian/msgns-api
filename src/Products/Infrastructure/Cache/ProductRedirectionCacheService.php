<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Cache;

use Src\Products\Domain\ValueObjects\RedirectionTarget;
use Src\Shared\Core\Ports\CachePort;

final class ProductRedirectionCacheService
{
    private const string KEY_PREFIX = 'products:redirection:';

    public function __construct(
        private readonly CachePort $cache,
    ) {}

    /**
     * @return array{target: RedirectionTarget, meta: array{userId: ?int, productName: string, password: string}}|null
     */
    public function get(int $productId): ?array
    {
        $raw = $this->cache->get($this->key($productId));

        if (!is_array($raw) || !isset($raw['target'], $raw['meta']) || !is_array($raw['target']) || !is_array($raw['meta'])) {
            return null;
        }

        if (!isset($raw['target']['url'], $raw['target']['type']) || !is_string($raw['target']['url']) || !is_string($raw['target']['type'])) {
            return null;
        }

        return [
            'target' => RedirectionTarget::fromArray($raw['target']),
            'meta' => [
                'userId' => isset($raw['meta']['userId']) && is_int($raw['meta']['userId']) ? $raw['meta']['userId'] : null,
                'productName' => (string) ($raw['meta']['productName'] ?? ''),
                'password' => (string) ($raw['meta']['password'] ?? ''),
            ],
        ];
    }

    /**
     * @param array{userId: ?int, productName: string, password: string} $meta
     */
    public function put(int $productId, RedirectionTarget $target, array $meta): void
    {
        $this->cache->setForever($this->key($productId), [
            'target' => $target->toArray(),
            'meta' => $meta,
        ]);
    }

    public function forget(int $productId): void
    {
        $this->cache->forget($this->key($productId));
    }

    public function key(int $productId): string
    {
        return self::KEY_PREFIX . $productId;
    }
}
