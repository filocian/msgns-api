<?php

namespace App\Exceptions\NFC;

use Exception;
use Illuminate\Http\Response;

class NFCNotFoundException extends Exception
{
    protected $message = 'nfc_not_found';
    protected $code = Response::HTTP_NOT_FOUND;
}
