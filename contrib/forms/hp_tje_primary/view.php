<?php

declare(strict_types=1);

require_once(__DIR__ . "/../../globals.php");
require_once($srcdir . '/api.inc.php');

require(__DIR__ . "/C_FormHpTje.class.php");

$c = new C_FormHpTje();
echo $c->view_action($_GET['id']);
