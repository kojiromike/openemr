<?php

/**
 * Icd10Loader.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc.
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services\CodeImport;

/**
 * Loader for ICD-10 codes (both DX and PCS)
 */
class Icd10Loader extends AbstractCodeLoader
{
    private const CODE_TYPE = 'ICD10';

    // File patterns and their configurations
    private const FILE_CONFIGS = [
        'icd10pcs_codes_' => [
            'table' => 'icd10_pcs_order_code',
            'fields' => [
                ['name' => 'pcs_code', 'pos' => 0, 'len' => 7],
                ['name' => 'long_desc', 'pos' => 8, 'len' => 300],
            ],
        ],
        'icd10cm_order_' => [
            'table' => 'icd10_dx_order_code',
            'fields' => [
                ['name' => 'dx_code', 'pos' => 6, 'len' => 7],
                ['name' => 'valid_for_coding', 'pos' => 14, 'len' => 1],
                ['name' => 'short_desc', 'pos' => 16, 'len' => 60],
                ['name' => 'long_desc', 'pos' => 77, 'len' => 300],
            ],
        ],
    ];

    public function getCodeType(): string
    {
        return self::CODE_TYPE;
    }

    /**
     * Import ICD-10 codes from text files
     *
     * Options:
     * - temp_dir: string - Directory where ICD10 files are extracted
     * - old_method: bool - Use old method for comparison
     */
    public function import(string $filePath, array $options = []): array
    {
        $tempDir = $options['temp_dir'] ?? ($GLOBALS['temporary_files_dir'] . '/ICD10');
        $useOldMethod = $options['old_method'] ?? $this->useOldMethod;

        if ($useOldMethod) {
            return $this->importOldMethod($tempDir);
        } else {
            return $this->importOptimized($tempDir);
        }
    }

    /**
     * Optimized import using batched inserts
     */
    private function importOptimized(string $tempDir): array
    {
        $stats = [
            'dx_inserted' => 0,
            'pcs_inserted' => 0,
            'errors' => 0,
        ];

        $dir = str_replace('\\', '/', $tempDir);

        if (!is_dir($dir)) {
            throw new \RuntimeException("ICD10 directory not found: $dir");
        }

        try {
            $this->firePreInstallEvent('ICD', ['type' => self::CODE_TYPE]);

            $this->beginBulkImport();

            // Get next revisions
            $dxRevision = $this->getNextRevision('icd10_dx_order_code');
            $pcsRevision = $this->getNextRevision('icd10_pcs_order_code');

            // Inactivate older sets
            sqlStatementNoLog("UPDATE icd10_pcs_order_code SET active = 0");
            sqlStatementNoLog("UPDATE icd10_dx_order_code SET active = 0");

            // Process files
            if ($handle = opendir($dir)) {
                while (false !== ($filename = readdir($handle))) {
                    // Skip non-text files and addenda files
                    if (!stripos($filename, '.txt') || stripos($filename, 'addenda')) {
                        continue;
                    }

                    $filePath = $dir . '/' . $filename;

                    // Determine file type and process
                    foreach (self::FILE_CONFIGS as $pattern => $config) {
                        if (stripos($filename, $pattern) !== false) {
                            $revision = ($config['table'] === 'icd10_dx_order_code') ? $dxRevision : $pcsRevision;
                            $rowCount = $this->processFile($filePath, $config, $revision);

                            if ($config['table'] === 'icd10_dx_order_code') {
                                $stats['dx_inserted'] += $rowCount;
                            } else {
                                $stats['pcs_inserted'] += $rowCount;
                            }
                            break;
                        }
                    }
                }
                closedir($handle);
            }

            $this->commitBulkImport();

            // Post-processing for dx codes
            $this->updateFormattedDxCodes();

            $this->firePostInstallEvent('ICD', ['type' => self::CODE_TYPE]);
        } catch (\Exception $e) {
            $this->rollbackBulkImport();
            throw $e;
        }

        return $stats;
    }

    /**
     * Process a single ICD10 file
     */
    private function processFile(string $filePath, array $config, int $revision): int
    {
        $batch = [];
        $table = $config['table'];
        $fields = $config['fields'];

        foreach ($this->readFileLines($filePath) as $line) {
            $row = [];

            // Extract fields based on position and length
            foreach ($fields as $field) {
                $value = substr((string) $line, $field['pos'], $field['len']);
                $row[] = $value;
            }

            // Add active and revision
            $row[] = 1; // active
            $row[] = $revision;

            $batch[] = $row;

            // Process batch when it reaches batch size
            if (count($batch) >= $this->batchSize) {
                $this->insertBatch($table, $fields, $batch);
                $count = count($batch);
                $batch = [];
            }
        }

        // Process remaining batch
        $count = 0;
        if (!empty($batch)) {
            $this->insertBatch($table, $fields, $batch);
            $count = count($batch);
        }

        return $count;
    }

    /**
     * Insert a batch of rows
     */
    private function insertBatch(string $table, array $fields, array $batch): void
    {
        $fieldNames = array_column($fields, 'name');
        $fieldNames[] = 'active';
        $fieldNames[] = 'revision';

        $this->batchInsert($table, $fieldNames, $batch, false);
    }

