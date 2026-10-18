<?php

namespace App\Exception;

abstract class ConversionException extends \RuntimeException
{
    abstract public function getStatusCode(): int;

    abstract public function getErrorCode(): string;

    public function getLineNumber(): ?int
    {
        return null;
    }
}