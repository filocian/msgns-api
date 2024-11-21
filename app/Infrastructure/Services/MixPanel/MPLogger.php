<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\MixPanel;

use App\Infrastructure\DTO\MixPanel\MPMessageDto;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

final class MPLogger
{
	public static string $INFO = 'INFO';
	public static string $WARN = 'WARN';
	public static string $ERROR = 'ERROR';
	public static string $CRITICAL = 'CRITICAL';

	public function __construct(private readonly MixPanelService $mixPanelService) {}

	public function info(string $eventName, string $title, string $message, array|null $data = null): void
	{
		$message = new MPMessageDto($eventName, $this->getSharedData(), self::$INFO, $title, $message, $data);

		$this->mixPanelService->addEvent($message);
	}

	public function warn(string $eventName, string $title, string $message, array|null $data = null): void
	{
		$message = new MPMessageDto($eventName, $this->getSharedData(), self::$WARN, $title, $message, $data);

		$this->mixPanelService->addEvent($message);
	}

	public function error(string $eventName, string $title, string $message, array $data): void
	{
		$message = new MPMessageDto($eventName, $this->getSharedData(), self::$ERROR, $title, $message, $data);

		$this->mixPanelService->addEvent($message);
	}

	public function critical(string $eventName, string $title, string $message, array $data): void
	{
		$message = new MPMessageDto($eventName, $this->getSharedData(), self::$CRITICAL, $title, $message, $data);

		$this->mixPanelService->addEvent($message);
	}

	private function getSharedData(): array
	{
		$userId = (string) Auth::user()->getAuthIdentifier() ?? 'null';
		$timestamp = Carbon::now()->toDateTimeString();

		return ['DISTINCT_ID' => $userId, 'TIMESTAMP' => $timestamp, 'SOURCE' => 'API'];
	}
}
