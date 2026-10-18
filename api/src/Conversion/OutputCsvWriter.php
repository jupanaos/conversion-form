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
    public function write(array $rows, string $outputFilePath): void
    {
        $fileHandle = fopen($outputFilePath, 'w');
        if ($fileHandle === false) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier pour l'écriture : $outputFilePath");
        }

        fwrite($fileHandle, self::BOM);
        $this->writeRow($fileHandle, self::HEADER);

        foreach ($rows as $row) {
            $this->writeRow($fileHandle, $row->toArray());
        }

        fclose($fileHandle);
    }

    /**
     * fputcsv() would put quotes to any field containing a space
     *
     * @param resource $fileHandle
     * @param string[] $columns
     */
    private function writeRow($fileHandle, array $columns): void
    {
        fwrite($fileHandle, implode(self::SEPARATOR, array_map($this->escape(...), $columns)) . self::EOL);
    }

    /** Puts quote only when needed */
    private function escape(string $field): string
    {
        if (!preg_match('/[;"\r\n]/', $field)) {
            return $field;
        }

        return '"' . str_replace('"', '""', $field) . '"';
    }
}
