<?php

/**
 * CcdaBuilderTest.php - Unit tests for CcdaBuilder
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\Carecoordination\PhpCcdaBuilder;

use PHPUnit\Framework\TestCase;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\CcdaBuilder;

class CcdaBuilderTest extends TestCase
{
    private CcdaBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        // Skip if OEGlobalsBag is not available (requires full OpenEMR context)
        if (!class_exists(\OpenEMR\Core\OEGlobalsBag::class)) {
            $this->markTestSkipped('OEGlobalsBag not available in isolated tests');
        }
        $this->builder = new CcdaBuilder();
    }

    public function testGenerateThrowsOnEmptyInput(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid CCDA XML structure');
        $this->builder->generate('');
    }

    public function testGenerateThrowsOnInvalidXmlStructure(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid CCDA XML structure');
        $this->builder->generate('<root>not ccda</root>');
    }

    public function testGenerateThrowsOnMalformedXml(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('XML parsing failed');
        $this->builder->generate('<CCDA><unclosed></CCDA>');
    }

    public function testSetDebugReturnsSelf(): void
    {
        $result = $this->builder->setDebug(true);
        $this->assertSame($this->builder, $result);
    }

    public function testSetStylesheetPathReturnsSelf(): void
    {
        $result = $this->builder->setStylesheetPath('/path/to/stylesheet.xsl');
        $this->assertSame($this->builder, $result);
    }
}
