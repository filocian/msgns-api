<?php

declare(strict_types=1);

namespace Src\Products\Domain\ValueObjects;

enum RedirectionType: string
{
    case EXTERNAL_URL = 'external_url';
    case FRONTEND_ROUTE = 'frontend_route';
}
