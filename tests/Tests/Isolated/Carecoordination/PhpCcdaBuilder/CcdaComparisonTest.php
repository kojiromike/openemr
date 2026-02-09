<?php

/**
 * CcdaComparisonTest.php - Compare PHP CCDA engine output with Node.js baseline
 *
 * This test validates that the PHP CcdaBuilder produces output equivalent to
 * the Node.js ccdaservice by comparing against a known-good baseline file.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Tests\Isolated\Carecoordination\PhpCcdaBuilder;

use DOMDocument;
use DOMXPath;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\CcdaBuilder;
use PHPUnit\Framework\TestCase;

class CcdaComparisonTest extends TestCase
{
    private const FIXTURE_DIR = __DIR__ . '/../../../../Tests/data/Services/Modules/CareCoordination/Model/CcdaServiceDocumentRequestor/';

    private const INPUT_FILE = 'ccda-example-input1.xml';
    private const EXPECTED_FILE = 'ccda-example-response1.xml';

    /**
     * Original generation date in the fixture files
     */
    private const FIXTURE_DATE = '20251215';

    public function testPhpEngineGeneratesValidCcda(): void
    {
        $inputXml = $this->loadFixture(self::INPUT_FILE);
        $this->assertNotEmpty($inputXml, 'Failed to load input fixture');

        $builder = new CcdaBuilder();
        $output = $builder->generate($inputXml);

        $this->assertNotEmpty($output, 'PHP engine produced empty output');
        $this->assertStringContainsString('<?xml', $output, 'Output is not valid XML');
        $this->assertStringContainsString('<ClinicalDocument', $output, 'Output is not a CDA document');
        $this->assertStringContainsString('</ClinicalDocument>', $output, 'CDA document is not complete');
    }

    public function testPhpEngineOutputMatchesNodeJsBaseline(): void
    {
        $inputXml = $this->loadFixture(self::INPUT_FILE);
        $expectedXml = $this->loadFixture(self::EXPECTED_FILE);

        $this->assertNotEmpty($inputXml, 'Failed to load input fixture');
        $this->assertNotEmpty($expectedXml, 'Failed to load expected output fixture');

        // Generate CCDA using PHP engine
        $builder = new CcdaBuilder();
        $actualXml = $builder->generate($inputXml);

        // Parse both documents
        $actualDom = $this->loadDom($actualXml);
        $expectedDom = $this->loadDom($expectedXml);

        // Normalize for comparison
        $this->normalizeTimestamps($actualDom, date('Ymd'), self::FIXTURE_DATE);
        $this->normalizeUuids($actualDom, $expectedDom);
        $this->cleanWhitespace($actualDom);
        $this->cleanWhitespace($expectedDom);

        // Compare using canonical XML
        $this->assertXmlStringEqualsXmlString(
            $expectedDom->C14N(),
            $actualDom->C14N(),
            'PHP engine output does not match Node.js baseline'
        );
    }

    public function testPhpEngineOutputContainsRequiredSections(): void
    {
        $inputXml = $this->loadFixture(self::INPUT_FILE);
        $builder = new CcdaBuilder();
        $output = $builder->generate($inputXml);

        $dom = $this->loadDom($output);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('hl7', 'urn:hl7-org:v3');

        // Check for required C-CDA sections
        $requiredSections = [
            'recordTarget' => '//hl7:recordTarget',
            'author' => '//hl7:author',
            'custodian' => '//hl7:custodian',
            'component' => '//hl7:component/hl7:structuredBody',
        ];

        foreach ($requiredSections as $name => $query) {
            $nodes = $xpath->query($query);
            $this->assertGreaterThan(
                0,
                $nodes->length,
                "Required section '{$name}' not found in output"
            );
        }
    }

    public function testPhpEngineOutputContainsPatientData(): void
    {
        $inputXml = $this->loadFixture(self::INPUT_FILE);
        $builder = new CcdaBuilder();
        $output = $builder->generate($inputXml);

        $dom = $this->loadDom($output);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('hl7', 'urn:hl7-org:v3');

        // Check patient name from fixture: Shon148 King743
        $familyName = $xpath->query('//hl7:recordTarget//hl7:patient/hl7:name/hl7:family');
        $this->assertGreaterThan(0, $familyName->length, 'Patient family name not found');
        $this->assertEquals('King743', trim($familyName->item(0)->textContent));

        $givenName = $xpath->query('//hl7:recordTarget//hl7:patient/hl7:name/hl7:given');
        $this->assertGreaterThan(0, $givenName->length, 'Patient given name not found');
        $this->assertEquals('Shon148', trim($givenName->item(0)->textContent));

        // Check birth date: 19870404
        $birthTime = $xpath->query('//hl7:recordTarget//hl7:patient/hl7:birthTime/@value');
        $this->assertGreaterThan(0, $birthTime->length, 'Patient birth time not found');
        $this->assertEquals('19870404', $birthTime->item(0)->value);
    }

    /**
     * Load a fixture file
     */
    private function loadFixture(string $filename): string
    {
        $path = self::FIXTURE_DIR . $filename;
        if (!file_exists($path)) {
            $this->fail("Fixture file not found: {$path}");
        }
        return trim(file_get_contents($path));
    }

    /**
     * Parse XML into DOMDocument
     */
    private function loadDom(string $xml): DOMDocument
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        if (!$dom->loadXML($xml, LIBXML_NOBLANKS)) {
            $this->fail('Failed to parse XML');
        }

        return $dom;
    }

    /**
     * Normalize timestamps in the document
     *
     * Replaces current date with fixture date for comparison
     */
    private function normalizeTimestamps(DOMDocument $dom, string $currentDate, string $targetDate): void
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('hl7', 'urn:hl7-org:v3');

        // Replace date in value attributes
        $nodes = $xpath->query("//*[@value]");
        foreach ($nodes as $node) {
            $value = $node->getAttribute('value');
            if (str_starts_with((string) $value, $currentDate)) {
                $newValue = $targetDate . substr((string) $value, strlen($currentDate));
                $node->setAttribute('value', $newValue);
            }
        }

        // Replace date in text content
        $currentFormatted = (new \DateTimeImmutable($currentDate))->format('Y-m-d');
        $targetFormatted = (new \DateTimeImmutable($targetDate))->format('Y-m-d');

        $textNodes = $xpath->query('//hl7:tr/hl7:td/text()');
        foreach ($textNodes as $textNode) {
            if (str_contains((string) $textNode->nodeValue, $currentFormatted)) {
                $textNode->nodeValue = str_replace($currentFormatted, $targetFormatted, $textNode->nodeValue);
            }
        }
    }

    /**
     * Normalize UUIDs that differ between runs
     *
     * Copies root IDs from expected document to actual document for specific elements
     */
    private function normalizeUuids(DOMDocument $actual, DOMDocument $expected): void
    {
        $actualXpath = new DOMXPath($actual);
        $expectedXpath = new DOMXPath($expected);
        $actualXpath->registerNamespace('hl7', 'urn:hl7-org:v3');
        $expectedXpath->registerNamespace('hl7', 'urn:hl7-org:v3');

        // Elements with dynamic UUIDs
        $queries = [
            "//hl7:observation/hl7:code[@code='76691-5']",  // Gender Identity
            "//hl7:observation/hl7:code[@code='46098-0']",  // Sex
            "//hl7:observation/hl7:code[@code='76690-7']",  // Sexual Orientation
            "//hl7:section/hl7:entry/hl7:organizer/hl7:code[@code='86744-0']",  // Care Team
            "//hl7:component/hl7:act/hl7:code[@code='85847-2']",  // Patient Care Team Info
        ];

        foreach ($queries as $query) {
            $actualNodes = $actualXpath->query($query);
            $expectedNodes = $expectedXpath->query($query);

            if ($actualNodes->length !== $expectedNodes->length) {
                continue;
            }

            for ($i = 0; $i < $actualNodes->length; $i++) {
                $actualParent = $actualNodes->item($i)->parentNode;
                $expectedParent = $expectedNodes->item($i)->parentNode;

                $actualId = $actualXpath->query('.//hl7:id', $actualParent)->item(0);
                $expectedId = $expectedXpath->query('.//hl7:id', $expectedParent)->item(0);

                if ($actualId && $expectedId) {
                    $actualId->setAttribute('root', $expectedId->getAttribute('root'));
                }
            }
        }
    }

    /**
     * Clean whitespace in text nodes for consistent comparison
     */
    private function cleanWhitespace(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('hl7', 'urn:hl7-org:v3');
        $xpath->registerNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

        $nodes = $xpath->query('//hl7:text//text() | //xhtml:td//text() | //hl7:value//text()');

        foreach ($nodes as $text) {
            $text->nodeValue = trim((string) preg_replace('/\s+/u', ' ', (string) $text->nodeValue));
        }
    }
}
