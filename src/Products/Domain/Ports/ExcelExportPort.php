<?php

declare(strict_types=1);

namespace Src\Products\Domain\Ports;

use Src\Products\Domain\DataTransfer\GeneratedProductsResult;

interface ExcelExportPort
{
    /**
     * Generate an Excel file from the given products result.
     *
     * One sheet per model code. Each row contains one redirect URL.
     * Returns the absolute path to the generated temp file.
     */
    public function generate(GeneratedProductsResult $result): string;
}
