<?php

/**
 * ImportValidator.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc.
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services\CodeImport;

/**
 * Utility to validate and compare code import results
 */
class ImportValidator
{
    /**
     * Compare two database snapshots to validate import consistency
     *
     * @param string $table Table name to compare
     * @param array $beforeSnapshot Snapshot before import
     * @param array $afterSnapshot Snapshot after import
     * @return array Comparison results
     */
    public function compareSnapshots(string $table, array $beforeSnapshot, array $afterSnapshot): array
    {
        $results = [
            'table' => $table,
            'matches' => true,
            'row_count_before' => $beforeSnapshot['row_count'] ?? 0,
            'row_count_after' => $afterSnapshot['row_count'] ?? 0,
            'row_count_diff' => ($afterSnapshot['row_count'] ?? 0) - ($beforeSnapshot['row_count'] ?? 0),
            'checksum_before' => $beforeSnapshot['checksum'] ?? '',
            'checksum_after' => $afterSnapshot['checksum'] ?? '',
            'sample_diffs' => [],
        ];

        // Compare row counts
        if ($results['row_count_before'] !== $results['row_count_after']) {
            $results['matches'] = false;
        }

        // Compare checksums
        if ($results['checksum_before'] !== $results['checksum_after']) {
            $results['matches'] = false;
        }

        return $results;
    }

    /**
     * Create a snapshot of a table for comparison
     *
     * @param string $table Table name
     * @param array $keyColumns Columns to use for ordering
     * @return array Snapshot data
     */
    public function createSnapshot(string $table, array $keyColumns = []): array
    {
        $snapshot = [
            'table' => $table,
            'row_count' => 0,
            'checksum' => '',
            'sample_rows' => [],
        ];

        // Get row count
        $result = sqlQuery("SELECT COUNT(*) as cnt FROM `$table`");
        $snapshot['row_count'] = (int)($result['cnt'] ?? 0);

        // Get checksum of entire table
        $result = sqlQuery("CHECKSUM TABLE `$table`");
        $snapshot['checksum'] = $result['Checksum'] ?? '';

        // Get sample rows for comparison
        $orderBy = !empty($keyColumns) ? 'ORDER BY ' . implode(', ', $keyColumns) : '';
        $sampleResult = sqlStatement("SELECT * FROM `$table` $orderBy LIMIT 100");

        while ($row = sqlFetchArray($sampleResult)) {
            $snapshot['sample_rows'][] = $row;
        }

        return $snapshot;
    }

    /**
     * Validate RXCUI import results
     *
     * @param int $codeTypeId Code type ID
     * @return array Validation results
     */
    public function validateRxcuiImport(int $codeTypeId): array
    {
        $results = [
            'total_codes' => 0,
            'prescribable_codes' => 0,
            'sample_codes' => [],
            'validation_passed' => true,
            'errors' => [],
        ];

        // Count total codes
        $result = sqlQuery("SELECT COUNT(*) as cnt FROM codes WHERE code_type = ?", [$codeTypeId]);
        $results['total_codes'] = (int)($result['cnt'] ?? 0);

        if ($results['total_codes'] === 0) {
            $results['validation_passed'] = false;
            $results['errors'][] = 'No codes imported';
        }

        // Get sample codes
        $sampleResult = sqlStatement(
            "SELECT code, code_text FROM codes WHERE code_type = ? ORDER BY code LIMIT 10",
            [$codeTypeId]
        );

        while ($row = sqlFetchArray($sampleResult)) {
            $results['sample_codes'][] = $row;
        }

        return $results;
    }

    /**
     * Validate RXNORM import results
     *
     * @return array Validation results
     */
    public function validateRxnormImport(): array
    {
        $results = [
            'tables' => [],
            'validation_passed' => true,
            'errors' => [],
        ];

        $tables = [
            'rxnatomarchive',
            'rxnconso',
            'rxncui',
            'rxncuichanges',
            'rxndoc',
            'rxnrel',
            'rxnsab',
            'rxnsat',
            'rxnsty',
        ];

        foreach ($tables as $table) {
            $tableResult = $this->validateTableExists($table);
            $results['tables'][$table] = $tableResult;

            if (!$tableResult['exists']) {
                $results['validation_passed'] = false;
                $results['errors'][] = "Table $table does not exist";
            } elseif ($tableResult['row_count'] === 0) {
                // Some tables are optional, so we just note it
                $results['errors'][] = "Warning: Table $table is empty";
            }
        }

        return $results;
    }

