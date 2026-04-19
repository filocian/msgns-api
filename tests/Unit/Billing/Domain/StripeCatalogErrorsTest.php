<?php

declare(strict_types=1);

use Src\Billing\Domain\Errors\StripeCatalogMisconfigured;
use Src\Billing\Domain\Errors\StripeCatalogUpstreamUnavailable;

describe('Stripe catalog domain errors', function () {
	it('StripeCatalogMisconfigured::withoutContext returns canonical code and status', function () {
		$error = StripeCatalogMisconfigured::withoutContext();

		expect($error->errorCode())->toBe('stripe_catalog.misconfigured')
			->and($error->httpStatus())->toBe(500)
			->and($error->context())->toBe([]);
	});

	it('StripeCatalogUpstreamUnavailable::withReason returns canonical code, status, and context', function () {
		$error = StripeCatalogUpstreamUnavailable::withReason('stripe_unreachable');

		expect($error->errorCode())->toBe('stripe_catalog.upstream_unavailable')
			->and($error->httpStatus())->toBe(502)
			->and($error->context())->toBe(['reason' => 'stripe_unreachable']);
	});
});
