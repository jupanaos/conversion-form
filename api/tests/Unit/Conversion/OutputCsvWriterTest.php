<?php

namespace App\Tests\Unit\Conversion;

use App\Conversion\CsvRow;
use App\Conversion\OutputCsvWriter;
use PHPUnit\Framework\TestCase;

class OutputCsvWriterTest extends TestCase
{
    private const HEADER = "Numéro d'écriture;Compte;Compte libellé;Date de pièce;Journal;Référence;Ecriture libellé;Crédit;Débit;Lettre;NomFichier\n";

    private function row(string $label = 'AI17'): CsvRow
    {
        return new CsvRow('12887', '41103176', '', '20260504', 'V1', '12887', $label, '0.00', '587.34', '', '');
    }

    private function written(array $rows): string
    {
        return (new OutputCsvWriter())->write($rows);
    }

    public function testStartsWithUtf8Bom(): void
    {
        $this->assertStringStartsWith("\xEF\xBB\xBF", $this->written([]));
    }

    public function testHeaderOnlyWhenNoRows(): void
    {
        $this->assertSame("\xEF\xBB\xBF".self::HEADER, $this->written([]));
    }

    public function testWritesRowWithSemicolonsAndTrailingEmptyColumns(): void
    {
        $this->assertSame(
            "\xEF\xBB\xBF".self::HEADER."12887;41103176;;20260504;V1;12887;AI17;0.00;587.34;;\n",
            $this->written([$this->row()]),
        );
    }

    public function testUsesLfOnlyAndEndsWithNewline(): void
    {
        $out = $this->written([$this->row(), $this->row()]);

        $this->assertStringNotContainsString("\r", $out);
        $this->assertStringEndsWith("\n", $out);
        $this->assertSame(3, substr_count($out, "\n"));
    }

    public function testFieldsWithSpacesAreNotQuoted(): void
    {
        $this->assertStringContainsString(';TVA COLLECTEE;', $this->written([$this->row('TVA COLLECTEE')]));
    }

    public function testKeepsAccentsAndApostropheInUtf8(): void
    {
        $this->assertStringContainsString(";AUTOSPHÈRE D'OR;", $this->written([$this->row("AUTOSPHÈRE D'OR")]));
    }

    public function testQuoteIsEscapedByDoubling(): void
    {
        $this->assertStringContainsString(';"A ""B""";', $this->written([$this->row('A "B"')]));
    }
}
