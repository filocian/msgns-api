<?php

namespace App\Exceptions\Product;

use Exception;
use Illuminate\Http\Response;

class InvalidProductTypeException extends Exception
{
    protected $message = 'invalid_product_type';
    protected $code = Response::HTTP_NOT_FOUND;
}
