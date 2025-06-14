<?php

declare(strict_types=1);

/**
 * CodeTypes/Module  Handles the mapping of code systems to our list options and any other code type processing that
 * we need to take care of in the system based on system events.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 *
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2022 Discover and Change, Inc. <snielson@discoverandchange.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace CodeTypes;

use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use OpenEMR\ZendModules\CodeTypes\Listener\CodeTypeEventsSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Module
{
    const NAMESPACE_NAME = 'CodeTypes';

    public function getAutoloaderConfig(): array
    {
        // TODO: verify that we need this namespace autoloader... it should be on by default...
        return array(
            \Laminas\Loader\StandardAutoloader::class => array(
                'namespaces' => array(
                    'OpenEMR\\ZendModules\\' . __NAMESPACE__ => __DIR__ . '/src/' . self::NAMESPACE_NAME,
                ),
            ),
        );
    }

    public function onBootstrap(MvcEvent $mvcEvent): void
    {
        // we grab the OpenEMR event listener (which is injected as Laminas has its own dispatcher)
        $serviceLocator = $mvcEvent->getApplication()->getServiceManager();
        $oemrDispatcher = $serviceLocator->get(EventDispatcherInterface::class);

        // now we can listen to our module events
        $subscriber = $serviceLocator->get(CodeTypeEventsSubscriber::class);
        $oemrDispatcher->addSubscriber($subscriber);
    }

    public function getServiceConfig(): array
    {
        return array();
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
