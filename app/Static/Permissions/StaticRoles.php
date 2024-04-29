<?php

declare(strict_types=1);

namespace App\Static\Permissions;

final class StaticRoles
{
	public const string DEV_ROLE = 'developer';
	public const string BACKOFFICE_ROLE = 'backoffice';
	public const string DESIGNER_ROLE = 'designer';
	public const string MARKETING_ROLE = 'marketing';
	public const string USER_ROLE = 'user';

	public static function all(): array
	{
		return [self::DEV_ROLE, self::BACKOFFICE_ROLE, self::DESIGNER_ROLE, self::MARKETING_ROLE, self::USER_ROLE, ];
	}
}
