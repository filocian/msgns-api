<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;
use Src\Subscriptions\Infrastructure\Persistence\SubscriptionTypeModel;

final class SubscriptionTypeCatalogController extends Controller
{
    #[OA\Get(
        path: '/ai/subscription-types',
        summary: 'List available AI subscription plans',
        description: 'Returns all active subscription types (classic and prepaid). Public endpoint — no authentication required.',
        operationId: 'listAiSubscriptionTypes',
        tags: ['AI Subscriptions'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of active subscription plans',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'slug', type: 'string'),
                                    new OA\Property(property: 'description', type: 'string', nullable: true),
                                    new OA\Property(property: 'mode', type: 'string', enum: ['classic', 'prepaid']),
                                    new OA\Property(property: 'billing_periods', type: 'array', nullable: true, items: new OA\Items(type: 'string', enum: ['monthly', 'annual'])),
                                    new OA\Property(property: 'base_price_cents', type: 'integer'),
                                    new OA\Property(property: 'permission_name', type: 'string'),
                                    new OA\Property(property: 'google_review_limit', type: 'integer'),
                                    new OA\Property(property: 'instagram_content_limit', type: 'integer'),
                                ],
                                type: 'object',
                            ),
                        ),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function index(): JsonResponse
    {
        $types = SubscriptionTypeModel::query()
            ->where('is_active', true)
            ->orderBy('base_price_cents')
            ->get();

        $data = $types->map(fn (SubscriptionTypeModel $t): array => [
            'id'                      => $t->id,
            'name'                    => $t->name,
            'slug'                    => $t->slug,
            'description'             => $t->description,
            'mode'                    => $t->mode,
            'billing_periods'         => $t->billing_periods,
            'base_price_cents'        => $t->base_price_cents,
            'permission_name'         => $t->permission_name,
            'google_review_limit'     => $t->google_review_limit,
            'instagram_content_limit' => $t->instagram_content_limit,
        ])->values()->all();

        return ApiResponseFactory::ok($data);
    }
}
