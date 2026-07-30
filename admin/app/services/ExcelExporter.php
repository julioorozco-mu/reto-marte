<?php
declare(strict_types=1);

class AdminExcelExporter
{
    public static function downloadParticipants(array $rows, array $filters = [], string $filename = 'rm_participantes.xlsx'): never
    {
        $headers = [
            'ID',
            'Fecha de registro',
            'Estatus',
            'Institucion',
            'Unidad / Plantel',
            'Semestre',
            'Carrera / Area',
            'Nombre(s)',
            'Apellido paterno',
            'Apellido materno',
            'Fecha de nacimiento',
            'Edad',
            'Sexo',
            'CURP',
            'Correo electronico',
            'Numero de telefono celular',
            'Estado',
            'Municipio',
            'Carta responsiva',
            'Certificado de estudios',
        ];

        $allUnach = count($rows) > 0;
        foreach ($rows as $row) {
            if (strtolower((string) ($row['institution'] ?? '')) !== 'unach') {
                $allUnach = false;
                break;
            }
        }
        $isUnachExport = $allUnach || strtolower((string) ($filters['institution'] ?? '')) === 'unach';

        if ($isUnachExport) {
            array_pop($headers); // Quita Certificado de estudios
            array_pop($headers); // Quita Carta responsiva
        }

        $sheetRows = [
            ['Reporte de participantes - Reto Marte'],
            ['Generado: ' . date('d/m/Y H:i') . ' | Filtros aplicados: ' . self::formatFilters($filters)],
            [],
            $headers,
        ];

        foreach ($rows as $row) {
            $isUnach = strtolower((string) ($row['institution'] ?? '')) === 'unach';
            $unitOrCampus = $isUnach ? (string) ($row['submission_unach_unit'] ?? $row['location_label'] ?? '') : (string) ($row['submission_cobach_campus'] ?? $row['location_label'] ?? '');
            $program = $isUnach ? (string) ($row['submission_unach_major'] ?? $row['program_label'] ?? '') : (string) ($row['submission_cobach_area'] ?? $row['program_label'] ?? '');
            $semester = $isUnach ? (string) ($row['submission_unach_semester'] ?? $row['semester'] ?? '') : (string) ($row['submission_cobach_semester'] ?? $row['semester'] ?? '');
            $firstName = $isUnach ? (string) ($row['submission_unach_first_name'] ?? '') : (string) ($row['submission_cobach_first_name'] ?? '');
            $lastName1 = $isUnach ? (string) ($row['submission_unach_last_name_1'] ?? '') : (string) ($row['submission_cobach_last_name_1'] ?? '');
            $lastName2 = $isUnach ? (string) ($row['submission_unach_last_name_2'] ?? '') : (string) ($row['submission_cobach_last_name_2'] ?? '');
            $birthdate = $isUnach ? (string) ($row['submission_unach_birthdate'] ?? $row['birthdate'] ?? '') : (string) ($row['submission_cobach_birthdate'] ?? $row['birthdate'] ?? '');
            $age = $isUnach ? (string) ($row['submission_unach_age'] ?? $row['age'] ?? '') : (string) ($row['submission_cobach_age'] ?? $row['age'] ?? '');
            $gender = $isUnach ? (string) ($row['submission_unach_gender'] ?? $row['gender'] ?? '') : (string) ($row['submission_cobach_gender'] ?? $row['gender'] ?? '');
            $curp = $isUnach ? (string) ($row['submission_unach_curp'] ?? $row['curp'] ?? '') : (string) ($row['submission_cobach_curp'] ?? $row['curp'] ?? '');
            $email = $isUnach ? (string) ($row['submission_unach_email'] ?? $row['email'] ?? '') : (string) ($row['submission_cobach_email'] ?? $row['email'] ?? '');
            $phone = $isUnach ? (string) ($row['submission_unach_phone'] ?? $row['phone'] ?? '') : (string) ($row['submission_cobach_phone'] ?? $row['phone'] ?? '');
            $state = $isUnach ? (string) ($row['submission_unach_state'] ?? $row['state_name'] ?? '') : (string) ($row['submission_cobach_state'] ?? $row['state_name'] ?? '');
            $city = $isUnach ? (string) ($row['submission_unach_city'] ?? $row['city_name'] ?? '') : (string) ($row['submission_cobach_city'] ?? $row['city_name'] ?? '');
            $responsiva = $isUnach ? '' : (string) ($row['submission_cobach_responsiva_path'] ?? $row['responsiva_file_path'] ?? '');
            $certificado = $isUnach ? '' : (string) ($row['submission_cobach_certificado_path'] ?? $row['certificado_file_path'] ?? '');

            $rowValues = [
                (string) ($row['id'] ?? ''),
                self::displayDateTime((string) ($row['created_at'] ?? '')),
                (string) ($row['current_status'] ?? 'Pendiente'),
                strtoupper((string) ($row['institution'] ?? '')),
                $unitOrCampus,
                $semester,
                $program,
                $firstName,
                $lastName1,
                $lastName2,
                self::displayDate((string) $birthdate),
                $age,
                $gender,
                $curp,
                $email,
                $phone,
                $state,
                $city,
            ];

            if (!$isUnachExport) {
                $rowValues[] = $responsiva;
                $rowValues[] = $certificado;
            }

            $sheetRows[] = $rowValues;
        }

        $xlsx = self::buildZipArchive([
            '[Content_Types].xml' => self::contentTypesXml(),
            '_rels/.rels' => self::rootRelsXml(),
            'docProps/app.xml' => self::appXml(),
            'docProps/core.xml' => self::coreXml(),
            'xl/workbook.xml' => self::workbookXml(),
            'xl/_rels/workbook.xml.rels' => self::workbookRelsXml(),
            'xl/styles.xml' => self::stylesXml(),
            'xl/worksheets/sheet1.xml' => self::sheetXml($sheetRows),
        ]);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) strlen($xlsx));
        header('Cache-Control: max-age=0');

        echo $xlsx;
        exit;
    }

    private static function buildZipArchive(array $entries): string
    {
        $archive = '';
        $centralDirectory = '';
        $offset = 0;
        [$dosTime, $dosDate] = self::dosDateTime();

        foreach ($entries as $name => $content) {
            $name = str_replace('\\', '/', $name);
            $content = (string) $content;
            $crc = crc32($content);
            if ($crc < 0) {
                $crc += 4294967296;
            }

            $length = strlen($content);
            $nameLength = strlen($name);

            $localHeader = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $length, $length, $nameLength, 0);
            $archive .= $localHeader . $name . $content;

            $centralDirectory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dosTime, $dosDate, $crc, $length, $length, $nameLength, 0, 0, 0, 0, 0, $offset) . $name;
            $offset = strlen($archive);
        }

        $endOfCentralDirectory = pack('VvvvvVVv', 0x06054b50, 0, 0, count($entries), count($entries), strlen($centralDirectory), strlen($archive), 0);
        return $archive . $centralDirectory . $endOfCentralDirectory;
    }

    private static function dosDateTime(): array
    {
        $now = getdate();
        $year = max(1980, (int) $now['year']);
        $dosTime = ((int) $now['hours'] << 11) | ((int) $now['minutes'] << 5) | (int) floor((int) $now['seconds'] / 2);
        $dosDate = (($year - 1980) << 9) | ((int) $now['mon'] << 5) | (int) $now['mday'];
        return [$dosTime, $dosDate];
    }

    private static function sheetXml(array $rows): string
    {
        $headerRow = 4;
        $lastColumn = self::columnName(count($rows[$headerRow - 1] ?? ['A']));
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="18"/>';
        $xml .= '<cols>';
        $numCols = count($rows[$headerRow - 1] ?? []);
        foreach (self::columnWidths($numCols) as $index => $width) {
            $xml .= '<col min="' . ($index + 1) . '" max="' . ($index + 1) . '" width="' . $width . '" customWidth="1"/>';
        }
        $xml .= '</cols>';
        $xml .= '<sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $xml .= '<row r="' . $rowNumber . '">';
            foreach ($row as $columnIndex => $value) {
                $cellRef = self::columnName($columnIndex + 1) . $rowNumber;
                $styleId = 0;
                if ($rowNumber === 1) {
                    $styleId = 1;
                } elseif ($rowNumber === 2) {
                    $styleId = 2;
                } elseif ($rowNumber === $headerRow) {
                    $styleId = 3;
                } elseif ($columnIndex === 0 || in_array($columnIndex, [1, 8, 13], true)) {
                    $styleId = 4;
                }
                $style = $styleId > 0 ? ' s="' . $styleId . '"' : '';
                $xml .= '<c r="' . $cellRef . '" t="inlineStr"' . $style . '><is><t>' . self::escape((string) $value) . '</t></is></c>';
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData>';
        $xml .= '<autoFilter ref="A' . $headerRow . ':' . $lastColumn . $headerRow . '"/>';
        $xml .= '<mergeCells count="2"><mergeCell ref="A1:' . $lastColumn . '1"/><mergeCell ref="A2:' . $lastColumn . '2"/></mergeCells>';
        $xml .= '</worksheet>';
        return $xml;
    }

    private static function columnWidths(int $count): array
    {
        $widths = [
            10, 22, 18, 15, 32, 12, 30, 22, 22, 22, 18, 8, 14, 20, 26, 18, 20, 20, 38, 38,
        ];
        return array_slice($widths, 0, $count);
    }

    private static function formatFilters(array $filters): string
    {
        $parts = [];
        $labels = [
            'q' => 'Busqueda',
            'institution' => 'Institucion',
            'status' => 'Estatus',
            'date_from' => 'Fecha inicial',
            'date_to' => 'Fecha final',
            'faculty' => 'Facultad',
            'plantel' => 'Plantel',
        ];

        foreach ($labels as $key => $label) {
            $value = trim((string) ($filters[$key] ?? ''));
            if ($value !== '') {
                $parts[] = $label . ': ' . strtoupper($key === 'institution' ? $value : $value);
            }
        }

        return $parts === [] ? 'Todos los registros' : implode(' | ', $parts);
    }

    private static function displayDateTime(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('d/m/Y H:i', $timestamp) : $value;
    }

    private static function displayDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('d/m/Y', $timestamp) : $value;
    }

    private static function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }
        return $name;
    }

    private static function escape(string $value): string
    {
        $value = (string) $value;
        // Remove control characters except tab, newline, and carriage return
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        // Escape XML special characters
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        return $value;
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private static function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Participantes" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="16"/><name val="Calibri"/></font>'
            . '<font><i/><sz val="10"/><color rgb="FF5B6572"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEAF4F8"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F9FC2"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="5">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private static function appXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>Reto Marte Backoffice</Application>'
            . '</Properties>';
    }

    private static function coreXml(): string
    {
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>Participantes Reto Marte</dc:title>'
            . '<dc:creator>Reto Marte Backoffice</dc:creator>'
            . '<cp:lastModifiedBy>Reto Marte Backoffice</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }
}
