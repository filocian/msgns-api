<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Ai\Application\Queries\GetPrepaidPackages\GetPrepaidPackagesQuery;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class GetPrepaidPackagesController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/ai/prepaid-packages',
        summary: 'List available prepaid AI packages',
        description: 'Returns all active prepaid AI packages ordered by price ascending. Public endpoint — no authentication required.',
        operationId: 'getPrepaidPackages',
        tags: ['AI Prepaid Packages'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of active prepaid packages',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        $packages = $this->queryBus->dispatch(new GetPrepaidPackagesQuery());

        return ApiResponseFactory::ok($packages);
    }
}
