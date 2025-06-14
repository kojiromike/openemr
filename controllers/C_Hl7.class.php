<?php

declare(strict_types=1);

/**
 * C_HL7 Class.
 *
 * @package OpenEMR
 * @link    https://www.open-emr.org
 * @author Stephen Waite <stephen.waite@cmsvt.com>
 * @copyright Copyright (c) 2021 Stephen Waite <stephen.waite@cmsvt.com>
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class C_Hl7 extends Controller
{
    public function __construct($template_mod = "general")
    {
        parent::__construct();
        $this->template_mod = $template_mod;
        $this->assign("STYLE", $GLOBALS['style']);
    }

    public function default_action()
    {
        return $this->fetch($GLOBALS['template_dir'] . "hl7/" . $this->template_mod . "_parse.html");
    }

    public function default_action_process(): void
    {
        $msg = '';
        if ($_POST['process'] == "true") {
            $msg = $_POST['hl7data'];
        }

        $parserHL7v2 = new Parser_HL7v2($msg);
        $this->assign("hl7_array", $parserHL7v2->parse());
    }
}
