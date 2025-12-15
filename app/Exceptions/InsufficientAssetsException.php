<?php

namespace App\Exceptions;

use Exception;

class InsufficientAssetsException extends Exception
{
    public function __construct(
        private string $required,
        private string $available,
    ) {
        parent::__construct('Insufficient assets');
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
