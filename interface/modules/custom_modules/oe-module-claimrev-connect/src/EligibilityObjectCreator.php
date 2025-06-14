<?php

declare(strict_types=1);

/**
 *
 * @package OpenEMR
 * @link    http://www.open-emr.org
 *
 * @author    Brad Sharp <brad.sharp@claimrev.com>
 * @copyright Copyright (c) 2022 Brad Sharp <brad.sharp@claimrev.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace OpenEMR\Modules\ClaimRevConnector;

use OpenEMR\Modules\ClaimRevConnector\EligibilityData;
use OpenEMR\Modules\ClaimRevConnector\EligibilityInquiryRequest;
use OpenEMR\Modules\ClaimRevConnector\InformationReceiver;
use OpenEMR\Modules\ClaimRevConnector\SubscriberPatientEligibilityRequest;
use OpenEMR\Modules\ClaimRevConnector\RevenueToolsRequest;
use OpenEMR\Modules\ClaimRevConnector\RevenueToolsPayer;

class EligibilityObjectCreator
{
    public static function buildRevenueToolsRequest($pid, $pr, $eventDate = null, $providerId = null, $facilityId = null): \OpenEMR\Modules\ClaimRevConnector\RevenueToolsRequest
    {
        $facilityName = "";
        $facilityState = "";
        $facilityNpi = "";
        $providerNpi = "";
        $providerPinCode = "";

        $useFacility = $GLOBALS['oe_claimrev_config_use_facility_for_eligibility'];
        $serviceTypeCodes = $GLOBALS['oe_claimrev_config_service_type_codes'];
        $accountNumber = "";
        $productsToRun = array(1);


        $revenueToolsRequest = new RevenueToolsRequest();
        $revenueToolsRequest->requestingSoftware = "openEmr ClaimRev Connect";
        $revenueToolsRequest->accountNumber = $accountNumber;
        $revenueToolsRequest->payerResponsibility = $pr;
        $revenueToolsRequest->includeCredit = false;
        $revenueToolsRequest->serviceTypeCodes = explode(",", $serviceTypeCodes);
        $revenueToolsRequest->productsToRun = $productsToRun;


        if ($eventDate == null) {
            $revenueToolsRequest->serviceBeginDate = date("Y-m-d");
            $revenueToolsRequest->serviceEndDate = date("Y-m-d");
        } else {
            $revenueToolsRequest->serviceBeginDate = $eventDate;
            $revenueToolsRequest->serviceEndDate = $eventDate;
        }

        //only 1 will come back here
        $patientData = EligibilityData::getPatientData($pid);

        if ($patientData != null) {
            if ($facilityId == null) {
                $facilityId = $patientData['facility_id'];
            }

            if ($providerId == null || $providerId < 1) {
                $providerId = $patientData['providerID'];
            }

            $facilityData = EligibilityData::getFacilityData($facilityId);
            $providerData = EligibilityData::getProviderData($providerId);

            if ($facilityData != null) {
                $facilityName = $facilityData['facility_name'];
                $facilityState = $facilityData['facility_state'];
                $facilityNpi = $facilityData['facility_npi'];
            }

            if ($providerData != null) {
                $providerPinCode = $providerData['provider_pin'];
                $providerNpi = $providerData['provider_npi'];
            }

            $revenueToolsRequest->practiceName = $facilityName;
            $revenueToolsRequest->practiceState = $facilityState;
            $revenueToolsRequest->npi = $facilityNpi;

            if ($useFacility == false) {
                $revenueToolsRequest->npi = $providerNpi;
            }

            $revenueToolsRequest->patientFirstName = $patientData['fname'];
            $revenueToolsRequest->patientLastName = $patientData['lname'];
            $revenueToolsRequest->patientGender = $patientData['sex'];
            $revenueToolsRequest->patientDob = $patientData['dob'];
            $revenueToolsRequest->patientSsn = $patientData['ss'];
            $revenueToolsRequest->patientAddress1 = $patientData['street'];
            $revenueToolsRequest->patientCity = $patientData['city'];
            $revenueToolsRequest->patientState = $patientData['state'];
            $revenueToolsRequest->patientZip = $patientData['postal_code'];
            $revenueToolsRequest->patientEmailAddress = $patientData['email'];

            $revenueToolsRequest->pinCode = $providerPinCode;
        }

        return $revenueToolsRequest;
    }

    /**
     * @return list
     */
    public static function buildObject($pid, $payer_responsibility, $eventDate = null, $facilityId = null, $providerId = null): array
    {
        $results = array();
        $resultSubscribers = EligibilityData::getSubscriberData($pid, $payer_responsibility);
        foreach ($resultSubscribers as $resultSubscriber) {
            $payers = array();
            $pr = ValueMapping::mapPayerResponsibility($resultSubscriber['type']);
            $revenueTools = EligibilityObjectCreator::buildRevenueToolsRequest($pid, $pr, $eventDate, $providerId, $facilityId);
            $payer = new RevenueToolsPayer();
            $payer->payerNumber = $resultSubscriber['payerId'];
            $payer->payerName = $resultSubscriber['payer_name'];
            $payer->subscriberNumber = $resultSubscriber['policy_number'];
            $revenueTools->subscriberFirstName = $resultSubscriber['subscriber_fname'];
            $revenueTools->subscriberLastName = $resultSubscriber['subscriber_lname'];
            if ($resultSubscriber['subscriber_dob'] != "0000-00-00") {
                $revenueTools->subscriberDob = $resultSubscriber['subscriber_dob'];
            }

            $payers[] = $payer;
            $revenueTools->payers = $payers;
        }

        $results[] = $revenueTools;

        return $results;
    }

    public static function saveSingleToDatabase($req, $pid): void
    {

        $stale_age = $GLOBALS['oe_claimrev_eligibility_results_age'];
        //status of re-check if results are still waiting on claimrev site

        //if it's greater than aged date then lets remove completely from the tables, the new one will handle it. We don't care about statuses
        $sql = "DELETE FROM mod_claimrev_eligibility WHERE pid = ? AND payer_responsibility = ? AND (datediff(now(),create_date) >= ? or status in('error','waiting','creating') ) ";
        $sqlarr = array($pid,$req->payerResponsibility, $stale_age);
        $result = sqlStatement($sql, $sqlarr);

        $sql = "SELECT * FROM mod_claimrev_eligibility WHERE pid = ? AND payer_responsibility = ?";
        $sqlarr = array($pid,$req->payerResponsibility);
        $result = sqlStatement($sql, $sqlarr);
        if (sqlNumRows($result) <= 0) {
            $status = "creating";
            $sql = "INSERT INTO mod_claimrev_eligibility (pid,payer_responsibility,status,create_date) VALUES(?,?,?,NOW())";

            $sqlarr = array($pid,$req->payerResponsibility,$status);
            $result = sqlInsert($sql, $sqlarr);
            $status = "waiting";

            $req->originatingSystemId = strval($result);
            $json = json_encode($req, true);
            $sql = "UPDATE mod_claimrev_eligibility SET request_json = ?, status = ? where id = ?";
            $sqlarr = array($json,$status,$result);
            sqlStatement($sql, $sqlarr);
        }
    }

    public static function saveToDatabase($requests, $pid): void
    {
        //oe_claimrev_eligibility_results_age
        //lets check for status for waiting or error and replace the json and reset-status, what to do if inprogress??

        foreach ($requests as $request) {
            EligibilityObjectCreator::saveSingleToDatabase($request, $pid);
        }
    }
}
