<?php

declare(strict_types=1);

/**
 * This bootstrap file connects the module to the OpenEMR system hooking to the API, api scopes, and event notifications
 *
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2022 Comlink Inc <https://comlinkinc.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Comlink\OpenEMR\Modules\TeleHealthModule;

use Comlink\OpenEMR\Modules\TeleHealthModule\Controller\Admin\TeleHealthPatientAdminController;
use Comlink\OpenEMR\Modules\TeleHealthModule\Controller\Admin\TeleHealthUserAdminController;
use Comlink\OpenEMR\Modules\TeleHealthModule\Controller\TeleconferenceRoomController;
use Comlink\OpenEMR\Modules\TeleHealthModule\Controller\TeleHealthCalendarController;
use Comlink\OpenEMR\Modules\TeleHealthModule\Controller\TeleHealthFrontendSettingsController;
use Comlink\OpenEMR\Modules\TeleHealthModule\Controller\TeleHealthPatientPortalController;
use Comlink\OpenEMR\Modules\TeleHealthModule\Controller\TeleHealthVideoRegistrationController;
use Comlink\OpenEMR\Modules\TeleHealthModule\Repository\CalendarEventCategoryRepository;
use Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthPersonSettingsRepository;
use Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthProviderRepository;
use Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthSessionRepository;
use Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthUserRepository;
use Comlink\OpenEMR\Modules\TeleHealthModule\Services\ParticipantListService;
use Comlink\OpenEMR\Modules\TeleHealthModule\Services\TeleHealthParticipantInvitationMailerService;
use Comlink\OpenEMR\Modules\TeleHealthModule\Services\TeleHealthProvisioningService;
use Comlink\OpenEMR\Modules\TeleHealthModule\Services\TelehealthRegistrationCodeService;
use Comlink\OpenEMR\Modules\TeleHealthModule\Services\TeleHealthRemoteRegistrationService;
use Laminas\Form\Element\Tel;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Common\Utils\CacheUtils;
use OpenEMR\Core\Kernel;
use OpenEMR\Events\Appointments\AppointmentSetEvent;
use OpenEMR\Events\Core\TwigEnvironmentEvent;
use OpenEMR\Events\Globals\GlobalsInitializedEvent;
use OpenEMR\Events\Main\Tabs\RenderEvent;
use OpenEMR\Services\Globals\GlobalSetting;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Bootstrap
{
    const OPENEMR_GLOBALS_LOCATION = "../../../../globals.php";
    const MODULE_INSTALLATION_PATH = "/interface/modules/custom_modules/";
    const MODULE_NAME = "";
    const MODULE_MENU_NAME = "TeleHealth";

    /**
     * @var EventDispatcherInterface The object responsible for sending and subscribing to events through the OpenEMR system
     */
    private \Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcher;

    private string $moduleDirectoryName;

    /**
     * The OpenEMR Twig Environment
     */
    private \Twig\Environment $twigEnvironment;

    private \Comlink\OpenEMR\Modules\TeleHealthModule\TelehealthGlobalConfig $telehealthGlobalConfig;

    const COMLINK_VIDEO_TELEHEALTH_API = 'comlink_telehealth_video_uri';

    private ?\Comlink\OpenEMR\Modules\TeleHealthModule\Controller\TeleHealthPatientPortalController $teleHealthPatientPortalController = null;

    private ?\Comlink\OpenEMR\Modules\TeleHealthModule\Controller\TeleHealthVideoRegistrationController $teleHealthVideoRegistrationController = null;

    private ?\Comlink\OpenEMR\Modules\TeleHealthModule\Controller\Admin\TeleHealthUserAdminController $teleHealthUserAdminController = null;

    private ?\Comlink\OpenEMR\Modules\TeleHealthModule\Controller\Admin\TeleHealthPatientAdminController $teleHealthPatientAdminController = null;

    private ?\Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthPersonSettingsRepository $teleHealthPersonSettingsRepository = null;

    private ?\Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthProviderRepository $teleHealthProviderRepository = null;

    private \OpenEMR\Common\Logging\SystemLogger $systemLogger;

    private ?\Comlink\OpenEMR\Modules\TeleHealthModule\Controller\TeleHealthCalendarController $teleHealthCalendarController = null;

    /**
     * @var array Hashmap of Service classname => Service used for dependency injection
     */
    private array $serviceRegistry = [];

    public function __construct(EventDispatcher $eventDispatcher, ?Kernel $kernel = null)
    {
        global $GLOBALS;

        if (!$kernel instanceof \OpenEMR\Core\Kernel) {
            $kernel = new Kernel();
        }
        $this->eventDispatcher = $eventDispatcher;
        $twigContainer = new TwigContainer($this->getTemplatePath(), $kernel);
        $twigEnvironment = $twigContainer->getTwig();
        $this->twigEnvironment = $twigEnvironment;

        $this->moduleDirectoryName = basename(dirname(__DIR__));
        $this->systemLogger = new SystemLogger();
        $this->telehealthGlobalConfig = new TelehealthGlobalConfig($this->getURLPath(), $this->moduleDirectoryName, $this->twigEnvironment);
    }

    public function getGlobalConfig(): TelehealthGlobalConfig
    {
        return $this->telehealthGlobalConfig;
    }

    public function getTemplatePath(): string
    {
        return \dirname(__DIR__) . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR;
    }

    public function getURLPath(): string
    {
        return $GLOBALS['webroot'] . self::MODULE_INSTALLATION_PATH . $this->moduleDirectoryName . "/public/";
    }

    /**
     * @return \Twig\Environment
     */
    public function getTwig()
    {
        return $this->twigEnvironment;
    }

    public function subscribeToEvents(): void
    {
        $this->addGlobalSettings();
        // we only show the telehealth settings if all of the telehealth configuration has been configured.
        if ($this->telehealthGlobalConfig->isTelehealthConfigured()) {
            $this->subscribeToTemplateEvents();
            $this->subscribeToProviderEvents();
            // note we need to subscribe at the admin controller as it must precede the registration controller
            // we need our telehealth settings setup for a user before we hit the registration controller
            // as there is an implicit data dependency here.
            // TODO: would it be better to abstract this into a separate controller that controls the flow of events
            // instead of relying on the admin being called before the registration?
            $this->getTeleHealthUserAdminController()->subscribeToEvents($this->eventDispatcher);
            $this->getTeleHealthPatientAdminController()->subscribeToEvents($this->eventDispatcher);
            $this->getPatientPortalController()->subscribeToEvents($this->eventDispatcher);
            $this->getRegistrationController()->subscribeToEvents($this->eventDispatcher);
            $this->getCalendarController()->subscribeToEvents($this->eventDispatcher);
        }
    }

    public function getCalendarController()
    {
        if (empty($this->teleHealthCalendarController)) {
            $this->teleHealthCalendarController = new TeleHealthCalendarController(
                $this->telehealthGlobalConfig,
                $this->getTwig(),
                $this->systemLogger,
                $this->getAssetPath(),
                $this->getCurrentLoggedInUser()
            );
        }
        return $this->teleHealthCalendarController;
    }

    public function getCurrentLoggedInUser()
    {
        return $_SESSION['authUserID'] ?? null;
    }

    public function subscribeToProviderEvents(): void
    {
        $this->eventDispatcher->addListener(AppointmentSetEvent::EVENT_HANDLE, [$this, 'createSessionRecord'], 10);
    }

    public function createSessionRecord(AppointmentSetEvent $appointmentSetEvent): void
    {
        $pc_catid = $appointmentSetEvent->givenAppointmentData()['pc_catid'] ?? null;
        $calendarEventCategoryRepository = new CalendarEventCategoryRepository();
        if (empty($calendarEventCategoryRepository->getEventCategoryForId($pc_catid))) {
            // not a telehealth category so we will just skip this.
            return;
        }

        $teleHealthSessionRepository = new TeleHealthSessionRepository();
        $teleHealthSessionRepository->getSessionByAppointmentId($appointmentSetEvent->eid);
    }

    public function subscribeToTemplateEvents(): void
    {
        $this->eventDispatcher->addListener(TwigEnvironmentEvent::EVENT_CREATED, [$this, 'addTemplateOverrideLoader']);
        $this->eventDispatcher->addListener(RenderEvent::EVENT_BODY_RENDER_POST, [$this, 'renderMainBodyTelehealthScripts']);
    }


    public function addTemplateOverrideLoader(TwigEnvironmentEvent $twigEnvironmentEvent): void
    {
        $twigEnvironment = $twigEnvironmentEvent->getTwigEnvironment();
        if ($twigEnvironment === $this->twigEnvironment) {
            // we do nothing if its our own twig environment instantiated that we already setup
            return;
        }
        // we make sure we can override our file system directory here.
        $loader = $twigEnvironment->getLoader();
        if ($loader instanceof FilesystemLoader) {
            $loader->prependPath($this->getTemplatePath());
        }
    }

    private function getPublicPathFQDN(): string
    {
        // return the public path with the fully qualified domain name in it
        // qualified_site_addr already has the webroot in it.
        return $GLOBALS['qualified_site_addr'] . self::MODULE_INSTALLATION_PATH . ($this->moduleDirectoryName ?? '') . '/' . 'public' . '/';
    }

    private function getAssetPath(): string
    {
        return $this->getURLPath() . 'assets' . '/';
    }

    public function renderMainBodyTelehealthScripts(): void
    {
        $scriptMinExtension = $this->telehealthGlobalConfig->isDebugModeEnabled() ? ".js" : ".min.js";
        ?>
        <script src="<?php echo $this->getAssetPath();?>../<?php echo CacheUtils::addAssetCacheParamToPath("index.php"); ?>&action=get_telehealth_settings"></script>
        <link rel="stylesheet" href="<?php echo $this->getAssetPath();?>css/<?php echo CacheUtils::addAssetCacheParamToPath("telehealth.css"); ?>">
        <script src="<?php echo $this->getAssetPath();?>js/dist/<?php echo CacheUtils::addAssetCacheParamToPath("telehealth" . $scriptMinExtension); ?>"></script>
        <script src="<?php echo $this->getAssetPath();?>js/<?php echo CacheUtils::addAssetCacheParamToPath("telehealth-provider.js"); ?>"></script>
        <?php
    }

    public function addGlobalSettings(): void
    {
        $this->eventDispatcher->addListener(GlobalsInitializedEvent::EVENT_HANDLE, [$this, 'addGlobalTeleHealthSettings']);
    }

    public function addGlobalTeleHealthSettings(GlobalsInitializedEvent $globalsInitializedEvent): void
    {
        $globalsService = $globalsInitializedEvent->getGlobalsService();
        $this->telehealthGlobalConfig->setupConfiguration($globalsService);
    }

    public function getTeleconferenceRoomController($isPatient): TeleconferenceRoomController
    {
        return new TeleconferenceRoomController(
            $this->getTwig(),
            new SystemLogger(),
            $this->getRegistrationController(),
            $this->getMailerService(),
            $this->getFrontendSettingsController(),
            $this->telehealthGlobalConfig,
            $this->getProvisioningService(),
            $this->getParticipantListService(),
            $this->getAssetPath(),
            $isPatient
        );
    }

    public function getProvisioningService()
    {
        $service = $this->getService(TeleHealthProvisioningService::class);
        if (empty($service)) {
            $service = new TeleHealthProvisioningService(
                $this->getUserRepository(),
                $this->getProviderRepository(),
                $this->getRemoteRegistrationService()
            );
            $this->storeService(TeleHealthProvisioningService::class, $service);
        }
        return $service;
    }

    public function getParticipantListService()
    {
        $service = $this->getService(ParticipantListService::class);
        if (empty($service)) {
            $service = new ParticipantListService($this->getTwig(), $this->getProvisioningService(), $this->getPublicPathFQDN());
            $this->storeService(ParticipantListService::class, $service);
        }
        return $service;
    }

    public function getRegistrationController(): TeleHealthVideoRegistrationController
    {
        if (empty($this->teleHealthVideoRegistrationController)) {
            $this->teleHealthVideoRegistrationController = new TeleHealthVideoRegistrationController(
                $this->getRemoteRegistrationService(),
                $this->getProviderRepository()
            );
        }
        return $this->teleHealthVideoRegistrationController;
    }
    public function getPatientPortalController(): TeleHealthPatientPortalController
    {
        if (empty($this->teleHealthPatientPortalController)) {
            $this->teleHealthPatientPortalController = new TeleHealthPatientPortalController($this->twigEnvironment, $this->getAssetPath(), $this->telehealthGlobalConfig);
        }
        return $this->teleHealthPatientPortalController;
    }

    private function getTeleHealthPatientAdminController()
    {
        if (empty($this->teleHealthPatientAdminController)) {
            $this->teleHealthPatientAdminController = new TeleHealthPatientAdminController(
                $this->telehealthGlobalConfig,
                $this->getUserRepository(),
                $this->getRemoteRegistrationService()
            );
        }
        return $this->teleHealthPatientAdminController;
    }

    private function getTeleHealthUserAdminController()
    {
        if (empty($this->teleHealthUserAdminController)) {
            $this->teleHealthUserAdminController = new TeleHealthUserAdminController(
                $this->telehealthGlobalConfig,
                $this->getTwig(),
                $this->getPersonSettingsRepository()
            );
        }
        return $this->teleHealthUserAdminController;
    }

    private function getPersonSettingsRepository(): TeleHealthPersonSettingsRepository
    {
        if (empty($this->teleHealthPersonSettingsRepository)) {
            $this->teleHealthPersonSettingsRepository = new TeleHealthPersonSettingsRepository($this->systemLogger);
        }
        return $this->teleHealthPersonSettingsRepository;
    }

    private function getProviderRepository(): TeleHealthProviderRepository
    {
        if (empty($this->teleHealthProviderRepository)) {
            $this->teleHealthProviderRepository = new TeleHealthProviderRepository($this->systemLogger, $this->telehealthGlobalConfig);
        }
        return $this->teleHealthProviderRepository;
    }

    private function getRegistrationCodeService()
    {
        $service = $this->getService(TelehealthRegistrationCodeService::class);
        if (empty($service)) {
            $service = new TelehealthRegistrationCodeService($this->telehealthGlobalConfig, $this->getUserRepository());
            $this->storeService(TelehealthRegistrationCodeService::class, $service);
        }
        return $service;
    }

    private function getMailerService(): \Comlink\OpenEMR\Modules\TeleHealthModule\Services\TeleHealthParticipantInvitationMailerService
    {
        return new TeleHealthParticipantInvitationMailerService($this->eventDispatcher, $this->getTwig(), $this->getPublicPathFQDN(), $this->telehealthGlobalConfig);
    }

    private function getFrontendSettingsController(): \Comlink\OpenEMR\Modules\TeleHealthModule\Controller\TeleHealthFrontendSettingsController
    {
        return new TeleHealthFrontendSettingsController($this->getAssetPath(), $this->getTwig(), $this->telehealthGlobalConfig);
    }

    private function getRemoteRegistrationService()
    {
        $service = $this->getService(TeleHealthRemoteRegistrationService::class);
        if (empty($service)) {
            $service = new TeleHealthRemoteRegistrationService($this->telehealthGlobalConfig, $this->getRegistrationCodeService());
            $this->storeService(TeleHealthRemoteRegistrationService::class, $service);
        }
        return $service;
    }
    private function getUserRepository()
    {
        $service = $this->getService(TeleHealthUserRepository::class);
        if (empty($service)) {
            $service = new TeleHealthUserRepository();
            $this->storeService(TeleHealthUserRepository::class, $service);
        }
        return $service;
    }

    private function storeService(string $className, \Comlink\OpenEMR\Modules\TeleHealthModule\Services\TeleHealthProvisioningService|\Comlink\OpenEMR\Modules\TeleHealthModule\Services\ParticipantListService|\Comlink\OpenEMR\Modules\TeleHealthModule\Services\TelehealthRegistrationCodeService|\Comlink\OpenEMR\Modules\TeleHealthModule\Services\TeleHealthRemoteRegistrationService|\Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthUserRepository $obj): void
    {
        $this->serviceRegistry[$className] = $obj;
    }

    private function getService(string $className)
    {
        if (isset($this->serviceRegistry[$className])) {
            return $this->serviceRegistry[$className];
        }
        return null;
    }
}
