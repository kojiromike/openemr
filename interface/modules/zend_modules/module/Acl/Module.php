<?php

declare(strict_types=1);

/**
 * interface/modules/zend_modules/module/Acl/Module.php
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Jacob T.Paul <jacob@zhservices.com>
 * @author    Basil PT <basil@zhservices.com>
 * @copyright Copyright (c) 2013 Z&H Consultancy Services Private Limited <sam@zhservices.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Acl;

use Acl\Model\AclTable;
use Laminas\ModuleManager\ModuleManager;

class Module
{
    public function getAutoloaderConfig(): array
    {
        // TODO: verify that we need this namespace autoloader... it should be on by default...
        return array(
            \Laminas\Loader\StandardAutoloader::class => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getServiceConfig(): array
    {
        return array(
            'factories' => array(
                \Acl\Model\AclTable::class =>  function ($sm): \Acl\Model\AclTable {
                    $dbAdapter = $sm->get(\Laminas\Db\Adapter\Adapter::class);
                    return new AclTable($dbAdapter);
                },
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
            $controller->layout('acl/layout/layout');

            $route = $controller->getEvent()->getRouteMatch();
            $controller->getEvent()->getViewModel()->setVariables(array(
                'current_controller' => $route->getParam('controller'),
                'current_action' => $route->getParam('action'),
            ));
        }, 100);
    }
}
