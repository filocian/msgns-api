<?php

declare(strict_types=1);

namespace Src\Shared\Core\Ports;

/**
 * Dispatches logical queue jobs without exposing framework job classes to callers.
 */
interface QueuePort
{
	/**
	 * Dispatches a configured job immediately.
	 *
	 * @param array<string, mixed> $payload
	 */
	public function dispatch(string $jobName, array $payload = []): void;

	/**
	 * Dispatches a configured job after the given delay.
	 *
	 * @param array<string, mixed> $payload
	 */
	public function dispatchAfter(string $jobName, int $delaySeconds, array $payload = []): void;
}
