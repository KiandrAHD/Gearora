<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor CSV streaming (UTF-8) dengan proteksi formula injection.
 */
final class CsvExporter
{
    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int|string|null>>  $rows
     */
    public static function stream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            // BOM agar Excel membaca UTF-8 dengan benar.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_map([self::class, 'safe'], $headers));

            foreach ($rows as $row) {
                fputcsv($handle, array_map([self::class, 'safe'], $row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Cegah formula injection: nilai yang diawali karakter kontrol formula
     * diberi prefiks apostrof agar dievaluasi sebagai teks.
     */
    public static function safe(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $text = (string) $value;

        if (preg_match('/^[=+\-@\t\r]/', $text) === 1) {
            return "'".$text;
        }

        return $text;
    }
}
