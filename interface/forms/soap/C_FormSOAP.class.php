<?php

declare(strict_types=1);

/*
 * soap form
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2019 Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2022 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once($GLOBALS['fileroot'] . "/library/forms.inc.php");
require_once(__DIR__ . "/FormSOAP.class.php");

use OpenEMR\Common\Twig\TwigContainer;

class C_FormSOAP extends Controller
{
    private TwigContainer $twig;

    public function __construct()
    {
        $path = $this->getTemplatePath();
        $this->twig = new TwigContainer($path);
    }

    /**
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig\Error\LoaderError
     */
    public function default_action(): string
    {
        $formSOAP = new FormSOAP();
        return $this->twig->getTwig()->render(
            'soap_form.twig',
            [
                "FORM_ACTION" => $GLOBALS['web_root'],
                "DONT_SAVE_LINK" => $GLOBALS['form_exit_url'],
                "data" => $formSOAP
            ]
        );
    }

    public function view_action($form_id): string
    {
        $form = is_numeric($form_id) ? new FormSOAP($form_id) : new FormSOAP();

        return $this->twig->getTwig()->render(
            'soap_form.twig',
            [
                "FORM_ACTION" => $GLOBALS['web_root'],
                "DONT_SAVE_LINK" => $GLOBALS['form_exit_url'],
                "data" => $form
            ]
        );
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
                "soap",
                $GLOBALS['pid'],
                $_SESSION['userauthorized']
            );
            $_POST['process'] = "";
        }
    }

    private function getTemplatePath(): string
    {
        return \dirname(__DIR__) . DIRECTORY_SEPARATOR . "soap/templates" . DIRECTORY_SEPARATOR;
    }
}
