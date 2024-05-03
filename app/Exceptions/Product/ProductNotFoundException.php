<?php

declare(strict_types=1);

namespace App\Exceptions\Product;

use Exception;
use Illuminate\Http\Response;

final class ProductNotFoundException extends Exception
{
	protected $message = 'product_not_found';
	protected $code = Response::HTTP_NOT_FOUND;
}
