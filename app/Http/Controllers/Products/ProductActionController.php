<?php

declare(strict_types=1);

namespace App\Http\Controllers\Products;

use App\Http\Contracts\Controller;
use App\Http\Requests\Products\AddBusinessInfoRequest;
use App\Http\Requests\Products\AssignToUserRequest;
use App\Http\Requests\Products\ChangeConfigStatusRequest;
use App\Http\Requests\Products\ConfigureProductRequest;
use App\Http\Requests\Products\RenameProductRequest;
use App\Http\Requests\Products\RegisterProductRequest;
use App\Http\Requests\Products\SetTargetUrlRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\AddBusinessInfo\AddBusinessInfoCommand;
use Src\Products\Application\Commands\ActivateProduct\ActivateProductCommand;
use Src\Products\Application\Commands\AssignToUser\AssignToUserCommand;
use Src\Products\Application\Commands\ChangeConfigStatus\ChangeConfigStatusCommand;
use Src\Products\Application\Commands\CloneFromProduct\CloneFromProductCommand;
use Src\Products\Application\Commands\ConfigureProduct\ConfigureProductCommand;
use Src\Products\Application\Commands\DeactivateProduct\DeactivateProductCommand;
use Src\Products\Application\Commands\GroupProducts\GroupProductsCommand;
use Src\Products\Application\Commands\RemoveProductLink\RemoveProductLinkCommand;
use Src\Products\Application\Commands\RenameProduct\RenameProductCommand;
use Src\Products\Application\Commands\RegisterProduct\RegisterProductCommand;
use Src\Products\Application\Commands\ResetProduct\ResetProductCommand;
use Src\Products\Application\Commands\RestoreProduct\RestoreProductCommand;
use Src\Products\Application\Commands\SetTargetUrl\SetTargetUrlCommand;
use Src\Products\Application\Commands\SoftRemoveProduct\SoftRemoveProductCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

