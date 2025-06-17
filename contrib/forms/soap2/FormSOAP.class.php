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
class FormSOAP extends ORDataObject
{
    public $objective;
    /**
     *
     * @access public
     */


    /**
     *
     * static
     */
    public $id;

    public $date;

    public $pid;

    public $user;

    public $groupname;

    public $authorized;

    public $activity;

    public $subjective;

    //var $objective;
    public $assessment;

    public $general;

    public $heent;

    public $neck;

    public $cardio;

    public $respiratory;

    public $breasts;

    public $abdomen;

    public $gastro;

    public $extremities;

    public $skin;

    public $neurological;

    public $mentalstatus;

    public $plan;

    /**
     * Constructor sets all Form attributes to their default value
     */

    public function __construct($id = "")
    {
        if (is_numeric($id)) {
            $this->id = $id;
        } else {
            $id = "";
            $this->date = date("Y-m-d H:i:s");
        }

        $this->_table = "form_soap2";
        $this->activity = 1;
        $this->pid = $GLOBALS['pid'];
        if ($id != "") {
            $this->populate();
            //$this->date = $this->get_date();
        }
    }

    public function populate(): void
    {
        parent::populate();
        //$this->temp_methods = parent::_load_enum("temp_locations",false);
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

    public function get_date()
    {
        return $this->date;
    }

    public function set_date($dt): void
    {
        if (!empty($dt)) {
            $this->date = $dt;
        }
    }

    public function get_user()
    {
        return $this->user;
    }

    public function set_user($u): void
    {
        if (!empty($u)) {
            $this->user = $u;
        }
    }

    public function get_subjective()
    {
        return $this->subjective;
    }

    public function set_subjective($data): void
    {
        if (!empty($data)) {
            $this->subjective = $data;
        }
    }

    public function get_objective()
    {
        return $this->objective;
    }

    public function set_objective($data): void
    {
        if (!empty($data)) {
            $this->objective = $data;
        }
    }

    public function get_assessment()
    {
        return $this->assessment;
    }

    public function set_assessment($data): void
    {
        if (!empty($data)) {
            $this->assessment = $data;
        }
    }



    /*  The following code replaces assessment.  It is
      part of the SOAP form Dr J has requested.
    */


    // **** General *****
    public function get_general()
    {
        return $this->general;
    }

    public function set_general($data): void
    {
        if (!empty($data)) {
            $this->general = $data;
        }
    }

    // **** HEENT  ******
    public function get_heent()
    {
        return $this->heent;
    }

    public function set_heent($data): void
    {
        if (!empty($data)) {
            $this->heent = $data;
        }
    }

    // **** Neck *****
    public function get_neck()
    {
        return $this->neck;
    }

    public function set_neck($data): void
    {
        if (!empty($data)) {
            $this->neck = $data;
        }
    }

    // **** CV *****
    public function get_cardio()
    {
        return $this->cardio;
    }

    public function set_cardio($data): void
    {
        if (!empty($data)) {
            $this->cardio = $data;
        }
    }

    // **** Lungs *****
    public function get_respiratory()
    {
        return $this->respiratory;
    }

    public function set_respiratory($data): void
    {
        if (!empty($data)) {
            $this->respiratory = $data;
        }
    }

    // **** Breasts *****
    // * my personal favorite :>  ***
    public function get_breasts()
    {
        return $this->breasts;
    }

    public function set_breasts($data): void
    {
        if (!empty($data)) {
            $this->breasts = $data;
        }
    }

    // **** Abdomen *****
    public function get_abdomen()
    {
        return $this->abdomen;
    }

    public function set_abdomen($data): void
    {
        if (!empty($data)) {
            $this->abdomen = $data;
        }
    }

    // **** GU *****
    public function get_gastro()
    {
        return $this->gastro;
    }

    public function set_gastro($data): void
    {
        if (!empty($data)) {
            $this->gastro = $data;
        }
    }

    // **** Bones/Joints/Extremities *****
    public function get_extremities()
    {
        return $this->extremities;
    }

    public function set_extremities($data): void
    {
        if (!empty($data)) {
            $this->extremities = $data;
        }
    }

    // **** Skin *****
    public function get_skin()
    {
        return $this->skin;
    }

    public function set_skin($data): void
    {
        if (!empty($data)) {
            $this->skin = $data;
        }
    }

    // **** Neuro/Psych *****
    public function get_neurological()
    {
        return $this->neurological;
    }

    public function set_neurological($data): void
    {
        if (!empty($data)) {
            $this->neurological = $data;
        }
    }

    // **** Mental Status *****
    public function get_mentalstatus()
    {
        return $this->mentalstatus;
    }

    public function set_mentalstatus($data): void
    {
        if (!empty($data)) {
            $this->mentalstatus = $data;
        }
    }

    public function get_plan()
    {
        return $this->plan;
    }

    public function set_plan($data): void
    {
        if (!empty($data)) {
            $this->plan = $data;
        }
    }

    public function persist(): void
    {
        parent::persist();
    }
}   // end of Form
