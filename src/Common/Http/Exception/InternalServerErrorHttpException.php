<?php

/**
 * Exception for HTTP 500 Internal Server Error
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Common\Http\Exception;

use OpenEMR\Common\Http\StatusCode;

class InternalServerErrorHttpException extends HttpException
{
    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        string $message = '',
        ?\Throwable $previous = null,
        array $headers = []
    ) {
        parent::__construct(StatusCode::INTERNAL_SERVER_ERROR, $message, $previous, $headers);
    }
}
