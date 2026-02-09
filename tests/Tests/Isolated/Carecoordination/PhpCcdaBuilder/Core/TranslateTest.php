<?php

/**
 * TranslateTest.php - Unit tests for Translate
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\Carecoordination\PhpCcdaBuilder\Core;

use PHPUnit\Framework\TestCase;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core\Translate;

class TranslateTest extends TestCase
{
    // ========== codeFromName() tests ==========

    public function testCodeFromNameWithProblemStatusActive(): void
    {
        $result = Translate::codeFromName('2.16.840.1.113883.3.88.12.80.68', 'Active');

        $this->assertEquals('55561003', $result['code']);
        $this->assertEquals('2.16.840.1.113883.6.96', $result['codeSystem']);
        $this->assertEquals('SNOMED CT', $result['codeSystemName']);
        $this->assertEquals('Active', $result['displayName']);
    }

    public function testCodeFromNameWithProblemStatusInactive(): void
    {
        $result = Translate::codeFromName('2.16.840.1.113883.3.88.12.80.68', 'Inactive');

        $this->assertEquals('73425007', $result['code']);
    }

    public function testCodeFromNameWithProblemStatusResolved(): void
    {
        $result = Translate::codeFromName('2.16.840.1.113883.3.88.12.80.68', 'Resolved');

        $this->assertEquals('413322009', $result['code']);
    }

    public function testCodeFromNameWithEmptyInput(): void
    {
        $result = Translate::codeFromName('2.16.840.1.113883.3.88.12.80.68', '');

        $this->assertEquals([], $result);
    }

    public function testCodeFromNameWithNullInput(): void
    {
        $result = Translate::codeFromName('2.16.840.1.113883.3.88.12.80.68', null);

        $this->assertEquals([], $result);
    }

    public function testCodeFromNameWithUnknownValue(): void
    {
        $result = Translate::codeFromName('2.16.840.1.113883.3.88.12.80.68', 'UnknownStatus');

        $this->assertEquals('UnknownStatus', $result['code']);
        $this->assertEquals('2.16.840.1.113883.3.88.12.80.68', $result['codeSystem']);
        $this->assertEquals('UnknownStatus', $result['displayName']);
    }

    public function testCodeFromNameWithArrayInput(): void
    {
        $input = ['name' => 'Active', 'code' => '12345'];
        $result = Translate::codeFromName('2.16.840.1.113883.3.88.12.80.68', $input);

        // Should use 'name' from array to look up in value set
        $this->assertEquals('55561003', $result['code']);
    }

    public function testCodeFromNameWithArrayInputNoMatch(): void
    {
        $input = ['name' => 'CustomName', 'code' => '12345', 'code_system' => 'custom'];
        $result = Translate::codeFromName('unknown-oid', $input);

        // Should return input values when no match
        $this->assertEquals('12345', $result['code']);
        $this->assertEquals('custom', $result['codeSystem']);
        $this->assertEquals('CustomName', $result['displayName']);
    }

    // ========== time() tests ==========

    public function testTimeWithHl7FormattedString(): void
    {
        $result = Translate::time('20260115');

        $this->assertEquals('20260115', $result);
    }

    public function testTimeWithFullHl7Format(): void
    {
        $result = Translate::time('20260115103045');

        $this->assertEquals('20260115103045', $result);
    }

    public function testTimeWithDateString(): void
    {
        $result = Translate::time('2026-01-15');

        $this->assertStringStartsWith('20260115', $result);
    }

    public function testTimeWithDateTimeString(): void
    {
        $result = Translate::time('2026-01-15 10:30:45');

        $this->assertStringStartsWith('20260115103045', $result);
    }

    public function testTimeWithEmptyString(): void
    {
        $this->assertNull(Translate::time(''));
    }

    public function testTimeWithNull(): void
    {
        $this->assertNull(Translate::time(null));
    }

    public function testTimeWithArrayInput(): void
    {
        $input = ['date' => '2026-01-15', 'precision' => 'day'];
        $result = Translate::time($input);

        $this->assertEquals('20260115', $result);
    }

    public function testTimeWithArrayInputTzPrecision(): void
    {
        $input = ['date' => '2026-01-15 10:30:00', 'precision' => 'tz'];
        $result = Translate::time($input);

        $this->assertNotNull($result);
        $this->assertStringStartsWith('202601151030', $result);
    }

    // ========== formatDate() tests ==========

    public function testFormatDateWithDayPrecision(): void
    {
        $result = Translate::formatDate('2026-01-15', 'day');
        $this->assertEquals('20260115', $result);
    }

    public function testFormatDateWithMonthPrecision(): void
    {
        $result = Translate::formatDate('2026-01-15', 'month');
        $this->assertEquals('202601', $result);
    }

    public function testFormatDateWithYearPrecision(): void
    {
        $result = Translate::formatDate('2026-01-15', 'year');
        $this->assertEquals('2026', $result);
    }

    public function testFormatDateWithHourPrecision(): void
    {
        $result = Translate::formatDate('2026-01-15 10:30:45', 'hour');
        $this->assertEquals('2026011510', $result);
    }

    public function testFormatDateWithMinutePrecision(): void
    {
        $result = Translate::formatDate('2026-01-15 10:30:45', 'minute');
        $this->assertEquals('202601151030', $result);
    }

    public function testFormatDateWithDefaultPrecision(): void
    {
        $result = Translate::formatDate('2026-01-15 10:30:45');
        $this->assertEquals('20260115103045', $result);
    }

    public function testFormatDateWithNull(): void
    {
        $this->assertNull(Translate::formatDate(null));
    }

    public function testFormatDateWithEmptyString(): void
    {
        $this->assertNull(Translate::formatDate(''));
    }

    // ========== acronymize() tests ==========

    public function testAcronymizeWithPrimaryHome(): void
    {
        $this->assertEquals('HP', Translate::acronymize('primary home'));
    }

    public function testAcronymizeWithWork(): void
    {
        $this->assertEquals('WP', Translate::acronymize('work'));
    }

    public function testAcronymizeWithMobile(): void
    {
        $this->assertEquals('MC', Translate::acronymize('mobile'));
    }

    public function testAcronymizeWithVacationHome(): void
    {
        $this->assertEquals('HV', Translate::acronymize('vacation home'));
    }

    public function testAcronymizeWithNull(): void
    {
        $this->assertNull(Translate::acronymize(null));
    }

    public function testAcronymizeWithEmptyString(): void
    {
        $this->assertNull(Translate::acronymize(''));
    }

    public function testAcronymizeWithUnknownValue(): void
    {
        // Unknown values are uppercased
        $this->assertEquals('UNKNOWN TYPE', Translate::acronymize('unknown type'));
    }

    public function testAcronymizeCaseInsensitive(): void
    {
        $this->assertEquals('HP', Translate::acronymize('PRIMARY HOME'));
        $this->assertEquals('WP', Translate::acronymize('Work'));
    }

    // ========== telecom() tests ==========

    public function testTelecomWithPhoneNumber(): void
    {
        $result = Translate::telecom(['number' => '555-123-4567']);

        $this->assertArrayHasKey('value', $result);
        $this->assertStringStartsWith('tel:', $result['value']);
        $this->assertStringContainsString('5551234567', $result['value']);
    }

    public function testTelecomWithPhone(): void
    {
        $result = Translate::telecom(['phone' => '(555) 123-4567']);

        $this->assertStringStartsWith('tel:', $result['value']);
    }

    public function testTelecomWithEmail(): void
    {
        $result = Translate::telecom(['email' => 'test@example.com']);

        $this->assertEquals('mailto:test@example.com', $result['value']);
    }

    public function testTelecomWithEmailAlreadyPrefixed(): void
    {
        $result = Translate::telecom(['email' => 'mailto:test@example.com']);

        $this->assertEquals('mailto:test@example.com', $result['value']);
    }

    public function testTelecomWithTelAlreadyPrefixed(): void
    {
        $result = Translate::telecom(['number' => 'tel:+15551234567']);

        $this->assertEquals('tel:+15551234567', $result['value']);
    }

    public function testTelecomWithUse(): void
    {
        $result = Translate::telecom(['number' => '555-1234', 'use' => 'HP']);

        $this->assertEquals('HP', $result['use']);
    }

    public function testTelecomWithDefaultUse(): void
    {
        $result = Translate::telecom(['number' => '555-1234']);

        $this->assertEquals('WP', $result['use']);
    }

    public function testTelecomWithNull(): void
    {
        $this->assertNull(Translate::telecom(null));
    }

    public function testTelecomWithString(): void
    {
        $result = Translate::telecom('555-1234');

        $this->assertEquals(['value' => '555-1234'], $result);
    }

    public function testTelecomWithEmptyNumber(): void
    {
        $result = Translate::telecom(['number' => '']);

        $this->assertEquals('', $result['value']);
    }

    // ========== code() tests ==========

    public function testCodeWithFullInput(): void
    {
        $input = [
            'code' => '12345',
            'code_system' => '2.16.840.1.113883.6.96',
            'code_system_name' => 'SNOMED CT',
            'name' => 'Test Code',
        ];
        $result = Translate::code($input);

        $this->assertEquals('12345', $result['code']);
        $this->assertEquals('2.16.840.1.113883.6.96', $result['codeSystem']);
        $this->assertEquals('SNOMED CT', $result['codeSystemName']);
        $this->assertEquals('Test Code', $result['displayName']);
    }

    public function testCodeWithMinimalInput(): void
    {
        $input = ['code' => '12345'];
        $result = Translate::code($input);

        $this->assertEquals('12345', $result['code']);
        $this->assertNull($result['codeSystem'] ?? null);
    }

    public function testCodeWithNull(): void
    {
        $result = Translate::code(null);
        $this->assertEquals([], $result);
    }

    public function testCodeWithEmptyArray(): void
    {
        $result = Translate::code([]);
        $this->assertEquals([], $result);
    }

}
