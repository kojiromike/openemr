<?php

declare(strict_types=1);

require_once($GLOBALS['fileroot'] . "/library/forms.inc.php");
require_once(__DIR__ . "/FormSOAP.class.php");

class C_FormSOAP extends Controller
{
    public $template_dir;

    public function __construct($template_mod = "general")
    {
        parent::__construct();
        $this->template_mod = $template_mod;
        $this->template_dir = dirname(__FILE__) . "/templates/";
        $this->assign("FORM_ACTION", $GLOBALS['web_root']);
        $this->assign("DONT_SAVE_LINK", $GLOBALS['form_exit_url']);
        $this->assign("STYLE", $GLOBALS['style']);
    }

    public function default_action()
    {
        $formSOAP = new FormSOAP();
        $this->assign("data", $formSOAP);
        return $this->fetch($this->template_dir . $this->template_mod . "_new.html");
    }

    public function view_action($form_id)
    {
        $form = is_numeric($form_id) ? new FormSOAP($form_id) : new FormSOAP();
        $this->assign("data", $form);
        return $this->fetch($this->template_dir . $this->template_mod . "_new.html");
    }

    public function default_action_process(): void
    {
        if ($_POST['process'] != "true") {
            return;
        }

        $this->form = new FormSOAP($_POST['id']);
        parent::populate_object($this->form);
        $this->form->persist();
        if ($GLOBALS['encounter'] == "") {
            $GLOBALS['encounter'] = date("Ymd");
        }

        if (empty($_POST['id'])) {
            addForm(
                $GLOBALS['encounter'],
                "SOAP",
                $this->form->id,
                "soap2",
                $GLOBALS['pid'],
                $_SESSION['userauthorized']
            );
            $_POST['process'] = "";
        }
    }
}
