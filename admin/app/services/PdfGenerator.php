<?php
declare(strict_types=1);

class AdminPdfGenerator
{
    public static function download(string $title, array $rows, string $filename): never
    {
        $columns = ['ID', 'Fecha', 'Nombre', 'Institucion', 'Ubicacion', 'Programa', 'Correo', 'Telefono', 'Estatus'];
        $lines = [];
        $lines[] = $title;
        $lines[] = 'Generado: ' . date('Y-m-d H:i');
        $lines[] = implode(' | ', $columns);
        $lines[] = str_repeat('-', 120);

        foreach ($rows as $row) {
            $lines[] = implode(' | ', [
                (string) ($row['id'] ?? ''),
                (string) ($row['created_at'] ?? ''),
                self::clip((string) ($row['full_name'] ?? ''), 28),
                strtoupper((string) ($row['institution'] ?? '')),
                self::clip((string) ($row['location_label'] ?? ''), 22),
                self::clip((string) ($row['program_label'] ?? ''), 24),
                self::clip((string) ($row['email'] ?? ''), 24),
                self::clip((string) ($row['phone'] ?? ''), 16),
                (string) ($row['current_status'] ?? 'Pendiente'),
            ]);
        }

        $pdf = self::buildPdf($lines);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private static function clip(string $text, int $max): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text));
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        return mb_substr($text, 0, $max - 3) . '...';
    }

    private static function buildPdf(array $lines): string
    {
        $pageWidth = 595;
        $pageHeight = 842;
        $left = 40;
        $top = 800;
        $lineHeight = 14;
        $maxLinesPerPage = 50;
        $pages = array_chunk($lines, $maxLinesPerPage);

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';

        $kids = [];
        $fontObjectNumber = 3;
        $nextObject = 4;
        $pageObjects = [];

        foreach ($pages as $pageLines) {
            $content = "BT\n/F1 12 Tf\n";
            $y = $top;
            foreach ($pageLines as $index => $line) {
                $size = $index === 0 ? 16 : 12;
                $font = $index === 0 ? '/F1 16 Tf' : '/F1 12 Tf';
                $content .= sprintf("/F1 %d Tf\n1 0 0 1 %d %d Tm\n(%s) Tj\n", $size, $left, $y, self::escape($line));
                $y -= $lineHeight;
            }
            $content .= "ET";
            $contentNumber = $nextObject++;
            $pageNumber = $nextObject++;
            $pageObjects[] = [$pageNumber, $contentNumber, $content];
            $kids[] = $pageNumber . ' 0 R';
        }

        $objects[] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        foreach ($pageObjects as [$pageNumber, $contentNumber, $content]) {
            while (count($objects) < $contentNumber - 1) {
                $objects[] = '';
            }
            $objects[$contentNumber - 1] = self::streamObject($content);

            while (count($objects) < $pageNumber - 1) {
                $objects[] = '';
            }
            $objects[$pageNumber - 1] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 3 0 R >> >> /Contents %d 0 R >>', $pageWidth, $pageHeight, $contentNumber);
        }

        $objects = array_values($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];
        foreach ($objects as $number => $object) {
            $offsets[$number + 1] = strlen($pdf);
            $pdf .= ($number + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";
        return $pdf;
    }

    private static function streamObject(string $content): string
    {
        $length = strlen($content);
        return '<< /Length ' . $length . " >>\nstream\n" . $content . "\nendstream";
    }

    private static function escape(string $text): string
    {
        $text = iconv('UTF-8', 'Windows-1252//TRANSLIT', $text) ?: $text;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
