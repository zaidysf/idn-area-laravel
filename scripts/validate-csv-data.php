#!/usr/bin/env php
<?php

/**
 * CSV Data Validation Script
 *
 * This script validates the CSV data files to ensure:
 * - No duplicate codes exist
 * - Data integrity is maintained
 * - File structure is correct
 *
 * Usage: php scripts/validate-csv-data.php
 */

declare(strict_types=1);

// ANSI color codes for terminal output
const COLOR_RED = "\033[31m";
const COLOR_GREEN = "\033[32m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";
const COLOR_RESET = "\033[0m";

function colorize(string $text, string $color): string
{
    return $color . $text . COLOR_RESET;
}

function success(string $message): void
{
    echo colorize("✓ ", COLOR_GREEN) . $message . PHP_EOL;
}

function error(string $message): void
{
    echo colorize("✗ ", COLOR_RED) . $message . PHP_EOL;
}

function warning(string $message): void
{
    echo colorize("⚠ ", COLOR_YELLOW) . $message . PHP_EOL;
}

function info(string $message): void
{
    echo colorize("ℹ ", COLOR_BLUE) . $message . PHP_EOL;
}

class CsvValidator
{
    private string $dataPath;
    private int $errorCount = 0;
    private int $warningCount = 0;

    public function __construct(string $dataPath)
    {
        $this->dataPath = $dataPath;
    }

    public function validate(): int
    {
        echo PHP_EOL;
        echo colorize("╔════════════════════════════════════════════════╗", COLOR_BLUE) . PHP_EOL;
        echo colorize("║   Indonesian Area Data - CSV Validator        ║", COLOR_BLUE) . PHP_EOL;
        echo colorize("╚════════════════════════════════════════════════╝", COLOR_BLUE) . PHP_EOL;
        echo PHP_EOL;

        $files = [
            'provinces' => [
                'file' => 'provinces.csv',
                'code_column' => 'code',
                'name_column' => 'name',
                'expected_columns' => ['code', 'name'],
            ],
            'regencies' => [
                'file' => 'regencies.csv',
                'code_column' => 'code',
                'name_column' => 'name',
                'expected_columns' => ['code', 'province_code', 'name'],
            ],
            'districts' => [
                'file' => 'districts.csv',
                'code_column' => 'code',
                'name_column' => 'name',
                'expected_columns' => ['code', 'regency_code', 'name'],
            ],
            'villages' => [
                'file' => 'villages.csv',
                'code_column' => 'code',
                'name_column' => 'name',
                'expected_columns' => ['code', 'district_code', 'name'],
            ],
        ];

        foreach ($files as $type => $config) {
            $this->validateFile($type, $config);
        }

        echo PHP_EOL;
        echo str_repeat("═", 50) . PHP_EOL;
        echo colorize("VALIDATION SUMMARY", COLOR_BLUE) . PHP_EOL;
        echo str_repeat("═", 50) . PHP_EOL;

        if ($this->errorCount === 0 && $this->warningCount === 0) {
            success("All CSV files passed validation!");
            echo colorize("  No errors or warnings found.", COLOR_GREEN) . PHP_EOL;
        } else {
            if ($this->errorCount > 0) {
                error("Found {$this->errorCount} error(s)");
            }
            if ($this->warningCount > 0) {
                warning("Found {$this->warningCount} warning(s)");
            }
        }

        echo PHP_EOL;

        return $this->errorCount > 0 ? 1 : 0;
    }

    private function validateFile(string $type, array $config): void
    {
        $filePath = $this->dataPath . '/' . $config['file'];

        echo colorize("Validating {$type}...", COLOR_BLUE) . PHP_EOL;

        // Check if file exists
        if (!file_exists($filePath)) {
            error("  File not found: {$filePath}");
            $this->errorCount++;
            echo PHP_EOL;
            return;
        }

        success("  File exists: {$config['file']}");

        // Read CSV file
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            error("  Cannot open file: {$filePath}");
            $this->errorCount++;
            echo PHP_EOL;
            return;
        }

        // Read header
        $header = fgetcsv($handle, 0, ',', '"', '');
        if ($header === false) {
            error("  Cannot read header from file");
            fclose($handle);
            $this->errorCount++;
            echo PHP_EOL;
            return;
        }

        // Validate header columns
        $missingColumns = array_diff($config['expected_columns'], $header);
        if (!empty($missingColumns)) {
            error("  Missing columns: " . implode(', ', $missingColumns));
            $this->errorCount++;
        } else {
            success("  Header structure is valid");
        }

        // Track codes and duplicates
        $codes = [];
        $duplicates = [];
        $rowCount = 0;
        $emptyRows = 0;

        // Read data rows
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $rowCount++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                $emptyRows++;
                continue;
            }

            // Check if row has correct number of columns
            if (count($row) !== count($header)) {
                warning("  Row {$rowCount}: Column count mismatch (expected " . count($header) . ", got " . count($row) . ")");
                $this->warningCount++;
                continue;
            }

            // Combine header with row to create associative array
            $data = array_combine($header, $row);

            if ($data === false) {
                warning("  Row {$rowCount}: Cannot parse row data");
                $this->warningCount++;
                continue;
            }

            $code = $data[$config['code_column']] ?? '';

            // Check for duplicate codes
            if (isset($codes[$code])) {
                $duplicates[] = [
                    'code' => $code,
                    'name' => $data[$config['name_column']] ?? 'N/A',
                    'first_row' => $codes[$code],
                    'duplicate_row' => $rowCount,
                ];
            } else {
                $codes[$code] = $rowCount;
            }

            // Validate code is not empty
            if (empty($code)) {
                warning("  Row {$rowCount}: Empty code found");
                $this->warningCount++;
            }
        }

        fclose($handle);

        // Report statistics
        info("  Total rows: " . number_format($rowCount));
        info("  Unique codes: " . number_format(count($codes)));

        if ($emptyRows > 0) {
            warning("  Empty rows skipped: {$emptyRows}");
            $this->warningCount++;
        }

        // Report duplicates
        if (!empty($duplicates)) {
            error("  DUPLICATE CODES FOUND: " . count($duplicates));
            $this->errorCount++;

            foreach (array_slice($duplicates, 0, 10) as $dup) {
                echo colorize("    • Code '{$dup['code']}' - First at row {$dup['first_row']}, duplicate at row {$dup['duplicate_row']}", COLOR_RED) . PHP_EOL;
            }

            if (count($duplicates) > 10) {
                echo colorize("    ... and " . (count($duplicates) - 10) . " more duplicates", COLOR_RED) . PHP_EOL;
            }
        } else {
            success("  No duplicate codes found");
        }

        echo PHP_EOL;
    }
}

// Main execution
$dataPath = __DIR__ . '/../database/seeders/data';

if (!is_dir($dataPath)) {
    error("Data directory not found: {$dataPath}");
    exit(1);
}

$validator = new CsvValidator($dataPath);
exit($validator->validate());
