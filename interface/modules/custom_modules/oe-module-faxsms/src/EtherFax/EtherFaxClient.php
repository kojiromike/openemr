<?php

declare(strict_types=1);

/**
 * Fax SMS Module Member
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2023 Jerry Padgett <sjpadgett@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General public License 3
 */
namespace OpenEMR\Modules\FaxSMS\EtherFax;

use DateTime;
use Guzzle\GuzzleHttp\GuzzleException;
use Guzzle\Http\Client;
use http\Exception;

class EtherFaxClient
{
    const EFAX_API_URL = 'https://na.connect.etherfax.net/rest/3.0/api';

    const DEFAULT_TIMEOUT = 30;

    const HTTP_OK = 200;

    private static $timeZone;

    protected $auth;

    protected $httpCode;

    protected $timeout = EtherFaxClient::DEFAULT_TIMEOUT;

    /**
     * OpenEMR\Modules\FaxSMS\EtherFax\EtherFaxClient class.
     * @param $account
     * @param $user
     * @param $password
     * @param $key
     */
    public function __construct($account = null, $user = null, $password = null, $key = null)
    {
        // set credentials, default timeout
        $this->setCredentials($account, $user, $password, $key);
        if (empty($GLOBALS['oefax_enable_fax'] ?? null)) {
            throw new \RuntimeException(xlt("Access denied! Module not enabled"));
        }
    }

    /**
     * Sets the credentials to use when connecting to the etherFAX web service.
     * @param $account
     * @param $user
     * @param $password
     * @param $key
     */
    public function setCredentials(string $account, ?string $user, string $password, ?string $key): void
    {
        // set credentials
        if (is_null($user)) {
            $this->auth = null;
        }

        // Bearer API token is first choice. Last is Basic.
        if ($key !== null && $key !== '' && $key !== '0') {
            $this->auth = 'Bearer ' . $key;
        } elseif ($user !== null && $user !== '' && $user !== '0') {
            $this->auth = 'Basic ' . base64_encode(($account . '/' . $user . ':' . $password));
        }
    }

