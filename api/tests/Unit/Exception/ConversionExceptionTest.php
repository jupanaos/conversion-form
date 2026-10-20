<?php

namespace App\Tests\Unit\Exception;

use App\Exception\ConversionException;
use App\Exception\FileTooLargeException;
use App\Exception\InvalidFileException;
use App\Exception\InvalidLineException;
use App\Exception\UnsupportedFileTypeException;
use PHPUnit\Framework\TestCase;

class ConversionExceptionTest extends TestCase
{
    public function testInvalidLineCarriesLineNumberAndFormatsMessage(): void
    {
        $e = new InvalidLineException(12, 'Date invalide');

        $this->assertInstanceOf(ConversionException::class, $e);
        $this->assertSame(12, $e->getLineNumber());
        $this->assertSame('Ligne 12 : Date invalide', $e->getMessage());
        $this->assertSame(422, $e->getStatusCode());
        $this->assertSame('invalid_line', $e->getErrorCode());
    }

    public function testInvalidFileHasNoLineNumber(): void
    {
        $e = new InvalidFileException('Fichier vide');

        $this->assertInstanceOf(ConversionException::class, $e);
        $this->assertNull($e->getLineNumber());
        $this->assertSame(400, $e->getStatusCode());
        $this->assertSame('invalid_file', $e->getErrorCode());
    }

    public function testFileTooLargeMapsTo413(): void
    {
        $e = new FileTooLargeException(2 * 1024 * 1024);

        $this->assertInstanceOf(ConversionException::class, $e);
        $this->assertNull($e->getLineNumber());
        $this->assertSame(413, $e->getStatusCode());
        $this->assertSame('file_too_large', $e->getErrorCode());
    }

    public function testUnsupportedFileTypeMapsTo415(): void
    {
        $e = new UnsupportedFileTypeException(['txt']);

        $this->assertInstanceOf(ConversionException::class, $e);
        $this->assertNull($e->getLineNumber());
        $this->assertSame(415, $e->getStatusCode());
        $this->assertSame('unsupported_file_type', $e->getErrorCode());
    }
}
