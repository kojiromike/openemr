<?php

/**
 * DateFormatterTest.php - Unit tests for DateFormatter
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
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Utils\DateFormatter;

class DateFormatterTest extends TestCase
{
    public function testFDateWithStandardDatetime(): void
    {
        // 2026-01-15 10:30:00 should become 202601151030+ZZZZ
        $result = DateFormatter::fDate('2026-01-15 10:30:00');

        // Check date/time portion (timezone varies by system)
        $this->assertStringStartsWith('202601151030', $result);
        // Check timezone format (+NNNN or -NNNN)
        $this->assertMatchesRegularExpression('/^\d{12}[+-]\d{4}$/', $result);
    }

    public function testFDateWithDateOnly(): void
    {
        $result = DateFormatter::fDate('2026-01-15', false);
        $this->assertEquals('20260115', $result);
    }

    public function testFDateWithEmptyStringReturnsCurrentDate(): void
    {
        $result = DateFormatter::fDate('');

        // Should return current date in CCDA format
        $this->assertMatchesRegularExpression('/^\d{12}[+-]\d{4}$/', $result);

        // Should be approximately now
        $today = date('Ymd');
        $this->assertStringStartsWith($today, $result);
    }

    public function testFDateWithNullReturnsCurrentDate(): void
    {
        $result = DateFormatter::fDate(null);
        $this->assertMatchesRegularExpression('/^\d{12}[+-]\d{4}$/', $result);
    }

    public function testFDateWithZeroDateReturnsCurrentDate(): void
    {
        $result = DateFormatter::fDate('0000-00-00');
        $this->assertMatchesRegularExpression('/^\d{12}[+-]\d{4}$/', $result);
    }

    public function testFDateWithYmdFormat(): void
    {
        $result = DateFormatter::fDate('20260115', false);
        $this->assertEquals('20260115', $result);
    }

    public function testFDateWithSlashFormat(): void
    {
        $result = DateFormatter::fDate('01/15/2026', false);
        $this->assertEquals('20260115', $result);
    }

    public function testFDateNoSecondsInOutput(): void
    {
        // CCDA spec requires YYYYMMDDHHMM+ZZZZ (no seconds)
        $result = DateFormatter::fDate('2026-01-15 10:30:45');

        // Should be 12 digits + timezone (4 digits with sign)
        $this->assertMatchesRegularExpression('/^\d{12}[+-]\d{4}$/', $result);
        // Should NOT include seconds (would be 14 digits)
        $this->assertEquals(17, strlen($result)); // 12 + 1 (sign) + 4 (offset)
    }

    public function testTemplateDateWithTimezonePrecision(): void
    {
        $result = DateFormatter::templateDate('2026-01-15 10:30:00', 'tz');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('precision', $result);
        $this->assertEquals('tz', $result['precision']);
        $this->assertStringStartsWith('202601151030', $result['date']);
    }

    public function testTemplateDateWithDayPrecision(): void
    {
        $result = DateFormatter::templateDate('2026-01-15', 'day');

        $this->assertIsArray($result);
        $this->assertEquals('day', $result['precision']);
        $this->assertEquals('20260115', $result['date']);
    }

    public function testParseCcdaDateWithFullFormat(): void
    {
        $result = DateFormatter::parseCcdaDate('20260115103045+0000');

        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertEquals('2026-01-15', $result->format('Y-m-d'));
        $this->assertEquals('10:30:45', $result->format('H:i:s'));
    }

    public function testParseCcdaDateWithDateOnly(): void
    {
        $result = DateFormatter::parseCcdaDate('20260115');

        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertEquals('2026-01-15', $result->format('Y-m-d'));
    }

    public function testParseCcdaDateWithEmptyReturnsNull(): void
    {
        $result = DateFormatter::parseCcdaDate('');
        $this->assertNull($result);
    }

    public function testParseCcdaDateWithWhitespaceReturnsNull(): void
    {
        $result = DateFormatter::parseCcdaDate('   ');
        $this->assertNull($result);
    }
}
