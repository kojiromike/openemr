<?php

declare(strict_types=1);

/**
 * Module is responsible for setting up the configuration of the module and any events it listens to.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Vinish K <vinish@zhservices.com>
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2015 Z&H Consultancy Services Private Limited <sam@zhservices.com>
 * @copyright Copyright (c) 2022 Discover and Change <snielson@discoverandchange.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Carecoordination;

use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Helper\Openemr\Emr;
use Laminas\View\Helper\Openemr\Menu;
use Carecoordination\Model\Progressnote;
use Carecoordination\Model\ProgressnoteTable;
use Carecoordination\Model\Continuitycaredocument;
use Carecoordination\Model\ContinuitycaredocumentTable;
use Carecoordination\Listener\CCDAEventsSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Module
{
    public function getAutoloaderConfig(): array
    {
        return array(
            \Laminas\Loader\ClassMapAutoloader::class => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            \Laminas\Loader\StandardAutoloader::class => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,

                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function init(ModuleManager $moduleManager): void
    {
        $sharedEvents = $moduleManager->getEventManager()->getSharedManager();
        $sharedEvents->attach(__NAMESPACE__, 'dispatch', function ($e): void {
            $controller = $e->getTarget();
            $controller->layout('carecoordination/layout/layout');

                $route = $controller->getEvent()->getRouteMatch();
                $controller->getEvent()->getViewModel()->setVariables(array(
                    'current_controller' => $route->getParam('controller'),
                    'current_action' => $route->getParam('action'),
                ));
        }, 100);
    }

    public function onBootstrap(MvcEvent $mvcEvent): void
    {
        // we grab the OpenEMR event listener (which is injected as Laminas has its own dispatcher)
        $serviceLocator = $mvcEvent->getApplication()->getServiceManager();
        $oemrDispatcher = $serviceLocator->get(EventDispatcherInterface::class);

        // now we can listen to our module events
        $menuSubscriber = $serviceLocator->get(CCDAEventsSubscriber::class);
        $oemrDispatcher->addSubscriber($menuSubscriber);
    }
}
