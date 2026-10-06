<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class CsvImportReader
{
    public function __construct(private Commerce $commerce) {}

    public function read(UploadedFile $file, array $columns, array $required, string $label): array
    {
        $contents = file_get_contents($file->getRealPath());
        if (! mb_check_encoding($contents, 'UTF-8') || str_contains($contents, "\0")) {
            $this->commerce->fail('Use a UTF-8 CSV file. Export as CSV UTF-8 from Excel.');
        }
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $contents));
        rewind($stream);
        try {
            $headers = fgetcsv($stream, 0, ',', '"', '');
            if (! $headers) {
                $this->commerce->fail('The CSV file is empty.');
            }
            $headers = array_map(fn ($header) => strtolower(trim($header)), $headers);
            if (count(array_unique($headers)) !== count($headers) || array_diff($headers, $columns)) {
                $this->commerce->fail('CSV headers must be unique supported column names. Download the template for the correct format.');
            }
            if (array_diff($required, $headers)) {
                $this->commerce->fail('Required CSV columns: '.implode(', ', $required).'.');
            }
            if ((in_array('brand', $headers) && in_array('brand_id', $headers)) || (in_array('categories', $headers) && in_array('category_ids', $headers))) {
                $this->commerce->fail('Use either brand/categories or their ID columns, not both.');
            }
            $rows = [];
            $number = 1;
            while (($values = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                $number++;
                if (count(array_filter($values, fn ($value) => trim($value ?? '') !== '')) === 0) {
                    continue;
                }
                if (count($rows) >= 500) {
                    $this->commerce->fail('Import up to 500 rows per CSV file.');
                }
                $rows[] = ['row' => $number, 'data' => count($values) === count($headers) ? array_combine($headers, array_map(fn ($value) => trim($value ?? ''), $values)) : null];
            }
            if (! $rows) {
                $this->commerce->fail('The CSV contains no '.$label.' rows.');
            }

            return $rows;
        } finally {
            fclose($stream);
        }
    }
}
