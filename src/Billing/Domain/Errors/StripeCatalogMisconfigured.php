<?php

declare(strict_types=1);

namespace Src\Billing\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class StripeCatalogMisconfigured extends DomainException
{
	public static function withoutContext(): self
	{
		return new self('stripe_catalog.misconfigured', Response::HTTP_INTERNAL_SERVER_ERROR);
	}
}
