<?php

declare(strict_types=1);

use OpenEMR\Core\AbstractModuleActionListener;
use OpenEMR\Modules\FaxSMS\BootstrapService;

/**
 * Class to be called from Laminas Module Manager for reporting management actions.
 * Example is if the module is enabled, disabled or unregistered ect.
 *
 * The class is in the Laminas "Installer\Controller" namespace.
 * Currently, register isn't supported of which support should be a part of install.
 * If an error needs to be reported to user, return description of error.
 * However, whatever action trapped here has already occurred in Manager.
 * Catch any exceptions because chances are they will be overlooked in Laminas module.
 * Report them in the return value.
 *
 * @package   OpenEMR Modules
 * @link      https://www.open-emr.org
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2024 Jerry Padgett <sjpadgett@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
/*
 * Do not declare a namespace
 * If you want Laminas manager to set namespace set it in getModuleNamespace
 * otherwise uncomment below and set path.
 *
 * */

/*
    $classLoader = new \OpenEMR\Core\ModulesClassLoader($GLOBALS['fileroot']);
    $classLoader->registerNamespaceIfNotExists("OpenEMR\\Modules\\FaxSMS\\", __DIR__ . DIRECTORY_SEPARATOR . 'src');
*/

class ModuleManagerListener extends AbstractModuleActionListener
{
    /**
     * @var \OpenEMR\Modules\FaxSMS\BootstrapService
     */
    public $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new BootstrapService();
    }

    /**
     * @param        $methodName
     * @param        $modId
     * @return string On method success a $currentAction status should be returned or error string.
     */
    public function moduleManagerAction($methodName, $modId, string $currentActionStatus = 'Success'): string
    {
        if (method_exists(self::class, $methodName)) {
            return self::$methodName($modId, $currentActionStatus);
        } else {
            return sprintf('Module cleanup method %s does not exist.', $methodName);
        }
    }

    /**
     * Required method to return namespace
     * If namespace isn't provided return an empty
     * and register namespace using example at top of this script.
     */
    protected static function getModuleNamespace(): string
    {
        return 'OpenEMR\\Modules\\FaxSMS\\';
    }

    /**
     * Required method to return this class object,
     * so it is instantiated in Laminas Manager.
     */
    protected static function initListenerSelf(): ModuleManagerListener
    {
        return new self();
    }

    /**
     * @param $modId
     * @param $currentActionStatus
     */
    private function install($currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    /**
     * @param $modId
     * @param $currentActionStatus
     */
    private function enable($currentActionStatus): mixed
    {
        if (empty($this->service)) {
            $this->service = new BootstrapService();
        }

        $globals = $this->service->fetchPersistedSetupSettings() ?? '';
        if (empty($globals)) {
            $globals = $this->service->getVendorGlobals();
        }

        $this->service->saveModuleListenerGlobals($globals);

        return $currentActionStatus;
    }

    /**
     * @param $modId
     * @param $currentActionStatus
     * @return mixed
     */
    private function disable($currentActionStatus)
    {
        if (empty($this->service)) {
            $this->service = new BootstrapService();
        }

        // fetch current.
        $globals = $this->service->getVendorGlobals();
        // persist current for enable action.
        $this->service->persistSetupSettings($globals);
        foreach ($globals as $k => $v) {
            if ($k == 'oefax_enable_sms' || $k == 'oefax_enable_fax') {
                // force disable of services
                $globals[$k] = 0;
            }
        }

        // save new disabled settings.
        $this->service->saveModuleListenerGlobals($globals);
        return $currentActionStatus;
    }

    /**
     * @param $modId
     * @param $currentActionStatus
     */
    private function unregister($currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    /**
     * @param $modId
     * @param $currentActionStatus
     */
    private function install_sql($currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    /**
     * @param $modId
     * @param $currentActionStatus
     */
    private function upgrade_sql($currentActionStatus): mixed
    {
        return $currentActionStatus;
    }
}