    /**
     * Validate SNOMED import results
     *
     * @param string $format 'RF1' or 'RF2'
     * @return array Validation results
     */
    public function validateSnomedImport(string $format = 'RF2'): array
    {
        $results = [
            'format' => $format,
            'tables' => [],
            'validation_passed' => true,
            'errors' => [],
        ];

        $tables = $format === 'RF2'
            ? ['sct2_concept', 'sct2_description', 'sct2_identifier', 'sct2_relationship', 'sct2_statedrelationship', 'sct2_textdefinition']
            : ['sct_concepts', 'sct_descriptions', 'sct_relationships'];

        foreach ($tables as $table) {
            $tableResult = $this->validateTableExists($table);
            $results['tables'][$table] = $tableResult;

            if (!$tableResult['exists']) {
                $results['validation_passed'] = false;
                $results['errors'][] = "Table $table does not exist";
            } elseif ($tableResult['row_count'] === 0) {
                $results['validation_passed'] = false;
                $results['errors'][] = "Table $table is empty";
            }
        }

        return $results;
    }

    /**
     * Validate ICD10 import results
     *
     * @return array Validation results
     */
    public function validateIcd10Import(): array
    {
        $results = [
            'dx_codes' => 0,
            'pcs_codes' => 0,
            'active_dx_codes' => 0,
            'active_pcs_codes' => 0,
            'validation_passed' => true,
            'errors' => [],
        ];

        // Check DX codes
        $result = sqlQuery("SELECT COUNT(*) as cnt FROM icd10_dx_order_code");
        $results['dx_codes'] = (int)($result['cnt'] ?? 0);

        $result = sqlQuery("SELECT COUNT(*) as cnt FROM icd10_dx_order_code WHERE active = 1");
        $results['active_dx_codes'] = (int)($result['cnt'] ?? 0);

        // Check PCS codes
        $result = sqlQuery("SELECT COUNT(*) as cnt FROM icd10_pcs_order_code");
        $results['pcs_codes'] = (int)($result['cnt'] ?? 0);

        $result = sqlQuery("SELECT COUNT(*) as cnt FROM icd10_pcs_order_code WHERE active = 1");
        $results['active_pcs_codes'] = (int)($result['cnt'] ?? 0);

        if ($results['active_dx_codes'] === 0 && $results['active_pcs_codes'] === 0) {
            $results['validation_passed'] = false;
            $results['errors'][] = 'No active ICD10 codes found';
        }

        // Check formatted_dx_code
        $result = sqlQuery("SELECT COUNT(*) as cnt FROM icd10_dx_order_code WHERE formatted_dx_code IS NULL OR formatted_dx_code = ''");
        $unformatted = (int)($result['cnt'] ?? 0);

        if ($unformatted > 0) {
            $results['validation_passed'] = false;
            $results['errors'][] = "$unformatted DX codes have no formatted_dx_code";
        }

        return $results;
    }

    /**
     * Validate CQM valueset import results
     *
     * @return array Validation results
     */
    public function validateCqmValuesetImport(): array
    {
        $results = [
            'valueset_count' => 0,
            'valueset_oid_count' => 0,
            'unique_nqf_codes' => 0,
            'validation_passed' => true,
            'errors' => [],
        ];

        // Check valueset table
        $result = sqlQuery("SELECT COUNT(*) as cnt FROM valueset");
        $results['valueset_count'] = (int)($result['cnt'] ?? 0);

        // Check valueset_oid table
        $result = sqlQuery("SELECT COUNT(*) as cnt FROM valueset_oid");
        $results['valueset_oid_count'] = (int)($result['cnt'] ?? 0);

        // Check unique NQF codes
        $result = sqlQuery("SELECT COUNT(DISTINCT nqf_code) as cnt FROM valueset");
        $results['unique_nqf_codes'] = (int)($result['cnt'] ?? 0);

        if ($results['valueset_count'] === 0) {
            $results['validation_passed'] = false;
            $results['errors'][] = 'No valuesets imported';
        }

        return $results;
    }

    /**
     * Check if a table exists and get row count
     *
     * @param string $table Table name
     * @return array Table info
     */
    private function validateTableExists(string $table): array
    {
        $result = [
            'exists' => false,
            'row_count' => 0,
        ];

        try {
            $checkResult = sqlQuery("SHOW TABLES LIKE ?", [$table]);
            if ($checkResult) {
                $result['exists'] = true;

                $countResult = sqlQuery("SELECT COUNT(*) as cnt FROM `$table`");
                $result['row_count'] = (int)($countResult['cnt'] ?? 0);
            }
        } catch (\Exception) {
            $result['exists'] = false;
        }

        return $result;
    }

    /**
     * Generate a detailed comparison report
     *
     * @param string $oldMethod Results from old method
     * @param string $newMethod Results from new method
     * @return array Report
     */
    public function generateComparisonReport(array $oldMethod, array $newMethod): array
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'old_method' => $oldMethod,
            'new_method' => $newMethod,
            'matches' => true,
            'differences' => [],
        ];

        // Compare each metric
        foreach ($oldMethod as $key => $oldValue) {
            $newValue = $newMethod[$key] ?? null;

            if ($oldValue !== $newValue) {
                $report['matches'] = false;
                $report['differences'][$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $report;
    }
}
