<?php

namespace App\Conversion;

class SourceEntryDto
{
    public function __construct(
        public readonly string $journal,
        public readonly \DateTimeImmutable $date,
        public readonly string $account,
        public readonly string $subaccount,
        public readonly string $reference,
        public readonly string $label,
        public readonly string $debit,
        public readonly string $credit,
    ) {
    }
}
