<?php

declare(strict_types=1);

namespace App\Support\Export;

use BackedEnum;
use DateTimeInterface;
use RuntimeException;

final class CsvWriter
{
    private const string DELIMITER = ';';

    private const string BOM = "\u{FEFF}";

    /**
     * @param  list<string>  $header
     * @param  iterable<array<int, mixed>>  $rows
     */
    public static function build(array $header, iterable $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException('Unable to open a temporary stream for CSV.');
        }

        fputcsv($stream, $header, self::DELIMITER, escape: '');

        foreach ($rows as $row) {
            fputcsv($stream, array_map(self::cell(...), $row), self::DELIMITER, escape: '');
        }

        rewind($stream);
        $contents = (string) stream_get_contents($stream);
        fclose($stream);

        return self::BOM.$contents;
    }

    public static function decimal(string|float|int|null $value): string
    {
        return $value === null ? '' : str_replace('.', ',', (string) $value);
    }

    private static function cell(mixed $value): string
    {
        $text = match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'так' : 'ні',
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            $value instanceof BackedEnum => (string) $value->value,
            default => (string) $value,
        };

        if ($text !== '' && ! is_numeric($text) && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$text;
        }

        return $text;
    }
}
