<?php

/**
 * CodeCleanerTest.php - Unit tests for CodeCleaner
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\Carecoordination\PhpCcdaBuilder\Utils;

use PHPUnit\Framework\TestCase;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Utils\CodeCleaner;

class CodeCleanerTest extends TestCase
{
    // ========== clean() tests ==========

    public function testCleanWithNull(): void
    {
        $this->assertEquals('', CodeCleaner::clean(null));
    }

    public function testCleanWithEmptyString(): void
    {
        $this->assertEquals('', CodeCleaner::clean(''));
    }

    public function testCleanTrimsWhitespace(): void
    {
        $this->assertEquals('12345', CodeCleaner::clean('  12345  '));
    }

    public function testCleanRemovesSnomedPrefix(): void
    {
        $this->assertEquals('73211009', CodeCleaner::clean('SNOMED-CT:73211009'));
        $this->assertEquals('73211009', CodeCleaner::clean('SNOMED CT:73211009'));
        $this->assertEquals('73211009', CodeCleaner::clean('SNOMED:73211009'));
    }

    public function testCleanRemovesIcd10Prefix(): void
    {
        $this->assertEquals('J06.9', CodeCleaner::clean('ICD10:J06.9'));
        $this->assertEquals('J06.9', CodeCleaner::clean('ICD-10:J06.9'));
        $this->assertEquals('J06.9', CodeCleaner::clean('ICD-10-CM:J06.9'));
    }

    public function testCleanRemovesRxNormPrefix(): void
    {
        $this->assertEquals('197361', CodeCleaner::clean('RXNORM:197361'));
        $this->assertEquals('197361', CodeCleaner::clean('RxNorm:197361'));
    }

    public function testCleanRemovesLoincPrefix(): void
    {
        $this->assertEquals('8867-4', CodeCleaner::clean('LOINC:8867-4'));
    }

    public function testCleanRemovesQuotes(): void
    {
        $this->assertEquals('12345', CodeCleaner::clean('"12345"'));
        $this->assertEquals('12345', CodeCleaner::clean("'12345'"));
    }

    public function testCleanPreservesCodeWithoutPrefix(): void
    {
        $this->assertEquals('73211009', CodeCleaner::clean('73211009'));
        $this->assertEquals('J06.9', CodeCleaner::clean('J06.9'));
    }

    // ========== getCodeSystem() tests ==========

    public function testGetCodeSystemFromSnomedPrefix(): void
    {
        $this->assertEquals('SNOMED CT', CodeCleaner::getCodeSystem('SNOMED-CT:73211009'));
        $this->assertEquals('SNOMED CT', CodeCleaner::getCodeSystem('SNOMED:73211009'));
    }

    public function testGetCodeSystemFromIcd10Prefix(): void
    {
        $this->assertEquals('ICD-10-CM', CodeCleaner::getCodeSystem('ICD10:J06.9'));
        $this->assertEquals('ICD-10-CM', CodeCleaner::getCodeSystem('ICD-10-CM:J06.9'));
    }

    public function testGetCodeSystemFromRxNormPrefix(): void
    {
        $this->assertEquals('RXNORM', CodeCleaner::getCodeSystem('RXNORM:197361'));
    }

    public function testGetCodeSystemFromLoincPrefix(): void
    {
        $this->assertEquals('LOINC', CodeCleaner::getCodeSystem('LOINC:8867-4'));
    }

    public function testGetCodeSystemReturnsDefaultForNull(): void
    {
        $this->assertEquals('SNOMED CT', CodeCleaner::getCodeSystem(null));
        $this->assertEquals('Custom', CodeCleaner::getCodeSystem(null, 'Custom'));
    }

    public function testGetCodeSystemInfersSnomedFromPattern(): void
    {
        // Long numeric codes (6-18 digits) are inferred as SNOMED
        $this->assertEquals('SNOMED CT', CodeCleaner::getCodeSystem('73211009'));
    }

    public function testGetCodeSystemInfersIcd10FromPattern(): void
    {
        // Letter + digits with optional decimal
        $this->assertEquals('ICD-10-CM', CodeCleaner::getCodeSystem('J06.9'));
        $this->assertEquals('ICD-10-CM', CodeCleaner::getCodeSystem('M54.5'));
    }

    public function testGetCodeSystemInfersLoincFromPattern(): void
    {
        // Digits-checkdigit pattern
        $this->assertEquals('LOINC', CodeCleaner::getCodeSystem('8867-4'));
        $this->assertEquals('LOINC', CodeCleaner::getCodeSystem('29463-7'));
    }

    public function testGetCodeSystemInfersHcpcsFromPattern(): void
    {
        // Letter + 4 digits
        $this->assertEquals('HCPCS', CodeCleaner::getCodeSystem('G0008'));
    }

    // ========== getCodeSystemOid() tests ==========

    public function testGetCodeSystemOidForSnomedCt(): void
    {
        $this->assertEquals('2.16.840.1.113883.6.96', CodeCleaner::getCodeSystemOid('SNOMED CT'));
    }

    public function testGetCodeSystemOidForIcd10(): void
    {
        $this->assertEquals('2.16.840.1.113883.6.90', CodeCleaner::getCodeSystemOid('ICD-10-CM'));
    }

    public function testGetCodeSystemOidForRxNorm(): void
    {
        $this->assertEquals('2.16.840.1.113883.6.88', CodeCleaner::getCodeSystemOid('RXNORM'));
    }

    public function testGetCodeSystemOidForLoinc(): void
    {
        $this->assertEquals('2.16.840.1.113883.6.1', CodeCleaner::getCodeSystemOid('LOINC'));
    }

    public function testGetCodeSystemOidForCvx(): void
    {
        $this->assertEquals('2.16.840.1.113883.12.292', CodeCleaner::getCodeSystemOid('CVX'));
    }

    public function testGetCodeSystemOidReturnsEmptyForUnknown(): void
    {
        $this->assertEquals('', CodeCleaner::getCodeSystemOid('Unknown System'));
    }

    // ========== normalizeCodeSystemName() tests ==========

    public function testNormalizeCodeSystemNameForSnomed(): void
    {
        $this->assertEquals('SNOMED CT', CodeCleaner::normalizeCodeSystemName('SNOMED'));
        $this->assertEquals('SNOMED CT', CodeCleaner::normalizeCodeSystemName('SNOMED-CT'));
        $this->assertEquals('SNOMED CT', CodeCleaner::normalizeCodeSystemName('snomedct'));
    }

    public function testNormalizeCodeSystemNameForIcd10(): void
    {
        $this->assertEquals('ICD-10-CM', CodeCleaner::normalizeCodeSystemName('ICD10'));
        $this->assertEquals('ICD-10-CM', CodeCleaner::normalizeCodeSystemName('ICD-10'));
    }

    public function testNormalizeCodeSystemNameForCpt(): void
    {
        $this->assertEquals('CPT', CodeCleaner::normalizeCodeSystemName('CPT4'));
        $this->assertEquals('CPT', CodeCleaner::normalizeCodeSystemName('CPT-4'));
    }

    public function testNormalizeCodeSystemNameReturnsOriginalIfUnknown(): void
    {
        $this->assertEquals('Custom System', CodeCleaner::normalizeCodeSystemName('Custom System'));
    }

    public function testNormalizeCodeSystemNameWithNull(): void
    {
        $this->assertEquals('', CodeCleaner::normalizeCodeSystemName(null));
    }

    public function testNormalizeCodeSystemNameWithEmpty(): void
    {
        $this->assertEquals('', CodeCleaner::normalizeCodeSystemName(''));
    }
}
