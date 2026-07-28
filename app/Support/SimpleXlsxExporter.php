<?php

namespace App\Support;

class SimpleXlsxExporter
{
    public static function makeReport(array $report): string
    {
        return self::zip([
            '[Content_Types].xml' => self::reportContentTypes(),
            '_rels/.rels' => self::rels(),
            'docProps/app.xml' => self::appProps(),
            'docProps/core.xml' => self::coreProps($report['title']),
            'xl/workbook.xml' => self::reportWorkbook(),
            'xl/_rels/workbook.xml.rels' => self::reportWorkbookRels(),
            'xl/styles.xml' => self::styles(),
            'xl/worksheets/sheet1.xml' => self::visualReportWorksheet($report),
            'xl/worksheets/_rels/sheet1.xml.rels' => self::sheetDrawingRels(),
            'xl/drawings/drawing1.xml' => self::drawing(),
            'xl/drawings/_rels/drawing1.xml.rels' => self::drawingRels(),
            'xl/media/report-visual.png' => self::reportVisualizationPng($report),
        ]);
    }

    public static function make(string $title, array $columns, array $rows): string
    {
        $normalizedRows = array_map(
            fn ($row) => array_map(fn ($cell) => (string) ($cell ?: '-'), $row),
            $rows
        );

        $sheetRows = [
            self::row([[$title, 2]], 1, 30),
            self::row([['Diskominfo Kota Batu', 3]], 2, 20),
            self::row([[self::dayLabel(now()).', '.now()->format('d M Y H:i'), 3]], 3, 20),
            self::row(array_map(fn ($column) => [$column, 1], $columns), 4, 24),
        ];

        foreach ($normalizedRows as $index => $row) {
            $sheetRows[] = self::row(array_map(fn ($cell) => [$cell, 0], $row), $index + 5, 22);
        }

        $worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="18"/>'
            .'<cols>'.self::columns(count($columns), $columns, $normalizedRows).'</cols>'
            .'<sheetData>'.implode('', $sheetRows).'</sheetData>'
            .'</worksheet>';

        return self::zip([
            '[Content_Types].xml' => self::contentTypes(),
            '_rels/.rels' => self::rels(),
            'docProps/app.xml' => self::appProps(),
            'docProps/core.xml' => self::coreProps($title),
            'xl/workbook.xml' => self::workbook(),
            'xl/_rels/workbook.xml.rels' => self::workbookRels(),
            'xl/styles.xml' => self::styles(),
            'xl/worksheets/sheet1.xml' => $worksheet,
        ]);
    }

