<?php

/**
 * CodeLoaderInterface.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc.
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services\CodeImport;

/**
 * Interface for code loaders
 */
interface CodeLoaderInterface
{
    /**
     * Import codes from a file or stream
     *
     * @param string $filePath Path to the file to import
     * @param array $options Import options (replace, batch_size, etc.)
     * @return array Statistics about the import (inserted, updated, errors)
     */
    public function import(string $filePath, array $options = []): array;

    /**
     * Validate the source file format
     *
     * @param string $filePath Path to the file to validate
     * @return bool True if valid
     */
    public function validate(string $filePath): bool;

    /**
     * Get the code type this loader handles
     *
     * @return string Code type identifier
     */
    public function getCodeType(): string;

    /**
     * Get estimated row count from file
     *
     * @param string $filePath Path to the file
     * @return int Estimated row count
     */
    public function estimateRowCount(string $filePath): int;
}
