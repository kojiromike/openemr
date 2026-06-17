<?php

/**
 * Upload and install a designated code set to the codes table.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Rod Roark <rod@sunsetsystems.com>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2014 Rod Roark <rod@sunsetsystems.com>
 * @copyright Copyright (c) 2018 Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc.
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

set_time_limit(0);

require_once '../globals.php';
require_once \OpenEMR\Core\OEGlobalsBag::getInstance()->getProjectDir() . '/custom/code_types.inc.php';

use OpenEMR\Common\Acl\AccessDeniedHelper;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Core\Header;
use OpenEMR\Services\CodeImport\RxcuiLoader;

if (!AclMain::aclCheckCore('admin', 'super')) {
    AccessDeniedHelper::denyWithTemplate("ACL check failed for admin/super: Install Code Set", xl("Install Code Set"));
}

$form_replace = !empty($_POST['form_replace']);
$code_type = empty($_POST['form_code_type']) ? '' : $_POST['form_code_type'];
?>
<html>

<head>
<title><?php echo xlt('Install Code Set'); ?></title>
<?php Header::setupHeader(); ?>

<style>
 .dehead {
   color: var(--black);
   font-family: sans-serif;
   font-size: 0.8125rem;
   font-weight: bold;
  }
 .detail {
   color: var(--black);
   font-family: sans-serif;
   font-size: 0.8125rem;
   font-weight:normal;
 }
</style>

</head>

<body class="body_top">

<?php
$session = SessionWrapperFactory::getInstance()->getActiveSession();
// Handle uploads.
if (!empty($_POST['bn_upload'])) {
    CsrfUtils::checkCsrfInput(INPUT_POST, dieOnFail: true);

    if (empty($code_types[$code_type])) {
        die(xlt('Code type not yet defined') . ": '" . text($code_type) . "'");
    }

    $tmp_name = $_FILES['form_file']['tmp_name'];

    if (is_uploaded_file($tmp_name) && $_FILES['form_file']['size']) {
        try {
            // Use new service-based loader
            $loader = new RxcuiLoader();

            // Check if we should use old method (for compatibility/testing)
            $useOldMethod = !empty($GLOBALS['code_import_use_old_method']);
            $loader->setUseOldMethod($useOldMethod);

            // Import the codes
            $stats = $loader->import($tmp_name, [
                'replace' => $form_replace,
            ]);

            // Display results
            $inscount = $stats['inserted'] ?? 0;
            $repcount = $stats['updated'] ?? 0;
            $skipped = $stats['skipped'] ?? 0;

            echo "<p class='text-success'>" . xlt('LOAD SUCCESSFUL. Codes inserted') . ": " . text($inscount) . ", " . xlt('updated') . ": " . text($repcount);
            if ($skipped > 0) {
                echo ", " . xlt('skipped') . ": " . text($skipped);
            }
            echo "</p>\n";
        } catch (\Exception $e) {
            echo "<p class='text-danger'>" . xlt('ERROR') . ": " . text($e->getMessage()) . "</p>\n";
        }
    } else {
        echo "<p class='text-danger'>" . xlt('ERROR. Could not open') . ". " . (php_ini_loaded_file() ?? "Server") . " upload_max_filesize: " . xlt('Your file is too large') . ". " . xlt('Set To') . " ≥ post_max_size.";
    }
}

?>
    <div class="container">

        <form method='post' action='load_codes.php' enctype='multipart/form-data'
        onsubmit='return top.restoreSession()'>

            <input type="hidden" name="csrf_token_form" value="<?php echo CsrfUtils::collectCsrfToken(session: $session); ?>" />

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr class="dehead">
                            <th colspan="2" class='text-center'><?php echo xlt('Install Code Set'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <?php echo xlt('Code Type'); ?>
                            </td>
                            <td>
                                <select name='form_code_type'>
                                    <?php
                                    foreach (['RXCUI'] as $codetype) {
                                        echo "    <option value='" . attr($codetype) . "'>" . text($codetype) . "</option>\n";
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="detail">
                                <?php echo xlt('Source File'); ?>
                                <input type="hidden" name="MAX_FILE_SIZE" value="350000000" />
                            </td>
                            <td class="detail">
                                <input type="file" name="form_file" size="40" />
                            </td>
                        </tr>
                        <tr>
                            <td class="detail">
                                <?php echo xlt('Replace entire code set'); ?>
                            </td>
                            <td class="detail">
                                <input type='checkbox' name='form_replace' value='1' checked />
                            </td>
                        </tr>
                        <tr class="bg-secondary">
                            <td colspan="2" class="text-center detail">
                                <input type='submit' class='btn btn-primary' name='bn_upload' value='<?php echo xlt('Upload and Install') ?>' />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class='font-weight-bold text-center'>
                <?php echo xlt('Be patient, some files can take several minutes to process!'); ?>
            </p>

            <!-- No translation because this text is long and US-specific and quotes other English-only text. -->
            <p class='text'>
            <span class="font-weight-bold">RXCUI codes</span> may be downloaded from
            <a href='https://www.nlm.nih.gov/research/umls/rxnorm/docs/rxnormfiles.html' rel="noopener" target='_blank'>
            www.nlm.nih.gov/research/umls/rxnorm/docs/rxnormfiles.html</a>.
            Get the "Current Prescribable Content Monthly Release" zip file, marked "no license required".
            Then you can upload that file as-is here, or extract the file RXNCONSO.RRF from it and upload just
            that (zipped or not). You may do the same with the weekly updates, but for those uncheck the
            "<?php echo xlt('Replace entire code set'); ?>" checkbox above.
            </p>
            <div class="alert alert-info"><p>
                    <i class="fa fa-circle-info"></i> <?php echo xlt("If you're Code Sets are not loading, verify your php.ini post_max_size value and your upload_max_filesize has been set large enough to handle the file size you are uploading."); ?>
            </p>
            </div>
            <!-- TBD: Another paragraph of instructions here for each code type. -->
        </form>
    </div>
</body>
</html>
