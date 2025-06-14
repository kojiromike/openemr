<?php

declare(strict_types=1);

/**
 *
 * @package OpenEMR
 * @link    http://www.open-emr.org
 *
 * @author    Brad Sharp <brad.sharp@claimrev.com>
 * @copyright Copyright (c) 2022-2025 Brad Sharp <brad.sharp@claimrev.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace OpenEMR\Modules\Dorn\models;

class CreateRouteFromPrimaryViewModel
{
    public $customerAccountNumber = "";

    public $npi = "";

    public $labGuid;

    public $labAccountNumber = "";

    public static function loadByPost(array $postData): \OpenEMR\Modules\Dorn\models\CreateRouteFromPrimaryViewModel
    {
        $createRouteFromPrimaryViewModel = new CreateRouteFromPrimaryViewModel();
        $createRouteFromPrimaryViewModel->npi = $postData["form_primaries"];
        $createRouteFromPrimaryViewModel->labGuid = $postData["form_labGuid"];
        $createRouteFromPrimaryViewModel->labAccountNumber = $postData["form_labAcctNumber"];
        return $createRouteFromPrimaryViewModel;
    }
}
