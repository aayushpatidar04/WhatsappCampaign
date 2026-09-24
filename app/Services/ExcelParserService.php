<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelParserService
{
    /**
     * Parse an Excel/CSV file and return all rows as associative arrays.
     * First row is used as headers.
     */
    public function parse(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (empty($rows)) {
            return [];
        }

        // First row = headers
        $headers = array_values(array_map('trim', $rows[1]));

        $data = [];
        for ($i = 2; $i <= count($rows); $i++) {
            $row = $rows[$i];
            if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                continue; // skip empty rows
            }
            $data[] = array_combine($headers, array_values($row));
        }

        return $data;
    }

    /**
     * Get column names (headers) from an Excel/CSV file.
     */
    public function getColumns(string $filePath): array
    {
        $rows = $this->parse($filePath);
        if (empty($rows)) {
            return [];
        }
        return array_keys($rows[0]);
    }

    /**
     * Detect which column most likely contains phone numbers.
     */
    public function detectPhoneColumn(array $headers): ?string
    {
        $keywords = ['phone', 'mobile', 'number', 'contact', 'tel', 'wa', 'whatsapp', 'no', 'num'];
        foreach ($headers as $header) {
            $lower = strtolower(trim($header));
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    return $header;
                }
            }
        }
        return $headers[0] ?? null;
    }

    /**
     * Normalize a phone number: strip non-digits, apply country code if needed.
     */
    public static function normalizePhone(string $raw, string $countryCode = '91'): string
    {
        $digits = preg_replace('/[^0-9]/', '', $raw);
        if (strlen($digits) <= 10 && strlen($digits) >= 7) {
            $digits = $countryCode . $digits;
        }
        return $digits;
    }
}
