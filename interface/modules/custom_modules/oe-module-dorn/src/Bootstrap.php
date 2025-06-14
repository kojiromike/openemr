<?php

declare(strict_types=1);

/**
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 *
 * @author    Brad Sharp <brad.sharp@claimrev.com>
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2022-2025 Brad Sharp <brad.sharp@claimrev.com>
 * @copyright Copyright (c) 2024-2025 Jerry Padgett <sjpadgett@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace OpenEMR\Modules\Dorn;

/**
* Note the below use statements are importing classes from the OpenEMR core codebase
*/
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Kernel;
use OpenEMR\Events\Core\TwigEnvironmentEvent;
use OpenEMR\Events\Globals\GlobalsInitializedEvent;
use OpenEMR\Events\Main\Tabs\RenderEvent;
use OpenEMR\Events\RestApiExtend\RestApiResourceServiceEvent;
use OpenEMR\Events\RestApiExtend\RestApiScopeEvent;
use OpenEMR\Modules\Dorn\EventSubscriber\DornLabSubscriber;
use OpenEMR\Services\Globals\GlobalSetting;
use OpenEMR\Menu\MenuEvent;
use OpenEMR\Events\RestApiExtend\RestApiCreateEvent;
use OpenEMR\Events\PatientDemographics\RenderEvent as pRenderEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

class Bootstrap
{
    const MODULE_INSTALLATION_PATH = "/interface/modules/custom_modules/";

    const MODULE_NAME = "oe-module-dorn";

    /**
     * @var EventDispatcherInterface The object responsible for sending and subscribing to events through the OpenEMR system
     */
    private \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher;

    /**
     * @var GlobalConfig Holds our module global configuration values that can be used throughout the module.
     */
    private \OpenEMR\Modules\Dorn\GlobalConfig $globalConfig;

    /**
     * @var \Twig\Environment The twig rendering environment
     */
    private \Twig\Environment $twigEnvironment;

    private \OpenEMR\Common\Logging\SystemLogger $systemLogger;

    public function __construct(EventDispatcherInterface $eventDispatcher, ?Kernel $kernel = null)
    {
        global $GLOBALS;

        if (!$kernel instanceof \OpenEMR\Core\Kernel) {
            $kernel = new Kernel();
        }

        // NOTE: eventually you will be able to pull the twig container directly from the kernel instead of instantiating
        // it here.
        $twigContainer = new TwigContainer($this->getTemplatePath(), $kernel);
        $twigEnvironment = $twigContainer->getTwig();
        $this->twigEnvironment = $twigEnvironment;
        $this->eventDispatcher = $eventDispatcher;

        // we inject our globals value.
        $this->globalConfig = new GlobalConfig($GLOBALS);
        $this->systemLogger = new SystemLogger();
    }

    public function subscribeToEvents(): void
    {
        $this->addGlobalSettings();

        // we only add the rest of our event listeners and configuration if we have been fully setup and configured
        if ($this->globalConfig->isConfigured()) {
            $this->registerMenuItems();
            $this->registerTemplateEvents();
            $this->subscribeToApiEvents();
            $this->eventDispatcher->addSubscriber(new DornLabSubscriber());
        }
    }


    /**
     * @return GlobalConfig
     */
    public function getGlobalConfig()
    {
        return $this->globalConfig;
    }

    public function addGlobalSettings(): void
    {
        $this->eventDispatcher->addListener(GlobalsInitializedEvent::EVENT_HANDLE, [$this, 'addGlobalSettingsSection']);
    }

    public function addGlobalSettingsSection(GlobalsInitializedEvent $globalsInitializedEvent): void
    {
        // If globals are properly included elsewhere this should not be needed.
        //  Will leave this here for now to avoid breaking anything.
        global $GLOBALS;

        $globalsService = $globalsInitializedEvent->getGlobalsService();
        $section = xlt("DORN Lab Integration");
        $globalsService->createSection($section, 'Portal');

        $settings = $this->globalConfig->getGlobalSettingSectionConfiguration();

        foreach ($settings as $key => $config) {
            $value = $GLOBALS[$key] ?? $config['default'];
            $globalsService->appendToSection(
                $section,
                $key,
                new GlobalSetting(
                    xlt($config['title']),
                    $config['type'],
                    $value,
                    xlt($config['description']),
                    false // Config only. No user settings entry.
                )
            );
        }
    }

