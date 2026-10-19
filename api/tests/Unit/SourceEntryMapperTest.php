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
            journal: $customValues['journal'] ?? 'V1',
            date: new \DateTimeImmutable($customValues['date'] ?? '2026-05-04'),
            account: $customValues['account'] ?? '411000',
            subaccount: $customValues['subaccount'] ?? '41103176',
            reference: $customValues['reference'] ?? '12887',
            label: $customValues['label'] ?? 'AI17',
            debit: $customValues['debit'] ?? '587.34',
            credit: $customValues['credit'] ?? '0.00',
        );
    }

    public function testSubaccountReplacesAccountWhenPresent(): void
    {
        $row = $this->mapper->toRow($this->entry());

        $this->assertSame('41103176', $row->account);
    }

    public function testAccountIsKeptWhenSubaccountIsEmpty(): void
    {
        $row = $this->mapper->toRow($this->entry(['account' => '44571', 'subaccount' => '']));
        $this->assertSame('44571', $row->account);

        $row = $this->mapper->toRow($this->entry(['account' => '70600000', 'subaccount' => '']));
        $this->assertSame('70600000', $row->account);
    }

    public function testDateIsFormattedYmd(): void
    {
        $this->assertSame('20260504', $this->mapper->toRow($this->entry())->date);
        $this->assertSame('20261231', $this->mapper->toRow($this->entry(['date' => '2026-12-31']))->date);
    }

    public function testEntryNumberAndReferenceAreTheSamePiece(): void
    {
        $row = $this->mapper->toRow($this->entry(['reference' => '12887']));

        $this->assertSame('12887', $row->entryNumber);
        $this->assertSame('12887', $row->reference);
    }

    public function testJournalIsCopied(): void
    {
        $this->assertSame('V1', $this->mapper->toRow($this->entry())->journal);
    }

    public function testLabelCollapsesRepeatedSpaces(): void
    {
        $row = $this->mapper->toRow($this->entry(['label' => 'CAFE MERLING  DISTRIBUTION   AUT']));

        $this->assertSame('CAFE MERLING DISTRIBUTION AUT', $row->label);
    }

    public function testLabelKeepsApostropheAndAccents(): void
    {
        $this->assertSame("VAL D'AUNIS", $this->mapper->toRow($this->entry(['label' => "VAL D'AUNIS"]))->label);
        $this->assertSame('AUTOSPHÈRE', $this->mapper->toRow($this->entry(['label' => 'AUTOSPHÈRE']))->label);
    }

    public function testCreditAndDebitAreSwappedInOutputOrder(): void
    {
        $row = $this->mapper->toRow($this->entry(['debit' => '587.34', 'credit' => '0.00']));

        $this->assertSame('0.00', $row->credit);
        $this->assertSame('587.34', $row->debit);
        $this->assertSame(['0.00', '587.34'], array_slice($row->toArray(), 7, 2));
    }

    public function testZeroAmountsAreKeptAsIs(): void
    {
        $row = $this->mapper->toRow($this->entry(['debit' => '0.00', 'credit' => '97.89']));

        $this->assertSame('97.89', $row->credit);
        $this->assertSame('0.00', $row->debit);
    }

    public function testConstantColumnsAreEmpty(): void
    {
        $row = $this->mapper->toRow($this->entry());

        $this->assertSame('', $row->accountLabel);
        $this->assertSame('', $row->letter);
        $this->assertSame('', $row->fileName);
    }
}
