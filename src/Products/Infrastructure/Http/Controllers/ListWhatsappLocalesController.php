<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Products\Application\Queries\ListWhatsappLocales\ListWhatsappLocalesQuery;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ListWhatsappLocalesController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    public function __invoke(): JsonResponse
    {
        $locales = $this->queryBus->dispatch(new ListWhatsappLocalesQuery());

        return ApiResponseFactory::ok(['locales' => $locales]);
    }
}
