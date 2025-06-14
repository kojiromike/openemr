<?php

declare(strict_types=1);

use OpenEMR\Common\ORDataObject\ORDataObject;

define("EVENT_VEHICLE", 1);
define("EVENT_WORK_RELATED", 2);
define("EVENT_SLIP_FALL", 3);
define("EVENT_OTHER", 4);


/**
 * class FormHpTjePrimary
 *
 */
class FormHpTjePrimary extends ORDataObject
{
    /**
     *
     * @access public
     */


    /**
     *
     * static
     */
    public $event_array = array("","Vehicular Accident","Work Related Accident","Slip & Fall","Other");

    /**
     *
     * @access private
     */

    public $id;

    public $referred_by;

    public $complaints;

    /**
     * @var string
     */
    public $date_of_onset;

    public $event;

    public $event_description;

    public $prior_symptoms;

    public $aggravated_symptoms;

    public $comments;

    /**
     * @var string
     */
    public $date;

    public $teeth_sore_number;

    public $teeth_mobile_number;

    public $teeth_fractured_number;

    public $teeth_avulsed_number;

    public $precipitating_factors_other_text;

    /**
     * @var array{}
     */
    public $checks;

    public $pid;

    public $activity;

    public $history;

    public $previous_accidents;

    /**
     * Constructor sets all Form attributes to their default value
     */

    public function __construct($id = "")
    {
        parent::__construct();

        if (is_numeric($id)) {
            $this->id = $id;
        } else {
            $id = "";
        }

        $this->date = date("Y-m-d H:i:s");
        $this->date_of_onset = date("Y-m-d");
        $this->_table = "form_hp_tje_primary";
        $this->checks = array();
        $this->activity = 1;
        $this->pid = $GLOBALS['pid'];
        if ($id != "") {
            $this->populate();
        }
    }

    public function populate(): void
    {
        parent::populate();

        $sql = "SELECT name from form_hp_tje_checks where foreign_id = ?";
        $results = sqlQ($sql, [$this->id]);

        while ($row = sqlFetchArray($results)) {
            $this->checks[] = $row['name'];
        }


        $sql = "SELECT doctor,specialty,tx_rendered,effectiveness,date from form_hp_tje_history where foreign_id = ?";
        $results = sqlQ($sql, [$this->id]);

        while ($row = sqlFetchArray($results)) {
            $this->history[] = $row;
        }

        $sql = "SELECT nature_of_accident,injuries,date from form_hp_tje_previous_accidents where foreign_id = ?";
        $results = sqlQ($sql, [$this->id]);

        while ($row = sqlFetchArray($results)) {
            $this->previous_accidents[] = $row;
        }
    }

    public function toString($html = false): string
    {
        $string = "\n" . "ID: " . $this->id . "\n";
        return $html ? nl2br($string) : $string;
    }

    public function set_id($id): void
    {
        if (!empty($id) && is_numeric($id)) {
            $this->id = $id;
        }
    }

    public function get_id()
    {
        return $this->id;
    }

    public function set_pid($pid): void
    {
        if (!empty($pid) && is_numeric($pid)) {
            $this->pid = $pid;
        }
    }

    public function get_pid()
    {
        return $this->pid;
    }

    public function set_activity($tf): void
    {
        if (!empty($tf) && is_numeric($tf)) {
            $this->activity = $tf;
        }
    }

    public function get_activity()
    {
        return $this->activity;
    }

    public function get_date_of_onset_y(): string
    {
        $ymd = explode("-", $this->date_of_onset);
        return $ymd[0];
    }

    public function set_date_of_onset_y($year): void
    {
        if (is_numeric($year)) {
            $ymd = explode("-", $this->date_of_onset);
            $ymd[0] = $year;
            $this->date_of_onset = $ymd[0] . "-" . $ymd[1] . "-" . $ymd[2];
        }
    }

    public function get_date_of_onset_m(): string
    {
        $ymd = explode("-", $this->date_of_onset);
        return $ymd[1];
    }

    public function set_date_of_onset_m($month): void
    {
        if (is_numeric($month)) {
            $ymd = explode("-", $this->date_of_onset);
            $ymd[1] = $month;
            $this->date_of_onset = $ymd[0] . "-" . $ymd[1] . "-" . $ymd[2];
        }
    }

