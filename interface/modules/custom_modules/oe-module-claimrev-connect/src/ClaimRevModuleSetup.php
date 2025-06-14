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

class ClaimRevModuleSetup
{
    public static function doesPartnerExists(): bool
    {
        $x12Name = $GLOBALS['oe_claimrev_x12_partner_name'];
        $sql = "SELECT * FROM x12_partners WHERE name = ?";
        $sqlarr = array($x12Name);
        $recordset = sqlStatementNoLog($sql, $sqlarr);
        $rowCount = sqlNumRows($recordset);
        return $rowCount > 0;
    }

    public static function couldSftpServiceCauseIssues(): bool
    {
        $sftp = ClaimRevModuleSetup::getServiceRecord("X12_SFTP");
        if ($sftp != null && $sftp["active"] == 1) {
            if ($sftp["require_once"] == "/library/billing_sftp_service.php") {
                return true;
            }
        }

        return false;
    }

    public static function deactivateSftpService(): void
    {
        $require_once = "/interface/modules/custom_modules/oe-module-claimrev-connect/src/SFTP_Mock_Service.php";
        ClaimRevModuleSetup::updateBackGroundServiceSetRequireOnce("X12_SFTP", $require_once);
    }

    public static function reactivateSftpService(): void
    {
        $require_once = "/library/billing_sftp_service.php";
        ClaimRevModuleSetup::updateBackGroundServiceSetRequireOnce("X12_SFTP", $require_once);
    }

    public static function updateBackGroundServiceSetRequireOnce($name, $requireOnce): void
    {
        $sql = "UPDATE background_services SET require_once = ? WHERE name = ?";
        $sqlarr = array($requireOnce,$name);
        sqlStatement($sql, $sqlarr);
    }

    public static function getServiceRecord($name)
    {
        $sql = "SELECT * FROM background_services WHERE name = ? LIMIT 1";
        $sqlarr = array($name);
        $recordset = sqlStatement($sql, $sqlarr);
        if (sqlNumRows($recordset) == 1) {
            foreach ($recordset as $row) {
                return $row;
            }
        }

        return null;
    }

    public static function getBackgroundServices()
    {
        $sql = "SELECT * FROM background_services WHERE name like '%ClaimRev%' OR name = 'X12_SFTP'";
        return sqlStatement($sql);
    }

    public static function createBackGroundServices(): void
    {
        $sql = "DELETE FROM background_services WHERE name like '%ClaimRev%'";
        sqlStatement($sql);

        $sql = "INSERT INTO `background_services` (`name`, `title`, `active`, `running`, `next_run`, `execute_interval`, `function`, `require_once`, `sort_order`) VALUES
            ('ClaimRev_Send', 'Send Claims To ClaimRev', 1, 0, '2017-05-09 17:39:10', 1, 'start_X12_Claimrev_send_files', '/interface/modules/custom_modules/oe-module-claimrev-connect/src/Billing_Claimrev_Service.php', 100);";
        sqlStatement($sql);

        $sql = "INSERT INTO `background_services` (`name`, `title`, `active`, `running`, `next_run`, `execute_interval`, `function`, `require_once`, `sort_order`) VALUES
            ('ClaimRev_Receive', 'Get Reports from ClaimRev', 1, 0, '2017-05-09 17:39:10', 240, 'start_X12_Claimrev_get_reports', '/interface/modules/custom_modules/oe-module-claimrev-connect/src/Billing_Claimrev_Service.php', 100);";
        sqlStatement($sql);

        $sql = "INSERT INTO `background_services` (`name`, `title`, `active`, `running`, `next_run`, `execute_interval`, `function`, `require_once`, `sort_order`) VALUES
            ('ClaimRev_Elig_Send_Receive', 'Send and Receive Eligibility from ClaimRev', 1, 0, '2017-05-09 17:39:10', 1, 'start_send_eligibility', '/interface/modules/custom_modules/oe-module-claimrev-connect/src/Eligibility_ClaimRev_Service.php', 100);";
        sqlStatement($sql);
    }
}
