<?php

/**
 * Base exception for improperly configured OpenEMR modules
 *
 * This class is abstract to enforce that modules define their own
 * namespaced exception rather than throwing this base class directly.
 * This enables catch blocks to distinguish between modules:
 *
 *   catch (FaxSMS\Exception\ImproperlyConfiguredException $e)     // one module
 *   catch (ImproperlyConfiguredModuleException $e)                // any module
 *   catch (ImproperlyConfiguredException $e)                      // core + modules
 *
 * Each module should define its own exception:
 *
 *   namespace OpenEMR\Modules\FaxSMS\Exception;
 *   class ImproperlyConfiguredException extends \OpenEMR\Exception\ImproperlyConfiguredModuleException {}
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Exception;

abstract class ImproperlyConfiguredModuleException extends ImproperlyConfiguredException
{
}
