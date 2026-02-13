<?php

/**
 * Exception for HTTP 429 Too Many Requests
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Exception\Http;

use OpenEMR\Common\Http\StatusCode;

class TooManyRequestsHttpException extends HttpException
{
    /**
     * @param int|null $retryAfter Seconds until the client should retry
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        ?int $retryAfter = null,
        string $message = '',
        ?\Throwable $previous = null,
        array $headers = []
    ) {
        if ($retryAfter !== null) {
            $headers['Retry-After'] = (string) $retryAfter;
        }
        parent::__construct(StatusCode::TOO_MANY_REQUESTS, $message, $previous, $headers);
    }
}
