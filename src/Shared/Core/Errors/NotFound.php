<?php

declare(strict_types=1);

namespace Src\Shared\Core\Errors;

use Symfony\Component\HttpFoundation\Response;

final class NotFound extends DomainException
{
	public static function entity(string $type, string $id): self
	{
		return new self(sprintf('%s_not_found', $type), Response::HTTP_NOT_FOUND, ['id' => $id]);
	}
}
