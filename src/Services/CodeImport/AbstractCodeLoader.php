<?php

/**
 * AbstractCodeLoader.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc.
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services\CodeImport;

use OpenEMR\Events\Codes\CodeTypeInstalledEvent;

/**
 * Abstract base class for code loaders with common functionality
 */
abstract class AbstractCodeLoader implements CodeLoaderInterface
{
    public const DEFAULT_BATCH_SIZE = 1000;
    public const MAX_BATCH_SIZE = 5000;

    protected int $batchSize;
    protected bool $useOldMethod = false;

    public function __construct(int $batchSize = self::DEFAULT_BATCH_SIZE)
    {
        $this->batchSize = min($batchSize, self::MAX_BATCH_SIZE);
    }

    /**
     * Enable old import method for comparison
     */
    public function setUseOldMethod(bool $useOld): void
    {
        $this->useOldMethod = $useOld;
    }

    /**
     * Execute a batched insert operation
     *
     * @param string $table Table name
     * @param array $columns Column names
     * @param array $rows Array of row data
     * @param bool $replace Use REPLACE instead of INSERT
     * @return int Number of rows inserted
     */
    protected function batchInsert(string $table, array $columns, array $rows, bool $replace = false): int
    {
        if (empty($rows)) {
            return 0;
        }

        $columnList = implode(', ', array_map(fn($col): string => "`$col`", $columns));
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        $verb = $replace ? 'REPLACE' : 'INSERT';
        $sql = "$verb INTO `$table` ($columnList) VALUES ";

        $batches = array_chunk($rows, $this->batchSize);
        $totalInserted = 0;

        foreach ($batches as $batch) {
            $batchPlaceholders = implode(', ', array_fill(0, count($batch), $placeholders));
            $batchSql = $sql . $batchPlaceholders;

            // Flatten the batch array for binding
            $params = [];
            foreach ($batch as $row) {
                foreach ($row as $value) {
                    $params[] = $value;
                }
            }

            sqlStatementNoLog($batchSql, $params);
            $totalInserted += count($batch);
        }

        return $totalInserted;
    }

    /**
     * Execute an idempotent upsert operation using ON DUPLICATE KEY UPDATE
     *
     * @param string $table Table name
     * @param array $columns Column names
     * @param array $rows Array of row data
     * @param array $updateColumns Columns to update on duplicate (if empty, updates all non-key columns)
     * @return array ['inserted' => int, 'updated' => int]
     */
    protected function batchUpsert(string $table, array $columns, array $rows, array $updateColumns = []): array
    {
        if (empty($rows)) {
            return ['inserted' => 0, 'updated' => 0];
        }

        $columnList = implode(', ', array_map(fn($col): string => "`$col`", $columns));
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        // Build UPDATE clause
        if (empty($updateColumns)) {
            $updateColumns = $columns;
        }
        $updateParts = array_map(fn($col): string => "`$col` = VALUES(`$col`)", $updateColumns);
        $updateClause = implode(', ', $updateParts);

        $sql = "INSERT INTO `$table` ($columnList) VALUES ";
        $onDuplicateClause = " ON DUPLICATE KEY UPDATE $updateClause";

        $batches = array_chunk($rows, $this->batchSize);
        $totalAffected = 0;

        foreach ($batches as $batch) {
            $batchPlaceholders = implode(', ', array_fill(0, count($batch), $placeholders));
            $batchSql = $sql . $batchPlaceholders . $onDuplicateClause;

            // Flatten the batch array for binding
            $params = [];
            foreach ($batch as $row) {
                foreach ($row as $value) {
                    $params[] = $value;
                }
            }

            $result = sqlStatementNoLog($batchSql, $params);
            $totalAffected += sqlNumRows($result);
        }

        // Note: MySQL returns affected rows: 1 for insert, 2 for update, 0 for no change
        // We approximate based on affected rows
        return [
            'inserted' => $totalAffected,
            'updated' => 0  // Would need more sophisticated tracking to separate
        ];
    }

    /**
     * Begin optimized transaction for bulk import
     */
    protected function beginBulkImport(): void
    {
        sqlStatementNoLog("SET autocommit=0");
        sqlStatementNoLog("SET unique_checks=0");
        sqlStatementNoLog("SET foreign_key_checks=0");
        sqlStatementNoLog("START TRANSACTION");
    }

    /**
     * Commit and restore settings after bulk import
     */
    protected function commitBulkImport(): void
    {
        sqlStatementNoLog("COMMIT");
        sqlStatementNoLog("SET foreign_key_checks=1");
        sqlStatementNoLog("SET unique_checks=1");
        sqlStatementNoLog("SET autocommit=1");
    }

    /**
     * Rollback transaction on error
     */
    protected function rollbackBulkImport(): void
    {
        sqlStatementNoLog("ROLLBACK");
        sqlStatementNoLog("SET foreign_key_checks=1");
        sqlStatementNoLog("SET unique_checks=1");
        sqlStatementNoLog("SET autocommit=1");
    }

    /**
     * Fire pre-install event
     */
    protected function firePreInstallEvent(string $codeType, array $data = []): void
    {
        if (!empty($GLOBALS['kernel'])) {
            $event = new CodeTypeInstalledEvent($codeType, $data);
            $GLOBALS['kernel']->getEventDispatcher()->dispatch($event, CodeTypeInstalledEvent::EVENT_INSTALLED_PRE);
        }
    }

    /**
     * Fire post-install event
     */
    protected function firePostInstallEvent(string $codeType, array $data = []): void
    {
        if (!empty($GLOBALS['kernel'])) {
            $event = new CodeTypeInstalledEvent($codeType, $data);
            $GLOBALS['kernel']->getEventDispatcher()->dispatch($event, CodeTypeInstalledEvent::EVENT_INSTALLED_POST);
        }
    }

    /**
     * Read file line by line as generator to save memory
     *
     * @param string $filePath Path to file
     * @return \Generator
     */
    protected function readFileLines(string $filePath): \Generator
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException("Unable to open file: $filePath");
        }

        try {
            while (($line = fgets($handle)) !== false) {
                yield $line;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Extract file from zip archive
     *
     * @param string $zipPath Path to zip file
     * @param string $filePattern Pattern to match file inside zip (e.g., 'RXNCONSO.RRF')
     * @return string|null Path to extracted file or null if not found
     */
    protected function extractFromZip(string $zipPath, string $filePattern): ?string
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return null;
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (basename($filename) === $filePattern) {
                    $tempDir = $GLOBALS['temporary_files_dir'] ?? sys_get_temp_dir();
                    $extractPath = $tempDir . '/' . basename($filePattern);

                    $fp = $zip->getStream($filename);
                    $ofp = fopen($extractPath, 'w');

                    if ($fp && $ofp) {
                        while (!feof($fp)) {
                            fwrite($ofp, fread($fp, 8192));
                        }
                        fclose($fp);
                        fclose($ofp);

                        $zip->close();
                        return $extractPath;
                    }
                }
            }
        } finally {
            $zip->close();
        }

        return null;
    }

    /**
     * Default validation - checks if file exists and is readable
     */
    public function validate(string $filePath): bool
    {
        return file_exists($filePath) && is_readable($filePath);
    }

    /**
     * Default row count estimation - counts lines in file
     */
    public function estimateRowCount(string $filePath): int
    {
        $count = 0;
        foreach ($this->readFileLines($filePath) as $line) {
            $count++;
        }
        return $count;
    }
}
