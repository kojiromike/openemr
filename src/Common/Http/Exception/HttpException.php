<?php

/**
 * Base exception for HTTP errors
 *
 * Carries an HTTP status code and optional headers. Subclasses provide
 * semantic meaning for specific status codes (NotFoundHttpException, etc.).
 *
 * Inspired by Symfony's HttpKernel HttpException.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Common\Http\Exception;

use OpenEMR\Common\Http\StatusCode;

class HttpException extends \RuntimeException implements HttpExceptionInterface
{
    /**
     * @param int $statusCode HTTP status code
     * @param string $message Error message
     * @param \Throwable|null $previous Previous exception for chaining
     * @param array<string, string|string[]> $headers Response headers
     */
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        ?\Throwable $previous = null,
        private array $headers = []
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * Create an HttpException from a status code
     *
     * Returns a specific subclass when available, otherwise a generic HttpException.
     *
     * @param array<string, string|string[]> $headers
     */
    public static function fromStatusCode(
        int $statusCode,
        string $message = '',
        ?\Throwable $previous = null,
        array $headers = []
    ): self {
        return match ($statusCode) {
            StatusCode::BAD_REQUEST => new BadRequestHttpException($message, $previous, $headers),
            StatusCode::UNAUTHORIZED => new UnauthorizedHttpException($message, $previous, $headers),
            StatusCode::NOT_FOUND => new NotFoundHttpException($message, $previous, $headers),
            StatusCode::TOO_MANY_REQUESTS => new TooManyRequestsHttpException(null, $message, $previous, $headers),
            StatusCode::INTERNAL_SERVER_ERROR => new InternalServerErrorHttpException($message, $previous, $headers),
            StatusCode::NOT_IMPLEMENTED => new NotImplementedHttpException($message, $previous, $headers),
            default => new self($statusCode, $message, $previous, $headers),
        };
    }
}
