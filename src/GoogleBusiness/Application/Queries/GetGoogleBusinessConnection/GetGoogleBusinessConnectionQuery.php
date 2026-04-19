<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Application\Queries\GetGoogleBusinessConnection;

use Src\Shared\Core\Bus\Query;

final readonly class GetGoogleBusinessConnectionQuery implements Query
{
    public function __construct(
        public int $userId,
    ) {}

    public function queryName(): string
    {
        return 'google_business.get_connection';
    }
}
