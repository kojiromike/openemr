<?php

/**
 * SnomedLoader.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc.
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services\CodeImport;

/**
 * Loader for SNOMED-CT codes (both RF1 and RF2 formats)
 */
class SnomedLoader extends AbstractCodeLoader
{
    private const CODE_TYPE = 'SNOMED';

    // RF1 tables
    private const RF1_TABLES = [
        'sct_concepts' => 'Concepts',
        'sct_descriptions' => 'Descriptions',
        'sct_relationships' => 'Relationships',
    ];

    // RF2 tables
    private const RF2_TABLES = [
        'sct2_concept' => 'Concept',
        'sct2_description' => 'Description',
        'sct2_identifier' => 'Identifier',
        'sct2_relationship' => 'Relationship',
        'sct2_statedrelationship' => 'StatedRelationship',
        'sct2_textdefinition' => 'TextDefinition',
    ]; // 'RF1' or 'RF2'

    public function __construct(int $batchSize = self::DEFAULT_BATCH_SIZE, private string $format = 'RF2')
    {
        parent::__construct($batchSize);
    }

    public function getCodeType(): string
    {
        return self::CODE_TYPE;
    }

    /**
     * Import SNOMED tables from extracted files
     *
     * Options:
     * - temp_dir: string - Directory where SNOMED files are extracted
     * - us_extension: bool - Whether importing US extension
     * - format: string - 'RF1' or 'RF2'
     * - old_method: bool - Use old method for comparison
     */
    public function import(string $filePath, array $options = []): array
    {
        $tempDir = $options['temp_dir'] ?? ($GLOBALS['temporary_files_dir'] . '/SNOMED');
        $usExtension = $options['us_extension'] ?? false;
        $this->format = $options['format'] ?? $this->format;
        $useOldMethod = $options['old_method'] ?? $this->useOldMethod;

        if ($useOldMethod) {
            if ($this->format === 'RF2') {
                return $this->importOldMethodRF2($tempDir);
            } else {
                return $this->importOldMethodRF1($tempDir, $usExtension);
            }
        } else {
            if ($this->format === 'RF2') {
                return $this->importOptimizedRF2($tempDir);
            } else {
                return $this->importOptimizedRF1($tempDir, $usExtension);
            }
        }
    }

    /**
     * Optimized RF2 import
     */
    private function importOptimizedRF2(string $tempDir): array
    {
        $stats = [
            'tables_created' => 0,
            'tables_loaded' => 0,
            'rows_imported' => 0,
            'errors' => 0,
        ];

        try {
            $this->firePreInstallEvent(self::CODE_TYPE, []);

            // Create tables
            $this->createTablesRF2();
            $stats['tables_created'] = count(self::RF2_TABLES);

            // Load data
            $this->beginBulkImport();

            $subPath = 'Full/Terminology/';
            $dir = str_replace('\\', '/', $tempDir);

            if (is_dir($dir) && $handle = opendir($dir)) {
                while (false !== ($filename = readdir($handle))) {
                    if ($filename === '.' || $filename === '..' || str_contains($filename, 'zip')) {
                        continue;
                    }

                    $path = "$dir/$filename/$subPath";
                    if (!is_dir($path)) {
                        $path = "$dir/$filename/RF2Release/$subPath";
                    }

                    if (is_dir($path)) {
                        $rowCount = $this->loadRF2Files($path);
                        $stats['rows_imported'] += $rowCount;
                        $stats['tables_loaded'] = count(self::RF2_TABLES);
                    }
                }
                closedir($handle);
            }

            $this->commitBulkImport();

            $this->firePostInstallEvent(self::CODE_TYPE, []);
        } catch (\Exception $e) {
            $this->rollbackBulkImport();
            throw $e;
        }

        return $stats;
    }

    /**
     * Load RF2 files using LOAD DATA INFILE
     */
    private function loadRF2Files(string $path): int
    {
        $totalRows = 0;

        if (!is_dir($path) || !($handle = opendir($path))) {
            return 0;
        }

        while (false !== ($filename = readdir($handle))) {
            if ($filename === '.' || $filename === '..') {
                continue;
            }

            $filePath = $path . $filename;

            foreach (self::RF2_TABLES as $tableName => $pattern) {
                if (str_contains($filename, $pattern)) {
                    $sql = "LOAD DATA LOCAL INFILE ?
                            INTO TABLE `$tableName`
                            FIELDS TERMINATED BY '\t'
                            ESCAPED BY ''
                            LINES TERMINATED BY '\n'
                            IGNORE 1 LINES";

                    sqlStatementNoLog($sql, [$filePath]);

                    $result = sqlQuery("SELECT COUNT(*) as cnt FROM `$tableName`");
                    $totalRows += (int)($result['cnt'] ?? 0);
                    break;
                }
            }
        }
        closedir($handle);

        return $totalRows;
    }

