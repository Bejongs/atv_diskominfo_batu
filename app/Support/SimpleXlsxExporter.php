<?php

namespace App\Support;

class SimpleXlsxExporter
{
    public static function make(string $title, array $columns, array $rows): string
    {
        $sheetRows = [
            self::row([[$title, 2]], 1),
            self::row([['Diekspor pada '.now()->format('d M Y H:i'), 3]], 2),
            self::row(array_map(fn ($column) => [$column, 1], $columns), 4),
        ];

        foreach ($rows as $index => $row) {
            $sheetRows[] = self::row(array_map(fn ($cell) => [(string) ($cell ?: '-'), 0], $row), $index + 5);
        }

        $lastColumn = self::columnName(max(count($columns), 1));
        $worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols>'.self::columns(count($columns)).'</cols>'
            .'<sheetData>'.implode('', $sheetRows).'</sheetData>'
            .'<autoFilter ref="A4:'.$lastColumn.max(count($rows) + 4, 4).'"/>'
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

    private static function row(array $cells, int $rowNumber): string
    {
        $xml = '<row r="'.$rowNumber.'">';

        foreach ($cells as $index => [$value, $style]) {
            $coordinate = self::columnName($index + 1).$rowNumber;
            $xml .= '<c r="'.$coordinate.'" t="inlineStr" s="'.$style.'"><is><t>'.e($value).'</t></is></c>';
        }

        return $xml.'</row>';
    }

    private static function columns(int $count): string
    {
        $widths = $count === 2
            ? [28, 90]
            : [28, 24, 16, 16, 18, 36, 22, 24, 14, 22];
        $xml = '';

        for ($index = 1; $index <= $count; $index++) {
            $width = $widths[$index - 1] ?? 18;
            $xml .= '<col min="'.$index.'" max="'.$index.'" width="'.$width.'" customWidth="1"/>';
        }

        return $xml;
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
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="3"><font><sz val="11"/><name val="Arial"/></font><font><b/><color rgb="FF000000"/><sz val="11"/><name val="Arial"/></font><font><b/><color rgb="FF0B1739"/><sz val="16"/><name val="Arial"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE5E7EB"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF7FAFF"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD1D5DB"/></left><right style="thin"><color rgb="FFD1D5DB"/></right><top style="thin"><color rgb="FFD1D5DB"/></top><bottom style="thin"><color rgb="FFD1D5DB"/></bottom><diagonal/></border></borders><cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="1" fillId="1" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs></styleSheet>';
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
