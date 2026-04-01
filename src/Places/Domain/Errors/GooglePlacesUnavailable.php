<?php

declare(strict_types=1);

namespace Src\Places\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class GooglePlacesUnavailable extends DomainException
{
	public static function because(string $reason): self
	{
		return new self($reason, Response::HTTP_BAD_GATEWAY);
	}
}