    public function get_date_of_onset_d(): string
    {
        $ymd = explode("-", $this->date_of_onset);
        return $ymd[2];
    }

    public function set_date_of_onset_d($day): void
    {
        if (is_numeric($day)) {
            $ymd = explode("-", $this->date_of_onset);
            $ymd[2] = $day;
            $this->date_of_onset = $ymd[0] . "-" . $ymd[1] . "-" . $ymd[2];
        }
    }

    public function get_date_of_onset()
    {
        return $this->date_of_onset;
    }

    public function set_date_of_onset($date)
    {
        return $this->date_of_onset = $date;
    }

    public function set_event($event): void
    {
        if (!is_numeric) {
            return;
        }

        $this->event = $event;
    }

    public function get_event()
    {
        return $this->event;
    }

    public function set_referred_by($string): void
    {
        $this->referred_by = $string;
    }

    public function get_referred_by()
    {
        return $this->referred_by;
    }

    public function set_complaints($string): void
    {
        $this->complaints = $string;
    }

    public function get_complaints()
    {
        return $this->complaints;
    }

    public function set_prior_symptoms($string): void
    {
        $this->prior_symptoms = $string;
    }

    public function get_prior_symptoms()
    {
        return $this->prior_symptoms;
    }

    public function set_aggravated_symptoms($string): void
    {
        $this->aggravated_symptoms = $string;
    }

    public function get_aggravated_symptoms()
    {
        return $this->aggravated_symptoms;
    }

    public function set_comments($string): void
    {
        $this->comments = $string;
    }

    public function get_comments()
    {
        return $this->comments;
    }

    public function set_event_description($description): void
    {
        $this->event_description = $description;
    }

    public function get_event_description()
    {
        return $this->event_description;
    }

    public function get_teeth_sore_number()
    {
        return $this->teeth_sore_number;
    }

    public function set_teeth_sore_number($num): void
    {
        $this->teeth_sore_number = $num;
    }

    public function get_teeth_mobile_number()
    {
        return $this->teeth_mobile_number;
    }

    public function set_teeth_mobile_number($num): void
    {
        $this->teeth_mobile_number = $num;
    }

    public function get_teeth_fractured_number()
    {
        return $this->teeth_fractured_number;
    }

    public function set_teeth_fractured_number($num): void
    {
        $this->teeth_fractured_number = $num;
    }

    public function get_teeth_avulsed_number()
    {
        return $this->teeth_avulsed_number;
    }

    public function set_teeth_avulsed_number($num): void
    {
        $this->teeth_avulsed_number = $num;
    }

    public function get_precipitating_factors_other_text()
    {
        return $this->precipitating_factors_other_text;
    }

    public function set_precipitating_factors_other_text($string): void
    {
        $this->precipitating_factors_other_text = $string;
    }

    public function get_checks()
    {
        return $this->checks;
    }

    public function set_checks($check_array): void
    {
        $this->checks = $check_array;
    }

    public function get_history()
    {
        return $this->history;
    }

    public function set_history($array): void
    {
        $this->history = $array;
    }

    public function get_previous_accidents()
    {
        return $this->previous_accidents;
    }

    public function set_previous_accidents($array): void
    {
        $this->previous_accidents = $array;
    }

    public function get_date()
    {
        return $this->date;
    }


    public function persist(): void
    {

        parent::persist();
        if (is_numeric($this->id) && !empty($this->checks)) {
            $sql = "delete FROM form_hp_tje_checks where foreign_id = ?";
            sqlQuery($sql, [$this->id]);
            foreach ($this->checks as $check) {
                if (!empty($check)) {
                    $sql = "INSERT INTO form_hp_tje_checks set foreign_id=?, name = ?";
                    sqlQuery($sql, [$this->id, $check]);
                    //echo "$sql<br />";
                }
            }
        }

        if (is_numeric($this->id) && !empty($this->history)) {
            $sql = "delete FROM form_hp_tje_history where foreign_id = ?";
            sqlQuery($sql, [$this->id]);
            foreach ($this->history as $history) {
                if (!empty($history)) {
                    $sql = "INSERT INTO form_hp_tje_history set foreign_id=?"
                    . ", doctor = ?"
                    . ", specialty = ?"
                    . ", tx_rendered = ?"
                    . ", effectiveness = ?"
                    . ", date = ?";
                    sqlQuery(
                        $sql,
                        [
                            $this->id,
                            $history['doctor'],
                            $history['specialty'],
                            $history['tx_rendered'],
                            $history['effectiveness'],
                            $history['date']
                        ]
                    );
                    //echo "$sql<br />";
                }
            }
        }

        if (is_numeric($this->id) && !empty($this->previous_accidents)) {
            $sql = "delete FROM form_hp_tje_previous_accidents where foreign_id = ?";
            sqlQuery($sql, [$this->id]);

            foreach ($this->previous_accidents as $previou_accident) {
                if (!empty($previou_accident)) {
                    $sql = "INSERT INTO form_hp_tje_previous_accidents set foreign_id=?" .
                    ", nature_of_accident = ?"
                    . ", injuries = ?"
                    . ", date = ?";

                    sqlQuery(
                        $sql,
                        [
                            $this->id,
                            $previou_accident['nature_of_accident'],
                            $previou_accident['injuries'],
                            $previou_accident['date']
                        ]
                    );
                    //echo "$sql<br />";
                }
            }
        }
    }

