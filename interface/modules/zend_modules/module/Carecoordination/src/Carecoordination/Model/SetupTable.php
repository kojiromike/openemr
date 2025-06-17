<?php

declare(strict_types=1);

/**
 * interface/modules/zend_modules/module/Carecoordination/src/Carecoordination/Model/SetupTable.php
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Vinish K <vinish@zhservices.com>
 * @copyright Copyright (c) 2014 Z&H Consultancy Services Private Limited <sam@zhservices.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Carecoordination\Model;

use Laminas\Db\TableGateway\AbstractTableGateway;
use Application\Model\ApplicationTable;
use Laminas\Db\Adapter\Driver\Pdo\Result;

class SetupTable extends AbstractTableGateway
{
    /*
     * This function will return an array of CCDA components and its sections, which will be displayed in the configuration screen.
     * @param        none
     * @return       array       $sections.
     */
    /**
     * @return list
     */
    public function getSections(): array
    {
        $sections   = array();

        $query      = "select com.ccda_components_field, com.ccda_components_name, ccda_sections_field, ccda_sections_name, ccda_sections_req_mapping
                        from ccda_components as com
                        left join ccda_sections as sec on sec.ccda_components_id = com.ccda_components_id
                        where 1=1 ORDER BY sec.ccda_components_id, sec.ccda_sections_id";
        $applicationTable   = new ApplicationTable();
        $type        = $applicationTable->zQuery($query, array());
        foreach ($type as $result) {
            $sections[] = $result;
        }

        return $sections;
    }

    /*
     * This function will return an array of all the HTML forms, which will be displayed in the configuration screen.
     * @param        none
     * @return       array       $forms.
     */
    /**
     * @return list<array{mixed, non-falsy-string}>
     */
    public function getFormsList(): array
    {
        $forms = array();

        $query      = "select name, directory, nickname from registry where state=? ORDER BY name";
        $applicationTable   = new ApplicationTable();
        $type        = $applicationTable->zQuery($query, array(1));
        foreach ($type as $result) {
            $name       = $result['nickname'] ? $result['nickname'] : $result['name'];
            $directory  = "1|" . $result['directory'];
            $forms[]    = array($name, $directory);
        }

        return $forms;
    }

    /*
     * This function will return an array of all the LBF forms and its elements, which will be displayed in the configuration screen.
     * @param        none
     * @return       array       $lbf.
     */
    /**
     * @return array{0: mixed, 1: non-falsy-string, 2?: non-empty-array<int<0, max>, array{mixed, non-falsy-string}>}[]
     */
    public function getLbfList(): array
    {
        $lbf = array();

        $query      = "select option_id, title from list_options where list_id = ? ORDER BY seq,title";
        $applicationTable   = new ApplicationTable();
        $type        = $applicationTable->zQuery($query, array('lbfnames'));
        $count      = 0;
        foreach ($type as $result) {
            $lbf[$count][0]     = $result['title'];
            $lbf[$count][1]     = "2|" . $result['option_id'];
            $res_1 =  $applicationTable->zQuery("SELECT field_id,title FROM layout_options WHERE form_id=? ORDER BY title", array($result['option_id']));
            $count_sub      = 0;
            foreach ($res_1 as $re_1) {
                $lbf[$count][2][$count_sub][0] = ($re_1['title'] ? $re_1['title'] : $re_1['field_id']);
                $lbf[$count][2][$count_sub][1] = $lbf[$count][1] . "|" . $re_1['field_id'];
                ++$count_sub;
            }

            ++$count;
        }

        return $lbf;
    }

    /*
     * This function will return an array of all the tables and its fields in EMR, which will be displayed in the configuration screen.
     * @param        none
     * @return       array       $tables.
     */
    /**
     * @return array{0: mixed, 1: non-falsy-string, 2?: non-empty-array<int<0, max>, array{mixed, non-falsy-string}>}[]
     */
    public function getTableList(): array
    {
        $tables = array();

        $query  = "SHOW TABLES LIKE 'form_%'";
        $applicationTable   = new ApplicationTable();
        $type        = $applicationTable->zQuery($query, array());
        $count  = 0;
        foreach ($type as $row) {
            $table_name     = array_shift($row);
            $tables[$count][0]  = $table_name;
            $tables[$count][1]  = "3|" . $table_name;
            $res_desc       = $applicationTable->zQuery("DESCRIBE " . $table_name);
            $count_sub      = 0;
            foreach ($res_desc as $re_desc) {
                $tables[$count][2][$count_sub][0] = $re_desc['Field'];
                $tables[$count][2][$count_sub][1] = $tables[$count][1] . "|" . $re_desc['Field'];
                ++$count_sub;
            }

            ++$count;
        }

        return $tables;
    }

    /*
     * This function will return an array of document categories, which will be displayed in the configuration screen.
     * @param        none
     * @return       array       $document_categories.
     */
    /**
     * @return list<array{mixed, non-falsy-string}>
     */
    public function getDocuments(): array
    {
        $document_categories = array();

        $query      = "SELECT * FROM categories WHERE id != ? ORDER BY NAME ASC";
        $applicationTable   = new ApplicationTable();
        $type        = $applicationTable->zQuery($query, array(1));
        foreach ($type as $row) {
            $document_categories[] = array($row['name'], '4|' . $row['id']);
        }

        return $document_categories;
    }

    /*
     * Function to fetch the mapped CCDA components and forms
     *  @param       None
     *  @return      $mapped_values
     */
    /**
     * @return non-empty-array<non-empty-array<int<0, max>, (array{form_dir: mixed, form_type: mixed, form_table: mixed, ccda_field: mixed, name: mixed, class: non-falsy-string} | array{form_dir: mixed, form_type: mixed, form_table: mixed, ccda_field: mixed})>>[]
     */
    public function getMappedFields($id): array
    {
        $mapped_values  = array();

        $query      = "SELECT *, reg.name AS form_name, lo.title as title, cat.name, cat.id FROM ccda_table_mapping AS tab1
			    LEFT JOIN ccda_field_mapping AS tab2 ON tab1.id = tab2.table_id
			    LEFT JOIN registry AS reg ON reg.directory = tab1.form_dir
			    LEFT JOIN list_options AS lo ON lo.list_id = ? AND tab1.form_dir=lo.option_id
                            LEFT JOIN categories AS cat ON cat.id = tab1.form_dir
			    WHERE tab1.deleted = ?";
        $applicationTable       = new ApplicationTable();
        $type            = $applicationTable->zQuery($query, array('lbfnames',0));

        $count      = 0;
        foreach ($type as $row) {
            $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['form_dir']      = $row['form_dir'];
            $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['form_type']     = $row['form_type'];
            $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['form_table']    = $row['form_table'];
            $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['ccda_field']    = $row['ccda_field'];
            if ($row['form_type'] == 1) {
                if ($row['ccda_field']) {
                    $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['name']  = $row['ccda_field'];
                    $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['class'] = "3|" . $row['form_dir'] . "|" . $row['ccda_field'];
                } elseif ($row['form_table'] && !$row['ccda_field']) {
                    $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['name']  = $row['form_table'];
                    $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['class'] = "1|" . $row['form_table'];
                } else {
                    $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['name']  = $row['form_name'];
                    $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['class'] = "1|" . $row['form_dir'];
                }
            } elseif ($row['form_type'] == 2) {
                if ($row['ccda_field']) {
                    $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['name']  = $row['ccda_field'];
                    $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['class'] = "2|" . $row['form_dir'] . "|" . $row['ccda_field'];
                } else {
                    $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['name']  = $row['title'];
                    $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['class'] = "2|" . $row['form_dir'];
                }
            } elseif ($row['form_type'] == 3) {
                $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['name']  = $row['name'];
                $mapped_values[$row['ccda_component']][$row['ccda_component_section']][$count]['class'] = "4|" . $row['id'];
            }

            ++$count;
        }

        return $mapped_values;
    }

    /*Function to fetch the maximum id of the CCDA template from mapping table

    * @param    None
    * @return   $id
    */
    public function getMaxIdCcda()
    {
        $query      = "select max(id) as id from ccda_table_mapping where user_id=?";
        $applicationTable       = new ApplicationTable();
        $type            = $applicationTable->zQuery($query, array(1));
        foreach ($type as $row) {
            return $row['id'];
        }
        return null;
    }

    /*Saving the CCDA structure in the master table

    * @param    $values     Array of values to be inserted in the table
    * @return   $row['id']  Last inserted ID from the table
    */
    public function insertMaster($values)
    {
        $sql        = "insert into ccda_table_mapping (ccda_component, ccda_component_section, form_dir, form_type, form_table, user_id)
	values (?, ?, ?, ?, ?, ?)";
        $appTable   = new ApplicationTable();
        $appTable->zQuery($sql, $values);

        $query      = "select max(id) as id from ccda_table_mapping";
        $appTable   = new ApplicationTable();
        $type        = $appTable->zQuery($query, array());
        foreach ($type as $row) {
            return $row['id'];
        }
        return null;
    }

    /*Saving the CCDA structure in the child table

    * @param    None
    * @return   None
    */
    public function insertChild($values): void
    {
        $sql_sub    = "insert into ccda_field_mapping (table_id, ccda_field) values (?, ?)";
        $applicationTable   = new ApplicationTable();
        $applicationTable->zQuery($sql_sub, $values);
    }

    /*Deleted existing CCDA mapped fields

    * @param    None
    * @return   None
    */
    public function updateExistingMappedFields($values): void
    {
        $applicationTable   = new ApplicationTable();
        $applicationTable->zQuery("update ccda_table_mapping set deleted = 1 where id <= ? and user_id = ?", $values);
    }
}
