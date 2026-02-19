<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    protected $context;
    
    public function __construct(string $message, array $context = [], int $code = 500)
    {
        parent::__construct($message, $code);
        $this->context = $context;
    }
    
    public function getContext()
    {
        return $this->context;
    }
}