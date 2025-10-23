<?php

/**
 * RxcuiLoader.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc.
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services\CodeImport;

/**
 * Loader for RXCUI codes from RXNCONSO.RRF file
 */
class RxcuiLoader extends AbstractCodeLoader
{
    private const CODE_TYPE = 'RXCUI';

    // RRF file column positions for RXNCONSO.RRF
    private const COL_RXCUI = 0;
    private const COL_SAB = 11;
    private const COL_STR = 14;
    private const COL_SUPPRESS = 16;
    private const COL_CVF = 17;

    public function getCodeType(): string
    {
        return self::CODE_TYPE;
    }

    /**
     * Import RXCUI codes from RXNCONSO.RRF file
     *
     * Options:
     * - replace: bool - Delete existing codes before import
     * - old_method: bool - Use old row-by-row method for comparison
     */
    public function import(string $filePath, array $options = []): array
    {
        global $code_types;

        $replace = $options['replace'] ?? true;
        $useOldMethod = $options['old_method'] ?? $this->useOldMethod;

        // Validate code type exists
        if (empty($code_types[self::CODE_TYPE])) {
            throw new \RuntimeException('Code type not yet defined: ' . self::CODE_TYPE);
        }

        $codeTypeId = $code_types[self::CODE_TYPE]['id'];

        // Handle zip file if needed
        if (str_ends_with(strtolower($filePath), '.zip')) {
            $extractedPath = $this->extractFromZip($filePath, 'RXNCONSO.RRF');
            if (!$extractedPath) {
                throw new \RuntimeException('Unable to locate RXNCONSO.RRF in zip file');
            }
            $filePath = $extractedPath;
        }

        if (!$this->validate($filePath)) {
            throw new \RuntimeException('Invalid file path: ' . $filePath);
        }

        if ($useOldMethod) {
            return $this->importOldMethod($filePath, $codeTypeId, $replace);
        } else {
            return $this->importOptimized($filePath, $codeTypeId, $replace);
        }
    }

    /**
     * Optimized import using batched upserts
     */
    private function importOptimized(string $filePath, int $codeTypeId, bool $replace): array
    {
        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0
        ];

        try {
            $this->beginBulkImport();

            // Delete existing codes if replace is enabled
            if ($replace) {
                sqlStatementNoLog("DELETE FROM codes WHERE code_type = ?", [$codeTypeId]);
            }

            $batch = [];
            $seenCodes = [];

            foreach ($this->readFileLines($filePath) as $line) {
                $fields = explode('|', (string) $line);

                // Validate line format
                if (count($fields) < 18) {
                    $stats['skipped']++;
                    continue;
                }

                // Filter by CVF = 4096 (prescribable)
                if (($fields[self::COL_CVF] ?? '') !== '4096') {
                    $stats['skipped']++;
                    continue;
                }

                // Filter by SAB = RXNORM
                if (($fields[self::COL_SAB] ?? '') !== 'RXNORM') {
                    $stats['skipped']++;
                    continue;
                }

                $code = trim($fields[self::COL_RXCUI]);
                $codeText = trim($fields[self::COL_STR]);

                // Skip duplicates within file
                if (isset($seenCodes[$code])) {
                    $stats['skipped']++;
                    continue;
                }

                $seenCodes[$code] = true;

                // Add to batch
                $batch[] = [
                    $codeTypeId,  // code_type
                    $code,        // code
                    $codeText,    // code_text
                    0,            // fee
                    0             // units
                ];

                // Process batch when it reaches batch size
                if (count($batch) >= $this->batchSize) {
                    $result = $this->processBatch($batch, $codeTypeId, $replace);
                    $stats['inserted'] += $result['inserted'];
                    $stats['updated'] += $result['updated'];
                    $batch = [];
                }
            }

            // Process remaining batch
            if (!empty($batch)) {
                $result = $this->processBatch($batch, $codeTypeId, $replace);
                $stats['inserted'] += $result['inserted'];
                $stats['updated'] += $result['updated'];
            }

            $this->commitBulkImport();
        } catch (\Exception $e) {
            $this->rollbackBulkImport();
            throw $e;
        }

        return $stats;
    }

    /**
     * Process a batch of codes
     */
    private function processBatch(array $batch, int $codeTypeId, bool $replace): array
    {
        $columns = ['code_type', 'code', 'code_text', 'fee', 'units'];

        if ($replace) {
            // Simple insert since we deleted all codes
            $inserted = $this->batchInsert('codes', $columns, $batch, false);
            return ['inserted' => $inserted, 'updated' => 0];
        } else {
            // Use upsert to handle existing codes
            $updateColumns = ['code_text']; // Only update code_text on duplicate
            return $this->batchUpsert('codes', $columns, $batch, $updateColumns);
        }
    }

    /**
     * Old method for comparison/validation (matches original load_codes.php logic)
     */
    private function importOldMethod(string $filePath, int $codeTypeId, bool $replace): array
    {
        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0
        ];

        sqlStatementNoLog("SET autocommit=0");
        sqlStatementNoLog("START TRANSACTION");

        if ($replace) {
            sqlStatementNoLog("DELETE FROM codes WHERE code_type = ?", [$codeTypeId]);
        }

        $seenCodes = [];

        foreach ($this->readFileLines($filePath) as $line) {
            $fields = explode('|', (string) $line);

            if (count($fields) < 18) {
                continue;
            }

            if (($fields[self::COL_CVF] ?? '') !== '4096') {
                continue;
            }

            if (($fields[self::COL_SAB] ?? '') !== 'RXNORM') {
                continue;
            }

            $code = trim($fields[self::COL_RXCUI]);
            $codeText = trim($fields[self::COL_STR]);

            if (isset($seenCodes[$code])) {
                continue;
            }

            $seenCodes[$code] = true;

            if (!$replace) {
                $tmp = sqlQuery(
                    "SELECT id FROM codes WHERE code_type = ? AND code = ? LIMIT 1",
                    [$codeTypeId, $code]
                );
                if (!empty($tmp)) {
                    sqlStatementNoLog(
                        "UPDATE codes SET code_text = ? WHERE code_type = ? AND code = ?",
                        [$codeText, $codeTypeId, $code]
                    );
                    $stats['updated']++;
                    continue;
                }
            }

            sqlStatementNoLog(
                "INSERT INTO codes SET code_type = ?, code = ?, code_text = ?, fee = 0, units = 0",
                [$codeTypeId, $code, $codeText]
            );
            $stats['inserted']++;
        }

        sqlStatementNoLog("COMMIT");
        sqlStatementNoLog("SET autocommit=1");

        return $stats;
    }

    public function validate(string $filePath): bool
    {
        if (!parent::validate($filePath)) {
            return false;
        }

        // Additional validation - check if it's RRF format
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return false;
        }

        $firstLine = fgets($handle);
        fclose($handle);

        // RRF files use pipe delimiters and should have at least 18 fields
        $fields = explode('|', $firstLine);
        return count($fields) >= 18;
    }
}
