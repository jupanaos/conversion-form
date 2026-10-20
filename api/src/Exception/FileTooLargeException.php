<?php

namespace App\Exception;

class FileTooLargeException extends ConversionException
{
    public function __construct(int $maxSize)
    {
        parent::__construct(sprintf('Le fichier dépasse la taille maximale autorisée (%s Mo).', round($maxSize / 1024 / 1024, 1)));
    }

    public function getStatusCode(): int
    {
        return 413;
    }

    public function getErrorCode(): string
    {
        return 'file_too_large';
    }
}
