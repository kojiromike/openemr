<?php

/**
 * CqmValuesetLoader.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc.
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services\CodeImport;

/**
 * Loader for CQM (Clinical Quality Measure) valuesets
 */
class CqmValuesetLoader extends AbstractCodeLoader
{
    private const CODE_TYPE = 'CQM_VALUESET';

    public function getCodeType(): string
    {
        return self::CODE_TYPE;
    }

    /**
     * Import CQM valuesets from XML files
     *
     * Options:
     * - temp_dir: string - Directory where valueset XML files are extracted
     * - old_method: bool - Use old method for comparison
     */
    public function import(string $filePath, array $options = []): array
    {
        $tempDir = $options['temp_dir'] ?? ($GLOBALS['temporary_files_dir'] . '/CQM_VALUESET');
        $useOldMethod = $options['old_method'] ?? $this->useOldMethod;

        if ($useOldMethod) {
            return $this->importOldMethod($tempDir);
        } else {
            return $this->importOptimized($tempDir);
        }
    }

    /**
     * Optimized import using batched upserts
     */
    private function importOptimized(string $tempDir): array
    {
        $stats = [
            'valuesets_processed' => 0,
            'concepts_inserted' => 0,
            'oids_inserted' => 0,
            'errors' => 0,
        ];

        $dir = str_replace('\\', '/', $tempDir);

        if (!is_dir($dir)) {
            throw new \RuntimeException("CQM valueset directory not found: $dir");
        }

        try {
            $this->firePreInstallEvent(self::CODE_TYPE, ['type' => self::CODE_TYPE]);

            $this->beginBulkImport();

            $valuesetBatch = [];
            $oidBatch = [];

            if ($handle = opendir($dir)) {
                while (false !== ($filename = readdir($handle))) {
                    // Skip non-XML files and zip files
                    if (stripos($filename, '.zip') !== false) {
                        continue;
                    }

                    if (stripos($filename, '.xml') !== false) {
                        $absPath = $dir . '/' . $filename;
                        $result = $this->processXmlFile($absPath, $valuesetBatch, $oidBatch);

                        $stats['valuesets_processed'] += $result['valuesets'];

                        // Process batches when they get large
                        if (count($valuesetBatch) >= $this->batchSize) {
                            $this->processBatches($valuesetBatch, $oidBatch);
                            $stats['concepts_inserted'] += count($valuesetBatch);
                            $stats['oids_inserted'] += count($oidBatch);
                            $valuesetBatch = [];
                            $oidBatch = [];
                        }
                    }
                }
                closedir($handle);
            }

            // Process remaining batches
            if (!empty($valuesetBatch) || !empty($oidBatch)) {
                $this->processBatches($valuesetBatch, $oidBatch);
                $stats['concepts_inserted'] += count($valuesetBatch);
                $stats['oids_inserted'] += count($oidBatch);
            }

            // Normalize code types
            $this->normalizeCodeTypes();

            $this->commitBulkImport();

            $this->firePostInstallEvent(self::CODE_TYPE, ['type' => self::CODE_TYPE]);
        } catch (\Exception $e) {
            $this->rollbackBulkImport();
            throw $e;
        }

        return $stats;
    }

    /**
     * Process a single XML file
     */
    private function processXmlFile(string $absPath, array &$valuesetBatch, array &$oidBatch): array
    {
        $valuesetCount = 0;

        $xml = simplexml_load_file($absPath, null, 0, 'ns0', true);
        if (!$xml) {
            return ['valuesets' => 0];
        }

        foreach ($xml->DescribedValueSet as $vset) {
            $valuesetCount++;
            $vsetAttr = $vset->attributes();
            $nqf = $vset->xpath('ns0:Group[@displayName="NQF Number"]/ns0:Keyword');

            foreach ($vset->ConceptList as $cp) {
                foreach ($nqf as $nqfCode) {
                    foreach ($cp->Concept as $con) {
                        $conAttr = $con->attributes();

                        // Add to valueset batch
                        $valuesetBatch[] = [
                            (string)$nqfCode,
                            (string)$conAttr->code,
                            (string)$conAttr->codeSystem,
                            (string)$conAttr->codeSystemName,
                            (string)$vsetAttr->ID,
                            (string)$conAttr->displayName,
                            (string)$vsetAttr->displayName,
                        ];

                        // Add to OID batch
                        $oidBatch[] = [
                            (string)$nqfCode,
                            (string)$vsetAttr->ID,
                            (string)$conAttr->codeSystem,
                            'OID',
                            (string)$vsetAttr->ID,
                            (string)$vsetAttr->displayName,
                            (string)$vsetAttr->displayName,
                        ];
                    }
                }
            }
        }

        return ['valuesets' => $valuesetCount];
    }

    /**
     * Process batches with upsert logic
     */
    private function processBatches(array $valuesetBatch, array $oidBatch): void
    {
        $columns = [
            'nqf_code',
            'code',
            'code_system',
            'code_type',
            'valueset',
            'description',
            'valueset_name',
        ];

        // Use ON DUPLICATE KEY UPDATE for idempotent inserts
        if (!empty($valuesetBatch)) {
            $this->batchUpsertValueset('valueset', $columns, $valuesetBatch);
        }

        if (!empty($oidBatch)) {
            $this->batchUpsertValueset('valueset_oid', $columns, $oidBatch);
        }
    }

