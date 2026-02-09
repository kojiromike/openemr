<?php

/**
 * TemplateHelpers.php - Type-safe helper methods for template closures
 *
 * These helpers extract values from mixed data with proper type checking,
 * allowing template closures to satisfy PHPStan without verbose inline guards.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core;

/**
 * Type-safe helpers for extracting values from mixed template data
 *
 * Usage in templates:
 *   use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core\TemplateHelpers as H;
 *   'text' => fn($input) => H::str($input, 'name'),
 */
class TemplateHelpers
{
    /**
     * Extract a string value from mixed data
     *
     * @param array-key $key
     */
    public static function str(mixed $input, string|int $key, string $default = ''): string
    {
        if (!is_array($input)) {
            return $default;
        }
        $val = $input[$key] ?? null;
        if (is_string($val)) {
            return $val;
        }
        if (is_numeric($val)) {
            return (string) $val;
        }
        return $default;
    }

    /**
     * Extract a nullable string value from mixed data
     *
     * @param array-key $key
     */
    public static function strOrNull(mixed $input, string|int $key): ?string
    {
        if (!is_array($input)) {
            return null;
        }
        $val = $input[$key] ?? null;
        if ($val === null || $val === '') {
            return null;
        }
        if (is_string($val)) {
            return $val;
        }
        if (is_numeric($val)) {
            return (string) $val;
        }
        return null;
    }

    /**
     * Extract an array value from mixed data
     *
     * @param array-key $key
     * @return array<array-key, mixed>
     */
    public static function arr(mixed $input, string|int $key): array
    {
        if (!is_array($input)) {
            return [];
        }
        $val = $input[$key] ?? null;
        return is_array($val) ? $val : [];
    }

    /**
     * Extract a nested string value using dot notation
     *
     * @param string $path Dot-separated path (e.g., 'point.date')
     */
    public static function nested(mixed $input, string $path, string $default = ''): string
    {
        if (!is_array($input)) {
            return $default;
        }

        $keys = explode('.', $path);
        $current = $input;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return $default;
            }
            $current = $current[$key];
        }

        if (is_string($current)) {
            return $current;
        }
        if (is_numeric($current)) {
            return (string) $current;
        }
        return $default;
    }

    /**
     * Extract a nullable nested string value
     *
     * @param string $path Dot-separated path (e.g., 'point.date')
     */
    public static function nestedOrNull(mixed $input, string $path): ?string
    {
        $result = self::nested($input, $path, "\0");
        return $result === "\0" ? null : $result;
    }

    /**
     * Check if input is a non-empty array with a given key
     *
     * @param array-key $key
     */
    public static function has(mixed $input, string|int $key): bool
    {
        return is_array($input) && !empty($input[$key]);
    }

    /**
     * Check if input is a non-empty array
     */
    public static function notEmpty(mixed $input): bool
    {
        return is_array($input) && !empty($input);
    }

    /**
     * Extract the first character of a string value (uppercase)
     *
     * @param array-key|null $key If null, treat $input as the string value
     */
    public static function firstChar(mixed $input, string|int|null $key = null): string
    {
        if ($key !== null) {
            $val = is_array($input) ? ($input[$key] ?? '') : '';
        } else {
            $val = $input;
        }

        if (is_string($val) && $val !== '') {
            return strtoupper(substr($val, 0, 1));
        }
        return '';
    }

    /**
     * Extract a float value from mixed data
     *
     * @param array-key $key
     */
    public static function num(mixed $input, string|int $key, float $default = 0.0): float
    {
        if (!is_array($input)) {
            return $default;
        }
        $val = $input[$key] ?? null;
        if (is_numeric($val)) {
            return (float) $val;
        }
        return $default;
    }

    /**
     * Extract a nullable float value from mixed data
     *
     * @param array-key $key
     */
    public static function numOrNull(mixed $input, string|int $key): ?float
    {
        if (!is_array($input)) {
            return null;
        }
        $val = $input[$key] ?? null;
        if ($val === null || $val === '') {
            return null;
        }
        if (is_numeric($val)) {
            return (float) $val;
        }
        return null;
    }

    /**
     * Get value from input, handling both direct string and array with key
     *
     * Useful for template attributes that can receive either a direct value
     * or an array with named keys.
     *
     * @param array-key $key
     */
    public static function valueOrKey(mixed $input, string|int $key): string
    {
        if (is_string($input)) {
            return $input;
        }
        if (is_array($input)) {
            $val = $input[$key] ?? $input;
            if (is_string($val)) {
                return $val;
            }
            if (is_numeric($val)) {
                return (string) $val;
            }
        }
        return '';
    }
}
