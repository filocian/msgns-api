<?php

namespace App\Exceptions\Product;

use Exception;
use Illuminate\Http\Response;

class ProductAlreadyActivated extends Exception
{
    protected $message = 'product_already_activated';
    protected $code = Response::HTTP_NOT_FOUND;
}
