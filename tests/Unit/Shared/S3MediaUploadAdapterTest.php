<?php

declare(strict_types=1);

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;
use Src\Shared\Core\Errors\MediaUploadFailed;
use Src\Shared\Infrastructure\Adapters\S3MediaUploadAdapter;

beforeEach(function (): void {
	Storage::fake('s3');
});

describe('S3MediaUploadAdapter::upload', function (): void {

	it('uploads a jpeg image and returns the public URL under the user-scoped dated path', function (): void {
		$adapter = new S3MediaUploadAdapter();
		$userId  = 42;
		$base64  = base64_encode('binary-jpeg-bytes');

		$url = $adapter->upload($base64, 'image/jpeg', $userId);

		// URL shape: .../ai-media/42/YYYY/MM/DD/{uuid}.jpg
		expect($url)->toBeString()
			->and($url)->toMatch('#/ai-media/42/\d{4}/\d{2}/\d{2}/[0-9a-f\-]{36}\.jpg$#');

		// Extract the stored path from the URL (everything after the last /ai-media/)
		$matches = [];
		expect(preg_match('#(ai-media/42/\d{4}/\d{2}/\d{2}/[0-9a-f\-]{36}\.jpg)$#', $url, $matches))->toBe(1);
		$path = $matches[1];

		/** @var FilesystemAdapter $disk */
		$disk = Storage::disk('s3');
		expect($disk->exists($path))->toBeTrue()
			->and($disk->get($path))->toBe('binary-jpeg-bytes')
			->and($disk->getVisibility($path))->toBe('public');
	});

	it('maps image/png to .png extension', function (): void {
		$adapter = new S3MediaUploadAdapter();
		$url     = $adapter->upload(base64_encode('png-bytes'), 'image/png', 7);

		expect($url)->toMatch('#/ai-media/7/\d{4}/\d{2}/\d{2}/[0-9a-f\-]{36}\.png$#');
	});

	it('maps image/webp to .webp extension', function (): void {
		$adapter = new S3MediaUploadAdapter();
		$url     = $adapter->upload(base64_encode('webp-bytes'), 'image/webp', 99);

		expect($url)->toMatch('#/ai-media/99/\d{4}/\d{2}/\d{2}/[0-9a-f\-]{36}\.webp$#');
	});

	it('throws MediaUploadFailed with reason unsupported_mime_type on unknown MIME', function (): void {
		$adapter = new S3MediaUploadAdapter();

		try {
			$adapter->upload(base64_encode('bytes'), 'image/gif', 1);
			expect(true)->toBeFalse('Expected MediaUploadFailed to be thrown');
		} catch (MediaUploadFailed $e) {
			expect($e->errorCode())->toBe('media_upload_failed')
				->and($e->httpStatus())->toBe(502)
				->and($e->context()['reason'])->toBe('unsupported_mime_type');
		}
	});

	it('throws MediaUploadFailed with reason invalid_base64 on malformed base64', function (): void {
		$adapter = new S3MediaUploadAdapter();

		try {
			$adapter->upload('!!!not-valid-base64@@@', 'image/jpeg', 1);
			expect(true)->toBeFalse('Expected MediaUploadFailed to be thrown');
		} catch (MediaUploadFailed $e) {
			expect($e->errorCode())->toBe('media_upload_failed')
				->and($e->httpStatus())->toBe(502)
				->and($e->context()['reason'])->toBe('invalid_base64');
		}
	});

	it('throws MediaUploadFailed with reason s3_upload_failed when Flysystem rejects the write', function (): void {
		// Reset facade to use a mock instead of fake — we need put() to throw.
		\Mockery::close();

		$disk = \Mockery::mock(FilesystemAdapter::class);
		$disk->shouldReceive('put')
			->once()
			->andThrow(new UnableToWriteFile('simulated write failure'));

		Storage::shouldReceive('disk')
			->with('s3')
			->andReturn($disk);

		$adapter = new S3MediaUploadAdapter();

		try {
			$adapter->upload(base64_encode('bytes'), 'image/jpeg', 1);
			expect(true)->toBeFalse('Expected MediaUploadFailed to be thrown');
		} catch (MediaUploadFailed $e) {
			expect($e->errorCode())->toBe('media_upload_failed')
				->and($e->httpStatus())->toBe(502)
				->and($e->context()['reason'])->toBe('s3_upload_failed');
		}
	});
});
