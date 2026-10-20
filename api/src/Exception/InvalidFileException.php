<?php

namespace App\Exception;

class InvalidFileException extends ConversionException
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }

    public function getStatusCode(): int
    {
        return 400;
    }

    public function getErrorCode(): string
    {
        return 'invalid_file';
    }
}
