<?php

declare(strict_types=1);

namespace App\Exceptions\Product;

use Exception;
use Illuminate\Http\Response;

final class ProductAlreadyActivated extends Exception
{
	protected $message = 'product_already_activated';
	protected $code = Response::HTTP_UNAUTHORIZED;
}
