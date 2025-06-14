<?php

declare(strict_types=1);

/**
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 *
 * @author    Brad Sharp <brad.sharp@claimrev.com>
 * @copyright Copyright (c) 2022-2025 Brad Sharp <brad.sharp@claimrev.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace OpenEMR\Modules\Dorn;

use DateTime;
use OpenEMR\Modules\Dorn\models\AckViewModel;
use OpenEMR\Modules\Dorn\models\ApiResponseViewModel;
use OpenEMR\Modules\Dorn\models\CompendiumInstallDateViewModel;
use OpenEMR\Modules\Dorn\models\LabOrderViewModel;

class ConnectorApi
{
    public static function searchOrderStatus($originalOrderNumber, $primaryId, $startDateTime, $endDateTime)
    {
        $api_server = ConnectorApi::getServerInfo();
        $url = $api_server . "/api/Orders/v1/SearchOrderStatus";
        $params = [];
// Initialize an empty params array

        if (!empty($originalOrderNumber)) {
            $params['originalOrderNumber'] = $originalOrderNumber;
        }

        if (!empty($primaryId)) {
            $params['primaryId'] = $primaryId;
        }

        if (!empty($startDateTime)) {
            $params['startDateTime'] = $startDateTime;
        }

        if (!empty($endDateTime)) {
            $params['endDateTime'] = $endDateTime;
        }

        $url = $url . '?' . http_build_query($params);
        return ConnectorApi::getData($url);
    }

    public static function sendAck($resultsGuid, $isRejected, $msgs)
    {
        $api_server = ConnectorApi::getServerInfo();
        $url = $api_server . "/api/Orders/v1/AcknowledgeResult";
        $ackViewModel = new AckViewModel();
        $ackViewModel->resultsGuid = $resultsGuid;
        $ackViewModel->isRejected = $isRejected;
        if (is_array($msgs) && $msgs !== []) {
            $ackViewModel->errorMessages = $msgs;
        }

        return ConnectorApi::postData($url, $ackViewModel);
    }

    public static function setCompendiumLastUpdate($labGuid)
    {
        $api_server = ConnectorApi::getServerInfo();
        $url = $api_server . "/api/Labs/v1/SetCompendiumInstallDate";
        $compendiumInstallDateViewModel = new CompendiumInstallDateViewModel();
        $compendiumInstallDateViewModel->installDate = (new DateTime())->format('Y-m-d\TH:i:s');
        $compendiumInstallDateViewModel->labGuid = $labGuid;
        return ConnectorApi::putData($url, $compendiumInstallDateViewModel);
    }

    public static function searchPendingLabResults($labAccountNumber, $startDateTime, $endDateTime)
    {
        $api_server = ConnectorApi::getServerInfo();
        $url = $api_server . "/api/Orders/v1/GetPendingResults";
        $params = [];
// Initialize an empty params array

        if (!empty($labAccountNumber)) {
            $params['labAccountNumber'] = $labAccountNumber;
        }

        if (!empty($startDateTime)) {
            $params['startDateTime'] = $startDateTime;
        }

        if (!empty($endDateTime)) {
            $params['endDateTime'] = $endDateTime;
        }

        $url = $url . '?' . http_build_query($params);
        return ConnectorApi::getData($url);
    }

    public static function getLabResults(string $resultsGuid)
    {
        $api_server = ConnectorApi::getServerInfo();
        $url = $api_server . "/api/Orders/v1/GetResults/" . $resultsGuid;
        return ConnectorApi::getData($url);
    }

    public static function sendOrder(string $labGuid, string $labAccountNumber, string $orderNumber, string $patientId, $hl7)
    {
        $api_server = ConnectorApi::getServerInfo();
        $url = $api_server . "/api/Orders/v1/SendLabOrder";
        $base64 = base64_encode($hl7);
        $labOrderViewModel = new LabOrderViewModel();
        $labOrderViewModel->labGuid = $labGuid . '';
        $labOrderViewModel->orderNumber = $orderNumber . '';
        $labOrderViewModel->patientId = $patientId . '';
        $labOrderViewModel->hl7Base64 = $base64;
        $labOrderViewModel->labAccountNumber = $labAccountNumber . '';
        return ConnectorApi::postData($url, $labOrderViewModel);
    }

    public static function getCompendium(string $labGuid)
    {
        $api_server = ConnectorApi::getServerInfo();
        $url = $api_server . "/api/Labs/v1/" . $labGuid . "/Compendium";
        return ConnectorApi::getData($url);
    }

    public static function createRoute($data)
    {
        $api_server = ConnectorApi::getServerInfo();
        $url = $api_server . "/api/Route/v1/CreateRoute";
        return ConnectorApi::postData($url, $data);
    }

    public static function getLab(string $labGuid)
    {
        $api_server = ConnectorApi::getServerInfo();
        $url = $api_server . "/api/Labs/v1/" . $labGuid;
        return ConnectorApi::getData($url);
    }

    public static function searchLabs($labName, $phoneNumber, $faxNumber, $city, $state, $zipCode, $isActive, $isConnected)
    {
        $api_server = ConnectorApi::getServerInfo();
        $url = $api_server . "/api/Labs/v1/SearchLabs";
        $params = [];
// Initialize an empty params array

        if (!empty($labName)) {
            $params['labName'] = $labName;
        }

        if (!empty($phoneNumber)) {
            $params['phoneNumber'] = $phoneNumber;
        }

        if (!empty($faxNumber)) {
            $params['faxNumber'] = $faxNumber;
        }

        if (!empty($city)) {
            $params['city'] = $city;
        }

        if (!empty($state)) {
            $params['state'] = $state;
        }

        if (!empty($zipCode)) {
            $params['zipCode'] = $zipCode;
        }

        if (!empty($isActive)) {
            if ($isActive == "yes") {
                $params['isActive'] = "true";
            } elseif ($isActive == "no") {
                $params['isActive'] = "false";
            }
        }

        if (!empty($isConnected)) {
            if ($isConnected == "yes") {
                $params['isConnected'] = "true";
            } elseif ($isConnected == "no") {
                $params['isConnected'] = "false";
            }
        }

        $url = $url . '?' . http_build_query($params);
        return ConnectorApi::getData($url);
    }

    public static function savePrimaryInfo($data)
    {
        $api_server = ConnectorApi::getServerInfo();
        $url = $api_server . "/api/Customer/v1/SaveCustomerPrimaryInfo";
        return ConnectorApi::postData($url, $data);
    }

    public static function getPrimaryInfoByNpi($npi)
    {
        $api_server = ConnectorApi::getServerInfo();
        $url = $api_server . "/api/Customer/v1/GetPrimaryInfoByNpi";
        if ($npi) {
            $params = array('npi' => $npi);
            $url = $url . '?' . http_build_query($params);
        }
        return ConnectorApi::getData($url);
    }

    public static function getPrimaryInfos($npi)
    {
        $api_server = ConnectorApi::getServerInfo();
        $url = $api_server . "/api/Customer/v1/SearchPrimaryInfo";
        if ($npi) {
            $params = array('npi' => $npi);
            $url = $url . '?' . http_build_query($params);
        }
        return ConnectorApi::getData($url);
    }


    public static function getData($url)
    {
        $headers = ConnectorApi::buildHeader();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpcode == 200 || $httpcode == 400) {
            return json_decode($result);
        }

        error_log('Error Status Code' . text($httpcode) . " sending in api " . text($url) . " Message " . text($result));
        return "";
    }

    public static function putData($url, $sendData)
    {
        $headers = ConnectorApi::buildHeader();
        $payload = json_encode($sendData, JSON_UNESCAPED_SLASHES);
        error_log("putting");
        error_log(text($payload));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
// Use PUT method
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpcode == 200 || $httpcode == 400) {
            return json_decode($result);
        }

        error_log('Error Status Code' . text($httpcode) . " sending in api " . text($url) . " Message " . text($result));
        $apiResponseViewModel = new ApiResponseViewModel();
        $apiResponseViewModel->isSuccess = false;
        $apiResponseViewModel->responseMessage = "Error Putting Data!";
        return $apiResponseViewModel;
    }

    public static function postData($url, $sendData)
    {
        $headers = ConnectorApi::buildHeader();
        $payload = json_encode($sendData, JSON_UNESCAPED_SLASHES);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpcode == 200 || $httpcode == 400) {
            return json_decode($result);
        }

        error_log('Error Status Code' . text($httpcode) . " sending in api " . text($url) . " Message " . text($result));
        $apiResponseViewModel = new ApiResponseViewModel();
        $apiResponseViewModel->isSuccess = false;
        $apiResponseViewModel->responseMessage = "Error Posting Data!";
        return $apiResponseViewModel;
    }

    public static function getServerInfo()
    {
        $bootstrap = new Bootstrap($GLOBALS['kernel']->getEventDispatcher());
        $globalConfig = $bootstrap->getGlobalConfig();
        return $globalConfig->getApiServer();
    }

    public static function buildHeader(): array
    {
        $token = ConnectorApi::getAccessToken();
        $content = 'content-type: application/json';
        $bearer = 'authorization: Bearer ' . $token;
        return [
            $content,
            $bearer
        ];
    }


    public static function canConnectToClaimRev(): string
    {
        $token = ClaimRevApi::GetAccessToken();
        if ($token == "") {
            return "No";
        }

        return "Yes";
    }

    public static function getAccessToken()
    {
        $bootstrap = new Bootstrap($GLOBALS['kernel']->getEventDispatcher());
        $globalConfig = $bootstrap->getGlobalConfig();
        $authority = $globalConfig->getClientAuthority();
        $clientId = $globalConfig->getClientId();
        $scope = $globalConfig->getClientScope();
        $client_secret = $globalConfig->getClientSecret();
        $globalConfig->getApiServer();
        $headers = [
            'content-type: application/x-www-form-urlencoded'
        ];
        $payload = "client_id=" . $clientId . "&scope=" . $scope . "&client_secret=" . $client_secret . "&grant_type=client_credentials";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $authority);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($result);
        $token = "";
        if (property_exists($data, 'access_token')) {
            $token = $data->access_token;
        }

        return $token;
    }
}
