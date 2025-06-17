<?php

declare(strict_types=1);

/**
 * Handles the API communication with the Comlink telehealth provisioning service.  Activation, suspension, updating,
 * creation of telehealth services for patients and users are handled here.
 *
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2022 Comlink Inc <https://comlinkinc.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Comlink\OpenEMR\Modules\TeleHealthModule\Services;

use Comlink\OpenEMR\Modules\TeleHealthModule\TelehealthGlobalConfig;
use Comlink\OpenEMR\Modules\TeleHealthModule\Models\TeleHealthUser;
use Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthUserRepository;
use Comlink\OpenEMR\Modules\TeleHealthModule\Models\UserVideoRegistrationRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use OpenEMR\Common\Database\SqlQueryException;
use OpenEMR\Common\Logging\SystemLogger;
use Ramsey\Uuid\Rfc4122\UuidV4;

class TeleHealthRemoteRegistrationService
{
    /**
     * @var \Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthUserRepository
     */
    public $userRepository;
    public $providerRepository;
    private \Comlink\OpenEMR\Modules\TeleHealthModule\Services\TelehealthRegistrationCodeService $telehealthRegistrationCodeService;

    /**
     * API url endpoint to send registration requests to.
     * @var string
     */
    private $apiURL;

    /*
     * UserID for api authentication needed for comlink video service
     * @var string
     */
    private $apiId;

    /*
     * Password for api authentication needed for comlink video service
     * @var string
     */
    private $apiPassword;

    /*
     * CMSID for api authentication needed for comlink video service
     * @var string
     */
    private $apiCMSID;


    /**
     * Client
     */
    private \GuzzleHttp\Client $httpClient;

    /**
     * Unique installation id of the OpenEMR Institution
     * @var string
     */
    private $institutionId;

    /**
     * Name of the OpenEMR institution
     * @var string
     */
    private $institutionName;

    private \OpenEMR\Common\Logging\SystemLogger $systemLogger;

    public function __construct(TelehealthGlobalConfig $telehealthGlobalConfig, TelehealthRegistrationCodeService $telehealthRegistrationCodeService)
    {
        $this->apiURL = $telehealthGlobalConfig->getRegistrationAPIURI();
        $this->apiId = $telehealthGlobalConfig->getRegistrationAPIUserId();
        $this->apiPassword = $telehealthGlobalConfig->getRegistrationAPIPassword();
        $this->apiCMSID = $telehealthGlobalConfig->getRegistrationAPICmsId();
        $this->institutionId = $telehealthGlobalConfig->getInstitutionId();
        $this->institutionName = $telehealthGlobalConfig->getInstitutionName();
        $this->userRepository = new TeleHealthUserRepository();
        $this->httpClient = new Client();
        $this->systemLogger = new SystemLogger();
        $this->telehealthRegistrationCodeService = $telehealthRegistrationCodeService;
    }

    public function createPatientRegistration($patient): bool
    {
        $userVideoRegistrationRequest = new UserVideoRegistrationRequest();
        $userVideoRegistrationRequest->setDbRecordId($patient['id']);
        $userVideoRegistrationRequest->setIsPatient(true);
        $userVideoRegistrationRequest->setUsername($patient['uuid']);
        $userVideoRegistrationRequest->setPassword($this->userRepository->createUniquePassword());
        $userVideoRegistrationRequest->setInstituationId($this->institutionId);
        $userVideoRegistrationRequest->setInstitutionName($this->institutionName);
        $userVideoRegistrationRequest->setFirstName($patient['fname'] ?? null);
        $userVideoRegistrationRequest->setLastName($patient['lname'] ?? null);
        $userVideoRegistrationRequest->setRegistrationCode($this->telehealthRegistrationCodeService->generateRegistrationCode());

        $this->systemLogger->debug("createPatientRegistration called");
        $userId = $this->addNewUser($userVideoRegistrationRequest);
        return !empty($userId);
    }

    public function createUserRegistration($user): bool
    {
        $userVideoRegistrationRequest = new UserVideoRegistrationRequest();
        $userVideoRegistrationRequest->setDbRecordId($user['id']);
        $userVideoRegistrationRequest->setIsPatient(false);
        $userVideoRegistrationRequest->setUsername($user['uuid']);
        $userVideoRegistrationRequest->setPassword($this->userRepository->createUniquePassword());
        $userVideoRegistrationRequest->setInstituationId($this->institutionId);
        $userVideoRegistrationRequest->setInstitutionName($this->institutionName);
        $userVideoRegistrationRequest->setFirstName($user['fname'] ?? null);
        $userVideoRegistrationRequest->setLastName($user['lname'] ?? null);
        $userVideoRegistrationRequest->setRegistrationCode($this->telehealthRegistrationCodeService->generateRegistrationCode());

        $this->systemLogger->debug("createUserRegistration called");
        $userId = $this->addNewUser($userVideoRegistrationRequest);
        return !empty($userId);
    }

    public function getUserRepository(): TeleHealthUserRepository
    {
        return $this->userRepository;
    }

    /**
     * @return Client
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }


    /**
     * Allows the http client used for api requests to be set for testing or extension purposes
     */
    public function setHttpClient(Client $client): void
    {
        $this->httpClient = $client;
    }

    /**
     * Allows the user repository to be set for testing or extension purposes
     */
    public function setTelehealthUserRepository(TeleHealthUserRepository $teleHealthUserRepository): void
    {
        $this->userRepository = $teleHealthUserRepository;
    }

    /**
     * Returns if a registration should be created for the given provider id.  This does not answer whether a registration
     * exists, but whether the user passes the criteria for creating a registration record regardless of whether it exists or not.
     * @param $providerId
     * @return bool
     */
    public function shouldCreateRegistrationForProvider($providerId)
    {
        return $this->providerRepository->isEnabledProvider($providerId);
    }

    /**
     * Provisions a new user with the Comlink video api system
     * @return false|int returns false if the user fails to add, otherwise returns the integer id of the provisioned user
     */
    public function addNewUser(UserVideoRegistrationRequest $userVideoRegistrationRequest)
    {
        if (!$userVideoRegistrationRequest->isValid()) {
            throw new \InvalidArgumentException("request is missing username, password, or institutionId");
        }

        $securePassword = $userVideoRegistrationRequest->getPassword();
        $userVideoRegistrationRequest->setPassword($this->userRepository->decryptPassword($securePassword));
        $httpDataRequest = $userVideoRegistrationRequest->toArray();

        $response = $this->sendAPIRequest($this->getEndpointUrl("userprovision"), $httpDataRequest);

        if ($response['status'] != 200) {
            (new SystemLogger())->errorLogCaller("Failed to provision user", ['username' => $userVideoRegistrationRequest->getUsername()
                , 'response' => $response]);
            return false;
        } else {
            try {
                $teleHealthUser = new TeleHealthUser();
                $teleHealthUser->setIsPatient($userVideoRegistrationRequest->isPatient());
                $teleHealthUser->setDbRecordId($userVideoRegistrationRequest->getDbRecordId());
                $teleHealthUser->setUsername($userVideoRegistrationRequest->getUsername());
                $teleHealthUser->setAuthToken($securePassword);
                $teleHealthUser->setDateRegistered(new \DateTime());
                $teleHealthUser->setIsActive(true);
                $teleHealthUser->setRegistrationCode($userVideoRegistrationRequest->getRegistrationCode());
                $userId = $this->userRepository->saveUser($teleHealthUser);
                $this->systemLogger->debug("Registered user on comlink api ", ['username' => $userVideoRegistrationRequest->getUsername(), 'id' => $userId]);
            } catch (SqlQueryException $exception) {
                $this->systemLogger->errorLogCaller("User registered on comlink api but did not save to database", ['record' => $teleHealthUser]);
                throw $exception;
            }

            return $userId;
        }
    }

    private function getEndpointUrl(string $endpoint): string
    {
        return $this->apiURL . $endpoint;
    }

    public function populateRequestFromUser(TeleHealthUser $teleHealthUser): UserVideoRegistrationRequest
    {
        $userVideoRegistrationRequest = new UserVideoRegistrationRequest();
        $userVideoRegistrationRequest->setRegistrationCode($teleHealthUser->getRegistrationCode())
            ->setUsername($teleHealthUser->getUsername())
            ->setPassword($teleHealthUser->getAuthToken())
            ->setDbRecordId($teleHealthUser->getId())
            ->setIsPatient($teleHealthUser->getIsPatient())
            ->setInstitutionName($this->institutionName)
            ->setInstituationId($this->institutionId);
        return $userVideoRegistrationRequest;
    }

    /**
     * Updates an existing provisioned user with the Comlink video api system.  Everything but username can be changed
     * @return false|int returns false if the user fails to update, otherwise returns the integer id of the updated user
     */
    public function updateUserFromRequest(UserVideoRegistrationRequest $userVideoRegistrationRequest)
    {
        if (!$userVideoRegistrationRequest->isValid()) {
            throw new \InvalidArgumentException("request is missing username, password, or institutionId");
        }

        // first make sure we can do the api request
        $dbUserRecord = $this->userRepository->getUser($userVideoRegistrationRequest->getUsername());
        if (empty($dbUserRecord)) {
            throw new \BadMethodCallException("user does not exist for username " . $userVideoRegistrationRequest->getUsername());
        }

        $securePassword = $userVideoRegistrationRequest->getPassword();
        $userVideoRegistrationRequest->setPassword($this->userRepository->decryptPassword($securePassword));
        $httpDataRequest = $userVideoRegistrationRequest->toArray();

        $response = $this->sendAPIRequest($this->getEndpointUrl("userupdate"), $httpDataRequest);

        if ($response['status'] != 200) {
            $this->systemLogger->errorLogCaller("Failed to update provisioned user", ['username' => $userVideoRegistrationRequest->getUsername()
                , 'response' => $response]);
            return false;
        } else {
            $dbUserRecord->setAuthToken($securePassword);
            $dbUserRecord->setIsActive(true);
            $dbUserRecord->setRegistrationCode($userVideoRegistrationRequest->getRegistrationCode());
            $userId = $this->userRepository->saveUser($dbUserRecord);
            $this->systemLogger->debug("Updated user on comlink api ", ['username' => $userVideoRegistrationRequest->getUsername(), 'id' => $userId]);
            return $userId;
        }
    }

    public function suspendUser(string $username, string $password): bool
    {
        // first make sure we can do the api request
        $dbUserRecord = $this->userRepository->getUser($username);
        if (empty($dbUserRecord)) {
            throw new \BadMethodCallException("user does not exist for username " . $username);
        }

        $decryptedPassword = $this->userRepository->decryptPassword($password);
        $httpDataRequest = ['userName' => $username, 'passwordString' => $decryptedPassword];

        $response = $this->sendAPIRequest($this->getEndpointUrl("usersuspend"), $httpDataRequest);
        unset($httpDataRequest['passwordString']);

        if ($response['status'] != 200) {
            $this->systemLogger->errorLogCaller("Failed to suspend user", ['username' => $username, 'response' => $response]);
            return false;
        } else {
            $this->systemLogger->debug("Suspended user on comlink api ", ['username' => $username]);
        }

        $dbUserRecord->setIsActive(false);
        $this->userRepository->saveUser($dbUserRecord);
        return true;
    }

    public function resumeUser(string $username, string $password): bool
    {
        // first make sure we can do the api request
        $dbUserRecord = $this->userRepository->getUser($username);
        if (empty($dbUserRecord)) {
            throw new \BadMethodCallException("user does not exist for username " . $username);
        }

        $passwordString = $this->userRepository->decryptPassword($password);
        $httpDataRequest = ['userName' => $username, 'passwordString' => $passwordString]; // clear out passwords in memory

        $response = $this->sendAPIRequest($this->getEndpointUrl("userresume"), $httpDataRequest); // clear out passwords in memory
        if ($response['status'] != 200) {
            $this->systemLogger->errorLogCaller("Failed to resume user", ['username' => $username, 'response' => $response]);
            return false;
        } else {
            $this->systemLogger->debug("Resumed user on comlink api ", ['username' => $username]);
        }

        $dbUserRecord->setIsActive(true);
        $this->userRepository->saveUser($dbUserRecord);
        return true;
    }

    public function deactivateUser(string $username, string $password): bool
    {
        // first make sure we can do the api request
        $dbUserRecord = $this->userRepository->getUser($username);
        if (empty($dbUserRecord)) {
            throw new \BadMethodCallException("user does not exist for username " . $username);
        }

        $httpDataRequest = ['userName' => $username, 'passwordString' => $password];

        $response = $this->sendAPIRequest($this->getEndpointUrl("userresume"), $httpDataRequest);

        if ($response['status'] != 200) {
            $this->systemLogger->errorLogCaller("Failed to deactivate user", ['username' => $username, 'response' => $response]);
            return false;
        } else {
            $this->systemLogger->debug("Deactivated user on comlink api ", ['username' => $username]);
        }

        $dbUserRecord->setIsActive(false);
        $this->userRepository->saveUser($dbUserRecord);
        return true;
    }

    public function verifyProvisioningServiceIsValid(): array
    {
        $randomUuid = UuidV4::uuid4()->toString();
        $randomPassword = UuidV4::uuid4()->toString();

        // if we are not authorized we will get a 401 response from this.
        $response = $this->sendAPIRequest($this->getEndpointUrl('usersuspend'), ['userName' => $randomUuid, 'passwordString' => $randomPassword]);
        return ['status' => $response['internalStatus'], 'message' => $response['internalError']];
    }

    private function sendAPIRequest($endpointUrl, array $body): array
    {
        if (empty($this->httpClient)) {
            throw new \BadMethodCallException("httpClient must be setup in order to send request");
        }

        // because this could be an already existing event we've tried saving before we decode the json, even though
        // on the first event notification we may be doubling the work
        $client = $this->getHttpClient();
        $internalErrorResponse = null;
        $bodyResponse = null;
        $statusCode = 500;
        $internalStatusCode = 200;

        try {
            $httpRequestOptions = [
                "headers" => [
                    "SvcmgrTk1" => $this->apiId
                    ,"SvcmgrTk2" => $this->apiPassword
                    ,"SvcmgrTk3" => $this->apiCMSID
                ],
                "body" => json_encode($body)
            ];
            $response = $client->post($endpointUrl, $httpRequestOptions);
            $statusCode = $response->getStatusCode();
            $response->getBody()->rewind();
            $bodyResponse = $response->getBody()->getContents();
        } catch (GuzzleException $guzzleException) {
            $this->systemLogger->errorLogCaller(
                "Failed to send registration request Exception: " . $guzzleException->getMessage(),
                ['trace' => $guzzleException->getTraceAsString(), 'endUrl' => $endpointUrl]
            );
            if ($guzzleException->getCode() == 401) { // unauthorized exception meaning the credentials are incorrect
                $statusCode = 401;
            }

            $internalErrorResponse = $guzzleException->getMessage();
            $internalStatusCode = $guzzleException->getCode();
        }

        return ['status' => $statusCode, 'internalStatus' => $internalStatusCode, 'bodyResponse' => $bodyResponse, 'internalError' => $internalErrorResponse];
    }
}
