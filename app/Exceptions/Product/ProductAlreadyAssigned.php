<?php

namespace App\Exceptions\Product;

use Exception;
use Illuminate\Http\Response;

class ProductAlreadyAssigned extends Exception
{
    protected $message = 'product_already_assigned';
    protected $code = Response::HTTP_NOT_FOUND;
}
