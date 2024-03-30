<?php

namespace App\Exceptions\NFC;

use Exception;
use Illuminate\Http\Response;

class NFCNotOwnedException extends Exception
{
    protected $message = 'nfc_not_owned';
    protected $code = Response::HTTP_FORBIDDEN;
}
