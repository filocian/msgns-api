<?php

declare(strict_types=1);

namespace Src\Instagram\Infrastructure\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Src\Instagram\Domain\Errors\InstagramApiUnavailable;

final class InstagramImageStorageService
{
    private const string TEMP_PREFIX = 'ai/instagram/temp';

    /**
     * Upload a file to S3 with public visibility.
     *
     * @return array{0: string, 1: string} [$path, $publicUrl]
     */
    public function storeTemporary(UploadedFile $file): array
    {
        /** @var string|false $path */
        $path = Storage::disk('s3')->putFile(self::TEMP_PREFIX, $file, 'public');

        if ($path === false) {
            throw InstagramApiUnavailable::because('s3_upload_failed');
        }

        $url = Storage::disk('s3')->url($path);

        return [$path, $url];
    }

    /**
     * Delete a temporary image by its storage path (not URL).
     */
    public function deleteTemporary(string $path): void
    {
        Storage::disk('s3')->delete($path);
    }
}
