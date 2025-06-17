<?php

declare(strict_types=1);
    
    /**
 * Used for adding dated reminders.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Craig Bezuidenhout <http://www.tajemo.co.za/>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2012 tajemo.co.za <http://www.tajemo.co.za/>
 * @copyright Copyright (c) 2017-2018 Brady Miller <brady.g.miller@gmail.com>
 */

    require_once(__DIR__ . "/../../globals.php");
    require_once($srcdir . '/dated_reminder_functions.php');

    use OpenEMR\Common\Acl\AclMain;
    use OpenEMR\Common\Csrf\CsrfUtils;
    use OpenEMR\Core\Header;

    $isAdmin = AclMain::aclCheckCore('admin', 'users');
?>
<?php
/*
    -------------------  HANDLE POST ---------------------
*/
if ($_GET !== []) {
    if (!CsrfUtils::verifyCsrfToken($_GET["csrf_token_form"])) {
        CsrfUtils::csrfNotVerified();
    }

    if (!$isAdmin && (empty($_GET['sentBy']) && empty($_GET['sentTo']))) {
        $_GET['sentTo'] = array(intval($_SESSION['authUserID']));
    }

    $remindersArray = array();
    $TempRemindersArray = logRemindersArray();
    foreach ($TempRemindersArray as $TempReminderArray) {
        $remindersArray[$TempReminderArray['messageID']]['messageID'] = $TempReminderArray['messageID'];
        $remindersArray[$TempReminderArray['messageID']]['ToName'] = ((empty($remindersArray[$TempReminderArray['messageID']]['ToName'])) ? $TempReminderArray['ToName'] ?? '' : ($remindersArray[$TempReminderArray['messageID']]['ToName'] . ', ' . ($TempReminderArray['ToName'] ?? '')));
        $remindersArray[$TempReminderArray['messageID']]['PatientName'] = $TempReminderArray['PatientName'];
        $remindersArray[$TempReminderArray['messageID']]['message'] = $TempReminderArray['message'];
        $remindersArray[$TempReminderArray['messageID']]['dDate'] = $TempReminderArray['dDate'];
        $remindersArray[$TempReminderArray['messageID']]['sDate'] = $TempReminderArray['sDate'];
        $remindersArray[$TempReminderArray['messageID']]['pDate'] = $TempReminderArray['pDate'];
        $remindersArray[$TempReminderArray['messageID']]['processedByName'] = $TempReminderArray['processedByName'];
        $remindersArray[$TempReminderArray['messageID']]['fromName'] = $TempReminderArray['fromName'];
    }

    echo '<div class="row">
            <div class="col-12 results-section mb-3">';

    if ($remindersArray === []) {
        echo '<div class="alert alert-info text-center mt-3 mb-3">' . xlt('No Messages Found') . '</div>';
    } else {
        echo '<div class="card mt-3 mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">' . xlt('Message Results') . '</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="logTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>' . xlt('ID') . '</th>
                                    <th>' . xlt('Sent Date') . '</th>
                                    <th>' . xlt('From') . '</th>
                                    <th>' . xlt('To{{Destination}}') . '</th>
                                    <th>' . xlt('Patient') . '</th>
                                    <th>' . xlt('Message') . '</th>
                                    <th>' . xlt('Due Date') . '</th>
                                    <th>' . xlt('Processed Date') . '</th>
                                    <th>' . xlt('Processed By') . '</th>
                                </tr>
                            </thead>
                            <tbody>';

        foreach ($remindersArray as $reminderArray) {
            echo '<tr>
                    <td>' . text($reminderArray['messageID']) . '</td>
                    <td>' . text(oeFormatDateTime($reminderArray['sDate'])) . '</td>
                    <td>' . text($reminderArray['fromName']) . '</td>
                    <td>' . text($reminderArray['ToName']) . '</td>
                    <td>' . text($reminderArray['PatientName']) . '</td>
                    <td>' . text($reminderArray['message']) . '</td>
                    <td>' . text(oeFormatShortDate($reminderArray['dDate'])) . '</td>
                    <td>' . text(oeFormatDateTime($reminderArray['pDate'])) . '</td>
                    <td>' . text($reminderArray['processedByName']) . '</td>
                </tr>';
        }

        echo '</tbody>
            </table>
            </div>
        </div>
        </div>';
    }

    echo '</div>
        </div>';

    die;
}
?>
<html>
  <head>
    <?php Header::setupHeader(['datetime-picker']); ?>

    <script>
      $(function () {
        $("#submitForm").click(function(){
          // top.restoreSession(); --> can't use this as it negates this ajax refresh
          $.get("dated_reminders_log.php?"+$("#logForm").serialize(),
               function(data) {
                  $("#resultsDiv").html(data);
                    <?php
                    if (!$isAdmin) {
                        echo '$("select option").removeAttr("selected");';
                    }
                    ?>
                    return false;
               }
             )
          return false;
        });

        $('.datepicker').datetimepicker({
            <?php $datetimepicker_timepicker = false; ?>
            <?php $datetimepicker_showseconds = false; ?>
            <?php $datetimepicker_formatInput = true; ?>
            <?php require($GLOBALS['srcdir'] . '/js/xl/jquery-datetimepicker-2-5-4.js.php'); ?>
            <?php // can add any additional javascript settings to datetimepicker here; need to prepend first setting with a comma ?>
        });
      })
    </script>
