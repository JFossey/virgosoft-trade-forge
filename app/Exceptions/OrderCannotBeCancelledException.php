<?php

namespace App\Exceptions;

use Exception;

class OrderCannotBeCancelledException extends Exception
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }
}
