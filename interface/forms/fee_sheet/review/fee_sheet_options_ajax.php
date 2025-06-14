<?php

declare(strict_types=1);

/**
 * Controller for getting information about fee sheet options
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Kevin Yeh <kevin.y@integralemr.com>
 * @copyright Copyright (c) 2013 Kevin Yeh <kevin.y@integralemr.com> and OEMR <www.oemr.org>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(__DIR__ . "/../../../globals.php");
require_once(__DIR__ . "/fee_sheet_options_queries.php");

use OpenEMR\Common\Acl\AclMain;

if (!AclMain::aclCheckCore('acct', 'bill')) {
    header("HTTP/1.0 403 Forbidden");
    echo "Not authorized for billing";
    return false;
}

$pricelevel = isset($_REQUEST['pricelevel']) ? $_REQUEST['pricelevel'] : 'standard';

$fso = load_fee_sheet_options($pricelevel);
$retval = array();
$retval['fee_sheet_options'] = $fso;
$retval['pricelevel'] = $pricelevel;
echo text(json_encode($retval));
