<?php

namespace App\Exceptions;

use Exception;

class InsufficientBalanceException extends Exception
{
    public function __construct(
        private string $required,
        private string $available,
    ) {
        parent::__construct('Insufficient balance');
    }

    public function getRequired(): string
    {
        return $this->required;
    }

    public function getAvailable(): string
    {
        return $this->available;
    }
}
