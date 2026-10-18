<?php

namespace App\Tests\Unit;

use App\Conversion\SourceEntryDto;
use App\Conversion\SourceEntryMapper;
use PHPUnit\Framework\TestCase;

class SourceEntryMapperTest extends TestCase
{
    private SourceEntryMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new SourceEntryMapper();
    }

    private function entry(array $customValues = []): SourceEntryDto
    {
        return new SourceEntryDto(
            journal:    $customValues['journal']    ?? 'V1',
            date:       new \DateTimeImmutable($customValues['date'] ?? '2026-05-04'),
            account:    $customValues['account']    ?? '411000',
            subaccount: $customValues['subaccount'] ?? '41103176',
            reference:  $customValues['reference']  ?? '12887',
            label:      $customValues['label']      ?? 'AI17',
            debit:      $customValues['debit']      ?? '587.34',
            credit:     $customValues['credit']     ?? '0.00',
        );
    }

    public function testAccountToReplaceIsReplacedBySubaccount(): void
    {
        $row = $this->mapper->toRow($this->entry(), '411000');

        $this->assertSame('41103176', $row->account);
    }

    public function testOtherAccountIsKeptAndSubaccountIgnored(): void
    {
        $row = $this->mapper->toRow($this->entry(['account' => '44571', 'subaccount' => '']), '411000');
        $this->assertSame('44571', $row->account);

        $row = $this->mapper->toRow($this->entry(['account' => '70600000', 'subaccount' => '999']), '411000');
        $this->assertSame('70600000', $row->account); // ambiguïté n°7
    }

    public function testAccountToReplaceIsConfigurable(): void
    {
        $row = $this->mapper->toRow($this->entry(['account' => '401000', 'subaccount' => 'FOU1']), '401000');

        $this->assertSame('FOU1', $row->account);
    }

    public function testDateIsFormattedYmd(): void
    {
        $this->assertSame('20260504', $this->mapper->toRow($this->entry(), '411000')->date);
        $this->assertSame('20261231', $this->mapper->toRow($this->entry(['date' => '2026-12-31']), '411000')->date);
    }

    public function testEntryNumberAndReferenceAreTheSamePiece(): void
    {
        $row = $this->mapper->toRow($this->entry(['reference' => '12887']), '411000');

        $this->assertSame('12887', $row->entryNumber);
        $this->assertSame('12887', $row->reference);
    }

    public function testJournalIsCopied(): void
    {
        $this->assertSame('V1', $this->mapper->toRow($this->entry(), '411000')->journal);
    }

    public function testLabelCollapsesRepeatedSpaces(): void
    {
        $row = $this->mapper->toRow($this->entry(['label' => 'CAFE MERLING  DISTRIBUTION   AUT']), '411000');

        $this->assertSame('CAFE MERLING DISTRIBUTION AUT', $row->label);
    }

    public function testLabelKeepsApostropheAndAccents(): void
    {
        $this->assertSame("VAL D'AUNIS", $this->mapper->toRow($this->entry(['label' => "VAL D'AUNIS"]), '411000')->label);
        $this->assertSame('AUTOSPHÈRE', $this->mapper->toRow($this->entry(['label' => 'AUTOSPHÈRE']), '411000')->label);
    }

    public function testCreditAndDebitAreSwappedInOutputOrder(): void
    {
        $row = $this->mapper->toRow($this->entry(['debit' => '587.34', 'credit' => '0.00']), '411000');

        $this->assertSame('0.00', $row->credit);
        $this->assertSame('587.34', $row->debit);
        $this->assertSame(['0.00', '587.34'], array_slice($row->toArray(), 7, 2));
    }

    public function testZeroAmountsAreKeptAsIs(): void
    {
        $row = $this->mapper->toRow($this->entry(['debit' => '0.00', 'credit' => '97.89']), '411000');

        $this->assertSame('97.89', $row->credit);
        $this->assertSame('0.00', $row->debit);
    }

    public function testConstantColumnsAreEmpty(): void
    {
        $row = $this->mapper->toRow($this->entry(), '411000');

        $this->assertSame('', $row->accountLabel);
        $this->assertSame('', $row->letter);
        $this->assertSame('', $row->fileName);
    }
}
