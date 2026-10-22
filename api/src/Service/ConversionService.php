<?php

namespace App\Service;

use App\Conversion\LineParser;
use App\Conversion\OutputCsvWriter;
use App\Conversion\SourceEntryMapper;
use App\Exception\InvalidFileException;

class ConversionService
{
    public function __construct(
        private readonly LineParser $lineParser,
        private readonly SourceEntryMapper $mapper,
        private readonly OutputCsvWriter $writer,
    ) {
    }

    public function convert(string $content): string
    {
        $rows = [];
        foreach ($this->splitLines($this->decode($content)) as $lineNumber => $line) {
            $rows[] = $this->mapper->toRow($this->lineParser->parse($line, $lineNumber));
        }

        if ([] === $rows) {
            throw new InvalidFileException('Le fichier est vide.');
        }

        return $this->writer->write($rows);
    }

    /**
     * Decodes the content to UTF-8, removing BOM if present
     * If the content is not UTF-8, it is assumed to be Windows-1252 and converted to UTF-8.
     */
    private function decode(string $content): string
    {
        if (mb_check_encoding($content, 'UTF-8')) {
            return str_starts_with($content, "\xEF\xBB\xBF") ? substr($content, 3) : $content;
        }

        return mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
    }

    /**
     * @return array<int, string>
     */
    private function splitLines(string $content): array
    {
        $lines = [];
        foreach (preg_split('/\r\n|\n|\r/', $content) as $index => $line) {
            if ('' !== trim($line)) {
                $lines[$index + 1] = $line;
            }
        }

        return $lines;
    }
}
