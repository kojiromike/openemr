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
 * OpenEMR\Modules\FaxSMS\EtherFax\FaxStatus class.
 */
class FaxStatus
{
    public $FaxResult = 0;

    public $State = FaxState::Idle;

    public $JobId;

    public $PagesDelivered = 0;

    public $ConnectTime = 0;

    public $ConnectSpeed = 0;

    public $Tag;

    public $CompletedOn;

    public int $Result;

    public $Message;

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
