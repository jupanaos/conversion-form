<?php

namespace App\Conversion;

class SourceEntryMapper
{
    public function toRow(SourceEntryDto $entry): CsvRow
    {
        return new CsvRow(
            entryNumber: $entry->reference,
            account: '' !== $entry->subaccount ? $entry->subaccount : $entry->account,
            accountLabel: '',
            date: $entry->date->format('Ymd'),
            journal: $entry->journal,
            reference: $entry->reference,
            label: preg_replace('/\s+/', ' ', $entry->label),
            credit: $entry->credit,
            debit: $entry->debit,
            letter: '',
            fileName: '',
        );
    }
}
