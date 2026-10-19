<?php

namespace App\Conversion;

class OutputCsvWriter
{
    private const SEPARATOR = ';';
    private const EOL = "\n";
    private const BOM = "\xEF\xBB\xBF";

    private const HEADER = [
        'Numéro d\'écriture',
        'Compte',
        'Compte libellé',
        'Date de pièce',
        'Journal',
        'Référence',
        'Ecriture libellé',
        'Crédit',
        'Débit',
        'Lettre',
        'NomFichier',
    ];

    /**
     * @param CsvRow[] $rows
     */
    public function write(array $rows): string
    {
        $csv = self::BOM.$this->writeRow(self::HEADER);

        foreach ($rows as $row) {
            $csv .= $this->writeRow($row->toArray());
        }

        return $csv;
    }

    /**
     * @param string[] $columns
     */
    private function writeRow(array $columns): string
    {
        return implode(self::SEPARATOR, array_map($this->escape(...), $columns)).self::EOL;
    }

    /**
     * Puts quote only when needed.
     */
    private function escape(string $field): string
    {
        if (!preg_match('/[;"\r\n]/', $field)) {
            return $field;
        }

        return '"'.str_replace('"', '""', $field).'"';
    }
}
