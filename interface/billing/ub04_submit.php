<?php

declare(strict_types=1);

/**
 * UB04 Submit
 *
 * UThis is used as an endpoint URL by the UI to call into the ub04 functions
 *
 * @package OpenEMR
 * @link    http://www.open-emr.org
 * @author  Jerry Padgett <sjpadgett@gmail.com>
 * @author  Ken Chapple <ken@mi-squared.com>
 * @copyright Copyright (c) 2021 Ken Chapple <ken@mi-squared.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(__DIR__ . "/../globals.php");
require_once __DIR__ . '/ub04_dispose.php';

ub04_dispose();
