<?php

/**
 * Translate.php - Translation helper functions
 *
 * PHP port of oe-blue-button-generate/lib/translate.js
 * Provides code translation and formatting utilities.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2026 Jerry Padgett <sjpadgett@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core;

class Translate
{
    /**
     * Value sets for codeFromName translations
     *
     * @var array<string, array<string, array{code: string, codeSystem: string, codeSystemName: string, displayName?: string}>>
     */
    private static array $valueSets = [
        // Problem Status
        '2.16.840.1.113883.3.88.12.80.68' => [
            'Active' => ['code' => '55561003', 'codeSystem' => '2.16.840.1.113883.6.96', 'codeSystemName' => 'SNOMED CT'],
            'Inactive' => ['code' => '73425007', 'codeSystem' => '2.16.840.1.113883.6.96', 'codeSystemName' => 'SNOMED CT'],
            'Resolved' => ['code' => '413322009', 'codeSystem' => '2.16.840.1.113883.6.96', 'codeSystemName' => 'SNOMED CT'],
        ],
        // Smoking Status
        '2.16.840.1.113883.11.20.9.38' => [
            'Current every day smoker' => ['code' => '449868002', 'codeSystem' => '2.16.840.1.113883.6.96', 'codeSystemName' => 'SNOMED CT'],
            'Current some day smoker' => ['code' => '428041000124106', 'codeSystem' => '2.16.840.1.113883.6.96', 'codeSystemName' => 'SNOMED CT'],
            'Former smoker' => ['code' => '8517006', 'codeSystem' => '2.16.840.1.113883.6.96', 'codeSystemName' => 'SNOMED CT'],
            'Never smoker' => ['code' => '266919005', 'codeSystem' => '2.16.840.1.113883.6.96', 'codeSystemName' => 'SNOMED CT'],
            'Smoker, current status unknown' => ['code' => '77176002', 'codeSystem' => '2.16.840.1.113883.6.96', 'codeSystemName' => 'SNOMED CT'],
            'Unknown if ever smoked' => ['code' => '266927001', 'codeSystem' => '2.16.840.1.113883.6.96', 'codeSystemName' => 'SNOMED CT'],
            'Heavy tobacco smoker' => ['code' => '428071000124103', 'codeSystem' => '2.16.840.1.113883.6.96', 'codeSystemName' => 'SNOMED CT'],
            'Light tobacco smoker' => ['code' => '428061000124105', 'codeSystem' => '2.16.840.1.113883.6.96', 'codeSystemName' => 'SNOMED CT'],
        ],
        // Administrative Gender
        '2.16.840.1.113883.5.1' => [
            'Male' => ['code' => 'M', 'codeSystem' => '2.16.840.1.113883.5.1', 'codeSystemName' => 'AdministrativeGender'],
            'Female' => ['code' => 'F', 'codeSystem' => '2.16.840.1.113883.5.1', 'codeSystemName' => 'AdministrativeGender'],
            'Undifferentiated' => ['code' => 'UN', 'codeSystem' => '2.16.840.1.113883.5.1', 'codeSystemName' => 'AdministrativeGender'],
        ],
        // Age Unit
        '2.16.840.1.113883.11.20.9.21' => [
            'min' => ['code' => 'min', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'h' => ['code' => 'h', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'd' => ['code' => 'd', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'wk' => ['code' => 'wk', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'mo' => ['code' => 'mo', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'a' => ['code' => 'a', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'year' => ['code' => 'a', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'years' => ['code' => 'a', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'month' => ['code' => 'mo', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'months' => ['code' => 'mo', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'week' => ['code' => 'wk', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'weeks' => ['code' => 'wk', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'day' => ['code' => 'd', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'days' => ['code' => 'd', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'hour' => ['code' => 'h', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'hours' => ['code' => 'h', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'minute' => ['code' => 'min', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
            'minutes' => ['code' => 'min', 'codeSystem' => '2.16.840.1.113883.6.8', 'codeSystemName' => 'UCUM'],
        ],
        // Observation Interpretation
        '2.16.840.1.113883.5.83' => [
            'H' => ['code' => 'H', 'codeSystem' => '2.16.840.1.113883.5.83', 'codeSystemName' => 'HL7 Result Interpretation', 'displayName' => 'High'],
            'L' => ['code' => 'L', 'codeSystem' => '2.16.840.1.113883.5.83', 'codeSystemName' => 'HL7 Result Interpretation', 'displayName' => 'Low'],
            'N' => ['code' => 'N', 'codeSystem' => '2.16.840.1.113883.5.83', 'codeSystemName' => 'HL7 Result Interpretation', 'displayName' => 'Normal'],
            'A' => ['code' => 'A', 'codeSystem' => '2.16.840.1.113883.5.83', 'codeSystemName' => 'HL7 Result Interpretation', 'displayName' => 'Abnormal'],
            'HH' => ['code' => 'HH', 'codeSystem' => '2.16.840.1.113883.5.83', 'codeSystemName' => 'HL7 Result Interpretation', 'displayName' => 'Critical High'],
            'LL' => ['code' => 'LL', 'codeSystem' => '2.16.840.1.113883.5.83', 'codeSystemName' => 'HL7 Result Interpretation', 'displayName' => 'Critical Low'],
        ],
        // Act Reason (for immunization refusal)
        '2.16.840.1.113883.5.8' => [
            'IMMUNE' => ['code' => 'IMMUNE', 'codeSystem' => '2.16.840.1.113883.5.8', 'codeSystemName' => 'ActReason'],
            'MEDPREC' => ['code' => 'MEDPREC', 'codeSystem' => '2.16.840.1.113883.5.8', 'codeSystemName' => 'ActReason'],
            'OSTOCK' => ['code' => 'OSTOCK', 'codeSystem' => '2.16.840.1.113883.5.8', 'codeSystemName' => 'ActReason'],
            'PATOBJ' => ['code' => 'PATOBJ', 'codeSystem' => '2.16.840.1.113883.5.8', 'codeSystemName' => 'ActReason'],
            'PHILISOP' => ['code' => 'PHILISOP', 'codeSystem' => '2.16.840.1.113883.5.8', 'codeSystemName' => 'ActReason'],
            'RELIG' => ['code' => 'RELIG', 'codeSystem' => '2.16.840.1.113883.5.8', 'codeSystemName' => 'ActReason'],
            'VACEFF' => ['code' => 'VACEFF', 'codeSystem' => '2.16.840.1.113883.5.8', 'codeSystemName' => 'ActReason'],
            'VACSAF' => ['code' => 'VACSAF', 'codeSystem' => '2.16.840.1.113883.5.8', 'codeSystemName' => 'ActReason'],
        ],
    ];

    /**
     * Translate code from name using value set
     *
     * @param array<int|string, mixed>|string|null $input
     * @return array<string, string|null>
     */
    public static function codeFromName(string $oid, array|string|null $input): array
    {
        if (in_array($input, [null, '', []], true)) {
            return [];
        }

        $name = is_string($input)
            ? $input
            : self::extractString($input, 'name', self::extractString($input, 'value', ''));

        if (isset(self::$valueSets[$oid][$name])) {
            $result = self::$valueSets[$oid][$name];
            // Use displayName from value set if available, otherwise use the lookup key
            if (!isset($result['displayName'])) {
                $result['displayName'] = $name;
            }
            return $result;
        }

        // Return input as-is if not found in value set
        if (is_array($input)) {
            return [
                'code' => self::extractStringOrNull($input, 'code'),
                'codeSystem' => self::extractString($input, 'code_system', $oid),
                'codeSystemName' => self::extractStringOrNull($input, 'code_system_name'),
                'displayName' => self::extractStringOrNull($input, 'name'),
            ];
        }

        return [
            'code' => $name,
            'codeSystem' => $oid,
            'displayName' => $name,
        ];
    }

    /**
     * Extract a string value from an array
     *
     * @param array<int|string, mixed> $arr
     */
    private static function extractString(array $arr, string $key, string $default = ''): string
    {
        $val = $arr[$key] ?? null;
        if (is_string($val)) {
            return $val;
        }
        if (is_int($val) || is_float($val)) {
            return (string) $val;
        }
        return $default;
    }

    /**
     * Extract a nullable string value from an array
     *
     * @param array<int|string, mixed> $arr
     */
    private static function extractStringOrNull(array $arr, string $key): ?string
    {
        $val = $arr[$key] ?? null;
        if ($val === null || $val === '') {
            return null;
        }
        if (is_string($val)) {
            return $val;
        }
        if (is_int($val) || is_float($val)) {
            return (string) $val;
        }
        return null;
    }

    /**
     * Translate time/date input to HL7 format
     *
     * @param array<int|string, mixed>|string|null $input
     */
    public static function time(array|string|null $input): ?string
    {
        if (in_array($input, [null, '', []], true)) {
            return null;
        }

        // If it's already a properly formatted string
        if (is_string($input)) {
            // Check if it's already in HL7 format (YYYYMMDD or YYYYMMDDHHMMSS)
            if (preg_match('/^\d{8,14}$/', $input)) {
                return $input;
            }

            // Try to parse as date
            $timestamp = strtotime($input);
            if ($timestamp !== false) {
                return date('YmdHis', $timestamp);
            }

            return $input;
        }

        // It's an array with date/precision
        $date = self::extractStringOrNull($input, 'date')
            ?? self::extractStringOrNull($input, 'point');
        $precision = self::extractStringOrNull($input, 'precision');

        if ($date !== null) {
            return self::formatDate($date, $precision);
        }

        return null;
    }

    /**
     * Format date with optional precision
     */
    public static function formatDate(string|int|null $date, ?string $precision = null): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        $timestamp = is_int($date) ? $date : strtotime($date);
        if ($timestamp === false) {
            return is_string($date) ? $date : null;
        }

        return match ($precision) {
            'year' => date('Y', $timestamp),
            'month' => date('Ym', $timestamp),
            'day' => date('Ymd', $timestamp),
            'hour' => date('YmdH', $timestamp),
            'minute' => date('YmdHi', $timestamp),
            default => date('YmdHis', $timestamp),
        };
    }

    /**
     * Acronymize address use
     */
    public static function acronymize(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $map = [
            'home' => 'HP',
            'work' => 'WP',
            'mobile' => 'MC',
            'primary home' => 'HP',
            'vacation home' => 'HV',
            'workplace' => 'WP',
            'public' => 'PUB',
            'bad address' => 'BAD',
            'temporary' => 'TMP',
            'direct' => 'DIR',
            'confidential' => 'CONF',
        ];

        $lower = strtolower(trim($value));
        return $map[$lower] ?? strtoupper($value);
    }

    /**
     * Translate code attributes from input
     *
     * @param array<string, mixed>|null $input
     * @return array<string, string>
     */
    public static function code(?array $input): array
    {
        if ($input === null || $input === []) {
            return [];
        }

        $result = [];
        $code = self::extractStringOrNull($input, 'code');
        if ($code !== null) {
            $result['code'] = $code;
        }
        $codeSystem = self::extractStringOrNull($input, 'code_system');
        if ($codeSystem !== null) {
            $result['codeSystem'] = $codeSystem;
        }
        $codeSystemName = self::extractStringOrNull($input, 'code_system_name');
        if ($codeSystemName !== null) {
            $result['codeSystemName'] = $codeSystemName;
        }
        $name = self::extractStringOrNull($input, 'name');
        if ($name !== null) {
            $result['displayName'] = $name;
        }
        return $result;
    }

    /**
     * Transform name for usRealmName
     *
     * @param array<string, mixed>|string|null $input
     * @return array<string, mixed>|null
     */
    public static function name(array|string|null $input): ?array
    {
        if (in_array($input, [null, '', []], true)) {
            return null;
        }

        // If it's a simple string
        if (is_string($input)) {
            $parts = explode(' ', $input);
            return [
                'given' => [array_shift($parts)],
                'family' => implode(' ', $parts) ?: null,
            ];
        }

        // If already in correct format
        if (isset($input['family']) || isset($input['given'])) {
            return $input;
        }

        // Try to extract from common formats
        $result = [];

        $family = self::extractStringOrNull($input, 'last')
            ?? self::extractStringOrNull($input, 'last_name');
        if ($family !== null) {
            $result['family'] = $family;
        }

        $first = self::extractStringOrNull($input, 'first')
            ?? self::extractStringOrNull($input, 'first_name');
        if ($first !== null) {
            $given = [$first];
            $middle = self::extractStringOrNull($input, 'middle')
                ?? self::extractStringOrNull($input, 'middle_name');
            if ($middle !== null) {
                $given[] = $middle;
            }
            $result['given'] = $given;
        }

        $prefix = self::extractStringOrNull($input, 'prefix');
        if ($prefix !== null) {
            $result['prefix'] = $prefix;
        }

        $suffix = self::extractStringOrNull($input, 'suffix');
        if ($suffix !== null) {
            $result['suffix'] = $suffix;
        }

        return $result !== [] ? $result : $input;
    }

    /**
     * Transform telecom
     *
     * @param array<string, mixed>|string|null $input
     * @return array<string, string>|null
     */
    public static function telecom(array|string|null $input): ?array
    {
        if (in_array($input, [null, '', []], true)) {
            return null;
        }

        if (is_string($input)) {
            return ['value' => $input];
        }

        $result = [];

        // Handle phone/email/fax
        if (isset($input['phone']) || isset($input['number'])) {
            $number = self::flattenToString($input['phone'] ?? $input['number']);
            if ($number !== '' && !str_starts_with($number, 'tel:')) {
                $number = 'tel:' . preg_replace('/[^0-9+]/', '', $number);
            }
            $result['value'] = $number;
            $result['use'] = self::extractString($input, 'use', 'WP');
        } elseif (isset($input['email'])) {
            $email = self::flattenToString($input['email']);
            if ($email !== '' && !str_starts_with($email, 'mailto:')) {
                $email = 'mailto:' . $email;
            }
            $result['value'] = $email;
        } elseif (isset($input['value'])) {
            $result['value'] = self::flattenToString($input['value']);
            $use = self::extractStringOrNull($input, 'use');
            if ($use !== null) {
                $result['use'] = $use;
            }
        }

        return $result !== [] ? $result : null;
    }

    /**
     * Flatten a potentially nested value to a string
     */
    private static function flattenToString(mixed $value): string
    {
        while (is_array($value)) {
            $value = $value[0] ?? '';
        }
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        return '';
    }
}
