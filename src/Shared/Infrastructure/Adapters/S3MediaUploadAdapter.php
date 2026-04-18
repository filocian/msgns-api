<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Adapters;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\UnableToWriteFile;
use Src\Shared\Core\Errors\MediaUploadFailed;
use Src\Shared\Core\Ports\MediaUploadPort;

final class S3MediaUploadAdapter implements MediaUploadPort
{
	private const string DISK = 's3';

	/** @var array<string, string> MIME → file extension */
	private const array MIME_EXTENSION_MAP = [
		'image/jpeg' => 'jpg',
		'image/png'  => 'png',
		'image/webp' => 'webp',
	];

	public function upload(string $base64Content, string $mimeType, int $userId): string
	{
		if (! isset(self::MIME_EXTENSION_MAP[$mimeType])) {
			throw MediaUploadFailed::because('unsupported_mime_type', [
				'mime_type' => $mimeType,
			]);
		}

		$extension = self::MIME_EXTENSION_MAP[$mimeType];
		$uuid      = Str::uuid()->toString();
		$path      = sprintf(
			'ai-media/%d/%s/%s.%s',
			$userId,
			now()->format('Y/m/d'),
			$uuid,
			$extension,
		);

		$decoded = base64_decode($base64Content, strict: true);

		if ($decoded === false) {
			throw MediaUploadFailed::because('invalid_base64');
		}

		try {
			Storage::disk(self::DISK)->put($path, $decoded, 'public');
		} catch (UnableToWriteFile) {
			throw MediaUploadFailed::because('s3_upload_failed', [
				'path' => $path,
			]);
		}

		return (string) Storage::disk(self::DISK)->url($path);
	}
}
