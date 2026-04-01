<?php

declare(strict_types=1);

namespace Src\Places\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Src\Places\Application\Queries\SearchPlace\SearchPlaceQuery;
use Src\Places\Domain\ValueObjects\PlaceSearchResult;
use Src\Places\Infrastructure\Http\Requests\SearchPlaceRequest;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class PlaceSearchController extends Controller
{
	public function __construct(private readonly QueryBus $queryBus) {}

	public function __invoke(SearchPlaceRequest $request): JsonResponse
	{
		/** @var PlaceSearchResult $result */
		$result = $this->queryBus->dispatch(new SearchPlaceQuery(
			userId: (int) Auth::id(),
			name: (string) $request->validated('name'),
		));

		return ApiResponseFactory::ok($result->toArray());
	}
}
