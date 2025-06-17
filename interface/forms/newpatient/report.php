<?php

declare(strict_types=1);

/**
 * Encounter form report function.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @author    Robert Down <robertdown@live.com
 * @copyright Copyright (c) 2019 Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2023 Robert Down <robertdown@live.com
 * @copyright Copyright (c) 2023 Providence Healthtech
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(dirname(__file__) . "/../../globals.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Services\AppointmentService;
use OpenEMR\Services\UserService;
use OpenEMR\Common\Twig\TwigContainer;

function newpatient_report($pid, $encounter, $cols, $id): void
{
    $recordset = sqlStatement("select e.*, f.name as facility_name from form_encounter as e join facility as f on f.id = e.facility_id where e.pid=? and e.id=?", array($pid,$id));
    $twig = new TwigContainer(__DIR__, $GLOBALS['kernel']);
    $twigEnvironment = $twig->getTwig();
    $encounters = [];
    $userService = new UserService();
    while ($result = sqlFetchArray($recordset)) {
        $hasAccess = (empty($result['sensitivity']) || AclMain::aclCheckCore('sensitivities', $result['sensitivity']));
        $rawProvider = $userService->getUser($result["provider_id"]);
        $rawRefProvider = $userService->getUser($result["referring_provider_id"]);
        $calendar_category = (new AppointmentService())->getOneCalendarCategory($result['pc_catid']);
        $reason = ($hasAccess) ? $result['reason'] : false;
        $provider = ($hasAccess) ? $rawProvider['fname'] .
            (($rawProvider['mname'] ?? '') ? " " . $rawProvider['mname'] . " " : " ") .
            $rawProvider['lname'] .
            ($rawProvider['suffix'] ? ", " . $rawProvider['suffix'] : '') .
            ($rawProvider['valedictory'] ? ", " . $rawProvider['valedictory'] : '') : false;
        $referringProvider = (!$hasAccess || !$rawRefProvider) ? false : $rawRefProvider['fname'] . " " . $rawRefProvider['lname'];
        $posCode = ($hasAccess) ? sprintf('%02d', trim($result['pos_code'] ?? false)) : false;
        $posCode = ($posCode && $posCode !== '00') ? $posCode : false;
        $facility_name = ($hasAccess) ? $result['facility_name'] : false;

        $encounters[] = [
            'category' => xl_appt_category($calendar_category[0]['pc_catname']),
            'reason' => $reason,
            'provider' => $provider,
            'referringProvider' => $referringProvider,
            'posCode' => $posCode,
            'facility' => $facility_name,
        ];
    }

    // TODO: @adunsulag in future EMR version switch this to templates/newpatient/report.html.twig
    echo $twigEnvironment->render("templates/report.html.twig", ['encounters' => $encounters]);
}
