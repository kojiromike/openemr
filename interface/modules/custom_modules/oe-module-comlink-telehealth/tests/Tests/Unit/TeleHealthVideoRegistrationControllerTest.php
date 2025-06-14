<?php

declare(strict_types=1);

/**
 * Handles the TeleHealthVideoRegistrationController Unit Tests
 *
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2022 Comlink Inc <https://comlinkinc.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Comlink\OpenEMR\Modules\TeleHealthModule;

use Comlink\OpenEMR\Modules\TeleHealthModule\Controller\TeleHealthVideoRegistrationController;
use Comlink\OpenEMR\Modules\TeleHealthModule\Models\TeleHealthUser;
use Comlink\OpenEMR\Modules\TeleHealthModule\Models\UserVideoRegistrationRequest;
use Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthProviderRepository;
use Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthUserRepository;
use Comlink\OpenEMR\Modules\TeleHealthModule\Services\TelehealthRegistrationCodeService;
use Comlink\OpenEMR\Modules\TeleHealthModule\Services\TeleHealthRemoteRegistrationService;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use PHPUnit\Framework\TestCase;

final class TeleHealthVideoRegistrationControllerTest extends TestCase
{
    private \Comlink\OpenEMR\Modules\TeleHealthModule\Controller\TeleHealthVideoRegistrationController $teleHealthVideoRegistrationController;

    private \Comlink\OpenEMR\Modules\TeleHealthModule\TelehealthGlobalConfig $telehealthGlobalConfig;

    private \Comlink\OpenEMR\Modules\TeleHealthModule\Services\TelehealthRegistrationCodeService $telehealthRegistrationCodeService;

    protected function setUp(): void
    {
        global $GLOBALS;
        parent::setUp();
        $telehealthGlobalConfig = new TelehealthGlobalConfig();
        $this->telehealthGlobalConfig = $telehealthGlobalConfig;

        $teleHealthProviderRepository = new TeleHealthProviderRepository(new SystemLogger(), $telehealthGlobalConfig);
        $teleHealthUserRepository = new TeleHealthUserRepository();
        $this->telehealthRegistrationCodeService = new TelehealthRegistrationCodeService($telehealthGlobalConfig, $teleHealthUserRepository);
        $teleHealthRemoteRegistrationService = new TeleHealthRemoteRegistrationService($telehealthGlobalConfig, $this->telehealthRegistrationCodeService);

        $this->teleHealthVideoRegistrationController = new TeleHealthVideoRegistrationController($teleHealthRemoteRegistrationService, $teleHealthProviderRepository);
    }

    public function testAddNewUser(): void
    {

        $userVideoRegistrationRequest = $this->getCreateUserRequest();

        $mock = $this->createMock(TeleHealthUserRepository::class);

        $mock->expects($this->once())
            ->method('saveUser')
            ->willReturn(1);

        $mock->expects($this->once())
            ->method('decryptPassword')
            ->willReturn($userVideoRegistrationRequest->getPassword());

        $this->teleHealthVideoRegistrationController->setTelehealthUserRepository($mock);
        $savedTelehealthUserId = $this->teleHealthVideoRegistrationController->addNewUser($userVideoRegistrationRequest);
        $this->assertEquals(1, $savedTelehealthUserId, "Request was made and saved user id was returned");
    }

    public function testSuspendUser(): void
    {
        $controller = $this->teleHealthVideoRegistrationController;
        $userVideoRegistrationRequest = $this->getCreateUserRequest();

        $mock = $this->createMock(TeleHealthUserRepository::class);
        $mock->method('saveUser')
            ->willReturn(1);
        $mock->method('getUser')
            ->willReturn($this->getMockUser(1, $userVideoRegistrationRequest->getUsername()));
        $mock->expects($this->once())
            ->method('decryptPassword')
            ->willReturn($userVideoRegistrationRequest->getPassword());

        $controller->setTelehealthUserRepository($mock);
        $savedTelehealthUserId = $controller->addNewUser($userVideoRegistrationRequest);
        $this->assertNotFalse($savedTelehealthUserId, "failed to provision new user before update");

        $result = $controller->suspendUser($userVideoRegistrationRequest->getUsername(), $userVideoRegistrationRequest->getPassword());
        $this->assertEquals(true, $result, "Request was made and user was suspended");
    }

    public function testDeactivateUser(): void
    {
        $this->markTestIncomplete("skipping test as we don't have a use for deactivation at this point");
    }

    public function testResumeUser(): void
    {
        $controller = $this->teleHealthVideoRegistrationController;
        $userVideoRegistrationRequest = $this->getCreateUserRequest();

        $mock = $this->createMock(TeleHealthUserRepository::class);
        $mock->method('saveUser')
            ->willReturn(1);
        $mock->method('getUser')
            ->willReturn($this->getMockUser(1, $userVideoRegistrationRequest->getUsername()));
        $mock->expects($this->once())
            ->method('decryptPassword')
            ->willReturn($userVideoRegistrationRequest->getPassword());

        $controller->setTelehealthUserRepository($mock);
        $savedTelehealthUserId = $controller->addNewUser($userVideoRegistrationRequest);
        $this->assertNotFalse($savedTelehealthUserId, "failed to provision new user before update");

        $result = $controller->suspendUser($userVideoRegistrationRequest->getUsername(), $userVideoRegistrationRequest->getPassword());
        $this->assertEquals(true, $result, "Request was made and user was suspended");

        // now resume the user and make sure that works
        $result = $controller->resumeUser($userVideoRegistrationRequest->getUsername(), $userVideoRegistrationRequest->getPassword());
        $this->assertEquals(true, $result, "Request was made and user status was resumed");
    }

    public function testUpdateUser(): void
    {
        $controller = $this->teleHealthVideoRegistrationController;
        $userVideoRegistrationRequest = $this->getCreateUserRequest();

        $mock = $this->createMock(TeleHealthUserRepository::class);
        $mock->method('saveUser')
            ->willReturn(1);
        $mock->method('getUser')
            ->willReturn($this->getMockUser(1, $userVideoRegistrationRequest->getUsername()));
        $mock->expects($this->once())
            ->method('decryptPassword')
            ->willReturn($userVideoRegistrationRequest->getPassword());

        $controller->setTelehealthUserRepository($mock);
        $savedTelehealthUserId = $controller->addNewUser($userVideoRegistrationRequest);
        $this->assertNotFalse($savedTelehealthUserId, "failed to provision new user before update");

        // now attempt to update the user
        $userVideoRegistrationRequest->setLastName("Test 2 first name " . $userVideoRegistrationRequest->getUsername())
            ->setPassword(sha1($userVideoRegistrationRequest->getUsername() . " random password"));
        $result = $controller->updateUser($userVideoRegistrationRequest);
        $this->assertEquals(1, $result, "Request was made and saved user id was returned");
    }

    private function getCreateUserRequest(): UserVideoRegistrationRequest
    {
        $uuid = UuidRegistry::getRegistryForTable("users")->createUuid();

        $teleHealthUserRepository = new TeleHealthUserRepository();
        $password = $teleHealthUserRepository->createUniquePassword();

        $userVideoRegistrationRequest = new UserVideoRegistrationRequest();
        $userVideoRegistrationRequest->setUsername(UuidRegistry::uuidToString($uuid))
            ->setPassword($password)
            ->setFirstName("Test First Name " . $userVideoRegistrationRequest->getUsername())
            ->setLastName("Test Last Name " . $userVideoRegistrationRequest->getUsername())
            ->setInstituationId($this->telehealthGlobalConfig->getInstitutionId())
            ->setInstitutionName($this->telehealthGlobalConfig->getInstitutionName())
            ->setDbRecordId(1)
            ->setRegistrationCode($this->telehealthRegistrationCodeService->generateRegistrationCode());
        return $userVideoRegistrationRequest;
    }

    private function getMockUser(int $id, string $username, $dbRecordId = null): TeleHealthUser
    {
        $teleHealthUser = new TeleHealthUser();
        return $teleHealthUser->setId($id)->setUsername($username)->setDbRecordId($dbRecordId);
    }
}
