<?php

declare(strict_types=1);

namespace Src\Shared\Core\Ports;

/**
 * Contract for analytics and operational event reporting.
 */
interface AnalyticsPort
{
	/**
	 * @param array<string, mixed> $properties
	 */
	public function track(string $event, array $properties = []): void;

	/**
	 * @param array<string, mixed> $properties
	 */
	public function identify(string $userId, array $properties = []): void;

	/**
	 * @param array<string, mixed> $properties
	 */
	public function setGroup(string $groupKey, string $groupId, array $properties = []): void;

	/**
	 * Sets the fallback distinct id used when there is no authenticated user.
	 */
	public function setSystemAlias(string $alias): void;

	/**
	 * @param array<string, mixed>|null $data
	 */
	public function info(string $eventName, string $title, string $message, ?array $data = null): void;

	/**
	 * @param array<string, mixed>|null $data
	 */
	public function warn(string $eventName, string $title, string $message, ?array $data = null): void;

	/**
	 * @param array<string, mixed>|null $data
	 */
	public function error(string $eventName, string $title, string $message, ?array $data = null): void;

	/**
	 * @param array<string, mixed>|null $data
	 */
	public function critical(string $eventName, string $title, string $message, ?array $data = null): void;
}
