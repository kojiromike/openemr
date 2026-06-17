<?php

/**
 * RxnormLoader.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc.
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services\CodeImport;

/**
 * Loader for RXNORM full database tables
 */
class RxnormLoader extends AbstractCodeLoader
{
    private const CODE_TYPE = 'RXNORM';

    // RXNORM table definitions
    private const RXNORM_TABLES = [
        'rxnatomarchive' => ['required' => false],
        'rxnconso' => ['required' => true],
        'rxncui' => ['required' => true],
        'rxncuichanges' => ['required' => true],
        'rxndoc' => ['required' => true],
        'rxnrel' => ['required' => true],
        'rxnsab' => ['required' => false],
        'rxnsat' => ['required' => false],
        'rxnsty' => ['required' => true],
    ];

    public function __construct(int $batchSize = self::DEFAULT_BATCH_SIZE, private readonly bool $isWindows = false)
    {
        parent::__construct($batchSize);
    }

    public function getCodeType(): string
    {
        return self::CODE_TYPE;
    }

    /**
     * Import RXNORM tables from extracted RRF files
     *
     * Options:
     * - temp_dir: string - Directory where RRF files are extracted
     * - old_method: bool - Use old SQL script method for comparison
     */
    public function import(string $filePath, array $options = []): array
    {
        $tempDir = $options['temp_dir'] ?? ($GLOBALS['temporary_files_dir'] . '/RXNORM');
        $useOldMethod = $options['old_method'] ?? $this->useOldMethod;

        if ($useOldMethod) {
            return $this->importOldMethod($tempDir);
        } else {
            return $this->importOptimized($tempDir);
        }
    }

    /**
     * Optimized import using LOAD DATA LOCAL INFILE with better performance
     */
    private function importOptimized(string $tempDir): array
    {
        $stats = [
            'tables_created' => 0,
            'tables_loaded' => 0,
            'rows_imported' => 0,
            'errors' => 0,
        ];

        $rrfDir = $tempDir . '/rrf';
        if (!is_dir($rrfDir)) {
            throw new \RuntimeException("RRF directory not found: $rrfDir");
        }

        $rrfDir = str_replace('\\', '/', $rrfDir);

        try {
            $this->firePreInstallEvent(self::CODE_TYPE, ['is_windows_flag' => $this->isWindows]);

            // Create tables
            $this->createTables($tempDir . '/scripts/mysql');
            $stats['tables_created'] = count(self::RXNORM_TABLES);

            // Import data with optimized settings
            $this->beginBulkImport();

            foreach (self::RXNORM_TABLES as $tableName => $tableInfo) {
                $fileName = strtoupper($tableName) . '.RRF';
                $filePath = $rrfDir . '/' . $fileName;

                if (!file_exists($filePath)) {
                    if ($tableInfo['required']) {
                        throw new \RuntimeException("Required file not found: $fileName");
                    }
                    continue;
                }

                $rowCount = $this->loadDataFile($tableName, $filePath);
                $stats['rows_imported'] += $rowCount;
                $stats['tables_loaded']++;
            }

            $this->commitBulkImport();

            // Create indexes
            $this->createIndexes($tempDir . '/scripts/mysql');

            $this->firePostInstallEvent(self::CODE_TYPE, ['is_windows_flag' => $this->isWindows]);
        } catch (\Exception $e) {
            $this->rollbackBulkImport();
            throw $e;
        }

        return $stats;
    }

    /**
     * Load data from RRF file using optimized LOAD DATA INFILE
     */
    private function loadDataFile(string $tableName, string $filePath): int
    {
        // Use LOAD DATA LOCAL INFILE which is fast and idempotent
        $sql = "LOAD DATA LOCAL INFILE ? INTO TABLE `$tableName`
                FIELDS TERMINATED BY '|'
                ESCAPED BY ''
                LINES TERMINATED BY '\\n'";

        sqlStatementNoLog($sql, [$filePath]);

        // Get row count
        $result = sqlQuery("SELECT COUNT(*) as cnt FROM `$tableName`");
        return (int)($result['cnt'] ?? 0);
    }

