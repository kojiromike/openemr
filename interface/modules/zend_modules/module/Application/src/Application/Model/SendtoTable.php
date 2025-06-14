<?php

declare(strict_types=1);

/**
 * interface/modules/zend_modules/module/Application/src/Application/Model/SendtoTable.php
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    BASIL PT <basil@zhservices.com>
 * @copyright Copyright (c) 2014 Z&H Consultancy Services Private Limited <sam@zhservices.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Application\Model;

use Laminas\Db\TableGateway\AbstractTableGateway;
use Application\Model\ApplicationTable;
use Laminas\Db\Adapter\Driver\Pdo\Result;

class SendtoTable extends AbstractTableGateway
{
    /*
    * getFacility
    * @return array facility
    *
    **/
    public function getFacility()
    {
        $applicationTable   = new ApplicationTable();
        $sql        = "SELECT * FROM facility ORDER BY name";
        return $applicationTable->zQuery($sql);
    }


    /*
    * getUsers
    * @param String $type
    * @return array
    *
    **/
    public function getUsers($type)
    {
        $applicationTable   = new ApplicationTable();
        $sql        = "SELECT * FROM users WHERE abook_type = ?";
        return $applicationTable->zQuery($sql, array($type));
    }


    /*
    * getFaxRecievers
    * @return array fax reciever types
    *
    **/
    public function getFaxRecievers()
    {
        $applicationTable   = new ApplicationTable();
        $sql        = "SELECT option_id, title FROM list_options WHERE list_id = 'abook_type'";
        return $applicationTable->zQuery($sql);
    }

    /*
     * CCDA component list
     *
     * @param    $type
     * @return   $components     Array of CCDA components
     **/
    /**
     * @return mixed[]
     */
    public function getCCDAComponents($type): array
    {
        $components = array();
        $query      = "select * from ccda_components where ccda_type = ?";
        $applicationTable   = new ApplicationTable();
        $result     = $applicationTable->zQuery($query, array($type));

        foreach ($result as $row) {
            $components[$row['ccda_components_field']] = $row['ccda_components_name'];
        }

        return $components;
    }
}
