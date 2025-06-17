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

use OpenEMR\Modules\ClaimRevConnector\Bootstrap;
use OpenEMR\Modules\ClaimRevConnector\ClaimRevApi;

class ConnectivityInfo
{
    public $token;
    public $client_authority;

    public $clientId;

    public $client_scope;

    public $client_secret;

    public $api_server;

    public $hasToken;

    public $defaultAccount;

    public function __construct()
    {
        $bootstrap = new Bootstrap($GLOBALS['kernel']->getEventDispatcher());
        $globalConfig = $bootstrap->getGlobalConfig();
        $this->client_authority = $globalConfig->getClientAuthority();
        $this->clientId = $globalConfig->getClientId();
        $this->client_scope = $globalConfig->getClientScope();
        $this->client_secret = $globalConfig->getClientSecret();
        $this->api_server = $globalConfig->getApiServer();
        $this->token = ClaimRevApi::getAccessToken();
        $this->hasToken = ClaimRevApi::canConnectToClaimRev();
        $this->defaultAccount = ClaimRevApi::getDefaultAccount($this->token);
    }
}