    /**
     * Create RXNORM tables from SQL script
     */
    private function createTables(string $scriptsDir): void
    {
        $tableScript = $scriptsDir . '/Table_scripts_mysql_rxn.sql';
        if (!file_exists($tableScript)) {
            throw new \RuntimeException("Table creation script not found: $tableScript");
        }

        $sql = file_get_contents($tableScript);
        $statements = explode(';', $sql);

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                sqlStatementNoLog($statement);
            }
        }
    }

    /**
     * Create indexes from SQL script
     */
    private function createIndexes(string $scriptsDir): void
    {
        $indexScript = $scriptsDir . '/Indexes_mysql_rxn.sql';
        if (!file_exists($indexScript)) {
            throw new \RuntimeException("Index creation script not found: $indexScript");
        }

        $sql = file_get_contents($indexScript);
        $statements = explode(';', $sql);

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                sqlStatementNoLog($statement);
            }
        }
    }

    /**
     * Old method for comparison (matches standard_tables_capture.inc.php)
     */
    private function importOldMethod(string $tempDir): array
    {
        $stats = [
            'tables_created' => 0,
            'tables_loaded' => 0,
            'rows_imported' => 0,
            'errors' => 0,
        ];

        $this->firePreInstallEvent(self::CODE_TYPE, ['is_windows_flag' => $this->isWindows]);

        // Set paths
        $dirScripts = $tempDir . '/scripts/mysql';
        $dir = $tempDir . '/rrf';
        $dir = str_replace('\\', '/', $dir);

        // Load scripts
        $fileLoad = file_get_contents($dirScripts . '/Table_scripts_mysql_rxn.sql', true);
        if ($this->isWindows) {
            $dataLoad = file_get_contents($dirScripts . '/Load_scripts_mysql_rxn_win.sql', true);
        } else {
            $dataLoad = file_get_contents($dirScripts . '/Load_scripts_mysql_rxn_unix.sql', true);
        }
        $indexesLoad = file_get_contents($dirScripts . '/Indexes_mysql_rxn.sql', true);

        // Create tables
        $fileArray = explode(';', $fileLoad);
        foreach ($fileArray as $val) {
            if (trim($val) != '') {
                sqlStatementNoLog($val);
            }
        }

        // Create indexes
        $indexesArray = explode(';', $indexesLoad);
        foreach ($indexesArray as $val) {
            if (trim($val) != '') {
                sqlStatementNoLog($val);
            }
        }

        // Load data
        sqlStatementNoLog("SET autocommit=0");
        sqlStatementNoLog("START TRANSACTION");

        $data = explode(';', $dataLoad);
        foreach ($data as $val) {
            foreach (self::RXNORM_TABLES as $tableName => $tableInfo) {
                $fileName = strtoupper($tableName) . '.RRF';
                $replacement = $dir . '/' . $fileName;

                if (str_contains($val, $fileName)) {
                    $val1 = str_replace($fileName, $replacement, $val);
                    if (trim($val1) != '') {
                        sqlStatementNoLog($val1);
                    }
                }
            }
        }

        sqlStatementNoLog("COMMIT");
        sqlStatementNoLog("SET autocommit=1");

        $this->firePostInstallEvent(self::CODE_TYPE, ['is_windows_flag' => $this->isWindows]);

        return $stats;
    }

    public function validate(string $filePath): bool
    {
        // For RXNORM, we validate the directory structure
        if (!is_dir($filePath)) {
            return false;
        }

        $rrfDir = $filePath . '/rrf';
        if (!is_dir($rrfDir)) {
            return false;
        }

        // Check for required files
        foreach (self::RXNORM_TABLES as $tableName => $tableInfo) {
            if ($tableInfo['required']) {
                $fileName = strtoupper($tableName) . '.RRF';
                if (!file_exists($rrfDir . '/' . $fileName)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function estimateRowCount(string $filePath): int
    {
        $rrfDir = $filePath . '/rrf';
        $totalLines = 0;

        foreach (self::RXNORM_TABLES as $tableName => $tableInfo) {
            $fileName = strtoupper($tableName) . '.RRF';
            $file = $rrfDir . '/' . $fileName;

            if (file_exists($file)) {
                $count = 0;
                $handle = fopen($file, 'r');
                if ($handle) {
                    while (fgets($handle) !== false) {
                        $count++;
                    }
                    fclose($handle);
                }
                $totalLines += $count;
            }
        }

        return $totalLines;
    }
}
