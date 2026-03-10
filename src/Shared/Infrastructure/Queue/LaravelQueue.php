<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Queue;

use DateTimeInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use RuntimeException;
use Src\Shared\Core\Ports\QueuePort;

/**
 * Dispatches logical shared jobs through Laravel's queue using config-backed mappings.
 */
final class LaravelQueue implements QueuePort
{
	public function __construct(
		private readonly Container $container,
		private readonly QueueContract $queue,
	) {}

	/**
	 * @param array<string, mixed> $payload
	 */
	public function dispatch(string $jobName, array $payload = []): void
	{
		$this->pushResolvedJob($this->resolveJobDefinition($jobName), $payload);
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public function dispatchAfter(string $jobName, int $delaySeconds, array $payload = []): void
	{
		$definition = $this->resolveJobDefinition($jobName);
		$this->pushResolvedJob($definition, $payload, \now()->addSeconds($delaySeconds));
	}

	/**
	 * @return array{class: class-string, connection: string|null, queue: string|null}
	 */
	private function resolveJobDefinition(string $jobName): array
	{
		$jobs = \config('shared.queue.jobs', []);
		$definition = is_array($jobs) ? ($jobs[$jobName] ?? null) : null;

		if (is_string($definition) && $definition !== '') {
			if (! class_exists($definition)) {
				throw new RuntimeException(sprintf(
					'Configured queue job class [%s] does not exist for [%s].',
					$definition,
					$jobName,
				));
			}

			return [
				'class' => $definition,
				'connection' => null,
				'queue' => null,
			];
		}

		if (! is_array($definition) || ! is_string($definition['class'] ?? null) || $definition['class'] === '') {
			$availableJobs = is_array($jobs) ? array_keys($jobs) : [];

			throw new RuntimeException(sprintf(
				'No queue job registered for [%s]. Registered jobs: %s.',
				$jobName,
				$availableJobs === [] ? 'none' : implode(', ', $availableJobs),
			));
		}

		if (! class_exists($definition['class'])) {
			throw new RuntimeException(sprintf(
				'Configured queue job class [%s] does not exist for [%s].',
				$definition['class'],
				$jobName,
			));
		}

		return [
			'class' => $definition['class'],
			'connection' => is_string($definition['connection'] ?? null) && $definition['connection'] !== ''
				? $definition['connection']
				: null,
			'queue' => is_string($definition['queue'] ?? null) && $definition['queue'] !== ''
				? $definition['queue']
				: null,
		];
	}

	/**
	 * @param array{class: class-string, connection: string|null, queue: string|null} $definition
	 * @param array<string, mixed> $payload
	 */
	private function pushResolvedJob(array $definition, array $payload, ?DateTimeInterface $delay = null): void
	{
		$job = $this->container->makeWith($definition['class'], $payload);

		if (! is_object($job)) {
			throw new RuntimeException(sprintf(
				'Container resolved queue job [%s] as a non-object value.',
				$definition['class'],
			));
		}

		if ($definition['connection'] !== null && method_exists($job, 'onConnection')) {
			$job->onConnection($definition['connection']);
		}

		if ($definition['queue'] !== null && method_exists($job, 'onQueue')) {
			$job->onQueue($definition['queue']);
		}

		if ($delay !== null && method_exists($job, 'delay')) {
			$job->delay($delay);
		}

		$this->queue->push($job);
	}
}
