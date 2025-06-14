<?php

declare(strict_types=1);

namespace Comlink\OpenEMR\Modules\TeleHealthModule\Services;

use Comlink\OpenEMR\Modules\TeleHealthModule\Exception\TelehealthProviderNotEnrolledException;
use Comlink\OpenEMR\Modules\TeleHealthModule\Exception\TeleHealthProviderSuspendedException;
use Comlink\OpenEMR\Modules\TeleHealthModule\Exception\TelehealthProvisioningServiceRequestException;
use Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthProviderRepository;
use Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthUserRepository;

class TeleHealthProvisioningService
{
    private \Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthUserRepository $teleHealthUserRepository;

    private \Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthProviderRepository $teleHealthProviderRepository;

    private \Comlink\OpenEMR\Modules\TeleHealthModule\Services\TeleHealthRemoteRegistrationService $teleHealthRemoteRegistrationService;


    public function __construct(TeleHealthUserRepository $teleHealthUserRepository, TeleHealthProviderRepository $teleHealthProviderRepository, TeleHealthRemoteRegistrationService $teleHealthRemoteRegistrationService)
    {
        $this->teleHealthUserRepository = $teleHealthUserRepository;
        $this->teleHealthProviderRepository = $teleHealthProviderRepository;
        $this->teleHealthRemoteRegistrationService = $teleHealthRemoteRegistrationService;
    }

    public function getRemoteRegistrationService(): TeleHealthRemoteRegistrationService
    {
        return $this->teleHealthRemoteRegistrationService;
    }

    /**
     * @param $user - a user as returned from UserService
     * @return \Comlink\OpenEMR\Modules\TeleHealthModule\Models\TeleHealthUser|null
     * @throws TelehealthProvisioningServiceRequestException
     */
    public function getOrCreateTelehealthProvider(array $user)
    {
        $providerTelehealthSettings = $this->teleHealthUserRepository->getUser($user['uuid']);
        if (empty($providerTelehealthSettings)) {
            if ($this->teleHealthProviderRepository->isEnabledProvider($user['id'])) {
                if ($this->teleHealthRemoteRegistrationService->createUserRegistration($user)) {
                    $providerTelehealthSettings = $this->teleHealthUserRepository->getUser($user['uuid']);
                } else {
                    throw new TelehealthProvisioningServiceRequestException("Could not create telehealth registration for user " . $user['uuid']);
                }
            } else {
                // we should never hit this situation as we are supposed to prevent launching of appointments on the client side of things.
                throw new TelehealthProviderNotEnrolledException("Provider is either suspended or not enrolled in telehealth. Cannot create telehealth registration for user " . $user['uuid']);
            }
        } elseif (!$providerTelehealthSettings->getIsActive()) {
            // provider is disabled... can't launch settings with this provider
            throw new TeleHealthProviderSuspendedException("Provider's telehealth subscription is suspended for user " . $user['uuid']);
        }

        return $providerTelehealthSettings;
    }

    /**
     * @param $patient
     * @return \Comlink\OpenEMR\Modules\TeleHealthModule\Models\TeleHealthUser|null
     * @throws TelehealthProvisioningServiceRequestException
     */
    public function getOrCreateTelehealthPatient(array $patient)
    {
        $telehealthSettings = $this->teleHealthUserRepository->getUser($patient['uuid']);
        if (empty($telehealthSettings)) {
            if ($this->teleHealthRemoteRegistrationService->createPatientRegistration($patient)) {
                $telehealthSettings = $this->teleHealthUserRepository->getUser($patient['uuid']);
            } else {
                throw new TelehealthProvisioningServiceRequestException("Could not create video registration for patient " . $patient['uuid']);
            }
        }

        return $telehealthSettings;
    }
}
