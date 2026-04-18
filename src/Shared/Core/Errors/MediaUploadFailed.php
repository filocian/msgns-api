<?php

declare(strict_types=1);

namespace Src\Shared\Core\Errors;

use Symfony\Component\HttpFoundation\Response;

final class MediaUploadFailed extends DomainException
{
	/**
	 * @param array<string, mixed> $context
	 */
	public static function because(string $reason, array $context = []): self
	{
		return new self(
			'media_upload_failed',
			Response::HTTP_BAD_GATEWAY,
			['reason' => $reason] + $context,
		);
	}
}
