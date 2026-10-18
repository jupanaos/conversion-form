<?php

namespace App\Exception;

class InvalidLineException extends ConversionException
{
    public function __construct(
        public readonly int $lineNumber,
        string $reason,
    ) {
        parent::__construct(sprintf('Ligne %d : %s', $lineNumber, $reason));
    }

    public function getStatusCode(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'invalid_line';
    }

    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }
}
