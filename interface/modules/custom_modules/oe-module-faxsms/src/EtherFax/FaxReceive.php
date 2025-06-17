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

/**
 * OpenEMR\Modules\FaxSMS\EtherFax\FaxReceive class.
 */
class FaxReceive
{
    public $FaxResult = 0;

    public $JobId;

    public $CalledNumber;

    public $CallingNumber;

    public $RemoteId;

    public $PagesReceived = 0;

    public $ConnectTime = 0;

    public $ConnectSpeed = 0;

    public $ReceivedOn;

    public $FaxImage;

    public $AnalyzeFormResult;

    public $DocumentParams;

    /**
     * @param $data
     */
    public function set($data): void
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