    private static function reportWorksheet(array $report): string
    {
        $rows = [];
        $merges = [];
        $row = 1;

        $rows[] = self::reportRow([
            ['col' => 1, 'value' => $report['title'], 'style' => 2],
        ], $row);
        $merges[] = 'A1:P1';
        $row++;

        $rows[] = self::reportRow([
            ['col' => 1, 'value' => 'Diekspor pada '.$report['generated_at'].' | Periode: '.$report['period'], 'style' => 3],
        ], $row);
        $merges[] = 'A2:P2';
        $row += 2;

        foreach (array_values($report['summary_cards']) as $index => $card) {
            $startColumn = 1 + ($index * 4);
            $endColumn = $startColumn + 3;
            $style = 4 + $index;

            $rows[] = self::reportRow([
                ['col' => $startColumn, 'value' => $card['label'], 'style' => $style],
            ], $row);
            $rows[] = self::reportRow([
                ['col' => $startColumn, 'value' => (string) $card['value'], 'style' => $style + 4],
            ], $row + 1);
            $rows[] = self::reportRow([
                ['col' => $startColumn, 'value' => $card['hint'], 'style' => $style + 8],
            ], $row + 2);
            $merges[] = self::rangeRef($startColumn, $row, $endColumn, $row);
            $merges[] = self::rangeRef($startColumn, $row + 1, $endColumn, $row + 1);
            $merges[] = self::rangeRef($startColumn, $row + 2, $endColumn, $row + 2);
        }

        $row += 4;

        foreach ($report['charts'] as $chartIndex => $chart) {
            $rows[] = self::reportRow([
                ['col' => 1, 'value' => $chart['title'], 'style' => 16],
            ], $row);
            $merges[] = 'A'.$row.':P'.$row;
            $row++;

            $rows[] = self::reportRow([
                ['col' => 1, 'value' => 'Label', 'style' => 17],
                ['col' => 3, 'value' => 'Visual', 'style' => 17],
                ['col' => 13, 'value' => 'Jumlah', 'style' => 17],
                ['col' => 14, 'value' => 'Persen', 'style' => 17],
            ], $row);
            $merges[] = 'A'.$row.':B'.$row;
            $merges[] = 'C'.$row.':L'.$row;
            $merges[] = 'M'.$row.':M'.$row;
            $merges[] = 'N'.$row.':N'.$row;
            $row++;

            foreach ($chart['items'] as $item) {
                $cells = [
                    ['col' => 1, 'value' => $item['label'], 'style' => 0],
                    ['col' => 13, 'value' => $item['count'], 'style' => 0],
                    ['col' => 14, 'value' => $item['percent'].'%', 'style' => 0],
                ];

                $filled = max(1, (int) round(($item['percent'] / 100) * 10));
                for ($bar = 0; $bar < 10; $bar++) {
                    $cells[] = [
                        'col' => 3 + $bar,
                        'value' => '',
                        'style' => $bar < $filled ? $chartIndex + 9 : 18,
                    ];
                }

                $rows[] = self::reportRow($cells, $row);
                $merges[] = 'A'.$row.':B'.$row;
                $row++;
            }

            $row++;
        }

        $rows[] = self::reportRow([
            ['col' => 1, 'value' => 'Durasi & File', 'style' => 16],
        ], $row);
        $merges[] = 'A'.$row.':P'.$row;
        $row++;

        foreach ($report['technical'] as $item) {
            $rows[] = self::reportRow([
                ['col' => 1, 'value' => $item['label'], 'style' => 0],
                ['col' => 3, 'value' => $item['value'], 'style' => 0],
            ], $row);
            $merges[] = 'A'.$row.':B'.$row;
            $merges[] = 'C'.$row.':P'.$row;
            $row++;
        }

        $sheetRows = implode('', $rows);
        $mergeXml = '<mergeCells count="'.count($merges).'">'.implode('', array_map(fn ($ref) => '<mergeCell ref="'.$ref.'"/>', $merges)).'</mergeCells>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="3" topLeftCell="A4" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols>'.self::reportColumns().'</cols>'
            .'<sheetData>'.$sheetRows.'</sheetData>'
            .$mergeXml
            .'</worksheet>';
    }

    private static function visualReportWorksheet(array $report): string
    {
        $rows = [
            self::visualRow([[1, $report['title'], 2]], 1),
            self::visualRow([
                [1, 'Diekspor pada '.$report['generated_at'], 3],
                [8, 'Periode: '.$report['period'], 3],
            ], 2),
            self::visualRow([[1, 'Visualisasi Laporan', 1]], 4),
        ];

        $row = 35;
        $rows[] = self::visualRow([
            [1, 'Bagian', 1],
            [3, 'Indikator', 1],
            [8, 'Jumlah', 1],
            [10, 'Persentase', 1],
        ], $row++);

        foreach ($report['summary_cards'] as $card) {
            $rows[] = self::visualRow([
                [1, 'Ringkasan', 0],
                [3, $card['label'], 0],
                [8, (string) $card['value'], 0],
                [10, '-', 0],
            ], $row++);
        }

        foreach ($report['charts'] as $chart) {
            foreach ($chart['items'] as $item) {
                $rows[] = self::visualRow([
                    [1, $chart['title'], 0],
                    [3, $item['label'], 0],
                    [8, (string) ($item['count'] ?? 0), 0],
                    [10, ($item['percent'] ?? 0).'%', 0],
                ], $row++);
            }
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="3" topLeftCell="A4" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols>'.self::visualColumns().'</cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .'<drawing r:id="rId1"/>'
            .'</worksheet>';
    }

    private static function drawing(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            .'<xdr:twoCellAnchor editAs="oneCell">'
            .'<xdr:from><xdr:col>0</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>5</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>'
            .'<xdr:to><xdr:col>16</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>32</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to>'
            .'<xdr:pic>'
            .'<xdr:nvPicPr><xdr:cNvPr id="2" name="Visualisasi Laporan"/><xdr:cNvPicPr/></xdr:nvPicPr>'
            .'<xdr:blipFill><a:blip xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
            .'<xdr:spPr><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr>'
            .'</xdr:pic>'
            .'<xdr:clientData/>'
            .'</xdr:twoCellAnchor>'
            .'</xdr:wsDr>';
    }

    private static function sheetDrawingRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/></Relationships>';
    }

