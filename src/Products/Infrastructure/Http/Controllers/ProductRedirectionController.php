<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Products\Application\Queries\ResolveProductRedirection\ResolveProductRedirectionQuery;
use Src\Products\Domain\ValueObjects\RedirectionTarget;
use Src\Products\Domain\ValueObjects\RedirectionType;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ProductRedirectionController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    public function webRedirect(Request $request, int $id, string $password): RedirectResponse
    {
        /** @var RedirectionTarget $target */
        $target = $this->queryBus->dispatch(new ResolveProductRedirectionQuery(
            productId: $id,
            password: $password,
            browserLocales: $request->header('Accept-Language', ''),
        ));

        return match ($target->type) {
            RedirectionType::EXTERNAL_URL => redirect()->away($target->url),
            RedirectionType::FRONTEND_ROUTE => redirect($target->url),
        };
    }

    #[OA\Get(
        path: '/products/{id}/{password}/redirection-target',
        summary: 'Resolve redirection target for a product',
        operationId: 'resolveProductRedirectionTarget',
        tags: ['Products'],
        security: [],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'password', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Redirection target resolved',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'target_url', type: 'string', example: 'https://google.com'),
                        new OA\Property(property: 'type', type: 'string', example: 'external_url'),
                    ], type: 'object'),
                ]),
            ),
            new OA\Response(response: 404, description: 'Product not found', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 422, description: 'Product misconfigured', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
        ],
    )]
    public function apiRedirect(Request $request, int $id, string $password): JsonResponse
    {
        /** @var RedirectionTarget $target */
        $target = $this->queryBus->dispatch(new ResolveProductRedirectionQuery(
            productId: $id,
            password: $password,
            browserLocales: $request->header('Accept-Language', ''),
        ));

        return ApiResponseFactory::ok([
            'url' => $target->url,
            'type' => $target->type->value,
        ]);
    }
}