    /**
     * Batch upsert for valueset tables
     */
    private function batchUpsertValueset(string $table, array $columns, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $columnList = implode(', ', $columns);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        $batches = array_chunk($rows, $this->batchSize);

        foreach ($batches as $batch) {
            $batchPlaceholders = implode(', ', array_fill(0, count($batch), $placeholders));

            $sql = "INSERT INTO `$table` ($columnList) VALUES $batchPlaceholders
                    ON DUPLICATE KEY UPDATE
                    code_system = VALUES(code_system),
                    description = VALUES(description),
                    valueset_name = VALUES(valueset_name)";

            // Flatten the batch array for binding
            $params = [];
            foreach ($batch as $row) {
                foreach ($row as $value) {
                    $params[] = $value;
                }
            }

            sqlStatementNoLog($sql, $params);
        }
    }

    /**
     * Normalize code types to match OpenEMR standards
     */
    private function normalizeCodeTypes(): void
    {
        sqlStatementNoLog("UPDATE valueset SET code_type='SNOMED CT' WHERE code_type='SNOMEDCT'");
        sqlStatementNoLog("UPDATE valueset SET code_type='ICD9' WHERE code_type='ICD9CM'");
        sqlStatementNoLog("UPDATE valueset SET code_type='ICD10' WHERE code_type='ICD10CM'");
    }

    /**
     * Old method for comparison (matches standard_tables_capture.inc.php)
     */
    private function importOldMethod(string $tempDir): array
    {
        $stats = [
            'valuesets_processed' => 0,
            'concepts_inserted' => 0,
            'errors' => 0,
        ];

        $this->firePreInstallEvent(self::CODE_TYPE, ['type' => self::CODE_TYPE]);

        $dir = str_replace('\\', '/', $tempDir);

        sqlStatementNoLog("SET autocommit=0");
        sqlStatementNoLog("START TRANSACTION");

        if (is_dir($dir) && $handle = opendir($dir)) {
            while (false !== ($filename = readdir($handle))) {
                // Skip zip files
                if (stripos($filename, '.zip')) {
                    continue;
                }

                if (stripos($filename, '.xml')) {
                    $absPath = $dir . '/' . $filename;
                    $xml = simplexml_load_file($absPath, null, 0, 'ns0', true);

                    foreach ($xml->DescribedValueSet as $vset) {
                        $vsetAttr = $vset->attributes();
                        $nqf = $vset->xpath('ns0:Group[@displayName="NQF Number"]/ns0:Keyword');

                        foreach ($vset->ConceptList as $cp) {
                            foreach ($nqf as $nqfCode) {
                                foreach ($cp->Concept as $con) {
                                    $conAttr = $con->attributes();

                                    sqlStatementNoLog(
                                        "INSERT INTO valueset values(?,?,?,?,?,?,?) on DUPLICATE KEY UPDATE
                                        code_system = values(code_system),
                                        description = values(description),
                                        valueset_name = values(valueset_name)",
                                        [
                                            (string)$nqfCode,
                                            (string)$conAttr->code,
                                            (string)$conAttr->codeSystem,
                                            (string)$conAttr->codeSystemName,
                                            (string)$vsetAttr->ID,
                                            (string)$conAttr->displayName,
                                            (string)$vsetAttr->displayName,
                                        ]
                                    );

                                    sqlStatementNoLog(
                                        "INSERT INTO valueset_oid values(?,?,?,?,?,?,?) on DUPLICATE KEY UPDATE
                                        code_system = values(code_system),
                                        description = values(description),
                                        valueset_name = values(valueset_name)",
                                        [
                                            (string)$nqfCode,
                                            (string)$vsetAttr->ID,
                                            (string)$conAttr->codeSystem,
                                            'OID',
                                            (string)$vsetAttr->ID,
                                            (string)$vsetAttr->displayName,
                                            (string)$vsetAttr->displayName,
                                        ]
                                    );
                                }
                            }
                        }
                    }

                    sqlStatementNoLog("UPDATE valueset set code_type='SNOMED CT' where code_type='SNOMEDCT'");
                    sqlStatementNoLog("UPDATE valueset set code_type='ICD9' where code_type='ICD9CM'");
                    sqlStatementNoLog("UPDATE valueset set code_type='ICD10' where code_type='ICD10CM'");
                }
            }
            closedir($handle);
        }

        sqlStatementNoLog("COMMIT");
        sqlStatementNoLog("SET autocommit=1");

        $this->firePostInstallEvent(self::CODE_TYPE, ['type' => self::CODE_TYPE]);

        return $stats;
    }

    public function validate(string $filePath): bool
    {
        if (!is_dir($filePath)) {
            return false;
        }

        // Check for XML files
        $hasXml = false;
        if ($handle = opendir($filePath)) {
            while (false !== ($filename = readdir($handle))) {
                if (stripos($filename, '.xml') !== false) {
                    $hasXml = true;
                    break;
                }
            }
            closedir($handle);
        }

        return $hasXml;
    }

    public function estimateRowCount(string $filePath): int
    {
        // CQM valuesets are relatively small
        return 10000;
    }
}
