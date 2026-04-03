<?php

declare(strict_types=1);

namespace Src\Shared\Core\Ports;

use Closure;

interface CachePort
{
	public function get(string $key, mixed $default = null): mixed;

	public function set(string $key, mixed $value, int $ttl): void;

	public function remember(string $key, int $ttl, Closure $callback): mixed;

	public function forget(string $key): bool;

	public function setForever(string $key, mixed $value): void;
}
