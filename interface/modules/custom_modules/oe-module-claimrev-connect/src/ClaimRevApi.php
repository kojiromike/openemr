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

use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Modules\ClaimRevConnector\UploadEdiFileContentModel;
use OpenEMR\Modules\ClaimRevConnector\Bootstrap;

class ClaimRevApi
{
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

    public static function uploadClaimFile($ediContents, $fileName, string $token)
    {
        $bootstrap = new Bootstrap($GLOBALS['kernel']->getEventDispatcher());
        $globalConfig = $bootstrap->getGlobalConfig();
        $api_server = $globalConfig->getApiServer();

        $content = 'content-type: application/json';
        $bearer = 'authorization: Bearer ' . $token;
        $headers = [
            $content,
            $bearer
         ];

        $url = $api_server . "/api/InputFile/v1";

        $uploadEdiFileContentModel = new UploadEdiFileContentModel("", $ediContents, $fileName);
        $payload = json_encode($uploadEdiFileContentModel, JSON_UNESCAPED_SLASHES);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($result);

        if ($httpcode != 200) {
            return false;
        }
        return !$data->isError;
    }

    public static function getReportFiles($reportType, string $token)
    {
        $bootstrap = new Bootstrap($GLOBALS['kernel']->getEventDispatcher());
        $globalConfig = $bootstrap->getGlobalConfig();
        $api_server = $globalConfig->getApiServer();

        $content = 'content-type: application/json';
        $bearer = 'authorization: Bearer ' . $token;
        $headers = [
            $content,
            $bearer
         ];

        $params = array('ediType' => $reportType);

        $endpoint = $api_server . "/api/EdiResponseFile/v1/GetReport";
        $url = $endpoint . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        if ($httpcode != 200) {
            return "";
        }

        return json_decode($result);
    }

    public static function getDefaultAccount(string $token): bool|string
    {
        $bootstrap = new Bootstrap($GLOBALS['kernel']->getEventDispatcher());
        $globalConfig = $bootstrap->getGlobalConfig();
        $api_server = $globalConfig->getApiServer();

        $content = 'content-type: application/json';
        $bearer = 'authorization: Bearer ' . $token;
        $headers = [
            $content,
            $bearer
         ];


        $endpoint = $api_server . "/api/UserProfile/v1/GetDefaultAccount";
        $url = $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        if ($httpcode != 200) {
            return "";
        }

        return $result;
    }

    public static function searchClaims($claimSearch, string $token)
    {
        $bootstrap = new Bootstrap($GLOBALS['kernel']->getEventDispatcher());
        $globalConfig = $bootstrap->getGlobalConfig();
        $api_server = $globalConfig->getApiServer();

        $content = 'content-type: application/json';
        $bearer = 'authorization: Bearer ' . $token;
        $headers = [
            $content,
            $bearer
         ];

        $url = $api_server . "/api/ClaimView/v1/SearchClaims";

        $payload = json_encode($claimSearch, JSON_UNESCAPED_SLASHES);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($result);

        if ($httpcode != 200) {
            return false;
        }

        return $data;
    }

    public static function searchDownloadableFiles($downloadSearch, string $token)
    {
        $bootstrap = new Bootstrap($GLOBALS['kernel']->getEventDispatcher());
        $globalConfig = $bootstrap->getGlobalConfig();
        $api_server = $globalConfig->getApiServer();

        $content = 'content-type: application/json';
        $bearer = 'authorization: Bearer ' . $token;
        $headers = [
            $content,
            $bearer
         ];

        $url = $api_server . "/FileManagement/SearchOutboundClientFiles";

        $payload = json_encode($downloadSearch, JSON_UNESCAPED_SLASHES);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($result);

        if ($httpcode != 200) {
            return false;
        }

        return $data;
    }

    public static function getFileForDownload($objectId, string $token)
    {
        $bootstrap = new Bootstrap($GLOBALS['kernel']->getEventDispatcher());
        $globalConfig = $bootstrap->getGlobalConfig();
        $api_server = $globalConfig->getApiServer();

        $content = 'content-type: application/json';
        $bearer = 'authorization: Bearer ' . $token;
        $headers = [
            $content,
            $bearer
         ];

        $endpoint = $api_server . "/FileManagement/GetFileForDownload";
        $params = array('id' => $objectId);
        $url = $endpoint . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        if ($httpcode != 200) {
            return false;
        }
        return json_decode($result);
    }

    public static function getEligibilityResult($originatingSystemId, string $token)
    {
        $bootstrap = new Bootstrap($GLOBALS['kernel']->getEventDispatcher());
        $globalConfig = $bootstrap->getGlobalConfig();
        $api_server = $globalConfig->getApiServer();

        $content = 'content-type: application/json';
        $bearer = 'authorization: Bearer ' . $token;
        $headers = [
            $content,
            $bearer
         ];

        $endpoint = $api_server . "/api/Eligibility/v1/GetEligibilityRequest";
        $params = array('originatingSystemId' => $originatingSystemId);
        $url = $endpoint . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        if ($httpcode != 200) {
            return false;
        }

        return json_decode($result);
    }

    public static function uploadEligibility($eligibility, string $token)
    {
        $bootstrap = new Bootstrap($GLOBALS['kernel']->getEventDispatcher());
        $globalConfig = $bootstrap->getGlobalConfig();
        $api_server = $globalConfig->getApiServer();

        $content = 'content-type: application/json';
        $bearer = 'authorization: Bearer ' . $token;
        $headers = [
            $content,
            $bearer
         ];


        $url = $api_server . "/api/SharpRevenue/v1/RunSharpRevenue";
        $payload = json_encode($eligibility, JSON_UNESCAPED_SLASHES);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($result);

        if ($httpcode != 200) {
            return false;
        }

        return $data;
    }
}
