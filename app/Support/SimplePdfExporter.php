<?php

namespace App\Support;

class SimplePdfExporter
{
    public static function make(string $title, array $columns, array $rows): string
    {
        $widths = count($columns) === 2
            ? [130, 660]
            : [105, 98, 52, 62, 68, 124, 78, 96, 46, 65];
        $pages = [];
        $content = self::pageHeader($title);
        $y = 500;
        $content .= self::tableHeader($columns, $widths, $y);
        $y -= 24;

        foreach ($rows ?: [array_fill(0, count($columns), '-')] as $rowIndex => $row) {
            $rowHeight = self::rowHeight($row, $widths);

            if ($y - $rowHeight < 32) {
                $pages[] = $content;
                $content = self::pageHeader($title);
                $y = 500;
                $content .= self::tableHeader($columns, $widths, $y);
                $y -= 24;
            }

            $content .= self::tableRow($row, $widths, $y, $rowHeight, $rowIndex % 2 === 0 ? 'FFFFFF' : 'F7FAFF');
            $y -= $rowHeight;
        }

        $pages[] = $content;

        return self::document($pages);
    }

    private static function pageHeader(string $title): string
    {
        return self::text(34, 555, $title, 18).self::text(34, 535, 'Diekspor pada '.now()->format('d M Y H:i'), 9);
    }

    private static function tableHeader(array $columns, array $widths, int $y): string
    {
        $content = '';
        $x = 24;

        foreach ($columns as $index => $column) {
            $width = $widths[$index] ?? 70;
            $content .= self::rect($x, $y - 18, $width, 22, 'E5E7EB', true);
            $content .= self::text($x + 4, $y - 10, $column, 6.7);
            $x += $width;
        }

        return $content;
    }

    private static function tableRow(array $row, array $widths, int $y, int $height, string $fill): string
    {
        $content = '';
        $x = 24;

        foreach ($widths as $index => $width) {
            $value = (string) ($row[$index] ?? '-');
            $lines = self::wrap($value !== '' ? $value : '-', $width);
            $content .= self::rect($x, $y - $height + 4, $width, $height, $fill, true);

            foreach ($lines as $lineIndex => $line) {
                $content .= self::text($x + 4, $y - 10 - ($lineIndex * 9), $line, 6.5);
            }

            $x += $width;
        }

        return $content;
    }

    private static function rowHeight(array $row, array $widths): int
    {
        $lineCount = 1;

        foreach ($widths as $index => $width) {
            $lineCount = max($lineCount, count(self::wrap((string) ($row[$index] ?? '-'), $width)));
        }

        return max(24, 12 + ($lineCount * 9));
    }

    private static function wrap(string $value, int $width): array
    {
        $limit = max(6, (int) floor($width / 4.1));
        $value = trim(preg_replace('/\s+/', ' ', $value) ?: '-');
        $words = explode(' ', $value);
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            if (mb_strlen($word) > $limit) {
                if ($line !== '') {
                    $lines[] = $line;
                    $line = '';
                }

                foreach (str_split($word, $limit) as $part) {
                    $lines[] = $part;
                }

                continue;
            }

            $candidate = $line === '' ? $word : $line.' '.$word;

            if (mb_strlen($candidate) > $limit) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return array_slice($lines ?: ['-'], 0, 4);
    }

    private static function document(array $pages): string
    {
        $objects = ["<< /Type /Catalog /Pages 2 0 R >>"];
        $kids = [];
        $fontObject = count($pages) * 2 + 3;

        foreach ($pages as $index => $stream) {
            $pageObject = 3 + ($index * 2);
            $contentObject = $pageObject + 1;
            $kids[] = $pageObject.' 0 R';
            $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 $fontObject 0 R >> >> /Contents $contentObject 0 R >>";
            $objects[] = "<< /Length ".strlen($stream)." >>\nstream\n".$stream."\nendstream";
        }

        array_splice($objects, 1, 0, ['<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($pages).' >>']);
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n$object\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        for ($index = 1; $index <= count($objects); $index++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$index]);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    }

    private static function text(int $x, int $y, string $text, float $size): string
    {
        $clean = self::escape(mb_substr($text, 0, 150));

        return "0 0 0 rg BT /F1 $size Tf $x $y Td ($clean) Tj ET\n";
    }

    private static function rect(int $x, int $y, int $width, int $height, string $hex, bool $border = false): string
    {
        [$r, $g, $b] = [hexdec(substr($hex, 0, 2)) / 255, hexdec(substr($hex, 2, 2)) / 255, hexdec(substr($hex, 4, 2)) / 255];
        $command = sprintf("%.3F %.3F %.3F rg %d %d %d %d re f\n", $r, $g, $b, $x, $y, $width, $height);

        if ($border) {
            $command .= sprintf("0.82 0.84 0.87 RG %d %d %d %d re S\n", $x, $y, $width, $height);
        }

        return $command;
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\(', '\)', ' ', ' '], $text);
    }
}
