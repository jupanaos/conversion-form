<?php

namespace App\Tests\Unit;

use App\Exception\ConversionException;
use App\Exception\InvalidFileException;
use App\Exception\InvalidLineException;
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
}
