<?php

declare(strict_types=1);

/**
 * CAMOS ajax_save.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Mark Leeds <drleeds@gmail.com>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (C) 2006-2009 Mark Leeds <drleeds@gmail.com>
 * @copyright Copyright (c) 2018 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(__DIR__ . "/../../globals.php");
require_once(__DIR__ . "/../../../library/api.inc.php");
require_once(__DIR__ . "/../../../library/forms.inc.php");
require_once(__DIR__ . "/content_parser.php");

use OpenEMR\Common\Csrf\CsrfUtils;

if (!CsrfUtils::verifyCsrfToken($_POST["csrf_token_form"])) {
    CsrfUtils::csrfNotVerified();
}

$field_names = array('category' => $_POST["category"], 'subcategory' => $_POST["subcategory"], 'item' => $_POST["item"], 'content' => $_POST['content']);
$camos_array = array();
process_commands($field_names['content'], $camos_array);

$CAMOS_form_name = "CAMOS-" . $field_names['category'] . '-' . $field_names['subcategory'] . '-' . $field_names['item'];

if ($encounter == "") {
    $encounter = date("Ymd");
}

if (preg_match("/^[\s\\r\\n\\\\r\\\\n]*$/", $field_names['content']) == 0) { //make sure blanks do not get submitted
  // Replace the placeholders before saving the form. This was changed in version 4.0. Previous to this, placeholders
  //   were submitted into the database and converted when viewing. All new notes will now have placeholders converted
  //   before being submitted to the database. Will also continue to support placeholder conversion on report
  //   views to support notes within database that still contain placeholders (ie. notes that were created previous to
  //   version 4.0).
    $field_names['content'] = replace($pid, $encounter, $field_names['content']);
    reset($field_names);
    $newid = formSubmit("form_CAMOS", $field_names, $_GET["id"], $userauthorized);
    addForm($encounter, $CAMOS_form_name, $newid, "CAMOS", $pid, $userauthorized);
}

//deal with embedded camos submissions here
foreach ($camos_array as $camo_array) {
    if (preg_match("/^[\s\\r\\n\\\\r\\\\n]*$/", $camo_array['content']) == 0) { //make sure blanks not submitted
        foreach ($camo_array as $k => $v) {
            // Replace the placeholders before saving the form. This was changed in version 4.0. Previous to this, placeholders
            //   were submitted into the database and converted when viewing. All new notes will now have placeholders converted
            //   before being submitted to the database. Will also continue to support placeholder conversion on report
            //   views to support notes within database that still contain placeholders (ie. notes that were created previous to
            //   version 4.0).
            $camo_array[$k] = trim(replace($pid, $encounter, $v));
        }

        $CAMOS_form_name = "CAMOS-" . $camo_array['category'] . '-' . $camo_array['subcategory'] . '-' . $camo_array['item'];
        reset($camo_array);
        $newid = formSubmit("form_CAMOS", $camo_array, $_GET["id"], $userauthorized);
        addForm($encounter, $CAMOS_form_name, $newid, "CAMOS", $pid, $userauthorized);
    }
}

echo "<font color=red><b>" . xlt('submitted') . ": " . text(time()) . "</b></font>";
