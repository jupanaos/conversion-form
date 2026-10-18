<?php

namespace App\Conversion;

use App\Conversion\SourceEntryDto;
use App\Exception\InvalidLineException;

class LineParser
{
    public function parse(string $line, int $lineNumber): SourceEntryDto
    {
        $columns = array_map('trim', explode(';', $line));

        if (count($columns) !== 9) {
            throw new InvalidLineException($lineNumber, 'Nombre de colonnes invalide (9 attendues)');
        }
        [$journal, $date, $account, $subaccount, $reference, $label, $debit, $credit] = $columns;

        if ($journal === '') {
            throw new InvalidLineException($lineNumber, 'Journal vide');
        }

        if ($reference === '') {
            throw new InvalidLineException($lineNumber, 'Référence vide');
        }

        if ($account === '') {
            throw new InvalidLineException($lineNumber, 'Compte vide');
        }

        if ($debit === '') {
            throw new InvalidLineException($lineNumber, 'Montant de débit vide');
        }

        if ($credit === '') {
            throw new InvalidLineException($lineNumber, 'Montant de crédit vide');
        }

        $dateTime = \DateTimeImmutable::createFromFormat('d/m/Y', $date);
        if (!$dateTime || $dateTime->format('d/m/Y') !== $date) {
            throw new \App\Exception\InvalidLineException($lineNumber, 'Date invalide ou mal formatée');
        }

        if (!preg_match('/^\d+\.\d{2}$/', $debit)) {
            throw new \App\Exception\InvalidLineException($lineNumber, 'Montant de débit invalide');
        }
        if (!preg_match('/^\d+\.\d{2}$/', $credit)) {
            throw new \App\Exception\InvalidLineException($lineNumber, 'Montant de crédit invalide');
        }

        return new SourceEntryDto(
            journal: $journal,
            date: $dateTime,
            account: $account,
            subaccount: $subaccount,
            reference: $reference,
            label: $label,
            debit: $debit,
            credit: $credit
        );
    }
}
