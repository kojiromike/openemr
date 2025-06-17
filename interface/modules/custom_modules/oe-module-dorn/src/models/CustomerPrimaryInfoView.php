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

class CustomerPrimaryInfoView
{
    public $accountNumber = "";

    public $npi = "";

    public $primaryId;

    public $primaryName = "";

    public $primaryPhone = "";

    public $primaryEmail = "";

    public $primaryAddress1 = "";

    public $primaryAddress2 = "";

    public $primaryCity = "";

    public $primaryState = "";

    public $primaryZipCode = "";

    public static function loadByPost(array $postData): \OpenEMR\Modules\Dorn\models\CustomerPrimaryInfoView
    {
        $customerPrimaryInfoView = new CustomerPrimaryInfoView();
        $customerPrimaryInfoView->primaryId = $postData["form_primaryId"];
        $customerPrimaryInfoView->npi = $postData["form_npi"];
        $customerPrimaryInfoView->primaryName = $postData["form_name"];
        $customerPrimaryInfoView->primaryPhone = $postData["form_phone"];
        $customerPrimaryInfoView->primaryEmail = $postData["form_email"];
        $customerPrimaryInfoView->primaryAddress1 = $postData["form_address1"];
        $customerPrimaryInfoView->primaryAddress2 = $postData["form_address2"];
        $customerPrimaryInfoView->primaryCity = $postData["form_city"];
        $customerPrimaryInfoView->primaryState = $postData["form_state"];
        $customerPrimaryInfoView->primaryZipCode = $postData["form_zip"];
        if ($customerPrimaryInfoView->primaryId == "") {
            $customerPrimaryInfoView->primaryId = null;
        }


        return $customerPrimaryInfoView;
    }
}
