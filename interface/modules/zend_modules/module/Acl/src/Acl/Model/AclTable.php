<?php

declare(strict_types=1);

/**
 * interface/modules/zend_modules/module/Acl/src/Acl/Model/AclTable.php
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Jacob T.Paul <jacob@zhservices.com>
 * @author    Basil PT <basil@zhservices.com>
 * @copyright Copyright (c) 2013 Z&H Consultancy Services Private Limited <sam@zhservices.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Acl\Model;

use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Application\Model\ApplicationTable;

class AclTable extends AbstractTableGateway
{
    protected $table = 'acl';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new Acl());
        $this->initialize();
    }

    public function aclSections($module_id)
    {
        $applicationTable    = new ApplicationTable();
        if ($module_id != '') {
            $sql    = "SELECT * FROM module_acl_sections WHERE module_id = ?";
            $params = array($module_id);
            $result = $applicationTable->zQuery($sql, $params);
        } else {
            $sql = "SELECT * FROM module_acl_sections ";
            $result = $applicationTable->zQuery($sql);
        }

        return $result;
    }

    public function aclUserGroupMapping()
    {
        $sql = "SELECT 
                    usr. id AS user_id,
                    garo.id AS aro_id,
                    garo.value AS username,
                    garo.name AS display_name,
                    gagp.id AS group_id,
                    gagp.name AS group_name,
                    gagp.value AS group_nick
                FROM
                    `gacl_aro` AS garo 
                        LEFT JOIN `gacl_groups_aro_map` AS gamp 
                            ON garo.id = gamp.aro_id 
                        LEFT JOIN `gacl_aro_groups` AS gagp
                            ON gagp.id = gamp.group_id
                        RIGHT JOIN `users_secure` usr 
                            ON usr. username =  garo.value
                WHERE
                    garo.section_value = ?";
        $params = array('users');
        $applicationTable    = new ApplicationTable();
        return $applicationTable->zQuery($sql, $params);
    }

    public function getActiveModules()
    {
        $sql    = "SELECT * FROM modules";
        $applicationTable    = new ApplicationTable();
        return $applicationTable->zQuery($sql);
    }

    public function getGroups()
    {
        $sql    = "SELECT * FROM gacl_aro_groups WHERE parent_id > 0";
        $applicationTable    = new ApplicationTable();
        return $applicationTable->zQuery($sql);
    }

    public function getGroupAcl($module_id)
    {
        $sql    = "SELECT * FROM module_acl_group_settings WHERE module_id = ? AND allowed = 1";
        $applicationTable    = new ApplicationTable();
        return $applicationTable->zQuery($sql, array($module_id));
    }

    public function deleteGroupACL($module_id, $section_id): void
    {
        $sql    = "DELETE FROM module_acl_group_settings WHERE module_id = ? AND section_id = ? ";
        $applicationTable    = new ApplicationTable();
        $applicationTable->zQuery($sql, array($module_id,$section_id));
    }

    public function deleteUserACL($module_id, $section_id): void
    {
        $sql    = "DELETE FROM module_acl_user_settings WHERE module_id = ? AND section_id = ? ";
        $applicationTable    = new ApplicationTable();
        $applicationTable->zQuery($sql, array($module_id,$section_id));
    }

    public function insertGroupACL($module_id, $group_id, $section_id, $allowed): void
    {
        $sql    = "INSERT INTO module_acl_group_settings (module_id,group_id,section_id,allowed) VALUES (?,?,?,?)";
        $applicationTable    = new ApplicationTable();
        $applicationTable->zQuery($sql, array($module_id,$group_id,$section_id,$allowed));
    }

    public function insertuserACL($module_id, $user_id, $section_id, $allowed): void
    {
        $sql    = "INSERT INTO module_acl_user_settings(module_id,user_id,section_id,allowed) VALUES (?,?,?,?)";
        $applicationTable    = new ApplicationTable();
        $applicationTable->zQuery($sql, array($module_id,$user_id,$section_id,$allowed));
    }

    public function getAclDataUsers($section_id)
    {
        $sql    = " SELECT 
                        usr_settings.*,
                        aromap.group_id   
                    FROM
                        `module_acl_user_settings` AS usr_settings 
                        LEFT JOIN `users_secure` AS usr 
                          ON usr_settings.`user_id` = usr.id 
                        LEFT JOIN `gacl_aro` AS aro 
                          ON aro.value = usr.username 
                        LEFT JOIN `gacl_groups_aro_map` AS aromap 
                          ON aromap.aro_id = aro.id 
                    WHERE 
                       usr_settings.`section_id` = ? AND aro.section_value = 'users'";
        $applicationTable    = new ApplicationTable();
        return $applicationTable->zQuery($sql, array($section_id));
    }

    public function getAclDataGroups($section_id)
    {
        $sql    = "SELECT * FROM module_acl_group_settings WHERE section_id =?";
        $applicationTable    = new ApplicationTable();
        return $applicationTable->zQuery($sql, array($section_id));
    }

    public function deleteModuleGroupACL($module_id): void
    {
        $sql    = "DELETE FROM module_acl_group_settings WHERE module_id =?";
        $applicationTable    = new ApplicationTable();
        $applicationTable->zQuery($sql, array($module_id));
    }

    public function getSectionsInsertId(): int|float
    {
        $sql    = "SELECT MAX(section_id) AS max_id FROM module_acl_sections";
        $applicationTable    = new ApplicationTable();
        $type = $applicationTable->zQuery($sql);
        $max_id = 0;
        foreach ($type as $row) {
            $max_id = $row['max_id'];
        }

        ++$max_id;
        return $max_id;
    }

    public function saveACLSections($module_id, $parent_id, $section_identifier, $section_name, $section_id): void
    {
        $sql        = "INSERT INTO module_acl_sections(section_id,section_name,parent_section,section_identifier,module_id) VALUES(?,?,?,?,?)";
        $applicationTable        = new ApplicationTable();
        $applicationTable->zQuery($sql, array($section_id,$section_name,$parent_id,$section_identifier,$module_id));
    }

    public function getModuleSections($module_id)
    {
        $sql    = "SELECT * FROM module_acl_sections WHERE module_id = ?";
        $applicationTable    = new ApplicationTable();
        return $applicationTable->zQuery($sql, array($module_id));
    }
}
