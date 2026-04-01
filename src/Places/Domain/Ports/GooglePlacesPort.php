<?php

declare(strict_types=1);

namespace Src\Places\Domain\Ports;

use Src\Places\Domain\Errors\GooglePlacesUnavailable;
use Src\Places\Domain\ValueObjects\PlaceSearchResult;

interface GooglePlacesPort
{
	/**
	 * @throws GooglePlacesUnavailable
	 */
	public function search(string $query): PlaceSearchResult;
}
