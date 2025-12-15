<?php

namespace App\Exceptions;

use Exception;

class OrderNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Order not found');
    }
}
