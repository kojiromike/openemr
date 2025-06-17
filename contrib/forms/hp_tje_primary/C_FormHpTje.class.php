<?php

declare(strict_types=1);

require_once($GLOBALS['fileroot'] . "/library/forms.inc.php");
require_once(__DIR__ . "/FormHpTjePrimary.class.php");

class C_FormHpTje extends Controller
{
    public $template_dir;

    public function __construct($template_mod = "general")
    {
        parent::__construct();
        $this->template_mod = $template_mod;
        $this->template_dir = dirname(__FILE__) . "/templates/hp_tje/";
        $this->assign("FORM_ACTION", $GLOBALS['web_root']);
        $this->assign("DONT_SAVE_LINK", $GLOBALS['form_exit_url']);
        $this->assign("STYLE", $GLOBALS['style']);
    }

    public function default_action()
    {
        $formHpTjePrimary = new FormHpTjePrimary();
        $this->assign("hptje_primary", $formHpTjePrimary);
        $this->assign("checks", $formHpTjePrimary->_form_layout());
        return $this->fetch($this->template_dir . $this->template_mod . "_new.html");
    }

    public function view_action($form_id)
    {
        $hptje_primary = is_numeric($form_id) ? new FormHpTjePrimary($form_id) : new FormHpTjePrimary();

        $this->assign("hptje_primary", $hptje_primary);
        $this->assign("checks", $hptje_primary->_form_layout());
        $this->assign("VIEW", true);
        return $this->fetch($this->template_dir . $this->template_mod . "_new.html");
    }

    public function default_action_process(): void
    {
        if ($_POST['process'] != "true") {
            return;
        }

        $this->form = new FormHpTjePrimary($_POST['id']);
        parent::populate_object($this->form);

        $this->form->persist();
        if ($GLOBALS['encounter'] == "") {
            $GLOBALS['encounter'] = date("Ymd");
        }

        addForm($GLOBALS['encounter'], "Head Pain TJE", $this->form->id, "hp_tje_primary", $GLOBALS['pid'], $_SESSION['userauthorized']);
        $_POST['process'] = "";
    }
}