    /**
     * Create RF2 tables
     */
    private function createTablesRF2(): void
    {
        $tableDefinitions = [
            "DROP TABLE IF EXISTS `sct2_concept`",
            "CREATE TABLE IF NOT EXISTS `sct2_concept` (
                `id` bigint(20) NOT NULL,
                `effectiveTime` date NOT NULL,
                `active` int(11) NOT NULL,
                `moduleId` bigint(20) NOT NULL,
                `definitionStatusId` bigint(25) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB",

            "DROP TABLE IF EXISTS `sct2_description`",
            "CREATE TABLE IF NOT EXISTS `sct2_description` (
                `id` bigint(20) NOT NULL,
                `effectiveTime` date NOT NULL,
                `active` bigint(11) NOT NULL,
                `moduleId` bigint(25) NOT NULL,
                `conceptId` bigint(20) NOT NULL,
                `languageCode` varchar(8) NOT NULL,
                `typeId` bigint(25) NOT NULL,
                `term` varchar(255) NOT NULL,
                `caseSignificanceId` bigint(25) NOT NULL,
                PRIMARY KEY (`id`, `active`, `conceptId`),
                KEY `idx_concept_id` (`conceptId`),
                INDEX `idx_term` (term),
                INDEX `idx_active_term` (active, term),
                FULLTEXT INDEX `ft_term` (term)
            ) ENGINE=InnoDB",

            "DROP TABLE IF EXISTS `sct2_identifier`",
            "CREATE TABLE IF NOT EXISTS `sct2_identifier` (
                `identifierSchemeId` bigint(25) NOT NULL,
                `alternateIdentifier` bigint(25) NOT NULL,
                `effectiveTime` date NOT NULL,
                `active` int(11) NOT NULL,
                `moduleId` bigint(25) NOT NULL,
                `referencedComponentId` bigint(25) NOT NULL,
                PRIMARY KEY (`identifierSchemeId`)
            ) ENGINE=InnoDB",

            "DROP TABLE IF EXISTS `sct2_relationship`",
            "CREATE TABLE IF NOT EXISTS `sct2_relationship` (
                `id` bigint(20) NOT NULL,
                `effectiveTime` date NOT NULL,
                `active` int(11) NOT NULL,
                `moduleId` bigint(25) NOT NULL,
                `sourceId` bigint(20) NOT NULL,
                `destinationId` bigint(20) NOT NULL,
                `typeId` bigint(25) NOT NULL,
                `characteristicTypeId` bigint(25) NOT NULL,
                `modifierId` bigint(25) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB",

            "DROP TABLE IF EXISTS `sct2_statedrelationship`",
            "CREATE TABLE IF NOT EXISTS `sct2_statedrelationship` (
                `id` bigint(20) NOT NULL,
                `effectiveTime` date NOT NULL,
                `active` int(11) NOT NULL,
                `moduleId` bigint(25) NOT NULL,
                `sourceId` bigint(20) NOT NULL,
                `destinationId` bigint(20) NOT NULL,
                `relationshipGroup` int(11) NOT NULL,
                `typeId` bigint(25) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB",

