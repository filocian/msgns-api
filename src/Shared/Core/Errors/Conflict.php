<?php

declare(strict_types=1);

namespace Src\Shared\Core\Errors;

use Symfony\Component\HttpFoundation\Response;

final class Conflict extends DomainException
{
	/**
	 * @param array<string, mixed> $context
	 */
	public static function because(string $reason, array $context = []): self
	{
		return new self($reason, Response::HTTP_CONFLICT, $context);
	}
}
