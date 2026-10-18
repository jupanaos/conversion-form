<?php

namespace App\Tests\Unit;

use App\Exception\InvalidLineException;
use App\Conversion\LineParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LineParserTest extends TestCase
{
    private LineParser $parser;

    protected function setUp(): void
    {
        $this->parser = new LineParser();
    }

    public function testParsesValidLine(): void
    {
        $entry = $this->parser->parse('V1 ;04/05/2026;411000;41103176  ;12887                              ;AI17                               ;              587.34;                0.00;', 1);

        $this->assertSame('V1', $entry->journal);
        $this->assertSame('04/05/2026', $entry->date->format('d/m/Y'));
        $this->assertSame('411000', $entry->account);
        $this->assertSame('41103176', $entry->subaccount);
        $this->assertSame('12887', $entry->reference);
        $this->assertSame('587.34', $entry->debit);
        $this->assertSame('0.00', $entry->credit);
    }

    #[DataProvider('invalidLines')]
    public function testRejectsInvalidLine(string $line, string $expectedMessagePart): void
    {
        try {
            $this->parser->parse($line, 7);
            $this->fail('InvalidLineException attendue');
        } catch (InvalidLineException $e) {
            $this->assertSame(7, $e->getLineNumber());
            $this->assertStringContainsString($expectedMessagePart, $e->getMessage());
        }
    }

    public function testAcceptsJournalZero(): void
    {
        $entry = $this->parser->parse('0;04/05/2026;411000;CLI001;F123;Lib;1.00;0.00;', 1);
        $this->assertSame('0', $entry->journal);
    }

    public function testTrimsAndKeepsEmptySubaccount(): void
    {
        $entry = $this->parser->parse('V1 ;04/05/2026;44571     ;      ;12887  ;TVA COLLECTEE   ;   0.00;  97.89;', 1);

        $this->assertSame('44571', $entry->account);
        $this->assertSame('', $entry->subaccount);
        $this->assertSame('TVA COLLECTEE', $entry->label);
        $this->assertSame('97.89', $entry->credit);
    }

    public function testKeepsApostropheAndInnerSpacesInLabel(): void
    {
        $entry = $this->parser->parse("V1;04/05/2026;411000;41102126;12902;VAL D'AUNIS  IMMO;1.00;0.00;", 1);

        $this->assertSame("VAL D'AUNIS  IMMO", $entry->label);
    }

    // TODO : use InvalidException
    public static function invalidLines(): iterable
    {
        yield 'trop peu de colonnes' => ['V1;04/05/2026;411000;', 'colonnes'];
        yield 'date invalide'        => ['V1;31/02/2026;411000;CLI001;F123;Lib;1.00;0.00;', 'Date'];
        yield 'date mal formatée'    => ['V1;2026-05-04;411000;CLI001;F123;Lib;1.00;0.00;', 'Date'];
        yield 'montant sans décimales' => ['V2;04/05/2026;411000;CLI001;F123;Lib;100;0.00;', 'Montant'];
        yield 'montant avec virgule' => ['V2;04/05/2026;411000;CLI001;F123;Lib;1,00;0.00;', 'Montant'];
        yield 'journal vide'         => [';04/05/2026;411000;CLI001;F123;Lib;1.00;0.00;', 'Journal'];
        yield 'pièce vide'           => ['V2;04/05/2026;411000;CLI001;;Lib;1.00;0.00;', 'Référence'];
        yield 'journal en espaces'     => ['   ;04/05/2026;411000;CLI001;F123;Lib;1.00;0.00;', 'Journal vide'];
        yield 'compte vide'         => ['V2;04/05/2026;;CLI001;F123;Lib;1.00;0.00;', 'Compte'];
        yield 'débit vide'          => ['V2;04/05/2026;411000;CLI001;F123;Lib;;0.00;', 'débit'];
        yield 'crédit vide'         => ['V2;04/05/2026;411000;CLI001;F123;Lib;1.00;;', 'crédit'];
        yield 'montant négatif'     => ['V2;04/05/2026;411000;CLI001;F123;Lib;-1.00;0.00;', 'débit'];
        yield 'trop de colonnes'    => ['V2;04/05/2026;411000;CLI001;F123;A;B;1.00;0.00;', 'colonnes'];
        yield 'ligne blanche'       => ['', 'colonnes'];
    }
}
