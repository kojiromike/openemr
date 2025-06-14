<?php

declare(strict_types=1);

namespace Comlink\OpenEMR\Modules\TeleHealthModule\Services;

use Comlink\OpenEMR\Modules\TeleHealthModule\Exception\TelehealthProvisioningServiceRequestException;
use Comlink\OpenEMR\Modules\TeleHealthModule\Models\TeleHealthUser;
use Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthUserRepository;
use Comlink\OpenEMR\Modules\TeleHealthModule\TelehealthGlobalConfig;
use Comlink\OpenEMR\Modules\TeleHealthModule\Util\TelehealthAuthUtils;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use OpenEMR\Common\Http\Psr17Factory;
use OpenEMR\Common\Logging\SystemLogger;

class TelehealthConfigurationVerifier
{
    private TeleHealthRemoteRegistrationService $teleHealthRemoteRegistrationService;


    public function __construct(private SystemLogger $systemLogger, private TeleHealthProvisioningService $teleHealthProvisioningService, private TeleHealthUserRepository $teleHealthUserRepository, private TelehealthGlobalConfig $telehealthGlobalConfig)
    {
        $this->teleHealthRemoteRegistrationService = $this->teleHealthProvisioningService->getRemoteRegistrationService();
    }

    public function verifyInstallationSettings($user): void
    {
        // using a json object so we can add additional checks / messages later
        $resultObject = [
            'status' => 'error'
            ,'message' => xlt('Could not verify settings')
        ];

        $config = $this->telehealthGlobalConfig;
        if (!$config->isTelehealthCoreSettingsConfigured()) { // no settings saved, means we can't continue.
            $resultObject['message'] = xlt('Telehealth settings must be saved to verify configuration');
        } elseif (!$config->isTelehealthConfigured()) {
            $resultObject['message'] = xlt('Telehealth settings portal is not setup for patient session invitations');
        } else {
            try {
                // for existing users we need to make sure we can issue operations on the user so we are going to
                // suspend and activate the user to make sure we can do this.
                // TODO: @adunsulag if comlink provides a better mechanism for api health checks we can use that instead
                if ($this->isProvisionedUser($user)) {
                    $provider = $this->suspendAndActivateUser($user);
                } else {
                    // provision the user through the normal process
                    $provider = $this->teleHealthProvisioningService->getOrCreateTelehealthProvider($user);
                }

                $bridgeSettings = $this->getVerifyBridgeSettings($provider);
                http_response_code(200);
                $resultObject['message'] = xlt('Settings verified');
                $resultObject['status'] = 'success';
                $resultObject['bridgeSettings'] = $bridgeSettings;
            } catch (\Exception $exception) {
                $this->systemLogger->errorLogCaller(
                    "Failed to verify telehealth connection settings" . $exception->getMessage(),
                    ['trace' => $exception->getTraceAsString()]
                );
                $resultObject["message"] = xlt("Could not successfully communicate with telehealth servers.  Check that your Telehealth configuration settings are valid.");
            }
        }

        echo json_encode($resultObject);
    }

    private function isProvisionedUser(array $user): bool
    {
        $providerTelehealthSettings = $this->teleHealthUserRepository->getUser($user['uuid']);
        return !empty($providerTelehealthSettings);
    }

    private function suspendAndActivateUser(array $user)
    {
        $providerTelehealthSettings = $this->teleHealthUserRepository->getUser($user['uuid']);
        $didSuspend = $this->teleHealthRemoteRegistrationService->suspendUser($providerTelehealthSettings->getUsername(), $providerTelehealthSettings->getAuthToken());
        if ($didSuspend) {
            $didActivate = $this->teleHealthRemoteRegistrationService->resumeUser($providerTelehealthSettings->getUsername(), $providerTelehealthSettings->getAuthToken());
            if ($didActivate) {
                return $providerTelehealthSettings;
            } else {
                throw new TelehealthProvisioningServiceRequestException("Could not activate user " . $user['id'] . ' with username ' . $user['username']);
            }
        } else {
            throw new TelehealthProvisioningServiceRequestException("Could not suspend user " . $user['id'] . ' with username ' . $user['username']);
        }
    }

    private function getVerifyBridgeSettings(TeleHealthUser $teleHealthUser): array
    {
        $password = $this->teleHealthUserRepository->decryptPassword($teleHealthUser->getAuthToken());
        $hashedPassword = TelehealthAuthUtils::getFormattedPassword($password);
        return ["userId" => $teleHealthUser->getUsername(), "passwordHash" => $hashedPassword
            , "type" => "normal"
            , 'telehealthApiUrl' => $this->telehealthGlobalConfig->getTelehealthAPIURI()
            , 'successMessage' => xlj("Successfully verified settings")
            , 'errorMessage' => xlj("Telehealth Video API URI is invalid")
        ];
    }
}
