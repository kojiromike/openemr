<?php

/**
 * Interface for HTTP-aware exceptions
 *
 * Exceptions implementing this interface carry HTTP status codes and headers,
 * allowing error handlers to generate appropriate HTTP responses.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Common\Http\Exception;

interface HttpExceptionInterface extends \Throwable
{
    /**
     * Returns the HTTP status code for this exception
     */
    public function getStatusCode(): int;

    /**
     * Returns response headers for this exception
     *
     * @return array<string, string|string[]>
     */
    public function getHeaders(): array;
}
