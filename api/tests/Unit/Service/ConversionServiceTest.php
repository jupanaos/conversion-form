<?php

namespace App\Tests\Unit\Service;

use App\Conversion\LineParser;
use App\Conversion\OutputCsvWriter;
use App\Conversion\SourceEntryMapper;
use App\Exception\InvalidFileException;
use App\Exception\InvalidLineException;
use App\Service\ConversionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConversionServiceTest extends TestCase
{
    private const BOM = "\xEF\xBB\xBF";
    private const HEADER = "Numéro d'écriture;Compte;Compte libellé;Date de pièce;Journal;Référence;Ecriture libellé;Crédit;Débit;Lettre;NomFichier\n";

    private ConversionService $service;

    protected function setUp(): void
    {
        $this->service = new ConversionService(new LineParser(), new SourceEntryMapper(), new OutputCsvWriter());
    }

    private function line(string $account = '411000', string $subaccount = '41103176', string $label = 'AI17'): string
    {
        return sprintf('V1 ;04/05/2026;%s;%s;12887;%s;587.34;0.00;', $account, $subaccount, $label);
    }

    private function csvRow(string $account, string $label): string
    {
        return sprintf("12887;%s;;20260504;V1;12887;%s;0.00;587.34;;\n", $account, $label);
    }

    public function testSampleIsConvertedByteForByte(): void
    {
        $fixtures = dirname(__DIR__, 2).'/Fixtures';

        $this->assertSame(
            file_get_contents($fixtures.'/VENTE_FINAL.csv'),
            $this->service->convert(file_get_contents($fixtures.'/VENTE_ORIGINE.TXT')),
        );
    }

    public function testEmptyContentIsRejected(): void
    {
        $this->expectException(InvalidFileException::class);

        $this->service->convert('');
    }

    public function testBlankLinesOnlyAreRejected(): void
    {
        $this->expectException(InvalidFileException::class);

        $this->service->convert("\r\n  \r\n\r\n");
    }

    public function testLineNumberIsKeptWhenBlankLinesAreSkipped(): void
    {
        $this->expectException(InvalidLineException::class);
        $this->expectExceptionMessageIsOrContains('Ligne 3');

        $this->service->convert($this->line()."\r\n\r\nV1;colonnes manquantes\r\n");
    }

    public function testHeaderLineInInputIsRejectedAsLineOne(): void
    {
        $this->expectException(InvalidLineException::class);
        $this->expectExceptionMessageIsOrContains('Ligne 1');

        $this->service->convert(self::HEADER.$this->line()."\r\n");
    }

    public function testCrlfAndLfGiveTheSameOutput(): void
    {
        $lines = [$this->line(), $this->line('70600000', '')];

        $this->assertSame(
            $this->service->convert(implode("\r\n", $lines)."\r\n"),
            $this->service->convert(implode("\n", $lines)."\n"),
        );
    }

    public function testSubaccountReplacesAccountWhateverTheGeneralAccount(): void
    {
        $content = $this->line('411002', 'CLIENT02')."\r\n".$this->line('70600000', '');

        $this->assertSame(
            self::BOM.self::HEADER.$this->csvRow('CLIENT02', 'AI17').$this->csvRow('70600000', 'AI17'),
            $this->service->convert($content),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function encodedContents(): array
    {
        $utf8 = 'V1 ;04/05/2026;70600000;;12887;AUTOSPHÈRE;587.34;0.00;';

        return [
            'windows-1252' => [mb_convert_encoding($utf8, 'Windows-1252', 'UTF-8')],
            'utf-8' => [$utf8],
            'utf-8 with BOM' => [self::BOM.$utf8],
        ];
    }

    #[DataProvider('encodedContents')]
    public function testInputEncodingsGiveTheSameUtf8Output(string $content): void
    {
        $this->assertSame(
            self::BOM.self::HEADER.$this->csvRow('70600000', 'AUTOSPHÈRE'),
            $this->service->convert($content),
        );
    }
}