#[OA\Tag(name: 'Products', description: 'Product action endpoints')]
final class ProductActionController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Patch(
        path: '/products/{id}/assign',
        summary: 'Assign product to a user',
        operationId: 'assignProductToUser',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 7),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product assigned', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function assignToUser(AssignToUserRequest $request, int $id): JsonResponse
    {
        $product = $this->commandBus->dispatch(new AssignToUserCommand(
            productId: $id,
            userId: (int) $request->validated('user_id'),
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }

    #[OA\Patch(
        path: '/products/{id}/target-url',
        summary: 'Set product target URL',
        operationId: 'setProductTargetUrl',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['target_url'],
                properties: [new OA\Property(property: 'target_url', type: 'string', format: 'uri', maxLength: 2048)],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Target URL updated', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function setTargetUrl(SetTargetUrlRequest $request, int $id): JsonResponse
    {
        $product = $this->commandBus->dispatch(new SetTargetUrlCommand(
            productId: $id,
            targetUrl: (string) $request->validated('target_url'),
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }

    #[OA\Post(
        path: '/products/{id}/register',
        summary: 'Register product with password and owner',
        operationId: 'registerProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'password'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 7),
                    new OA\Property(property: 'password', type: 'string', example: 'my-secret'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product registered', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error or invalid password'),
        ],
    )]
    public function register(RegisterProductRequest $request, int $id): JsonResponse
    {
        $product = $this->commandBus->dispatch(new RegisterProductCommand(
            productId: $id,
            userId: (int) $request->validated('user_id'),
            password: (string) $request->validated('password'),
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }

    #[OA\Put(
        path: '/products/{id}/configure',
        summary: 'Configure product target URL with forward-only status transition',
        operationId: 'configureProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['target_url'],
                properties: [new OA\Property(property: 'target_url', type: 'string', format: 'uri', maxLength: 2048)],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product configured', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function configure(ConfigureProductRequest $request, int $id): JsonResponse
    {
        $product = $this->commandBus->dispatch(new ConfigureProductCommand(
            productId: $id,
            targetUrl: (string) $request->validated('target_url'),
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }

    #[OA\Post(
        path: '/products/{referenceId}/group/{candidateId}',
        summary: 'Group two compatible products',
        operationId: 'groupProducts',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'referenceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'candidateId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Products grouped', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Invalid grouping combination'),
        ],
    )]
    public function group(Request $request, int $referenceId, int $candidateId): JsonResponse
    {
        unset($request);

        $product = $this->commandBus->dispatch(new GroupProductsCommand(
            referenceId: $referenceId,
            candidateId: $candidateId,
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }

    #[OA\Post(
        path: '/products/{id}/clone-from/{sourceId}',
        summary: 'Clone configuration from another product',
        operationId: 'cloneFromProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sourceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product cloned', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Products must have same type'),
        ],
    )]
    public function cloneFrom(Request $request, int $id, int $sourceId): JsonResponse
    {
        unset($request);

        $product = $this->commandBus->dispatch(new CloneFromProductCommand(
            targetId: $id,
            sourceId: $sourceId,
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }

    #[OA\Post(
        path: '/products/{id}/business',
        summary: 'Add or update business information',
        operationId: 'addBusinessInfo',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['types'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 150, nullable: true),
                    new OA\Property(property: 'not_a_business', type: 'boolean', nullable: true),
                    new OA\Property(property: 'types', type: 'object'),
                    new OA\Property(property: 'place_types', type: 'object', nullable: true),
                    new OA\Property(property: 'size', type: 'string', maxLength: 50, nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Business information saved', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function addBusinessInfo(AddBusinessInfoRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        assert($user !== null);

        $product = $this->commandBus->dispatch(new AddBusinessInfoCommand(
            productId: $id,
            userId: (int) $user->id,
            notABusiness: (bool) ($validated['not_a_business'] ?? false),
            types: (array) $validated['types'],
            name: isset($validated['name']) ? (string) $validated['name'] : null,
            placeTypes: isset($validated['place_types']) ? (array) $validated['place_types'] : null,
            size: isset($validated['size']) ? (string) $validated['size'] : null,
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }

    #[OA\Post(
        path: '/products/{id}/activate',
        summary: 'Activate a product',
        operationId: 'activateProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product activated', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
        ],
    )]
    public function activate(Request $request, int $id): JsonResponse
    {
        unset($request);

        $product = $this->commandBus->dispatch(new ActivateProductCommand(
            productId: $id,
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }

    #[OA\Post(
        path: '/products/{id}/deactivate',
        summary: 'Deactivate a product',
        operationId: 'deactivateProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product deactivated', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
        ],
    )]
    public function deactivate(Request $request, int $id): JsonResponse
    {
        unset($request);

        $product = $this->commandBus->dispatch(new DeactivateProductCommand(
            productId: $id,
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }

    #[OA\Patch(
        path: '/products/{id}/config-status',
        summary: 'Change product configuration status',
        operationId: 'changeProductConfigurationStatus',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [new OA\Property(property: 'status', type: 'string', example: 'assigned')],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Configuration status updated', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function changeConfigStatus(ChangeConfigStatusRequest $request, int $id): JsonResponse
    {
        $product = $this->commandBus->dispatch(new ChangeConfigStatusCommand(
            productId: $id,
            status: (string) $request->validated('status'),
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }

    #[OA\Patch(
        path: '/products/{id}/name',
        summary: 'Rename a product',
        operationId: 'renameProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [new OA\Property(property: 'name', type: 'string', maxLength: 255)],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product renamed', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function rename(RenameProductRequest $request, int $id): JsonResponse
    {
        $product = $this->commandBus->dispatch(new RenameProductCommand(
            productId: $id,
            name: (string) $request->validated('name'),
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }

    #[OA\Delete(
        path: '/products/{id}',
        summary: 'Soft delete a product',
        operationId: 'softDeleteProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Product soft deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
        ],
    )]
    public function softDelete(Request $request, int $id): Response
    {
        unset($request);

        $this->commandBus->dispatch(new SoftRemoveProductCommand(
            productId: $id,
        ));

        return ApiResponseFactory::noContent();
    }

    #[OA\Post(
        path: '/products/{id}/restore',
        summary: 'Restore a soft-deleted product',
        operationId: 'restoreProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product restored', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
        ],
    )]
    public function restore(Request $request, int $id): JsonResponse
    {
        unset($request);

        $product = $this->commandBus->dispatch(new RestoreProductCommand(
            productId: $id,
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }

    #[OA\Delete(
        path: '/products/{id}/link',
        summary: 'Remove product link',
        operationId: 'removeProductLink',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product link removed', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
        ],
    )]
    public function removeLink(Request $request, int $id): JsonResponse
    {
        unset($request);

        $product = $this->commandBus->dispatch(new RemoveProductLinkCommand(
            productId: $id,
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }

    #[OA\Post(
        path: '/products/{id}/reset',
        summary: 'Reset a product to virgin state',
        operationId: 'resetProduct',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product reset to virgin state', content: new OA\JsonContent(ref: '#/components/schemas/ProductEnvelope')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Product type cannot be reset (bracelet/fancelet types)'),
        ],
    )]
    public function reset(Request $request, int $id): JsonResponse
    {
        unset($request);

        $product = $this->commandBus->dispatch(new ResetProductCommand(
            productId: $id,
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }
}
