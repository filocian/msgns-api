<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Src\Instagram\Domain\Errors\InstagramApiUnavailable;
use Src\Instagram\Infrastructure\Services\InstagramImageStorageService;

describe('InstagramImageStorageService', function (): void {

    it('uploads file to s3 with public visibility and returns path and url', function (): void {
        Storage::fake('s3');

        $file    = UploadedFile::fake()->image('test.jpg');
        $service = new InstagramImageStorageService();

        [$path, $url] = $service->storeTemporary($file);

        expect($path)->toStartWith('ai/instagram/temp/')
            ->and($url)->toBeString()
            ->and($url)->not->toBeEmpty();

        Storage::disk('s3')->assertExists($path);
    });

    it('throws InstagramApiUnavailable when s3 upload fails', function (): void {
        $disk = Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $disk->shouldReceive('putFile')->andReturn(false);
        $disk->shouldReceive('url')->never();

        Storage::shouldReceive('disk')->with('s3')->andReturn($disk);

        $file    = UploadedFile::fake()->image('test.jpg');
        $service = new InstagramImageStorageService();

        expect(fn () => $service->storeTemporary($file))
            ->toThrow(InstagramApiUnavailable::class);
    });

    it('deletes temporary file by path', function (): void {
        Storage::fake('s3');

        $file    = UploadedFile::fake()->image('test.jpg');
        $service = new InstagramImageStorageService();

        [$path] = $service->storeTemporary($file);
        Storage::disk('s3')->assertExists($path);

        $service->deleteTemporary($path);
        Storage::disk('s3')->assertMissing($path);
    });
});
