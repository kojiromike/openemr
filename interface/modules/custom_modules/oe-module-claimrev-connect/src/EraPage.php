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

use OpenEMR\Modules\ClaimRevConnector\EraSearch;

class EraPage
{
    public static function searchEras(array $postData)
    {
        $startDate = $postData['startDate'];
        $endDate = $postData['endDate'];
        $fileStatus = $postData['downloadStatus'];

        $fileSearchModel = new FileSearchModel();
        $fileSearchModel->fileStatus = intval($fileStatus);
        $fileSearchModel->ediType = "835";
        $fileSearchModel->receivedDateStart = $startDate;
        $fileSearchModel->receivedDateEnd = $endDate;

        if ($fileSearchModel->receivedDateStart == "") {
            $fileSearchModel->receivedDateStart = null;
        }

        if ($fileSearchModel->receivedDateEnd == "") {
            $fileSearchModel->receivedDateEnd = null;
        }
        return EraSearch::search($fileSearchModel);
    }

    public static function downloadEra($id)
    {
        $data = EraSearch::downloadEra($id);
        $data->fileName = $data->ediType . "-" . $data->payerNumber . "-" .  convert_safe_file_dir_name($id) . ".txt";

        return $data;
    }
}
