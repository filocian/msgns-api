<?php

namespace App\Exceptions\Product;

use Exception;
use Illuminate\Http\Response;

class ProductNotOwnedException extends Exception
{
    protected $message = 'product_not_owned';
    protected $code = Response::HTTP_FORBIDDEN;
}
