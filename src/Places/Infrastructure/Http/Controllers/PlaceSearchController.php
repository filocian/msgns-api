<?php

declare(strict_types=1);

namespace Src\Places\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Places\Application\Queries\SearchPlace\SearchPlaceQuery;
use Src\Places\Domain\ValueObjects\PlaceSearchResult;
use Src\Places\Infrastructure\Http\Requests\SearchPlaceRequest;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

#[OA\Tag(name: 'Places', description: 'Place search endpoints')]
#[OA\Schema(
	schema: 'PlaceCandidateResource',
	type: 'object',
	properties: [
		new OA\Property(property: 'place_id', type: 'string', example: 'ChIJN1t_tDeuEmsRUsoyG83frY4'),
		new OA\Property(property: 'name', type: 'string', example: 'Restaurant Example'),
		new OA\Property(property: 'formatted_address', type: 'string', example: '123 Main St, City, Country'),
		new OA\Property(property: 'rating', type: 'number', format: 'float', nullable: true, example: 4.5),
		new OA\Property(property: 'user_ratings_total', type: 'integer', nullable: true, example: 200),
		new OA\Property(property: 'types', type: 'array', items: new OA\Items(type: 'string'), example: ['restaurant', 'food', 'establishment']),
		new OA\Property(
			property: 'geometry',
			type: 'object',
			properties: [
				new OA\Property(
					property: 'location',
					type: 'object',
					properties: [
						new OA\Property(property: 'lat', type: 'number', example: 40.416),
						new OA\Property(property: 'lng', type: 'number', example: -3.703),
					],
					required: ['lat', 'lng'],
				),
			],
			required: ['location'],
		),
		new OA\Property(
			property: 'photos',
			type: 'array',
			nullable: true,
			items: new OA\Items(
				type: 'object',
				properties: [
					new OA\Property(property: 'photo_reference', type: 'string', example: 'Aap_uEAtExamplePhotoRef'),
					new OA\Property(property: 'width', type: 'integer', example: 400),
					new OA\Property(property: 'height', type: 'integer', example: 300),
				],
			),
		),
		new OA\Property(
			property: 'opening_hours',
			type: 'object',
			nullable: true,
			properties: [
				new OA\Property(property: 'open_now', type: 'boolean', example: true),
			],
		),
	],
	required: ['place_id', 'name', 'formatted_address', 'types', 'geometry'],
)]
#[OA\Schema(
	schema: 'PlaceSearchResponse',
	type: 'object',
	properties: [
		new OA\Property(
			property: 'data',
			type: 'object',
			properties: [
				new OA\Property(
					property: 'candidates',
					type: 'array',
					items: new OA\Items(ref: '#/components/schemas/PlaceCandidateResource'),
				),
			],
			required: ['candidates'],
		),
	],
	required: ['data'],
)]
final class PlaceSearchController extends Controller
{
	public function __construct(private readonly QueryBus $queryBus) {}

	#[OA\Get(
		path: '/places/search',
		summary: 'Search Google Places',
		description: 'Searches the Google Places API for place candidates matching the query. Rate limited to 12 requests per minute per user. Results are cached for 30 minutes.',
		operationId: 'searchPlaces',
		tags: ['Places'],
		security: [['bearerAuth' => []]],
		parameters: [
			new OA\Parameter(
				name: 'name',
				in: 'query',
				required: true,
				description: 'Place name or search term',
				schema: new OA\Schema(type: 'string', minLength: 2, maxLength: 255),
			),
		],
		responses: [
			new OA\Response(
				response: 200,
				description: 'Successful search',
				content: new OA\JsonContent(ref: '#/components/schemas/PlaceSearchResponse'),
			),
			new OA\Response(
				response: 401,
				description: 'Unauthenticated',
				content: new OA\JsonContent(ref: '#/components/schemas/DomainError'),
			),
			new OA\Response(
				response: 422,
				description: 'Validation error',
				content: new OA\JsonContent(
					properties: [
						new OA\Property(
							property: 'error',
							type: 'object',
							properties: [
								new OA\Property(property: 'code', type: 'string', example: 'validation_failed'),
								new OA\Property(
									property: 'context',
									type: 'object',
									properties: [
										new OA\Property(property: 'errors', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string'))),
									],
									required: ['errors'],
								),
							],
							required: ['code', 'context'],
						),
					],
					required: ['error'],
				),
			),
			new OA\Response(response: 429, description: 'Rate limit exceeded (12 requests/minute)'),
			new OA\Response(
				response: 502,
				description: 'Google Places API unavailable',
				content: new OA\JsonContent(ref: '#/components/schemas/DomainError'),
			),
		],
	)]
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
