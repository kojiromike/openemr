<?php

declare(strict_types=1);

/**
 * Handles the generation and retrieval of registration codes for telehealth users and patients.
 *
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2022 Comlink Inc <https://comlinkinc.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Comlink\OpenEMR\Modules\TeleHealthModule\Services;

use Comlink\OpenEMR\Modules\TeleHealthModule\TelehealthGlobalConfig;
use Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthUserRepository;
use OpenEMR\Common\Utils\RandomGenUtils;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Services\PatientService;

class TelehealthRegistrationCodeService
{
    private \Comlink\OpenEMR\Modules\TeleHealthModule\TelehealthGlobalConfig $telehealthGlobalConfig;

    private \Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthUserRepository $teleHealthUserRepository;

    public function __construct(TelehealthGlobalConfig $telehealthGlobalConfig, TeleHealthUserRepository $teleHealthUserRepository)
    {
        $this->telehealthGlobalConfig = $telehealthGlobalConfig;
        $this->teleHealthUserRepository = $teleHealthUserRepository;
    }

    public function getRegistrationCodeForPatient($pid)
    {
        $registrationCode = null;
        // first we check to see if the table already has a registration code
        // then we populate it in the session

        $user = $this->getTelehealthUserForPid($pid);
        if (!empty($user)) {
            $registrationCode = $user->getRegistrationCode();
        }

        return $registrationCode;
    }

    public function generateRegistrationCode()
    {
        // generate a unique character string that will identify the patient with this installation
        return RandomGenUtils::createUniqueToken($this->telehealthGlobalConfig->getAppRegistrationCodeLength());
    }

    private function getTelehealthUserForPid($pid)
    {
        $patientService = new PatientService();
        $patient = $patientService->findByPid($pid);
        $the = UuidRegistry::uuidToString($patient['uuid']);
        return $this->teleHealthUserRepository->getUser($the);
    }
}
