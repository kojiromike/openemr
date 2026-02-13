<?php

/**
 * Exception for when a requested resource is not found
 *
 * Use for domain-level "not found" errors (patient, document, appointment, etc.)
 * that aren't HTTP-specific. For HTTP 404 responses, use NotFoundHttpException.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Exception;

class ResourceNotFoundException extends \RuntimeException
{
    /**
     * @param string $resourceType The type of resource (e.g., "Patient", "Document")
     * @param string|int|null $resourceId The identifier that was not found
     * @param string $message Custom message (auto-generated if empty)
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        private readonly string $resourceType = '',
        private readonly string|int|null $resourceId = null,
        string $message = '',
        ?\Throwable $previous = null
    ) {
        if ($message === '' && $resourceType !== '') {
            $message = $resourceId !== null
                ? "{$resourceType} with ID '{$resourceId}' not found"
                : "{$resourceType} not found";
        }
        parent::__construct($message, 0, $previous);
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function getResourceId(): string|int|null
    {
        return $this->resourceId;
    }
}
