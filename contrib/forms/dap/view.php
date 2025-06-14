<?php

declare(strict_types=1);

/*
 *  @package OpenEMR
 *  @link    http://www.open-emr.org
 *  @author  Sherwin Gaddis <sherwingaddis@gmail.com>
 *  @copyright Copyright (c) 2020.  Sherwin Gaddis <sherwingaddis@gmail.com>
 *  @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(__DIR__ . "/../../globals.php");
require_once($srcdir . '/api.inc.php');

require(__DIR__ . "/C_FormDAP.class.php");

$c = new C_FormDAP();
echo $c->view_action($_GET['id']);
