<?php

declare(strict_types=1);

namespace Src\Ai\Application\Queries\GetPrepaidPackages;

use Src\Shared\Core\Bus\Query;

final readonly class GetPrepaidPackagesQuery implements Query
{
    public function queryName(): string
    {
        return 'ai.get_prepaid_packages';
    }
}
