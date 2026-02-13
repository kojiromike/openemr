<?php

/**
 * Exception for data validation failures
 *
 * Throw when user-provided or external data fails validation rules.
 * Carries the validation errors for display or logging.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Exception;

class ValidationException extends \InvalidArgumentException
{
    /**
     * @param array<string, string|string[]> $errors Validation errors keyed by field name
     * @param string $message Summary message
     * @param int $code Exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        private readonly array $errors = [],
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if ($message === '' && count($errors) > 0) {
            $message = 'Validation failed';
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, string|string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
