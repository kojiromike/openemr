<?php

declare(strict_types=1);

require_once(__DIR__ . "/../../../../globals.php");
require_once(__DIR__ . "/../../../../drugs/drugs.inc.php");

use OpenEMR\Common\Acl\AclMain;

/**
 * @return list<array{name: mixed, drug_id: mixed, selector: mixed}>
 */
function find_contraceptive_methods(string $contraceptive_code): array
{
    $retval = array();
    $code = "IPPFCM:" . $contraceptive_code;
    $sqlSearch = "SELECT name,drugs.drug_id,related_code, selector FROM drugs, drug_templates"
              . " WHERE related_code like ? "
              . " AND drug_templates.drug_id=drugs.drug_id AND drugs.active = 1 AND drugs.consumable = 0 "
              . " ORDER BY drugs.name, drug_templates.selector, drug_templates.drug_id";
    $recordset = sqlStatement($sqlSearch, array("%" . $code . "%"));
    while ($row = sqlFetchArray($recordset)) {
        if (!isProductSelectable($row['drug_id'])) {
            continue;
        }

        $rel_codes = explode(";", $row['related_code']);
        $match = false;
        foreach ($rel_codes as $rel_code) {
            if ($rel_code === $code) {
                $match = true;
            }
        }

        if ($match) {
            $retval[] = array("name" => $row['name'], "drug_id" => $row['drug_id'], "selector" => $row['selector']);
        }
    }

    return $retval;
}

function get_method_description($contraceptive_code)
{
    $sqlSearch = " SELECT code_text FROM codes "
               . " WHERE code_type = 32 "
               . " AND code = ? AND active = 1";
    $recordset = sqlStatement($sqlSearch, array($contraceptive_code));
    if ($recordset) {
        $row = sqlFetchArray($recordset);
        return $row['code_text'];
    }
    return null;
}

if (!AclMain::aclCheckCore('acct', 'bill')) {
    header("HTTP/1.0 403 Forbidden");
    echo "Not authorized for billing";
    return false;
}

$retval = array();
$methods_lookup = array();
if (isset($_REQUEST['methods'])) {
    $methods = $_REQUEST['methods'];
    foreach ($methods as $method) {
        if (!isset($methods_lookup[$method])) {
            $list = array();
            $list['products'] = find_contraceptive_methods($method);
            $list['method'] = get_method_description($method);
            $methods_lookup[$method] = $list;
            $retval[] = $list;
        }
    }
}



echo json_encode($retval);
