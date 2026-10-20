<?php

namespace App\Validation;

use App\Exception\ConversionException;
use App\Exception\FileTooLargeException;
use App\Exception\InvalidFileException;
use App\Exception\UnsupportedFileTypeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedFileValidator
{
    public function __construct(
        private readonly int $maxSize = 2 * 1024 * 1024,
        private readonly array $allowedExtensions = ['txt'],
    ) {
    }

    /** @throws ConversionException */
    public function validate(mixed $file): UploadedFile
    {
        if (!$file instanceof UploadedFile) {
            throw new InvalidFileException('Le format du fichier n\'est pas valide.');
        }

        if (\in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new FileTooLargeException($this->maxSize);
        }

        if (!$file->isValid()) {
            throw new InvalidFileException('Le fichier n\'a pas pu être reçu correctement.');
        }

        if ($file->getSize() > $this->maxSize) {
            throw new FileTooLargeException($this->maxSize);
        }

        if (!in_array(strtolower($file->getClientOriginalExtension()), $this->allowedExtensions, true)) {
            throw new UnsupportedFileTypeException($this->allowedExtensions);
        }

        return $file;
    }
}