    public function _form_layout(): array
    {
        $a = array();

        //at is array temp
        //a is array
        //a_bottom is the textually identified rows of a checkbox group

        $at[1]['headache_facial_pain_frontal']  =  "Frontal";
        $at[1]['headache_facial_pain_frontal_l']    =  "L";
        $at[1]['headache_facial_pain_frontal_r']    =  "R";
        $at[1]['headache_facial_pain_temporal']     =  "Temporal";
        $at[1]['headache_facial_pain_temporal_l']   =  "L";
        $at[1]['headache_facial_pain_temporal_r']   =  "R";
        $at[1]['headache_facial_pain_retro_orbtal']     =  "Retro-Orbital";
        $at[1]['headache_facial_pain_retro_orbtal_l']   =  "L";
        $at[1]['headache_facial_pain_retro_orbtal_r']   =  "R";
        $at[1]['headache_facial_pain_zygoma']       =  "Zygoma";
        $at[1]['headache_facial_pain_zygoma_l']     =  "L";
        $at[1]['headache_facial_pain_zygoma_r']     =  "R";

        $at[2]['headache_facial_pain_crown']        =  "Crown";
        $at[2]['headache_facial_pain_crown_l']  =  "L";
        $at[2]['headache_facial_pain_crown_r']  =  "R";
        $at[2]['headache_facial_pain_occipital']    =  "Occipital";
        $at[2]['headache_facial_pain_occipital_l'] =  "L";
        $at[2]['headache_facial_pain_occipital_r'] =  "R";
        $at[2]['headache_facial_pain_mastoid']  =  "Mastoid";
        $at[2]['headache_facial_pain_mastoid_l']    =  "L";
        $at[2]['headache_facial_pain_mastoid_r']    =  "R";
        $at[2]['headache_facial_pain_jaw_muscles']      =  "Jaw Muscles";
        $at[2]['headache_facial_pain_jaw_muscles_l']    =  "L";
        $at[2]['headache_facial_pain_jaw_muscles_r']    =  "R";

        $a_bottom = $this->_name_rows("headache_facial_pain", array("onset","intensity","duration","frequency","quality of pain","aggravation","occurance"));
        $a['Headaches / Facial Pain'] = array_merge($at, $a_bottom);

        $at = array();
        $a_bottom = array();
        $at[1]['neck_pain_anterior']    =  "Anterior";
        $at[1]['neck_pain_anterior_l']  =  "L";
        $at[1]['neck_pain_anterior_r']  =  "R";
        $at[1]['neck_pain_posterior']   =  "Posterior";
        $at[1]['neck_pain_posterior_l']     =  "L";
        $at[1]['neck_pain_posterior_r']     =  "R";
        $at[1]['neck_pain_radiating_to_head']   =  "Radiating to Head";
        $at[1]['neck_pain_radiating_to_head_l']     =  "L";
        $at[1]['neck_pain_radiating_to_head_r']     =  "R";

        $a_bottom = $this->_name_rows("neck_pain", array("onset","intensity","duration","frequency","quality of pain","aggravation","occurance"));
        $a['Neck Pain'] = array_merge($at, $a_bottom);

        $at = array();
        $a_bottom = array();
        $at[1]['shoulder_back_or_chest_shoulder']   =  "Shoulder";
        $at[1]['shoulder_back_or_chest_shoulder_l']     =  "L";
        $at[1]['shoulder_back_or_chest_shoulder_r']     =  "R";
        $at[1]['shoulder_back_or_chest_back_upper']     =  "Back/Upper";
        $at[1]['shoulder_back_or_chest_back_upper_l']   =  "L";
        $at[1]['shoulder_back_or_chest_back_upper_r']   =  "R";
        $at[1]['shoulder_back_or_chest_back_lower']     =  "Back/Lower";
        $at[1]['shoulder_back_or_chest_back_lower_l']   =  "L";
        $at[1]['shoulder_back_or_chest_back_lower_r']   =  "R";
        $at[1]['shoulder_back_or_chest_chest']  =  "Chest";
        $at[1]['shoulder_back_or_chest_chest_l']    =  "L";
        $at[1]['shoulder_back_or_chest_chest_r']    =  "R";
        $at[1]['shoulder_back_or_radiating_to_arm_hand']    =  "Radiating to arm/hand";
        $at[1]['shoulder_back_or_radiating_to_arm_hand_l']  =  "L";
        $at[1]['shoulder_back_or_radiating_to_arm_hand_r']  =  "R";

        $a_bottom = $this->_name_rows("shoulder_back_or_chest", array("onset","intensity","duration","frequency","quality of pain","aggravation","occurance"));
        $a['Shoulder, Back or Chest Pain'] = array_merge($at, $a_bottom);

        $at = array();
        $a_bottom = array();
        $at[1]['ear_symptoms_pain']     =  "Pain";
        $at[1]['ear_symptoms_pain_l']   =  "L";
        $at[1]['ear_symptoms_pain_r']   =  "R";
        $at[1]['ear_symptoms_tinnitus']     =  "Tinnitus";
        $at[1]['ear_symptoms_tinnitus_l']   =  "L";
        $at[1]['ear_symptoms_tinnitus_r']   =  "R";
        $at[1]['ear_symptoms_stuffiness']   =  "Stuffiness";
        $at[1]['ear_symptoms_stuffiness_l']     =  "L";
        $at[1]['ear_symptoms_stuffiness_r']     =  "R";
        $at[1]['ear_symptoms_hearing_loss']     =  "Hearing Loss";
        $at[1]['ear_symptoms_hearing_loss_l']   =  "L";
        $at[1]['ear_symptoms_hearing_loss_r']   =  "R";

        $a_bottom = $this->_name_rows("ear_symptoms", array("onset","intensity","duration","frequency","quality of pain","aggravation","occurance"));
        $a['Ear Symptoms'] = array_merge($at, $a_bottom);

        $at = array();
        $a_bottom = array();
        $at[1]['eye_symptoms_pain']     =  "Pain";
        $at[1]['eye_symptoms_pain_l']   =  "L";
        $at[1]['eye_symptoms_pain_r']   =  "R";
        $at[1]['eye_symptoms_burning']  =  "Burning";
        $at[1]['eye_symptoms_burning_l']    =  "L";
        $at[1]['eye_symptoms_burning_r']    =  "R";
        $at[1]['eye_symptoms_tearing']  =  "Tearing";
        $at[1]['eye_symptoms_tearing_l']    =  "L";
        $at[1]['eye_symptoms_tearing_r']    =  "R";
        $at[1]['eye_symptoms_change_in_vision']     =  "Change in Vision";
        $at[1]['eye_symptoms_change_in_vision_l']   =  "L";
        $at[1]['eye_symptoms_change_in_vision_r']   =  "R";
        $at[1]['eye_symptoms_bluriness']    =  "Bluriness";
        $at[1]['eye_symptoms_bluriness_l']  =  "L";
        $at[1]['eye_symptoms_bluriness_r']  =  "R";

        $a_bottom = $this->_name_rows("eye_symptoms", array("onset","intensity","duration","frequency","quality of pain","aggravation","occurance"));
        $a['Eye Symptoms'] = array_merge($at, $a_bottom);

        $at = array();
        $a_bottom = array();
        $at[1]['teeth_sore']    =  "Sore";
        $at[1]['teeth_mobile']  =  "Mobile";
        $at[1]['teeth_fractured']   =  "Fractured";
        $at[1]['teeth_avulsed']     =  "Avulsed";
        //special actions are included for teeth in the template

        $a_bottom = $this->_name_rows("teeth", array("onset","intensity","duration","frequency","quality of pain","aggravation","occurance"));
        $a['Teeth'] = array_merge($at, $a_bottom);

        $a['Change in Bite'] = $this->_name_rows("change_in_bite", array("onset","intensity"));

        $at = array();
        $a_bottom = array();
        $at[1]['tmj_pain_l']    =  "L";
        $at[1]['tmp_pain_r']    =  "R";

        $a_bottom = $this->_name_rows("tmj_pain", array("onset","intensity","duration","frequency","quality of pain","aggravation","occurance"));
        $a['TMJ Pain'] = array_merge($at, $a_bottom);

        $at = array();
        $a_bottom = array();
        $at[1]['tmj_clicking_crepitation_clicking']     =  "Clicking";
        $at[1]['tmj_clicking_crepitation_clicking_l']   =  "L";
        $at[1]['tmj_clicking_crepitation_clicking_r']   =  "R";
        $at[1]['tmj_clicking_crepitation_crepitation']  =  "Crepitation";
        $at[1]['tmj_clicking_crepitation_crepitation_l']    =  "L";
        $at[1]['tmj_clicking_crepitation_crepitation_r']    =  "R";

        $a_bottom = $this->_name_rows("tmj_clicking_crepitation", array("onset","intensity","frequency","aggravation"));
        $a['TMJ Clicking / Crepitation'] = array_merge($at, $a_bottom);

        $at = array();
        $a_bottom = array();
        $at[1]['tmj_catching_locking_catching']     =  "Catching";
        $at[1]['tmj_catching_locking_catching_l']   =  "L";
        $at[1]['tmj_catching_locking_catching_r']   =  "R";
        $at[1]['tmj_catching_locking_locking_closed']   =  "Locking Closed";
        $at[1]['tmj_catching_locking_locking_closed_l']     =  "L";
        $at[1]['tmj_catching_locking_locking_closed_r']     =  "R";
        $at[1]['tmj_catching_locking_locking_open']     =  "Locking Open";
        $at[1]['tmj_catching_locking_locking_open_l']   =  "L";
        $at[1]['tmj_catching_locking_locking_open_r']   =  "R";

        $a_bottom = $this->_name_rows("tmj_catching_locking", array("onset","intensity","frequency","aggravation"));
        $a['TMJ Catching / Locking'] = array_merge($at, $a_bottom);

        $at = array();
        $a_bottom = array();
        $at[1]['tmj_chewing_swallowing_difficult']  =  "Difficult";
        $at[1]['tmj_chewing_swallowing_painful']    =  "Painful";

        $a_bottom = $this->_name_rows("tmj_chewing_swallowing", array("onset","intensity"));
        $a['TMJ Chewing / Swallowing'] = array_merge($at, $a_bottom);

        $at = array();
        $a_bottom = array();
        $at[1]['sinus_pain']    =  "Pain";
        $at[1]['sinus_pressure']    =  "Pressure";
        $at[1]['sinus_drainage']    =  "Drainage";
        $at[1]['sinus_infection']   =  "Infection";

        $a_bottom = $this->_name_rows("sinus", array("onset"));
        $a['Sinus'] = array_merge($at, $a_bottom);

        $at = array();
        $a_bottom = array();
        $at[1]['migraine_headache_aura']    =  "Aura";
        $at[1]['migraine_headache_nausea']  =  "Nausea";
        $at[1]['migraine_headache_relieved_by_vascular_drugs']  =  "Relieved by Vascular Drugs";
        $at[1]['migraine_headache_vertigo']     =  "Vertigo";

        $a_bottom = $this->_name_rows("migraine_headache", array("onset","intensity","duration","frequency","aggravation"));
        $a['Migraine Headache'] = array_merge($at, $a_bottom);

        $at = array();
        $a_bottom = array();
        $at[1]['dizziness_loss_of_balance']     =  "Loss of Balance";
        $at[1]['dizziness_vertigo']     =  "Vertigo";
        $at[1]['dizziness_spatial_distortion']  =  "Spatial Distortion";
        $at[1]['dizziness_syncope']     =  "Syncope";
        $at[1]['dizziness_nausea']  =  "Nausea";

        $a_bottom = $this->_name_rows("dizziness", array("onset","intensity","duration","frequency","aggravation"));
        $a['Dizziness'] = array_merge($at, $a_bottom);

        $at = array();
        $a_bottom = array();
        $at[1]['neuralgia_tic_doloreau']    =  "Tic Doloreau";
        $at[1]['neuralgia_tic_doloreau_l']  =  "L";
        $at[1]['neuralgia_tic_doloreau_r']  =  "R";
        $at[1]['neuralgia_parasthesis']     =  "Parasthesis";
        $at[1]['neuralgia_parasthesis_l']   =  "L";
        $at[1]['neuralgia_parasthesis_r']   =  "R";
        $at[1]['neuralgia_numbness']    =  "Numbness";
        $at[1]['neuralgia_numbness_l']  =  "L";
        $at[1]['neuralgia_numbness_r']  =  "R";

        $at[2]['neuralgia_cold_spots']  =  '"Cold Spots"';
        $at[2]['neuralgia_cold_spots_l']    =  "L";
        $at[2]['neuralgia_cold_spots_r']    =  "R";
        $at[2]['neuralgia_burning_tungue_lips_mouth']   =  "Burning Lips/Tongue/Mouth";
        $at[2]['neuralgia_burning_tungue_lips_mouth_l']     =  "L";
        $at[2]['neuralgia_burning_tungue_lips_mouth_r']     =  "R";
        $at[2]['neuralgia_hyperalgesia']    =  "Hyperalgesia";
        $at[2]['neuralgia_hyperalgesia_l']  =  "L";
        $at[2]['neuralgia_hyperalgesia_r']  =  "R";

        $a_bottom = $this->_name_rows("neuralgia", array("onset","intensity","duration","frequency","aggravation"));
        $a['Neuralgia'] = array_merge($at, $a_bottom);

        $at = array();
        $a_bottom = array();
        $at[1]['history_digenerative_joint_disease']    =  "Degenerative Joint Disease";
        $at[1]['history_rheumatoid_arthritis']  =  "Rheumatoid Arthritis";
        $at[1]['history_psioratic_arthritis']   =  "Psioratic Arthiritis";

        $at[2]['history_lupus_erythmatosis']    =  "Lupus Erythmatosis";
        $at[2]['history_scleroderma']   =  "Scleroderma";
        $at[2]['history_other']     =  "Other";

        $a['History'] = $at;

        $at = array();
        $a_bottom = array();
        $at[1]['precipitating_factors_direct_trauma']   =  "Direct Trauma";
        $at[1]['precipitating_factors_airbag']  =  "Airbag";
        $at[1]['precipitating_factors_whiplash']    =  "Whiplash";
        $at[1]['precipitating_factors_biting_on_foreign_object']    =  "Biting on Foreign Object";

        $at[2]['precipitating_factors_intubation']  =  "Intubation";
        $at[2]['precipitating_factors_forced_hypertranslation']     =  "Forced Hypertranslation";
        $at[2]['precipitating_factors_medication']  =  "Medication (Phenothiazines,etc.)";
        $at[2]['precipitating_factors_other']   =  "Other";

        $a['Precipitating Factors'] = $at;

        $at = array();
        $at[1]['predisposing_factors_previous_injury_problem']  =  "Previous Injury/Problem";
        $at[1]['predisposing_factors_ligament_laxity']  =  "Ligament Laxity";
        $at[1]['predisposing_factors_deep_bite']    =  "Deep Bite";
        $at[1]['predisposing_factors_midline_division']     =  "Midline Division";

        $at[2]['predisposing_factors_loss_of_posterior_support']    =  "Loss of Posterior Support";
        $at[2]['predisposing_factors_mandibular_retrusion']     =  "Mandibular Retrusion";
        $at[2]['predisposing_factors_occlusal_alterations']     =  "Occlusal Alterations";
        $at[2]['predisposing_factors_clenching_bruxing']    =  "Clenching/Bruxing";

        $a['Predisposing Factors'] = $at;

        $at = array();
        $at[1]['perpetuating_factors_previous_injury_problem']  =  "Previous Injury/Problem";
        $at[1]['perpetuating_factors_ligament_laxity']  =  "Ligament Laxity";
        $at[1]['perpetuating_factors_deep_bite']    =  "Deep Bite";
        $at[1]['perpetuating_factors_midline_division']     =  "Midline Division";

        $at[2]['perpetuating_factors_loss_of_posterior_support']    =  "Loss of Posterior Support";
        $at[2]['perpetuating_factors_mandibular_retrusion']     =  "Mandibular Retrusion";
        $at[2]['perpetuating_factors_occlusal_alterations']     =  "Occlusal Alterations";
        $at[2]['perpetuating_factors_clenching_bruxing']    =  "Clenching/Bruxing";

        $a['Perpetuating Factors'] = $at;

        return $a;
    }

