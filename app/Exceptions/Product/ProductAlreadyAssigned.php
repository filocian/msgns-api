<?php

declare(strict_types=1);

namespace App\Exceptions\Product;

use Exception;
use Illuminate\Http\Response;

final class ProductAlreadyAssigned extends Exception
{
	protected $message = 'product_already_assigned';
	protected $code = Response::HTTP_UNAUTHORIZED;
}
