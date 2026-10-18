<?php

namespace App\Conversion;

class CsvRow
{
    public function __construct(
        public readonly string $entryNumber,
        public readonly string $account,
        public readonly string $accountLabel,
        public readonly string $date,
        public readonly string $journal,
        public readonly string $reference,
        public readonly string $label,
        public readonly string $credit,
        public readonly string $debit,
        public readonly string $letter,
        public readonly string $fileName,
    ) {
    }

    /** @return string[] */
    public function toArray(): array
    {
        return [
            $this->entryNumber, $this->account, $this->accountLabel, $this->date,
            $this->journal, $this->reference, $this->label, $this->credit,
            $this->debit, $this->letter, $this->fileName,
        ];
    }
}
