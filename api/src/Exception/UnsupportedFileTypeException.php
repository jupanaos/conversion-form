<?php

namespace App\Exception;

class UnsupportedFileTypeException extends ConversionException
{
    /**
     * @param string[] $allowedExtensions
     */
    public function __construct(array $allowedExtensions)
    {
        parent::__construct(sprintf('Type de fichier non pris en charge (extensions acceptées : %s).', implode(', ', $allowedExtensions)));
    }

    public function getStatusCode(): int
    {
        return 415;
    }

    public function getErrorCode(): string
    {
        return 'unsupported_file_type';
    }
}
