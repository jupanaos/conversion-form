<?php

namespace App\Conversion;

class SourceEntryMapper
{
    /**
     * @param string $accountToReplace account with a 'subaccount' (ex: 411000), the subaccount replaces the account
     */
    public function toRow(SourceEntryDto $entry, string $accountToReplace): CsvRow
    {
        return new CsvRow(
            entryNumber:  $entry->reference,
            account:      $entry->account === $accountToReplace ? $entry->subaccount : $entry->account,
            accountLabel: '',
            date:         $entry->date->format('Ymd'),
            journal:      $entry->journal,
            reference:    $entry->reference,
            label:        preg_replace('/\s+/', ' ', $entry->label),
            credit:       $entry->credit,
            debit:        $entry->debit,
            letter:       '',
            fileName:     '',
        );
    }
}