    /**
     * We tie into any events dealing with the templates / page rendering of the system here
     */
    public function registerTemplateEvents(): void
    {
        $this->eventDispatcher->addListener(TwigEnvironmentEvent::EVENT_CREATED, [$this, 'addTemplateOverrideLoader']);
    }

    /**
     * Add our javascript and css file for the module to the main tabs page of the system
     */
    public function renderMainBodyScripts(RenderEvent $renderEvent)
    {
    }

    public function addTemplateOverrideLoader(TwigEnvironmentEvent $twigEnvironmentEvent): void
    {
        try {
            $twig = $twigEnvironmentEvent->getTwigEnvironment();
            if ($twig === $this->twigEnvironment) {
                // we do nothing if its our own twig environment instantiated that we already setup
                return;
            }

            // we make sure we can override our file system directory here.
            $loader = $twig->getLoader();
            if ($loader instanceof FilesystemLoader) {
                $loader->prependPath($this->getTemplatePath());
            }
        } catch (LoaderError $loaderError) {
            $this->systemLogger->errorLogCaller("Failed to create template loader", ['innerMessage' => $loaderError->getMessage(), 'trace' => $loaderError->getTraceAsString()]);
        }
    }

    public function registerMenuItems(): void
    {
        if ($this->getGlobalConfig()->getGlobalSetting(GlobalConfig::CONFIG_ENABLE_MENU)) {
            /**
             * @global $eventDispatcher @see ModulesApplication::loadCustomModule
             * @global $module @see ModulesApplication::loadCustomModule
             */
            $this->eventDispatcher->addListener(MenuEvent::MENU_UPDATE, [$this, 'addCustomModuleMenuItem']);
        }
    }

    public function addCustomModuleMenuItem(MenuEvent $menuEvent): MenuEvent
    {
        $menu = $menuEvent->getMenu();

        $menuItem = new \stdClass();
        $menuItem->requirement = 0;
        $menuItem->target = 'mod';
        $menuItem->menu_id = 'mod0';
        $menuItem->acl_req = ["patients", "lab"];
        $menuItem->label = xlt("DORN Lab Integration");
        $menuItem->global_req = [];
            // TODO: pull the install location into a constant into the codebase so if OpenEMR changes this location it
        // doesn't break any modules.
        $menuItem->url = "/interface/modules/custom_modules/oe-module-dorn/public/index.php";
        $menuItem->children = [];

        /**
         * This defines the Access Control List properties that are required to use this module.
         * Several examples are provided
         */
        $menuItem->acl_req = [];

        /**
         * If you would like to restrict this menu to only logged in users who have access to see all user data
         */
        //$menuItem->acl_req = ["admin", "users"];

        /**
         * If you would like to restrict this menu to logged in users who can access patient demographic information
         */
        //$menuItem->acl_req = ["users", "demo"];


        /**
         * This menu flag takes a boolean property defined in the $GLOBALS array that OpenEMR populates.
         * It allows a menu item to display if the property is true, and be hidden if the property is false
         */
        //$menuItem->global_req = ["custom_skeleton_module_enable"];

        /**
         * If you want your menu item to allows be shown then leave this property blank.
         */
        $menuItem->global_req = [];

        foreach ($menu as $item) {
            if ($item->menu_id == 'proimg') {
                $item->children[] = $menuItem;
                break;
            }
        }

        $menuEvent->setMenu($menu);

        return $menuEvent;
    }

    public function subscribeToApiEvents()
    {
    }


    public function addMetadataConformance(RestApiResourceServiceEvent $restApiResourceServiceEvent): RestApiResourceServiceEvent
    {
        $restApiResourceServiceEvent->setServiceClass(CustomSkeletonFHIRResourceService::class);
        return $restApiResourceServiceEvent;
    }

    public function getTemplatePath(): string
    {
        return \dirname(__DIR__) . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR;
    }
}
