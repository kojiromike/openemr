<?php

/**
 * Exception for improperly configured OpenEMR core
 *
 * Throw when required configuration (environment variables, globals, etc.)
 * is missing or invalid. Inspired by Django's ImproperlyConfigured.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Exception;

class ImproperlyConfiguredException extends \RuntimeException
{
}
