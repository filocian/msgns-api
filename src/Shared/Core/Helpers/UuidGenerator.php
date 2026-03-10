<?php

declare(strict_types=1);

namespace Src\Shared\Core\Helpers;

use Ramsey\Uuid\Uuid;

final class UuidGenerator
{
	public static function generate(): string
	{
		return Uuid::uuid4()->toString();
	}
}