    /**
     * Holds the default request timeout.
     *
     * @param $timeout
     */
    public function setTimeout($timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * Returns the current set httpCode.
     */
    public function getHttpCode(): mixed
    {
        return $this->httpCode;
    }

    /**
     * Get fax account information.
     */
    public function getFaxAccount(): ?\OpenEMR\Modules\FaxSMS\EtherFax\FaxAccount
    {
        $account = null;

        // get account status
        $response = $this->clientHttpGet('/accounts?a=status');
        if ($response && $this->isOK()) {
            // process response
            $account = new FaxAccount();
            $account->set(json_decode($response));
        }

        // While here grab the distant timezone.
        self::$timeZone = $account->TimeZone ?? null;

        return $account;
    }

    /**
     * Our HTTP GET
     *
     * @param            $url
     * @param array|null $get
     */
    private function clientHttpGet(string $url, array $get = null): string
    {
        // create full uri request
        $uri = EtherFaxClient::EFAX_API_URL . $url;
        if ($get != null) {
            $uri .= '?' . http_build_query($get);
        }

        try {
            $client = new \GuzzleHttp\Client(array(
            "defaults" => array(
                "allow_redirects" => true,
                "exceptions" => false
            ),
            'verify' => false,
            //'proxy' => "localhost:8888", // use a proxy for debugging.
            ));
            // request it
            $response = $client->request('GET', $uri, [
                'debug' => false,
                'headers' => [
                    'accept' => 'application/json',
                    'Authorization' => $this->auth,
                ],
            ]);
        } catch (\GuzzleHttp\Exception\GuzzleException $guzzleException) {
            return $guzzleException->getMessage();
        }

        $this->httpCode = $response->getStatusCode();

        return $response->getBody();
    }

    public function isOK(): bool
    {
        return $this->httpCode == EtherFaxClient::HTTP_OK;
    }

    /**
     * @param $number
     * @param $file
     * @param $pages
     * @param $localId
     * @param $callerId
     * @param $tag
     * @param $tz
     */
    public function sendFax($number, $file, $pages, $localId = null, $callerId = null, $tag = null, $isDocument = null, $fileName = null): FaxStatus
    {
        // create fax status
        $faxStatus = new FaxStatus();
        if (is_file($file) && !$isDocument) {
            $data = file_get_contents($file);
            if ($data === '' || $data === '0' || $data === false) {
                $faxStatus->Result = FaxResult::InvalidOrMissingFile;
                return $faxStatus;
            }

            unlink($file);
        } else {
            // is content of document
            $data = $file;
        }

        //use server timezone
        if (empty($tz)) {
            $now = new DateTime();
            $tz = (string)($now->getOffset() / 3600);
        }

        // set default page count
        if (is_null($pages)) {
            $pages = 1;
        }

        // create post array/items
        $post = array(
            'DialNumber' => $number,
            'FaxImage' => base64_encode($data),
            'TotalPages' => $pages,
            'TimeZoneOffset' => $tz
        );
        // add optional items
        if (!is_null($localId)) {
            $post['LocalId'] = $localId;
        }

        if (!is_null($callerId)) {
            $post['CallerId'] = $callerId;
        }

        if (!is_null($tag)) {
            $post['Tag'] = $tag;
        }

        $DocumentParams = new \stdClass();
        $DocumentParams->Name = $fileName ?? 'Unknown';
        $post['DocumentParams'] = $DocumentParams;

        $post['HeaderString'] = "  {date:d-MMM-yyyy}  {time}   FROM: {csid}  TO: {number}   P. {page}";
        // set error default
        $faxStatus->Result = FaxResult::Error;
        // send fax
        $response = $this->clientHttpPost('/outbox', $post);
        if ($response && $this->isOK()) {
            $faxStatus->set(json_decode($response));
        } else {
            // This will have an error 'Message' in response.
            $faxStatus->set(json_decode($response));
        }

        return $faxStatus;
    }

    /**
     * Handles the http request "post" method and returns the response.
     * The vars contain an array of items that will be url encoded.
     *
     * @param            $url
     * @param array|null $post
     */
    private function clientHttpPost(string $url, array $post = null): bool|string
    {
        // create full uri
        $uri = EtherFaxClient::EFAX_API_URL . $url;

        try {
            $client = new \GuzzleHttp\Client();

            $response = $client->request('POST', $uri, [
                'allow_redirects' => false,
                'form_params' => $post,
                'headers' => [
                    'Authorization' => $this->auth,
                    'accept' => 'application/json',
                    'content-type' => 'application/x-www-form-urlencoded',
                ],
            ]);
            $this->httpCode = $response->getStatusCode();
            $result = $response->getBody();
        } catch (\GuzzleHttp\Exception\GuzzleException $guzzleException) {
            throw new \Exception($guzzleException->getMessage(), $guzzleException->getCode(), $guzzleException);
        }

        return $result;
    }

    /**
     * Returns the status of the specified fax id.
     *
     * @param $id
     */
    public function getFaxStatus(string $id): ?FaxStatus
    {
        // create fax status
        $status = null;
        // get fax status for specified id
        $response = $this->clientHttpGet('/status?id=' . $id);
        if ($response && $this->isOK()) {
            $status = new FaxStatus();
            $status->set(json_decode($response));
        }

        // return status
        return $status;
    }

    /**
     * Gets the number of pending faxes waiting for delivery.
     */
    public function getPendingFaxCount(): int
    {
        // get pending fax count
        $response = $this->clientHttpGet('/outbox?a=pending');
        if ($response && $this->isOK()) {
            $obj = json_decode($response);
            return (int)($obj->{'PendingFaxes'});
        }

        return 0;
    }

    /**
     * Gets the number of unread faxes.
     */
    public function getUnreadFaxCount(): int
    {
        // get unread fax count
        $response = $this->clientHttpGet('/inbox?a=unread');

        if ($response && $this->isOK()) {
            $obj = json_decode($response);
            return (int)($obj->{'UnreadFaxes'});
        }

        return 0;
    }

    /**
     * Gets a list of unread faxes.
     *
     * @return mixed|null
     */
    public function getUnreadFaxList(): mixed
    {
        // get unread fax list
        $response = $this->clientHttpGet('/inbox?a=list');
        if ($response && $this->isOK()) {
            return json_decode($response);
        }

        return null;
    }

    /**
     * Retrieves (downloads) the specified fax id including the image.
     *
     * @param $id
     */
    public function getFax(string $id): ?FaxReceive
    {
        // retrieve the specified fax
        $response = $this->clientHttpGet('/inbox?a=get&f=pdf&id=' . $id);
        if ($response && $this->isOK()) {
            $faxReceive = new FaxReceive();
            $set = json_decode($response);
            $faxReceive->set($set);
            return $faxReceive;
        }

        return null;
    }

    /**
     * Retrieves the next unread fax (oldest). This function will automatically flag
     * the received fax so other peers may not download it. This flag will be released
     * if the fax is not received within 5 minutes (by default). The FaxImage will only be
     * downloaded if $download = true.
     *
     * @param $download
     * @param $sid
     */
    public function getNextUnreadFax($download = false, $sid = null): ?FaxReceive
    {
        // create set parameters
        $get = array(
            'a' => 'getnext',
            'download' => $download ? "1" : "0"
        );
        // sid?
        if (!is_null($sid)) {
            $get ['sid'] = $sid;
        }

        // retrieve the specified fax
        $response = $this->clientHttpGet('/inbox', $get);
        if ($response && $this->isOK()) {
            $faxReceive = new FaxReceive();
            $faxReceive->set(json_decode($response));
            return $faxReceive;
        }

        return null;
    }

    /**
     * Sets the specified fax id as "received". Once called, this fax will no longer
     * appear in the unread fax items.
     *
     * @param $id
     */
    public function setFaxReceived(string $id): bool
    {
        // set fax as received
        $response = $this->clientHttpGet('/inbox?a=received&id=' . $id);
        return $response && $this->isOK();
    }

    /**
     * @param $id
     * Retrieves the accepted formats for the route (number) specified.
     *
     * @return mixed|null
     */
    public function getRouteInfo(string $id): mixed
    {
        $response = $this->clientHttpGet('/routes?a=info&id=' . $id);
        if ($response && $this->isOK()) {
            return json_decode($response);
        }

        return null;
    }
}
