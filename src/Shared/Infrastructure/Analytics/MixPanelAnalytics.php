<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Analytics;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Mixpanel;
use Src\Shared\Core\Ports\AnalyticsPort;

/**
 * Mixpanel-backed analytics adapter for the shared kernel.
 */
final class MixPanelAnalytics implements AnalyticsPort
{
	private string $systemAlias;

	private string $source;

	public function __construct(
		private readonly Mixpanel $client,
		private readonly AuthFactory $auth,
	) {
		$this->systemAlias = (string) \config('services.mixpanel.system_alias', 'SYS@API');
		$this->source = (string) \config('services.mixpanel.source', 'API');
	}

	/**
	 * @param array<string, mixed> $properties
	 */
	public function track(string $event, array $properties = []): void
	{
		$this->client->track($event, $properties);
	}

	/**
	 * @param array<string, mixed> $properties
	 */
	public function identify(string $userId, array $properties = []): void
	{
		$this->client->identify($userId);
		$this->client->people->set($userId, $properties);
	}

	/**
	 * @param array<string, mixed> $properties
	 */
	public function setGroup(string $groupKey, string $groupId, array $properties = []): void
	{
		/** @var mixed $group */
		$group = $this->client->group;
		$group->set($groupKey, $groupId, $properties);
	}

	/**
	 * Sets the fallback distinct id used when there is no authenticated user.
	 */
	public function setSystemAlias(string $alias): void
	{
		$this->systemAlias = $alias;
	}

	/**
	 * @param array<string, mixed>|null $data
	 */
	public function info(string $eventName, string $title, string $message, ?array $data = null): void
	{
		$this->log('INFO', $eventName, $title, $message, $data);
	}

	/**
	 * @param array<string, mixed>|null $data
	 */
	public function warn(string $eventName, string $title, string $message, ?array $data = null): void
	{
		$this->log('WARN', $eventName, $title, $message, $data);
	}

	/**
	 * @param array<string, mixed>|null $data
	 */
	public function error(string $eventName, string $title, string $message, ?array $data = null): void
	{
		$this->log('ERROR', $eventName, $title, $message, $data);
	}

	/**
	 * @param array<string, mixed>|null $data
	 */
	public function critical(string $eventName, string $title, string $message, ?array $data = null): void
	{
		$this->log('CRITICAL', $eventName, $title, $message, $data);
	}

	/**
	 * @param array<string, mixed>|null $data
	 */
	private function log(string $severity, string $eventName, string $title, string $message, ?array $data = null): void
	{
		$sharedProperties = $this->sharedProperties();

		$this->track($this->formatEventName($eventName, $severity), [
			'user_id' => $sharedProperties['user_id'],
			'timestamp' => $sharedProperties['timestamp'],
			'source' => $sharedProperties['source'],
			'severity' => $severity,
			'title' => $title,
			'message' => $message,
			'data' => $data,
		]);
	}

	/**
	 * @return array{user_id: string, timestamp: string, source: string}
	 */
	private function sharedProperties(): array
	{
		$user = $this->auth->guard()->user();
		$userId = $user === null
			? $this->systemAlias
			: ((string) $user->getAuthIdentifier()) . '@USER';

		return [
			'user_id' => $userId,
			'timestamp' => Carbon::now()->toDateTimeString(),
			'source' => $this->source,
		];
	}

	/**
	 * Formats the event name using the legacy logger convention.
	 */
	private function formatEventName(string $eventName, string $severity): string
	{
		return sprintf('[%s] [#%s] => %s', $this->source, $severity, $eventName);
	}
}
