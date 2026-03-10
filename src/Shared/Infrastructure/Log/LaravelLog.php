<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Log;

use Psr\Log\LoggerInterface;
use Src\Shared\Core\Ports\LogPort;

final class LaravelLog implements LogPort
{
	public function __construct(private readonly LoggerInterface $logger) {}

	public function info(string $message, array $context = []): void
	{
		$this->logger->info($message, $context);
	}

	public function warning(string $message, array $context = []): void
	{
		$this->logger->warning($message, $context);
	}

	public function error(string $message, array $context = []): void
	{
		$this->logger->error($message, $context);
	}

	public function debug(string $message, array $context = []): void
	{
		$this->logger->debug($message, $context);
	}
}