</head>
<body>
    <div class="container">
    <!-- Required for the popup date selectors -->
        <div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
        <?php
        $allUsers = array();
        $uSQL = sqlStatement('SELECT id, fname, mname, lname FROM `users` WHERE `active` = 1 AND `facility_id` > 0 AND id != ?', array(intval($_SESSION['authUserID'])));
        for ($i = 0; $uRow = sqlFetchArray($uSQL); ++$i) {
            $allUsers[] = $uRow;
        }
        ?>
        <div class="row">
            <div class="col-12 mb-2">
                <h2 class="title">
                    <?php echo xlt('Dated Message Log'); ?>
                    <i id="show_hide" class="fa fa-eye-slash ml-2" data-toggle="tooltip" data-placement="top" title="<?php echo xla('Click to Hide Filters'); ?>"></i>
                </h2>
            </div>
            <div class="col-12 filter-section mb-3">
                <form method="get" id="logForm" onsubmit="return top.restoreSession()">
                    <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?php echo xlt('Filters') ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="section-header mb-2">
                                <h6 class="text-muted"><?php echo xlt('Message Date Range');?></h6>
                            </div>
                            <div class="form-group row">
                                <div class="col-12 col-md-6">
                                    <label class="col-form-label" for="sd"><?php echo xlt('Start Date') ?>:</label>
                                    <input id="sd" type="text" class='form-control datepicker' name="sd" value="" title='<?php echo attr(DateFormatRead('validateJS')) ?>'>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="col-form-label" for="ed"><?php echo xlt('End Date') ?>:</label>
                                    <input id="ed" type="text" class='form-control datepicker' name="ed" value="" title='<?php echo attr(DateFormatRead('validateJS')) ?>'>
                                </div>
                            </div>

                            <div class="section-header mt-4 mb-2">
                                <h6 class="text-muted"><?php echo xlt('Message Participants');?></h6>
                            </div>
                            <div class="form-group row">
                                <div class="col-12 col-md-6">
                                    <label class="col-form-label" for="sentBy">
                                        <?php echo xlt('Sent By');?>:
                                        <small class="text-muted"><?php echo xlt('Leave blank for all'); ?></small>
                                    </label>
                                    <select class="form-control" id="sentBy" name="sentBy[]" multiple="multiple">
                                        <option value="<?php echo attr(intval($_SESSION['authUserID'])); ?>"><?php echo xlt('Myself') ?></option>
                                        <?php
                                        if ($isAdmin) {
                                            foreach ($allUsers as $allUser) {
                                                echo '<option value="' . attr($allUser['id']) . '">' . text($allUser['fname'] . ' ' . $allUser['mname'] . ' ' . $allUser['lname']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        <?php echo xlt('([ctrl] + click or [cmd] + click on Mac to select multiple)'); ?>
                                    </small>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="col-form-label" for="sentTo">
                                        <?php echo xlt('Sent To');?>:
                                        <small class="text-muted"><?php echo xlt('Leave blank for all'); ?></small>
                                    </label>
                                    <select class="form-control" id="sentTo" name="sentTo[]" multiple="multiple">
                                        <option value="<?php echo attr(intval($_SESSION['authUserID'])); ?>"><?php echo xlt('Myself') ?></option>
                                        <?php
                                        if ($isAdmin) {
                                            foreach ($allUsers as $allUser) {
                                                echo '<option value="' . attr($allUser['id']) . '">' . text($allUser['fname'] . ' ' . $allUser['mname'] . ' ' . $allUser['lname']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        <?php echo xlt('([ctrl] + click or [cmd] + click on Mac to select multiple)'); ?>
                                    </small>
                                </div>
                            </div>

                            <div class="section-header mt-4 mb-2">
                                <h6 class="text-muted"><?php echo xlt('Message Status');?></h6>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input type="checkbox" class="custom-control-input" name="processed" id="processed">
                                    <label class="custom-control-label" for="processed"><?php echo xlt('Processed') ?></label>
                                </div>
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input type="checkbox" class="custom-control-input" name="pending" id="pending">
                                    <label class="custom-control-label" for="pending"><?php echo xlt('Pending') ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="button" id="submitForm" class="btn btn-primary">
                                <i class="fa fa-refresh mr-1"></i><?php echo xlt('Apply Filters') ?>
                            </button>
                            <button type="reset" class="btn btn-secondary ml-1">
                                <i class="fa fa-eraser mr-1"></i><?php echo xlt('Reset') ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-12">
                <div id="resultsDiv"></div>
            </div>
        </div>
    </div><!--end of container div-->
    <script>
        $('#show_hide').click(function() {
            var elementTitle = $('#show_hide').prop('title');
            var hideTitle = '<?php echo xla('Click to Hide Filters'); ?>';
            var showTitle = '<?php echo xla('Click to Show Filters'); ?>';

            $('.filter-section').toggle('1000');
            $(this).toggleClass('fa-eye-slash fa-eye');
            if (elementTitle == hideTitle) {
                elementTitle = showTitle;
            } else if (elementTitle == showTitle) {
                elementTitle = hideTitle;
            }
            $('#show_hide').prop('title', elementTitle);
        });
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>