    private static function drawingRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/report-visual.png"/></Relationships>';
    }

    private static function visualRow(array $cells, int $rowNumber): string
    {
        $xml = '<row r="'.$rowNumber.'">';

        foreach ($cells as $cell) {
            $coordinate = self::columnName((int) $cell[0]).$rowNumber;
            $value = (string) ($cell[1] ?? '');
            $style = (int) ($cell[2] ?? 0);
            $xml .= '<c r="'.$coordinate.'" t="inlineStr" s="'.$style.'"><is><t>'.self::xmlText($value).'</t></is></c>';
        }

        return $xml.'</row>';
    }

    private static function visualColumns(): string
    {
        $widths = [24, 6, 24, 6, 14, 6, 14, 12, 6, 12, 6, 6, 6, 6, 6, 20];
        $xml = '';

        foreach ($widths as $index => $width) {
            $column = $index + 1;
            $xml .= '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }

        return $xml;
    }

    private static function barStyle(string $hex): int
    {
        return match (strtoupper($hex)) {
            '16A34A' => 11,
            'F97316', 'F59E0B' => 12,
            '8B5CF6', '7C3AED' => 13,
            'DC2626' => 14,
            default => 10,
        };
    }

    private static function reportVisualizationPng(array $report): string
    {
        $width = 1000;
        $height = 520;
        $image = array_fill(0, $height, str_repeat("\xFF\xFF\xFF", $width));

        self::pngRect($image, $width, 0, 0, $width, $height, 'FFFFFF');
        self::pngRect($image, $width, 0, 0, $width, 8, '2563EB');

        $cardWidth = 180;
        $cardGap = 16;
        foreach (array_values($report['summary_cards']) as $index => $card) {
            $x = 20 + ($index * ($cardWidth + $cardGap));
            self::pngRect($image, $width, $x, 34, $cardWidth, 72, 'F8FAFC');
            self::pngBorder($image, $width, $x, 34, $cardWidth, 72, 'CBD5E1');
            self::pngRect($image, $width, $x, 34, $cardWidth, 8, $card['color'] ?? '64748B');
            self::pngText($image, $width, $x + 16, 54, $card['label'], '0F172A', 2);
            self::pngText($image, $width, $x + 16, 76, (string) $card['value'], '0F172A', 3);
            self::pngText($image, $width, $x + 16, 98, $card['hint'], '64748B', 1);
        }

        $top = 132;
        foreach ($report['charts'] as $chart) {
            self::pngChart($image, $width, 20, $top, 960, 112, $chart);
            $top += 126;
        }

        return self::pngEncode($image, $width, $height);
    }

    private static function pngChart(array &$image, int $imageWidth, int $x, int $y, int $width, int $height, array $chart): void
    {
        self::pngRect($image, $imageWidth, $x, $y, $width, $height, 'FFFFFF');
        self::pngBorder($image, $imageWidth, $x, $y, $width, $height, 'CBD5E1');
        self::pngText($image, $imageWidth, $x + 18, $y + 16, $chart['title'], '0F172A', 2);

        $labelX = $x + 28;
        $barX = $x + 310;
        $barWidth = 520;
        $countX = $x + 850;
        $percentX = $x + 910;
        $rowY = $y + 42;

        foreach ($chart['items'] as $item) {
            $percent = max(0, min(100, (int) ($item['percent'] ?? 0)));
            self::pngText($image, $imageWidth, $labelX, $rowY - 1, $item['label'], '0F172A', 1);
            self::pngRect($image, $imageWidth, $barX, $rowY, $barWidth, 12, 'E2E8F0');
            self::pngBorder($image, $imageWidth, $barX, $rowY, $barWidth, 12, 'CBD5E1');
            $fillWidth = (int) round($barWidth * ($percent / 100));
            if ($fillWidth > 0) {
                self::pngRect($image, $imageWidth, $barX, $rowY, $fillWidth, 12, $item['color'] ?? $chart['color'] ?? '2563EB');
            }
            self::pngText($image, $imageWidth, $countX, $rowY - 1, ($item['count'] ?? 0).' data', '0F172A', 1);
            self::pngText($image, $imageWidth, $percentX, $rowY - 1, $percent.'%', '0F172A', 1);
            $rowY += 18;
        }
    }

    private static function pngText(array &$image, int $imageWidth, int $x, int $y, string $text, string $hex, int $scale = 1): void
    {
        $cursor = $x;
        $text = mb_strtoupper(mb_substr($text, 0, 36));

        foreach (str_split($text) as $char) {
            self::pngChar($image, $imageWidth, $cursor, $y, $char, $hex, $scale);
            $cursor += 6 * $scale;
        }
    }

    private static function pngChar(array &$image, int $imageWidth, int $x, int $y, string $char, string $hex, int $scale): void
    {
        $font = self::tinyFont();
        $glyph = $font[$char] ?? $font[' '];

        foreach ($glyph as $row => $bits) {
            for ($column = 0; $column < 5; $column++) {
                if ($bits[$column] === '1') {
                    self::pngRect($image, $imageWidth, $x + ($column * $scale), $y + ($row * $scale), $scale, $scale, $hex);
                }
            }
        }
    }

    private static function tinyFont(): array
    {
        static $font = null;

        if ($font !== null) {
            return $font;
        }

        $font = [
            ' ' => ['00000', '00000', '00000', '00000', '00000', '00000', '00000'],
            '-' => ['00000', '00000', '00000', '11111', '00000', '00000', '00000'],
            '+' => ['00000', '00100', '00100', '11111', '00100', '00100', '00000'],
            '(' => ['00010', '00100', '01000', '01000', '01000', '00100', '00010'],
            ')' => ['01000', '00100', '00010', '00010', '00010', '00100', '01000'],
            '%' => ['11001', '11010', '00100', '01000', '10110', '00110', '00000'],
            '0' => ['01110', '10001', '10011', '10101', '11001', '10001', '01110'],
            '1' => ['00100', '01100', '00100', '00100', '00100', '00100', '01110'],
            '2' => ['01110', '10001', '00001', '00010', '00100', '01000', '11111'],
            '3' => ['11110', '00001', '00001', '01110', '00001', '00001', '11110'],
            '4' => ['00010', '00110', '01010', '10010', '11111', '00010', '00010'],
            '5' => ['11111', '10000', '10000', '11110', '00001', '00001', '11110'],
            '6' => ['01110', '10000', '10000', '11110', '10001', '10001', '01110'],
            '7' => ['11111', '00001', '00010', '00100', '01000', '01000', '01000'],
            '8' => ['01110', '10001', '10001', '01110', '10001', '10001', '01110'],
            '9' => ['01110', '10001', '10001', '01111', '00001', '00001', '01110'],
        ];

        foreach (range('A', 'Z') as $letter) {
            $font[$letter] = match ($letter) {
                'A' => ['01110', '10001', '10001', '11111', '10001', '10001', '10001'],
                'B' => ['11110', '10001', '10001', '11110', '10001', '10001', '11110'],
                'C' => ['01111', '10000', '10000', '10000', '10000', '10000', '01111'],
                'D' => ['11110', '10001', '10001', '10001', '10001', '10001', '11110'],
                'E' => ['11111', '10000', '10000', '11110', '10000', '10000', '11111'],
                'F' => ['11111', '10000', '10000', '11110', '10000', '10000', '10000'],
                'G' => ['01111', '10000', '10000', '10111', '10001', '10001', '01111'],
                'H' => ['10001', '10001', '10001', '11111', '10001', '10001', '10001'],
                'I' => ['11111', '00100', '00100', '00100', '00100', '00100', '11111'],
                'J' => ['00111', '00010', '00010', '00010', '10010', '10010', '01100'],
                'K' => ['10001', '10010', '10100', '11000', '10100', '10010', '10001'],
                'L' => ['10000', '10000', '10000', '10000', '10000', '10000', '11111'],
                'M' => ['10001', '11011', '10101', '10101', '10001', '10001', '10001'],
                'N' => ['10001', '11001', '10101', '10011', '10001', '10001', '10001'],
                'O' => ['01110', '10001', '10001', '10001', '10001', '10001', '01110'],
                'P' => ['11110', '10001', '10001', '11110', '10000', '10000', '10000'],
                'Q' => ['01110', '10001', '10001', '10001', '10101', '10010', '01101'],
                'R' => ['11110', '10001', '10001', '11110', '10100', '10010', '10001'],
                'S' => ['01111', '10000', '10000', '01110', '00001', '00001', '11110'],
                'T' => ['11111', '00100', '00100', '00100', '00100', '00100', '00100'],
                'U' => ['10001', '10001', '10001', '10001', '10001', '10001', '01110'],
                'V' => ['10001', '10001', '10001', '10001', '10001', '01010', '00100'],
                'W' => ['10001', '10001', '10001', '10101', '10101', '10101', '01010'],
                'X' => ['10001', '10001', '01010', '00100', '01010', '10001', '10001'],
                'Y' => ['10001', '10001', '01010', '00100', '00100', '00100', '00100'],
                'Z' => ['11111', '00001', '00010', '00100', '01000', '10000', '11111'],
            };
        }

        return $font;
    }

    private static function pngRect(array &$image, int $imageWidth, int $x, int $y, int $width, int $height, string $hex): void
    {
        $rgb = self::pngRgb($hex);
        $maxY = min(count($image), $y + $height);
        $maxX = min($imageWidth, $x + $width);

        for ($row = max(0, $y); $row < $maxY; $row++) {
            for ($column = max(0, $x); $column < $maxX; $column++) {
                $offset = $column * 3;
                $image[$row][$offset] = $rgb[0];
                $image[$row][$offset + 1] = $rgb[1];
                $image[$row][$offset + 2] = $rgb[2];
            }
        }
    }

    private static function pngBorder(array &$image, int $imageWidth, int $x, int $y, int $width, int $height, string $hex): void
    {
        self::pngRect($image, $imageWidth, $x, $y, $width, 1, $hex);
        self::pngRect($image, $imageWidth, $x, $y + $height - 1, $width, 1, $hex);
        self::pngRect($image, $imageWidth, $x, $y, 1, $height, $hex);
        self::pngRect($image, $imageWidth, $x + $width - 1, $y, 1, $height, $hex);
    }

    private static function pngRgb(string $hex): array
    {
        $hex = str_pad(ltrim($hex, '#'), 6, '0');

        return [
            chr(hexdec(substr($hex, 0, 2))),
            chr(hexdec(substr($hex, 2, 2))),
            chr(hexdec(substr($hex, 4, 2))),
        ];
    }

    private static function pngEncode(array $rows, int $width, int $height): string
    {
        $raw = '';
        foreach ($rows as $row) {
            $raw .= "\0".$row;
        }

        return "\x89PNG\r\n\x1a\n"
            .self::pngChunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .self::pngChunk('IDAT', gzcompress($raw, 9))
            .self::pngChunk('IEND', '');
    }

    private static function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }

    private static function reportRow(array $cells, int $rowNumber): string
    {
        $xml = '<row r="'.$rowNumber.'">';

        foreach ($cells as $cell) {
            $coordinate = self::columnName($cell['col']).$rowNumber;
            $xml .= '<c r="'.$coordinate.'" t="inlineStr" s="'.$cell['style'].'"><is><t>'.self::xmlText((string) $cell['value']).'</t></is></c>';
        }

        return $xml.'</row>';
    }

    private static function reportColumns(): string
    {
        $widths = [22, 22, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 11, 10, 12, 12];
        $xml = '';

        foreach ($widths as $index => $width) {
            $col = $index + 1;
            $xml .= '<col min="'.$col.'" max="'.$col.'" width="'.$width.'" customWidth="1"/>';
        }

        return $xml;
    }

    private static function reportWorkbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Laporan Visual" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private static function reportWorkbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private static function rangeRef(int $startColumn, int $startRow, int $endColumn, int $endRow): string
    {
        return self::columnName($startColumn).$startRow.':'.self::columnName($endColumn).$endRow;
    }

    private static function reportRows(array $report): array
    {
        $rows = [
            ['Identitas', 'Periode', $report['period'], '-', 'Filter tanggal tayang yang digunakan'],
            ['Identitas', 'Tanggal generate', $report['generated_at'], '-', 'Waktu laporan dibuat'],
        ];

        foreach ($report['summary_cards'] as $card) {
            $rows[] = ['Ringkasan', $card['label'], $card['value'], '-', $card['hint']];
        }

        foreach ($report['charts'] as $chart) {
            foreach ($chart['items'] as $item) {
                $rows[] = [
                    $chart['title'],
                    $item['label'],
                    ($item['count'] ?? 0).' data',
                    self::bar((int) ($item['percent'] ?? 0)),
                    ($item['percent'] ?? 0).'% dari total arsip',
                ];
            }
        }

        foreach ($report['technical'] as $item) {
            $rows[] = ['Teknis', $item['label'], $item['value'], '-', 'Ringkasan teknis arsip'];
        }

        return $rows;
    }

    private static function bar(int $percent): string
    {
        $filled = (int) round(max(0, min(100, $percent)) / 5);

        return str_repeat('#', $filled).str_repeat('-', 20 - $filled).' '.$percent.'%';
    }

    private static function row(array $cells, int $rowNumber, ?int $height = null): string
    {
        $xml = '<row r="'.$rowNumber.'"'.($height ? ' ht="'.$height.'" customHeight="1"' : '').'>';

        foreach ($cells as $index => [$value, $style]) {
            $coordinate = self::columnName($index + 1).$rowNumber;
            $xml .= '<c r="'.$coordinate.'" t="inlineStr" s="'.$style.'"><is><t>'.self::xmlText((string) $value).'</t></is></c>';
        }

        return $xml.'</row>';
    }

    private static function columns(int $count, array $headers = [], array $rows = []): string
    {
        $widths = match ($count) {
            2 => [28, 90],
            3 => [32, 28, 72],
            6 => [22, 34, 28, 16, 28, 58],
            12 => [34, 30, 22, 24, 18, 18, 24, 44, 22, 34, 16, 22],
            default => [30, 24, 18, 18, 18, 24, 22, 32, 22, 28, 16, 22],
        };

        if ($headers !== []) {
            $widths = self::contentWidths($count, $headers, $rows, $widths);
        }

        $xml = '';

        for ($index = 1; $index <= $count; $index++) {
            $width = $widths[$index - 1] ?? 18;
            $xml .= '<col min="'.$index.'" max="'.$index.'" width="'.$width.'" customWidth="1" bestFit="1"/>';
        }

        return $xml;
    }

    private static function contentWidths(int $count, array $headers, array $rows, array $fallback): array
    {
        $limits = [
            'Judul' => [22, 42],
            'Kategori' => [16, 30],
            'Issue' => [14, 24],
            'Rating Usia' => [16, 26],
            'Status' => [14, 20],
            'Durasi' => [12, 18],
            'Rencana Tayang' => [18, 26],
            'Link Video' => [24, 48],
            'Pengunggah' => [16, 24],
            'Nama File' => [22, 40],
            'Ukuran' => [12, 16],
            'Dibuat' => [18, 24],
        ];

        $widths = [];

        for ($index = 0; $index < $count; $index++) {
            $header = (string) ($headers[$index] ?? '');
            [$min, $max] = $limits[$header] ?? [12, 32];
            $longest = mb_strlen($header);

            foreach ($rows as $row) {
                $value = (string) ($row[$index] ?? '');
                $longest = max($longest, mb_strlen($value));
            }

            $widths[] = min($max, max($min, $longest + 3, $fallback[$index] ?? $min));
        }

        return $widths;
    }

    private static function columnName(int $number): string
    {
        $name = '';

        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private static function dayLabel($date): string
    {
        return match ($date->format('l')) {
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            default => $date->format('l'),
        };
    }

    private static function zip(array $files): string
    {
        $local = '';
        $central = '';
        $offset = 0;

        foreach ($files as $name => $contents) {
            $crc = crc32($contents);
            $size = strlen($contents);
            $localHeader = "\x50\x4b\x03\x04".pack('v', 20).pack('v', 0).pack('v', 0).pack('v', 0).pack('v', 0).pack('V', $crc).pack('V', $size).pack('V', $size).pack('v', strlen($name)).pack('v', 0).$name;
            $central .= "\x50\x4b\x01\x02".pack('v', 20).pack('v', 20).pack('v', 0).pack('v', 0).pack('v', 0).pack('v', 0).pack('V', $crc).pack('V', $size).pack('V', $size).pack('v', strlen($name)).pack('v', 0).pack('v', 0).pack('v', 0).pack('v', 0).pack('V', 0).pack('V', $offset).$name;
            $local .= $localHeader.$contents;
            $offset += strlen($localHeader) + $size;
        }

        return $local.$central."\x50\x4b\x05\x06".pack('v', 0).pack('v', 0).pack('v', count($files)).pack('v', count($files)).pack('V', strlen($central)).pack('V', strlen($local)).pack('v', 0);
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    }

    private static function reportContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    }

    private static function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private static function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Arsip Video" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="4">'
            .'<font><sz val="11"/><name val="Arial"/></font>'
            .'<font><b/><color rgb="FF000000"/><sz val="11"/><name val="Arial"/></font>'
            .'<font><b/><color rgb="FF111827"/><sz val="16"/><name val="Arial"/></font>'
            .'<font><i/><color rgb="FF6B7280"/><sz val="11"/><name val="Arial"/></font>'
            .'</fonts>'
            .'<fills count="11">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFFFE699"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF6D28D9"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF2563EB"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF16A34A"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFF97316"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFEEF2FF"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFE5E7EB"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFDC2626"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF8B5CF6"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left style="thin"><color rgb="FFD1D5DB"/></left><right style="thin"><color rgb="FFD1D5DB"/></right><top style="thin"><color rgb="FFD1D5DB"/></top><bottom style="thin"><color rgb="FFD1D5DB"/></bottom><diagonal/></border>'
            .'</borders>'
            .'<cellXfs count="15">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="8" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="6" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="10" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="9" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    private static function xmlText(string $value): string
    {
        return str_replace("\n", '&#10;', e($value));
    }

    private static function appProps(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>ATV Arsip</Application></Properties>';
    }

    private static function coreProps(string $title): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>'.e($title).'</dc:title></cp:coreProperties>';
    }
}
