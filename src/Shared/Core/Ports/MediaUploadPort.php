<?php

declare(strict_types=1);

namespace Src\Shared\Core\Ports;

use Src\Shared\Core\Errors\MediaUploadFailed;

interface MediaUploadPort
{
	/**
	 * Upload a base64-encoded image for the given user.
	 * Generates a UUID-named object at `ai-media/{userId}/{Y/m/d}/{uuid}.{ext}`
	 * with public visibility.
	 *
	 * @throws MediaUploadFailed when MIME is unsupported or the filesystem refuses the write
	 * @return string Public URL of the uploaded object
	 */
	public function upload(string $base64Content, string $mimeType, int $userId): string;
}
