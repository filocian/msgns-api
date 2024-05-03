<?php

declare(strict_types=1);

namespace App\Exceptions\Permissions;

use Exception;
use Illuminate\Http\Response;

final class ActionNotAllowedException extends Exception
{
	protected $message = 'you_are_not_allowed_to_perform_this_action';
	protected $code = Response::HTTP_NOT_FOUND;
}