    /**
     * Update formatted dx codes
     */
    private function updateFormattedDxCodes(): void
    {
        sqlStatement("UPDATE `icd10_dx_order_code` SET formatted_dx_code = dx_code");
        sqlStatement("UPDATE `icd10_dx_order_code` SET formatted_dx_code = concat(concat(left(dx_code, 3), '.'), substr(dx_code, 4)) WHERE LENGTH(dx_code) > 3");
    }

    /**
     * Get next revision number for a table
     */
    private function getNextRevision(string $table): int
    {
        $result = sqlQueryNoLog("SELECT max(revision) rev FROM `$table`");
        return ((int)($result['rev'] ?? 0)) + 1;
    }

    /**
     * Old method for comparison (matches standard_tables_capture.inc.php)
     */
    private function importOldMethod(string $tempDir): array
    {
        $stats = [
            'dx_inserted' => 0,
            'pcs_inserted' => 0,
            'errors' => 0,
        ];

        $this->firePreInstallEvent('ICD', ['type' => self::CODE_TYPE]);

        $dir = str_replace('\\', '/', $tempDir);

        // File configurations with revisions
        $dxRevision = $this->getNextRevision('icd10_dx_order_code');
        $pcsRevision = $this->getNextRevision('icd10_pcs_order_code');

        $incoming = [
            'icd10pcs_codes_' => [
                'TABLENAME' => 'icd10_pcs_order_code',
                'FLD1' => 'pcs_code', 'POS1' => 0, 'LEN1' => 7,
                'FLD2' => 'long_desc', 'POS2' => 8, 'LEN2' => 300,
                'REV' => $pcsRevision,
            ],
            'icd10cm_order_' => [
                'TABLENAME' => 'icd10_dx_order_code',
                'FLD1' => 'dx_code', 'POS1' => 6, 'LEN1' => 7,
                'FLD2' => 'valid_for_coding', 'POS2' => 14, 'LEN2' => 1,
                'FLD3' => 'short_desc', 'POS3' => 16, 'LEN3' => 60,
                'FLD4' => 'long_desc', 'POS4' => 77, 'LEN4' => 300,
                'REV' => $dxRevision,
            ],
        ];

        sqlStatementNoLog("SET autocommit=0");
        sqlStatementNoLog("START TRANSACTION");

        // First inactivate older sets
        sqlStatementNoLog("UPDATE icd10_pcs_order_code SET active = 0");
        sqlStatementNoLog("UPDATE icd10_dx_order_code SET active = 0");

        if (is_dir($dir) && $handle = opendir($dir)) {
            while (false !== ($filename = readdir($handle))) {
                // Bypass unwanted entries
                if (!stripos($filename, '.txt') || stripos($filename, 'addenda')) {
                    continue;
                }

                $keys = array_keys($incoming);
                while ($thisKey = array_pop($keys)) {
                    if (stripos($filename, $thisKey) !== false) {
                        $generator = $this->readFileLines($dir . '/' . $filename);
                        foreach ($generator as $value) {
                            $runSql = "INSERT INTO `" . $incoming[$thisKey]['TABLENAME'] . "` (";
                            $sqlPlace = "(";
                            $sqlValues = [];

                            foreach (range(1, 4) as $field) {
                                $fld = "FLD" . $field;
                                $nxtfld = "FLD" . ($field + 1);
                                $pos = "POS" . $field;
                                $len = "LEN" . $field;

                                $runSql .= $incoming[$thisKey][$fld] . ", ";
                                $sqlPlace .= "?, ";

                                array_push($sqlValues, substr((string)$value, $incoming[$thisKey][$pos], $incoming[$thisKey][$len]));

                                if (!array_key_exists($nxtfld, $incoming[$thisKey])) {
                                    $runSql .= "active, revision) VALUES ";
                                    $sqlPlace .= "?, ?)";
                                    array_push($sqlValues, 1);
                                    array_push($sqlValues, $incoming[$thisKey]['REV']);
                                    sqlStatementNoLog($runSql . $sqlPlace, $sqlValues);
                                    break;
                                } else {
                                    $runSql .= " ";
                                    $sqlPlace .= " ";
                                }
                            }
                        }
                    }
                }
            }

            sqlStatementNoLog("COMMIT");
            sqlStatementNoLog("SET autocommit=1");
            closedir($handle);
        } else {
            throw new \RuntimeException("ERROR: No ICD import directory.");
        }

        // Update formatted dx codes
        $this->updateFormattedDxCodes();

        $this->firePostInstallEvent('ICD', ['type' => self::CODE_TYPE]);

        return $stats;
    }

    public function validate(string $filePath): bool
    {
        if (!is_dir($filePath)) {
            return false;
        }

        // Check for required .txt files
        $hasFiles = false;
        if ($handle = opendir($filePath)) {
            while (false !== ($filename = readdir($handle))) {
                if (stripos($filename, '.txt') !== false && stripos($filename, 'addenda') === false) {
                    $hasFiles = true;
                    break;
                }
            }
            closedir($handle);
        }

        return $hasFiles;
    }

    public function estimateRowCount(string $filePath): int
    {
        $totalLines = 0;

        if (is_dir($filePath) && $handle = opendir($filePath)) {
            while (false !== ($filename = readdir($handle))) {
                if (stripos($filename, '.txt') !== false && stripos($filename, 'addenda') === false) {
                    $file = $filePath . '/' . $filename;
                    $count = 0;
                    foreach ($this->readFileLines($file) as $line) {
                        $count++;
                    }
                    $totalLines += $count;
                }
            }
            closedir($handle);
        }

        return $totalLines;
    }
}
