<?php

declare(strict_types=1);

/**
 * Handles the mapping and retrieving of telehealth providers in the OpenEMR system.
 *
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2022 Comlink Inc <https://comlinkinc.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Comlink\OpenEMR\Modules\TeleHealthModule\Repository;

use Comlink\OpenEMR\Modules\TeleHealthModule\Models\TeleHealthPersonSettings;
use Comlink\OpenEMR\Modules\TeleHealthModule\TelehealthGlobalConfig;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Services\UserService;
use OpenEMR\Validators\ProcessingResult;

class TeleHealthProviderRepository
{
    /**
     * @var \Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthPersonSettingsRepository
     */
    public $personSettings;
    /**
     * @var \Comlink\OpenEMR\Modules\TeleHealthModule\TelehealthGlobalConfig
     */
    public $config;
    public function __construct(SystemLogger $systemLogger, TelehealthGlobalConfig $telehealthGlobalConfig)
    {
        $this->personSettings = new TeleHealthPersonSettingsRepository($systemLogger);
        $this->config = $telehealthGlobalConfig;
    }

    public function isEnabledProvider($providerId)
    {
        if ($this->config->shouldAutoProvisionProviders()) {
            return true;
        }

        $setting = $this->personSettings->getSettingsForUser($providerId);
        if (!empty($setting)) {
            return $setting->getIsEnabled();
        }

        return false;
    }

    public function getEnabledProviders()
    {
        $providers =  [];
        // if we auto provision we need to grab our entire provider array
        if ($this->config->shouldAutoProvisionProviders()) {
            // grab all the providers and return them as enabled settings
            $userService = new UserService();
            $facility = $_SESSION['pc_facility'] ?? "";
            $dataArray = $userService->getUsersForCalendar($facility);
            if (empty($dataArray)) { // if our facility came back with nothing we will try to hit the current logged in user
                $userService->getUsersForCalendar($_SESSION['authUserID']);
            }

            if (!empty($dataArray)) {
                $providers = array_map(function ($provider) {
                    return $this->mapProviderToPersonSetting($provider);
                }, $dataArray);
            }
        } else {
            // just grab all of our enabled users
            $providers = $this->personSettings->getEnabledUsers();
        }

        return $providers;
    }

    private function mapProviderToPersonSetting(array $provider): \Comlink\OpenEMR\Modules\TeleHealthModule\Models\TeleHealthPersonSettings
    {
        $teleHealthPersonSettings = new TeleHealthPersonSettings();
        $teleHealthPersonSettings->setIsPatient(false);
        $teleHealthPersonSettings->setDbRecordId($provider['id']);
        $teleHealthPersonSettings->setIsEnabled(true);
        return $teleHealthPersonSettings;
    }
}
