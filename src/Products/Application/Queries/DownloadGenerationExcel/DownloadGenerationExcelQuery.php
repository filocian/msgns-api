<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\DownloadGenerationExcel;

use Src\Shared\Core\Bus\Query;

final readonly class DownloadGenerationExcelQuery implements Query
{
    public function __construct(
        public int $generationId,
    ) {}

    public function queryName(): string
    {
        return 'products.download_generation_excel';
    }
}
