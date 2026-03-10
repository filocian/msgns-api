<?php

declare(strict_types=1);

namespace Src\Shared\Core\Errors;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
	/**
	 * @param array<string, mixed> $context
	 */
	final public function __construct(
		private readonly string $errorCode,
		private readonly int $httpStatus,
		private readonly array $context = [],
	) {
		parent::__construct($errorCode);
	}

	public function errorCode(): string
	{
		return $this->errorCode;
	}

	public function httpStatus(): int
	{
		return $this->httpStatus;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function context(): array
	{
		return $this->context;
	}
}
