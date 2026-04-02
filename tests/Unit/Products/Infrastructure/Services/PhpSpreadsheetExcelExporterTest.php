<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;
use Src\Products\Domain\DataTransfer\GeneratedProductItem;
use Src\Products\Domain\DataTransfer\GeneratedProductsResult;
use Src\Products\Infrastructure\Services\PhpSpreadsheetExcelExporter;

describe('PhpSpreadsheetExcelExporter', function () {
    it('generates bytes as non empty xlsx binary', function () {
        $exporter = new PhpSpreadsheetExcelExporter();

        $bytes = $exporter->generateBytes(new GeneratedProductsResult(
            totalCount: 2,
            productsByTypeCode: [
                'QR_BASIC' => [
                    new GeneratedProductItem(1, 'QR_BASIC (1)', 'secret-1', 'QR_BASIC', 'https://front/secret-1'),
                    new GeneratedProductItem(2, 'QR_BASIC (2)', 'secret-2', 'QR_BASIC', 'https://front/secret-2'),
                ],
            ],
        ));

        expect($bytes)->not->toBe('')
            ->and(substr($bytes, 0, 2))->toBe('PK');
    });

    it('generates the same workbook content via generateBytes as via generate', function () {
        $exporter = new PhpSpreadsheetExcelExporter();
        $result = new GeneratedProductsResult(
            totalCount: 2,
            productsByTypeCode: [
                'QR_BASIC' => [
                    new GeneratedProductItem(1, 'QR_BASIC (1)', 'secret-1', 'QR_BASIC', 'https://front/secret-1'),
                ],
                'NFC_PRO' => [
                    new GeneratedProductItem(2, 'NFC_PRO (2)', 'secret-2', 'NFC_PRO', 'https://front/secret-2'),
                ],
            ],
        );

        $bytes = $exporter->generateBytes($result);
        $filePath = $exporter->generate($result);

        $bytesFilePath = tempnam(sys_get_temp_dir(), 'msgns_products_bytes_') . '.xlsx';
        file_put_contents($bytesFilePath, $bytes);

        $bytesSpreadsheet = IOFactory::load($bytesFilePath);
        $fileSpreadsheet = IOFactory::load($filePath);

        expect($bytesSpreadsheet->getSheetNames())->toBe($fileSpreadsheet->getSheetNames())
            ->and($bytesSpreadsheet->getSheetByName('QR_BASIC')?->getCell('A2')->getValue())->toBe('QR_BASIC (1)')
            ->and($fileSpreadsheet->getSheetByName('QR_BASIC')?->getCell('A2')->getValue())->toBe('QR_BASIC (1)')
            ->and($bytesSpreadsheet->getSheetByName('NFC_PRO')?->getCell('B2')->getValue())->toBe('https://front/secret-2')
            ->and($fileSpreadsheet->getSheetByName('NFC_PRO')?->getCell('B2')->getValue())->toBe('https://front/secret-2');

        @unlink($bytesFilePath);
        @unlink($filePath);
    });
});
