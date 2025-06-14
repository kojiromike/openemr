<?php

declare(strict_types=1);

/**
 *  @package OpenEMR
 *  @link    http://www.open-emr.org
 *  @author  Sherwin Gaddis <sherwingaddis@gmail.com>
 *  @copyright Copyright (c) 2020.  Sherwin Gaddis <sherwingaddis@gmail.com>
 *  @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

use OpenEMR\Common\ORDataObject\ORDataObject;

define("EVENT_VEHICLE", 1);
define("EVENT_WORK_RELATED", 2);
define("EVENT_SLIP_FALL", 3);
define("EVENT_OTHER", 4);

/**
 * class FormHpTjePrimary
 *
 */
class FormDAP extends ORDataObject
{
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

    public $data;

    public $assessment;

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

        $this->_table = "form_dap";
        $this->activity = 1;
        $this->pid = $GLOBALS['pid'];
        if ($id != "") {
            $this->populate();
        }
    }

    public function populate(): void
    {
        parent::populate();
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

    public function get_data()
    {
        return $this->data;
    }

    public function set_data($data): void
    {
        if (!empty($data)) {
            $this->data = $data;
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
}
