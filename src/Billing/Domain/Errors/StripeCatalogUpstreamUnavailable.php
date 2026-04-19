<?php

declare(strict_types=1);

namespace Src\Billing\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class StripeCatalogUpstreamUnavailable extends DomainException
{
	public static function withReason(string $reason): self
	{
		return new self('stripe_catalog.upstream_unavailable', Response::HTTP_BAD_GATEWAY, [
			'reason' => $reason,
		]);
	}
}
