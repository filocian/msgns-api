<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Src\Products\Domain\DataTransfer\GeneratedProductItem;
use Src\Products\Domain\DataTransfer\GeneratedProductsResult;
use Src\Products\Domain\Ports\ExcelExportPort;

final class PhpSpreadsheetExcelExporter implements ExcelExportPort
{
    /**
     * Generate an Excel .xlsx file with one sheet per model code.
     * Each sheet has a header row ("Name", "Redirect URL") followed by one row per product.
     *
     * @return string Absolute path to the generated temp file
     */
    public function generate(GeneratedProductsResult $result): string
    {
        $spreadsheet = $this->buildSpreadsheet($result);

        $tempFile = tempnam(sys_get_temp_dir(), 'msgns_products_') . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }

    public function generateBytes(GeneratedProductsResult $result): string
    {
        $spreadsheet = $this->buildSpreadsheet($result);
        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');

        $bytes = ob_get_clean();

        return is_string($bytes) ? $bytes : '';
    }

    private function buildSpreadsheet(GeneratedProductsResult $result): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $sheetIndex = 0;

        foreach ($result->productsByTypeCode as $code => $items) {
            $sheet = $spreadsheet->createSheet($sheetIndex);
            $sheet->setTitle(mb_substr($code, 0, 31));

            $sheet->setCellValue('A1', 'Name');
            $sheet->setCellValue('B1', 'Redirect URL');

            $row = 2;
            foreach ($items as $item) {
                /** @var GeneratedProductItem $item */
                $sheet->setCellValue('A' . $row, $item->name);
                $sheet->setCellValue('B' . $row, $item->redirectUrl);
                $row++;
            }

            $sheetIndex++;
        }

        if ($spreadsheet->getSheetCount() > 0) {
            $spreadsheet->setActiveSheetIndex(0);
        }

        return $spreadsheet;
    }
}
