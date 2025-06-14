<?php

declare(strict_types=1);

/**
 * interface/forms/group_attendance/functions.php functions for form
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Shachar Zilbershlag <shaharzi@matrix.co.il>
 * @author    Amiel Elboim <amielel@matrix.co.il>
 * @copyright Copyright (c) 2016 Shachar Zilbershlag <shaharzi@matrix.co.il>
 * @copyright Copyright (c) 2016 Amiel Elboim <amielel@matrix.co.il>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(dirname(__FILE__) . "/../../../library/api.inc.php");
require_once(dirname(__FILE__) . "/../../../library/forms.inc.php");
require_once(dirname(__FILE__) . "/../../../library/patient_tracker.inc.php");

/**
 * Returns form_id of an existing attendance form for group encounter (if one already exists);
 * @param $encounter
 * @param $group_id
 * @return array|null
 */
function get_form_id_of_existing_attendance_form($encounter, $group_id)
{
    $sql = "SELECT form_id FROM forms WHERE encounter = ? AND formdir = 'group_attendance' AND therapy_group_id = ? AND deleted = 0;";
    return sqlQuery($sql, array($encounter, $group_id));
}

/**
 * Inserts participant data into DB
 * @param $form_id
 * @param $therapy_group
 * @param $group_encounter_data
 * @param $appt_data
 */
function participant_insertions($form_id, $therapy_group, array $group_encounter_data, $appt_data): void
{
    $patientData = $_POST['patientData'];
    foreach ($patientData as $pid => $patient) {
        //Insert into therapy_groups_participants_attendance table
        insert_into_tgpa_table($form_id, $pid, $patient);

        //Check if to create appt and encounter for each patient (if has certain status and 'bottom' submit was pressed, not 'add_patient' submit).
        $create_for_patient = if_to_create_for_patient($patient['status']);
        if ($create_for_patient) {
            //Create encounter for each patient
            $encounter_id = insert_patient_encounter($pid, $therapy_group, $group_encounter_data['date'], $patient, $appt_data['pc_aid']);

            //Create appt for each patient (if there is appointment connected to encounter)
            if (!empty($appt_data)) {
                $pc_eid = insert_patient_appt($pid, $therapy_group, $appt_data['pc_aid'], $appt_data['pc_eventDate'], $appt_data['pc_startTime'], $patient);
                manage_tracker_status($appt_data['pc_eventDate'], $appt_data['pc_startTime'], $pc_eid, $pid, $appt_data['pc_aid'], $patient['status'], $appt_data['pc_room'], $encounter_id);
            }
        }
    }
}

/**
 * Inserts data into therapy_groups_participant_attendance table
 * @param $form_id
 * @param $pid
 * @param $participantData
 */
function insert_into_tgpa_table($form_id, $pid, array $participantData): void
{

    $sql_for_table_tgpa = "INSERT INTO therapy_groups_participant_attendance (form_id, pid, meeting_patient_comment, meeting_patient_status) " .
        "VALUES(?,?,?,?);";
    sqlStatement($sql_for_table_tgpa, array($form_id, $pid, $participantData['comment'], $participantData['status']));
}

/**
 * Creates an appointment for patient from attendance form
 * @param $pid
 * @param $gid
 * @param $pc_aid
 * @param $pc_eventDate
 * @param $pc_startTime
 * @param $participantData
 */
function insert_patient_appt($pid, $gid, $pc_aid, $pc_eventDate, $pc_startTime, array $participantData)
{
    $select_sql = "SELECT pc_eid FROM openemr_postcalendar_events WHERE pc_pid = ? AND pc_gid = ? AND pc_eventDate = ? AND pc_startTime = ?;";
    $recordset = sqlStatement($select_sql, array($pid, $gid, $pc_eventDate, $pc_startTime));
    $result_array = sqlFetchArray($recordset);
    if ($result_array) {
        $insert_sql = "UPDATE openemr_postcalendar_events SET pc_apptstatus = ? WHERE pc_eid = ?;";
        sqlStatement($insert_sql, array($participantData['status'], $result_array['pc_eid']));
        return $result_array['pc_eid'];
    } else {
        $insert_sql =
            "INSERT INTO openemr_postcalendar_events " .
            "(pc_catid, pc_aid, pc_pid, pc_gid, pc_title, pc_informant, pc_eventDate, pc_recurrspec, pc_startTime, pc_sharing, pc_apptstatus) " .
            "VALUES (?, ?, ?, ?, 'Group Therapy', 1, ?, ?, ?, 0, ?); ";
        $recurrspec = 'a:6:{s:17:"event_repeat_freq";s:1:"0";s:22:"event_repeat_freq_type";s:1:"0";s:19:"event_repeat_on_num";s:1:"1";s:19:"event_repeat_on_day";s:1:"0";s:20:"event_repeat_on_freq";s:1:"0";s:6:"exdate";s:0:"";}';
        $sqlBindArray = array();
        $sqlBindArray[] = get_groups_cat_id();
        $sqlBindArray[] = $pc_aid;
        $sqlBindArray[] = $pid;
        $sqlBindArray[] = $gid;
        $sqlBindArray[] = $pc_eventDate;
        $sqlBindArray[] = $recurrspec;
        $sqlBindArray[] = $pc_startTime;
        $sqlBindArray[] = $participantData['status'];
        return sqlInsert($insert_sql, $sqlBindArray);
    }
}

