<?php

namespace App\Tests\Unit\Http;

use App\Http\ConversionResponseFactory;
use PHPUnit\Framework\TestCase;

class ConversionResponseFactoryTest extends TestCase
{
    private ConversionResponseFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ConversionResponseFactory();
    }

    public function testBodyIsTheCsvUntouched(): void
    {
        $csv = "\xEF\xBB\xBFa;b\nc;é\n";

        $this->assertSame($csv, $this->factory->csv($csv, 'x.csv')->getContent());
    }

    public function testHeaders(): void
    {
        $headers = $this->factory->csv('a', 'ventes.csv')->headers;

        $this->assertSame('text/csv; charset=utf-8', $headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', $headers->get('Cache-Control'));
        $this->assertSame('nosniff', $headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('attachment; filename=ventes.csv', $headers->get('Content-Disposition'));
    }

    public function testAccentedFilenameIsEncoded(): void
    {
        $disposition = $this->factory->csv('a', 'Ventes été.csv')->headers->get('Content-Disposition');

        $this->assertStringContainsString("filename*=utf-8''Ventes%20%C3%A9t%C3%A9.csv", $disposition);
    }
}
