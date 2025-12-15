<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedOrderAccessException extends Exception
{
    public function __construct()
    {
        parent::__construct('Unauthorized to access this order');
    }
}
