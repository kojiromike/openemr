<?php

/**
 * TemplateHelpersTest.php - Unit tests for TemplateHelpers
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
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core\TemplateHelpers as H;

class TemplateHelpersTest extends TestCase
{
    // ========== str() tests ==========

    public function testStrWithString(): void
    {
        $this->assertEquals('hello', H::str(['key' => 'hello'], 'key'));
    }

    public function testStrWithMissingKey(): void
    {
        $this->assertEquals('', H::str(['other' => 'value'], 'key'));
    }

    public function testStrWithDefault(): void
    {
        $this->assertEquals('default', H::str(['other' => 'value'], 'key', 'default'));
    }

    public function testStrWithIntValue(): void
    {
        $this->assertEquals('42', H::str(['key' => 42], 'key'));
    }

    public function testStrWithNullValue(): void
    {
        $this->assertEquals('', H::str(['key' => null], 'key'));
    }

    public function testStrWithArrayValue(): void
    {
        // Arrays should return empty string
        $this->assertEquals('', H::str(['key' => ['nested']], 'key'));
    }

    public function testStrWithNonArrayInput(): void
    {
        $this->assertEquals('', H::str('not an array', 'key'));
    }

    // ========== strOrNull() tests ==========

    public function testStrOrNullWithString(): void
    {
        $this->assertEquals('hello', H::strOrNull(['key' => 'hello'], 'key'));
    }

    public function testStrOrNullWithMissingKey(): void
    {
        $this->assertNull(H::strOrNull(['other' => 'value'], 'key'));
    }

    public function testStrOrNullWithEmptyString(): void
    {
        $this->assertNull(H::strOrNull(['key' => ''], 'key'));
    }

    public function testStrOrNullWithWhitespace(): void
    {
        // Whitespace-only strings are returned as-is (not trimmed to null)
        $this->assertEquals('   ', H::strOrNull(['key' => '   '], 'key'));
    }

    public function testStrOrNullWithValidWhitespace(): void
    {
        $this->assertEquals('hello world', H::strOrNull(['key' => 'hello world'], 'key'));
    }

    // ========== arr() tests ==========

    public function testArrWithArray(): void
    {
        $this->assertEquals(['a', 'b'], H::arr(['key' => ['a', 'b']], 'key'));
    }

    public function testArrWithMissingKey(): void
    {
        $this->assertEquals([], H::arr(['other' => 'value'], 'key'));
    }

    public function testArrWithNonArrayValue(): void
    {
        $this->assertEquals([], H::arr(['key' => 'string'], 'key'));
    }

    public function testArrWithNestedArray(): void
    {
        $input = ['outer' => ['inner' => ['a', 'b']]];
        $this->assertEquals(['inner' => ['a', 'b']], H::arr($input, 'outer'));
    }

    // ========== nested() tests ==========

    public function testNestedWithDotNotation(): void
    {
        $data = ['level1' => ['level2' => ['level3' => 'value']]];
        $this->assertEquals('value', H::nested($data, 'level1.level2.level3'));
    }

    public function testNestedWithMissingPath(): void
    {
        $data = ['level1' => ['level2' => 'value']];
        $this->assertEquals('', H::nested($data, 'level1.missing.path'));
    }

    public function testNestedWithDefault(): void
    {
        $data = ['level1' => []];
        $this->assertEquals('default', H::nested($data, 'level1.missing', 'default'));
    }

    public function testNestedWithSingleLevel(): void
    {
        $data = ['key' => 'value'];
        $this->assertEquals('value', H::nested($data, 'key'));
    }

    public function testNestedWithNumericIndex(): void
    {
        $data = ['items' => ['first', 'second', 'third']];
        $this->assertEquals('second', H::nested($data, 'items.1'));
    }

    // ========== nestedOrNull() tests ==========

    public function testNestedOrNullWithValue(): void
    {
        $data = ['a' => ['b' => 'value']];
        $this->assertEquals('value', H::nestedOrNull($data, 'a.b'));
    }

    public function testNestedOrNullWithMissing(): void
    {
        $data = ['a' => []];
        $this->assertNull(H::nestedOrNull($data, 'a.b'));
    }

    public function testNestedOrNullWithEmptyString(): void
    {
        // Empty string is returned (the value exists but is empty)
        $data = ['a' => ['b' => '']];
        $this->assertEquals('', H::nestedOrNull($data, 'a.b'));
    }

    // ========== notEmpty() tests ==========

    public function testNotEmptyWithNonEmptyArray(): void
    {
        $this->assertTrue(H::notEmpty(['item']));
    }

    public function testNotEmptyWithEmptyArray(): void
    {
        $this->assertFalse(H::notEmpty([]));
    }

    public function testNotEmptyWithNull(): void
    {
        $this->assertFalse(H::notEmpty(null));
    }

    public function testNotEmptyWithString(): void
    {
        // notEmpty only checks arrays, strings are always false
        $this->assertFalse(H::notEmpty('hello'));
    }

    public function testNotEmptyWithAssociativeArray(): void
    {
        $this->assertTrue(H::notEmpty(['key' => 'value']));
    }

    // ========== has() tests ==========

    public function testHasWithExistingKey(): void
    {
        $this->assertTrue(H::has(['key' => 'value'], 'key'));
    }

    public function testHasWithMissingKey(): void
    {
        $this->assertFalse(H::has(['other' => 'value'], 'key'));
    }

    public function testHasWithEmptyValue(): void
    {
        // Empty values are considered "not having" the key
        $this->assertFalse(H::has(['key' => ''], 'key'));
        $this->assertFalse(H::has(['key' => null], 'key'));
    }

    public function testHasWithNonArray(): void
    {
        $this->assertFalse(H::has('string', 'key'));
    }

    // ========== num() tests ==========

    public function testNumWithInteger(): void
    {
        $this->assertEquals(42.0, H::num(['key' => 42], 'key'));
    }

    public function testNumWithFloat(): void
    {
        $this->assertEquals(3.14, H::num(['key' => 3.14], 'key'));
    }

    public function testNumWithNumericString(): void
    {
        $this->assertEquals(42.0, H::num(['key' => '42'], 'key'));
    }

    public function testNumWithMissingKey(): void
    {
        $this->assertEquals(0.0, H::num(['other' => 42], 'key'));
    }

    public function testNumWithDefault(): void
    {
        $this->assertEquals(99.0, H::num(['other' => 42], 'key', 99.0));
    }

    // ========== numOrNull() tests ==========

    public function testNumOrNullWithNumber(): void
    {
        $this->assertEquals(42.0, H::numOrNull(['key' => 42], 'key'));
    }

    public function testNumOrNullWithMissingKey(): void
    {
        $this->assertNull(H::numOrNull(['other' => 42], 'key'));
    }

    public function testNumOrNullWithEmptyString(): void
    {
        $this->assertNull(H::numOrNull(['key' => ''], 'key'));
    }

    // ========== firstChar() tests ==========

    public function testFirstCharWithString(): void
    {
        $this->assertEquals('H', H::firstChar('hello'));
    }

    public function testFirstCharWithLowercase(): void
    {
        $this->assertEquals('A', H::firstChar('abc'));
    }

    public function testFirstCharWithKey(): void
    {
        $this->assertEquals('T', H::firstChar(['name' => 'test'], 'name'));
    }

    public function testFirstCharWithEmptyString(): void
    {
        $this->assertEquals('', H::firstChar(''));
    }

    // ========== valueOrKey() tests ==========

    public function testValueOrKeyWithDirectString(): void
    {
        $this->assertEquals('direct', H::valueOrKey('direct', 'key'));
    }

    public function testValueOrKeyWithArrayKey(): void
    {
        $this->assertEquals('value', H::valueOrKey(['key' => 'value'], 'key'));
    }

    public function testValueOrKeyWithMissingKey(): void
    {
        // Falls back to the array itself (which isn't a string)
        $this->assertEquals('', H::valueOrKey(['other' => 'value'], 'key'));
    }

    public function testValueOrKeyWithNumericValue(): void
    {
        $this->assertEquals('42', H::valueOrKey(['key' => 42], 'key'));
    }
}