            "DROP TABLE IF EXISTS `sct2_textdefinition`",
            "CREATE TABLE IF NOT EXISTS `sct2_textdefinition` (
                `id` bigint(20) NOT NULL,
                `effectiveTime` date NOT NULL,
                `active` int(11) NOT NULL,
                `moduleId` bigint(25) NOT NULL,
                `conceptId` bigint(20) NOT NULL,
                `languageCode` varchar(8) NOT NULL,
                `typeId` bigint(25) NOT NULL,
                `term` varchar(655) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB",
        ];

        foreach ($tableDefinitions as $sql) {
            if (trim($sql) !== '') {
                sqlStatement($sql);
            }
        }
    }

    /**
     * Optimized RF1 import
     */
    private function importOptimizedRF1(string $tempDir, bool $usExtension): array
    {
        $stats = [
            'tables_created' => 0,
            'tables_loaded' => 0,
            'rows_imported' => 0,
            'errors' => 0,
        ];

        try {
            $this->firePreInstallEvent(self::CODE_TYPE, ['us_extension' => $usExtension]);

            // Create tables only if not US extension
            if (!$usExtension) {
                $this->createTablesRF1();
                $stats['tables_created'] = count(self::RF1_TABLES);
            }

            // Load data
            $this->beginBulkImport();

            $subPath = 'Terminology/Content/';
            $dir = str_replace('\\', '/', $tempDir);

            if (is_dir($dir) && $handle = opendir($dir)) {
                while (false !== ($filename = readdir($handle))) {
                    if ($filename === '.' || $filename === '..' || str_contains($filename, 'zip')) {
                        continue;
                    }

                    $path = "$dir/$filename/$subPath";
                    if (!is_dir($path)) {
                        $path = "$dir/$filename/RF1Release/$subPath";
                    }

                    if (is_dir($path)) {
                        $rowCount = $this->loadRF1Files($path);
                        $stats['rows_imported'] += $rowCount;
                        $stats['tables_loaded'] = count(self::RF1_TABLES);
                    }
                }
                closedir($handle);
            }

            $this->commitBulkImport();

            $this->firePostInstallEvent(self::CODE_TYPE, ['us_extension' => $usExtension]);
        } catch (\Exception $e) {
            $this->rollbackBulkImport();
            throw $e;
        }

        return $stats;
    }

    /**
     * Load RF1 files using LOAD DATA INFILE
     */
    private function loadRF1Files(string $path): int
    {
        $totalRows = 0;

        if (!is_dir($path) || !($handle = opendir($path))) {
            return 0;
        }

        while (false !== ($filename = readdir($handle))) {
            if ($filename === '.' || $filename === '..') {
                continue;
            }

            $filePath = $path . $filename;

            foreach (self::RF1_TABLES as $tableName => $pattern) {
                if (str_contains($filename, $pattern)) {
                    $sql = "LOAD DATA LOCAL INFILE ?
                            INTO TABLE `$tableName`
                            FIELDS TERMINATED BY '\t'
                            ESCAPED BY ''
                            LINES TERMINATED BY '\n'
                            IGNORE 1 LINES";

                    sqlStatement($sql, [$filePath]);

                    $result = sqlQuery("SELECT COUNT(*) as cnt FROM `$tableName`");
                    $totalRows += (int)($result['cnt'] ?? 0);
                    break;
                }
            }
        }
        closedir($handle);

        return $totalRows;
    }

    /**
     * Create RF1 tables
     */
    private function createTablesRF1(): void
    {
        $tableDefinitions = [
            "DROP TABLE IF EXISTS `sct_concepts`",
            "CREATE TABLE IF NOT EXISTS `sct_concepts` (
                `ConceptId` bigint(20) NOT NULL,
                `ConceptStatus` int(11) NOT NULL,
                `FullySpecifiedName` varchar(255) NOT NULL,
                `CTV3ID` varchar(5) NOT NULL,
                `SNOMEDID` varchar(8) NOT NULL,
                `IsPrimitive` tinyint(1) NOT NULL,
                PRIMARY KEY (`ConceptId`)
            ) ENGINE=InnoDB",

            "DROP TABLE IF EXISTS `sct_descriptions`",
            "CREATE TABLE IF NOT EXISTS `sct_descriptions` (
                `DescriptionId` bigint(20) NOT NULL,
                `DescriptionStatus` int(11) NOT NULL,
                `ConceptId` bigint(20) NOT NULL,
                `Term` varchar(255) NOT NULL,
                `InitialCapitalStatus` tinyint(1) NOT NULL,
                `DescriptionType` int(11) NOT NULL,
                `LanguageCode` varchar(8) NOT NULL,
                PRIMARY KEY (`DescriptionId`),
                KEY `idx_concept_id` (`ConceptId`)
            ) ENGINE=InnoDB",

            "DROP TABLE IF EXISTS `sct_relationships`",
            "CREATE TABLE IF NOT EXISTS `sct_relationships` (
                `RelationshipId` bigint(20) NOT NULL,
                `ConceptId1` bigint(20) NOT NULL,
                `RelationshipType` bigint(20) NOT NULL,
                `ConceptId2` bigint(20) NOT NULL,
                `CharacteristicType` int(11) NOT NULL,
                `Refinability` int(11) NOT NULL,
                `RelationshipGroup` int(11) NOT NULL,
                PRIMARY KEY (`RelationshipId`)
            ) ENGINE=InnoDB",
        ];

        foreach ($tableDefinitions as $sql) {
            if (trim($sql) !== '') {
                sqlStatement($sql);
            }
        }
    }

    /**
     * Old method RF2 (matches standard_tables_capture.inc.php)
     */
    private function importOldMethodRF2(string $tempDir): array
    {
        $this->firePreInstallEvent(self::CODE_TYPE, []);

        $this->createTablesRF2();

        $subPath = 'Full/Terminology/';
        $dir = str_replace('\\', '/', $tempDir);

        if (is_dir($dir) && $handle = opendir($dir)) {
            while (false !== ($filename = readdir($handle))) {
                if ($filename === '.' || $filename === '..' || str_contains($filename, 'zip')) {
                    continue;
                }

                $path = "$dir/$filename/$subPath";
                if (!is_dir($path)) {
                    $path = "$dir/$filename/RF2Release/$subPath";
                }

                if (is_dir($path) && $handle1 = opendir($path)) {
                    while (false !== ($filename1 = readdir($handle1))) {
                        $loadScript = "Load data local infile '#FILENAME#' into table #TABLE# fields terminated by '\\t' ESCAPED BY '' lines terminated by '\\n' ignore 1 lines";
                        $arrayReplace = ['#FILENAME#', '#TABLE#'];

                        if ($filename1 !== '.' && $filename1 !== '..') {
                            $fileReplace = $path . $filename1;

                            foreach (self::RF2_TABLES as $tableName => $pattern) {
                                if (str_contains($filename1, $pattern)) {
                                    $newStr = str_replace($arrayReplace, [$fileReplace, $tableName], $loadScript);
                                    if ($newStr !== '') {
                                        sqlStatement($newStr);
                                    }
                                    break;
                                }
                            }
                        }
                    }
                    closedir($handle1);
                }
            }
            closedir($handle);
        }

        $this->firePostInstallEvent(self::CODE_TYPE, []);

        return ['success' => true];
    }

    /**
     * Old method RF1 (matches standard_tables_capture.inc.php)
     */
    private function importOldMethodRF1(string $tempDir, bool $usExtension): array
    {
        $this->firePreInstallEvent(self::CODE_TYPE, ['us_extension' => $usExtension]);

        if (!$usExtension) {
            $this->createTablesRF1();
        }

        $subPath = 'Terminology/Content/';
        $dir = str_replace('\\', '/', $tempDir);

        if (is_dir($dir) && $handle = opendir($dir)) {
            while (false !== ($filename = readdir($handle))) {
                if ($filename === '.' || $filename === '..' || str_contains($filename, 'zip')) {
                    continue;
                }

                $path = "$dir/$filename/$subPath";
                if (!is_dir($path)) {
                    $path = "$dir/$filename/RF1Release/$subPath";
                }

                if (is_dir($path) && $handle1 = opendir($path)) {
                    while (false !== ($filename1 = readdir($handle1))) {
                        $loadScript = "Load data local infile '#FILENAME#' into table #TABLE# fields terminated by '\\t' ESCAPED BY '' lines terminated by '\\n' ignore 1 lines";
                        $arrayReplace = ['#FILENAME#', '#TABLE#'];

                        if ($filename1 !== '.' && $filename1 !== '..') {
                            $fileReplace = $path . $filename1;

                            foreach (self::RF1_TABLES as $tableName => $pattern) {
                                if (str_contains($filename1, $pattern)) {
                                    $newStr = str_replace($arrayReplace, [$fileReplace, $tableName], $loadScript);
                                    if ($newStr !== '') {
                                        sqlStatement($newStr);
                                    }
                                    break;
                                }
                            }
                        }
                    }
                    closedir($handle1);
                }
            }
            closedir($handle);
        }

        $this->firePostInstallEvent(self::CODE_TYPE, ['us_extension' => $usExtension]);

        return ['success' => true];
    }

    public function validate(string $filePath): bool
    {
        if (!is_dir($filePath)) {
            return false;
        }

        // Check for RF2 or RF1 format
        return $this->validateRF2($filePath) || $this->validateRF1($filePath);
    }

    private function validateRF2(string $filePath): bool
    {
        $subPath = 'Full/Terminology/';
        if (is_dir("$filePath/$subPath")) {
            return true;
        }

        // Check for any subdirectory with the path
        if ($handle = opendir($filePath)) {
            while (false !== ($filename = readdir($handle))) {
                if ($filename !== '.' && $filename !== '..') {
                    if (is_dir("$filePath/$filename/$subPath")) {
                        closedir($handle);
                        return true;
                    }
                }
            }
            closedir($handle);
        }

        return false;
    }

    private function validateRF1(string $filePath): bool
    {
        $subPath = 'Terminology/Content/';
        if (is_dir("$filePath/$subPath")) {
            return true;
        }

        // Check for any subdirectory with the path
        if ($handle = opendir($filePath)) {
            while (false !== ($filename = readdir($handle))) {
                if ($filename !== '.' && $filename !== '..') {
                    if (is_dir("$filePath/$filename/$subPath")) {
                        closedir($handle);
                        return true;
                    }
                }
            }
            closedir($handle);
        }

        return false;
    }

    public function estimateRowCount(string $filePath): int
    {
        // This would require parsing all files, which is expensive
        // Return a rough estimate
        return 1000000; // SNOMED typically has millions of records
    }
}
