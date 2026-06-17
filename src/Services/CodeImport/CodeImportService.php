<?php

/**
 * CodeImportService.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc.
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services\CodeImport;

/**
 * Main service class for managing code imports
 */
class CodeImportService
{
    private array $loaders = [];

    /**
     * Constructor
     *
     * @param bool $useOldMethod Use old import methods for compatibility
     * @param int $batchSize Batch size for imports
     */
    public function __construct(private bool $useOldMethod = false, private int $batchSize = AbstractCodeLoader::DEFAULT_BATCH_SIZE)
    {
    }

    /**
     * Get a loader for a specific code type
     *
     * @param string $codeType Code type (RXCUI, RXNORM, SNOMED, ICD10, CQM_VALUESET)
     * @param array $options Additional options for the loader
     * @return CodeLoaderInterface
     */
    public function getLoader(string $codeType, array $options = []): CodeLoaderInterface
    {
        $cacheKey = $codeType;

        if (!isset($this->loaders[$cacheKey])) {
            $loader = match ($codeType) {
                'RXCUI' => new RxcuiLoader($this->batchSize),
                'RXNORM' => new RxnormLoader(
                    $this->batchSize,
                    $options['is_windows'] ?? false
                ),
                'SNOMED' => new SnomedLoader(
                    $this->batchSize,
                    $options['format'] ?? 'RF2'
                ),
                'ICD10' => new Icd10Loader($this->batchSize),
                'CQM_VALUESET' => new CqmValuesetLoader($this->batchSize),
                default => throw new \InvalidArgumentException("Unsupported code type: $codeType"),
            };

            $loader->setUseOldMethod($this->useOldMethod);
            $this->loaders[$cacheKey] = $loader;
        }

        return $this->loaders[$cacheKey];
    }

    /**
     * Import codes using the appropriate loader
     *
     * @param string $codeType Code type
     * @param string $filePath Path to file or directory
     * @param array $options Import options
     * @return array Import statistics
     */
    public function import(string $codeType, string $filePath, array $options = []): array
    {
        $loader = $this->getLoader($codeType, $options);

        if (!$loader->validate($filePath)) {
            throw new \RuntimeException("Invalid file or directory for $codeType: $filePath");
        }

        return $loader->import($filePath, $options);
    }

    /**
     * Set whether to use old import methods
     *
     * @param bool $useOld
     */
    public function setUseOldMethod(bool $useOld): void
    {
        $this->useOldMethod = $useOld;
        // Clear cached loaders so they get recreated with new setting
        $this->loaders = [];
    }

    /**
     * Set batch size for imports
     *
     * @param int $batchSize
     */
    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = min($batchSize, AbstractCodeLoader::MAX_BATCH_SIZE);
        // Clear cached loaders so they get recreated with new setting
        $this->loaders = [];
    }

    /**
     * Validate a file before import
     *
     * @param string $codeType Code type
     * @param string $filePath Path to validate
     * @param array $options Validation options
     * @return bool
     */
    public function validateFile(string $codeType, string $filePath, array $options = []): bool
    {
        try {
            $loader = $this->getLoader($codeType, $options);
            return $loader->validate($filePath);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Estimate row count for import
     *
     * @param string $codeType Code type
     * @param string $filePath Path to file
     * @param array $options Options
     * @return int Estimated row count
     */
    public function estimateRowCount(string $codeType, string $filePath, array $options = []): int
    {
        $loader = $this->getLoader($codeType, $options);
        return $loader->estimateRowCount($filePath);
    }

    /**
     * Get list of supported code types
     *
     * @return array
     */
    public function getSupportedCodeTypes(): array
    {
        return [
            'RXCUI' => [
                'name' => 'RxNorm Concept Unique Identifier',
                'description' => 'Prescribable drug codes from RxNorm',
            ],
            'RXNORM' => [
                'name' => 'RxNorm',
                'description' => 'Full RxNorm database tables',
            ],
            'SNOMED' => [
                'name' => 'SNOMED CT',
                'description' => 'SNOMED Clinical Terms (RF1 or RF2 format)',
            ],
            'ICD10' => [
                'name' => 'ICD-10',
                'description' => 'ICD-10 diagnosis and procedure codes',
            ],
            'CQM_VALUESET' => [
                'name' => 'CQM Valuesets',
                'description' => 'Clinical Quality Measure valuesets',
            ],
        ];
    }
}