    /**
     * @return array{}|array{Occurance?: non-empty-array<non-falsy-string, ('At Walking' | 'Evening' | 'Mid Day' | 'Variable')>, Aggravation?: non-empty-array<non-falsy-string, ('Chewing' | 'Clenching' | 'Physical Activity' | 'Speaking')>, 'Quality of Pain'?: non-empty-array<non-falsy-string, ('Aching' | 'Deep' | 'Dull' | 'Triggered')>, Frequency?: non-empty-array<non-falsy-string, ('1/Week' | '2-3/Week' | 'Daily' | 'No Pattern')>, Duration?: non-empty-array<non-falsy-string, ('Constant' | 'Days' | 'Hours' | 'Minutes')>, Intensity?: non-empty-array<non-falsy-string, ('Mild' | 'Moderate' | 'Moderately Severe' | 'Severe')>, Onset?: non-empty-array<non-falsy-string, ('Aggravated By Accident' | 'Other' | 'Pre-existing' | 'Precipitated By Accident')>}
     */
    public function _name_rows(string $name, $row_array): array
    {
        $a = array();
        foreach ($row_array as $row) {
            switch (strtolower($row)) {
                case "onset":
                    $a["Onset"][$name . '_onset_precipitated_by_accident']      =  "Precipitated By Accident";
                    $a["Onset"][$name . '_onset_aggravated_by_accident']    =  "Aggravated By Accident";
                    $a["Onset"][$name . '_onset_pre_existing']  =  "Pre-existing";
                    $a["Onset"][$name . '_onset_other']     =  "Other";
                    break;
                case "intensity":
                    $a["Intensity"][$name . '_intensity_mild'] =  "Mild";
                    $a["Intensity"][$name . '_intensity_moderate'] =  "Moderate";
                    $a["Intensity"][$name . '_intensity_moderately_severe'] =  "Moderately Severe";
                    $a["Intensity"][$name . '_intensity_severe'] =  "Severe";
                    break;
                case "duration":
                    $a["Duration"][$name . '_duration_minutes'] =  "Minutes";
                    $a["Duration"][$name . '_duration_hours'] =  "Hours";
                    $a["Duration"][$name . '_duration_days'] =  "Days";
                    $a["Duration"][$name . '_duration_constant'] =  "Constant";
                    break;
                case "frequency":
                    $a["Frequency"][$name . '_frequency_no_pattern'] =  "No Pattern";
                    $a["Frequency"][$name . '_frequency_1_week'] =  "1/Week";
                    $a["Frequency"][$name . '_frequency_2_3_week'] =  "2-3/Week";
                    $a["Frequency"][$name . '_frequency_daily'] =  "Daily";
                    break;
                case "quality of pain":
                    $a["Quality of Pain"][$name . '_quality_of_pain_dull'] =  "Dull";
                    $a["Quality of Pain"][$name . '_quality_of_pain_deep'] =  "Deep";
                    $a["Quality of Pain"][$name . '_quality_of_pain_aching'] =  "Aching";
                    $a["Quality of Pain"][$name . '_quality_of_pain_triggered'] =  "Triggered";
                    break;
                case "aggravation":
                    $a["Aggravation"][$name . '_aggravation_chewing'] =  "Chewing";
                    $a["Aggravation"][$name . '_aggravation_speaking'] =  "Speaking";
                    $a["Aggravation"][$name . '_aggravation_clenching'] =  "Clenching";
                    $a["Aggravation"][$name . '_aggravation_physical_activity'] =  "Physical Activity";
                    break;
                case "occurance":
                    $a["Occurance"][$name . '_occurance_at_walking'] =  "At Walking";
                    $a["Occurance"][$name . '_occurance_mid_day'] =  "Mid Day";
                    $a["Occurance"][$name . '_occurance_evening'] =  "Evening";
                    $a["Occurance"][$name . '_occurance_variable'] =  "Variable";
                    break;
            }
        }

        return $a;
    }
}   // end of Form