/**
 * Creates an encounter for patient from attendance form
 * @param $pid
 * @param $gid
 * @param $group_encounter_date
 * @param $participantData
 * @param $pc_aid
 */
function insert_patient_encounter($pid, $gid, $group_encounter_date, array $participantData, $pc_aid)
{
    $select_sql = "SELECT id, encounter FROM form_encounter WHERE pid = ? AND external_id = ? AND pc_catid = ? AND date = ?; ";
    $recordset = sqlStatement($select_sql, array($pid, $gid, get_groups_cat_id(), $group_encounter_date));
    $result_array = sqlFetchArray($recordset);
    if ($result_array) {
        $insert_sql = "UPDATE form_encounter SET reason = ? WHERE id = ?;";
        sqlStatement($insert_sql, array($participantData['comment'], $result_array['id']));
        return $result_array['encounter'];
    } else {
        $insert_encounter_sql =
            "INSERT INTO form_encounter (date, reason, pid, encounter, pc_catid, provider_id, external_id) " .
            "VALUES (?, ?, ?, ?, ?, ?, ?);";
        $enc_id = generate_id();
        $sqlBindArray = array();
        $user = (is_null($pc_aid)) ? $_SESSION['authUserID'] : $pc_aid;
        $sqlBindArray[] = $group_encounter_date;
        $sqlBindArray[] = $participantData['comment'];
        $sqlBindArray[] = $pid;
        $sqlBindArray[] = $enc_id;
        $sqlBindArray[] = get_groups_cat_id();
        $sqlBindArray[] = $user;
        $sqlBindArray[] = $gid;
        $form_id = sqlInsert($insert_encounter_sql, $sqlBindArray);

        global $userauthorized;

        addForm($enc_id, "New Patient Encounter", $form_id, "newpatient", $pid, $userauthorized, $group_encounter_date, '', '', null);

        return $enc_id;
    }
}

/**
 * If the group encounter was created in relation to a group appointment, fetches the appointment relevant data.
 * @param $encounter_id
 * @return array
 */
function get_appt_data($encounter_id)
{
    $sql =
        "SELECT ope.pc_aid, ope.pc_eventDate, ope.pc_startTime, ope.pc_room FROM form_groups_encounter as fge " .
        "JOIN openemr_postcalendar_events as ope ON fge.appt_id = ope.pc_eid " .
        "WHERE fge.encounter = ?;";
    return sqlQuery($sql, array($encounter_id));
}

function getGroupAttendance($form_id): array
{
    $participants_sql =  "SELECT tgpa.*, p.fname, p.lname " .
        "FROM therapy_groups_participant_attendance as tgpa " .
        "JOIN patient_data as p ON tgpa.pid = p.id " .
        "WHERE tgpa.form_id = ?;";
    $recordset = sqlStatement($participants_sql, array($form_id));
    $participants = array();
    while ($p = sqlFetchArray($recordset)) {
        $participants[] = $p;
    }

    return $participants;
}

/**
 * Gets group encounter data
 * @param $encounter_id
 * @return array
 */
function get_group_encounter_data($encounter_id)
{
    $sql = "SELECT date FROM form_groups_encounter WHERE encounter = ?";
    return sqlQuery($sql, array($encounter_id));
}

/**
 * Checks if to create appointment and encounter for patient himself based on the status in the attendance form.
 * [Note: `toggle_setting_1` in table `list_options` is used as a flag to know the statuses for which an appt or encounter should be created.]
 * @param $status
 * @return bool
 */
function if_to_create_for_patient($status)
{
    $sql = "SELECT toggle_setting_1 FROM list_options WHERE list_id = 'attendstat' AND toggle_setting_1 = 1 AND option_id = ?";
    return sqlQuery($sql, array($status));
}

function getAttendanceStatus($status)
{
    $sql = "SELECT title FROM list_options WHERE list_id = 'attendstat' AND option_id = ?";
    $result = sqlQuery($sql, array($status));
    return $result['title'];
}

/**
 * Returns the number after the greatest id number in the table
 * @param $table
 * @return int
 */
function largest_id_plus_one($table): int|float
{
    $maxId = largest_id($table);

    return $maxId ? $maxId + 1 : 1;
}

/**
 * Returns greatest id number in the table
 * @param $table
 * @return mixed
 */
function largest_id($table)
{
    $recordset = sqlStatement("SELECT MAX(id) as largestId FROM `" . escape_table_name($table) . "`");
    $getMaxid = sqlFetchArray($recordset);
    return $getMaxid['largestId'];
}


function get_groups_cat_id()
{
    $result = sqlQuery('SELECT pc_catid FROM openemr_postcalendar_categories WHERE pc_cattype = 3 AND pc_active = 1 LIMIT 1');
    return empty($result) ? 0 : $result['pc_catid'];
}
