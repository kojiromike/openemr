<?php

declare(strict_types=1);

// Copyright (C) 2009 Aron Racho <aron@mi-squared.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2

use OpenEMR\Common\ORDataObject\ORDataObject;

define("EVENT_VEHICLE", 1);
define("EVENT_WORK_RELATED", 2);
define("EVENT_SLIP_FALL", 3);
define("EVENT_OTHER", 4);


/**
 * class FormHpTjePrimary
 *
 */
class FormLegLength extends ORDataObject
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

    public $activity;

    public $AE_left;

    public $AE_right;

    public $BE_left;

    public $BE_right;

    public $AK_left;

    public $AK_right;

    public $K_left;

    public $K_right;

    public $BK_left;

    public $BK_right;

    public $ASIS_left;

    public $ASIS_right;

    public $UMB_left;

    public $UMB_right;

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
            $this->date = date("Y-m-d H:i:s");
        }

        $this->_table = "form_leg_length";
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

    public function __toString(): string
    {
        return "ID: " . $this->id . "\n";
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

    public function persist(): void
    {
        parent::persist();
    }

    public function get_AE_left()
    {
        return $this->AE_left;
    }

    public function set_AE_left($tf): void
    {
        $this->AE_left = $tf;
    }

    public function get_AE_right()
    {
        return $this->AE_right;
    }

    public function set_AE_right($tf): void
    {
        $this->AE_right = $tf;
    }

    public function get_BE_left()
    {
        return $this->BE_left;
    }

    public function set_BE_left($tf): void
    {
        $this->BE_left = $tf;
    }

    public function get_BE_right()
    {
        return $this->BE_right;
    }

    public function set_BE_right($tf): void
    {
        $this->BE_right = $tf;
    }

    public function get_AK_left()
    {
        return $this->AK_left;
    }

    public function set_AK_left($tf): void
    {
        $this->AK_left = $tf;
    }

    public function get_AK_right()
    {
        return $this->AK_right;
    }

    public function set_AK_right($tf): void
    {
        $this->AK_right = $tf;
    }

    public function get_K_left()
    {
        return $this->K_left;
    }

    public function set_K_left($tf): void
    {
        $this->K_left = $tf;
    }

    public function get_K_right()
    {
        return $this->K_right;
    }

    public function set_K_right($tf): void
    {
        $this->K_right = $tf;
    }

    public function get_BK_left()
    {
        return $this->BK_left;
    }

    public function set_BK_left($tf): void
    {
        $this->BK_left = $tf;
    }

    public function get_BK_right()
    {
        return $this->BK_right;
    }

    public function set_BK_right($tf): void
    {
        $this->BK_right = $tf;
    }

    public function get_ASIS_left()
    {
        return $this->ASIS_left;
    }

    public function set_ASIS_left($tf): void
    {
        $this->ASIS_left = $tf;
    }

    public function get_ASIS_right()
    {
        return $this->ASIS_right;
    }

    public function set_ASIS_right($tf): void
    {
        $this->ASIS_right = $tf;
    }

    public function get_UMB_left()
    {
        return $this->UMB_left;
    }

    public function set_UMB_left($tf): void
    {
        $this->UMB_left = $tf;
    }

    public function get_UMB_right()
    {
        return $this->UMB_right;
    }

    public function set_UMB_right($tf): void
    {
        $this->UMB_right = $tf;
    }

    // ----- notes -----

    public $notes;

    public function get_notes()
    {
        return $this->notes;
    }

    public function set_notes($data): void
    {
        if (!empty($data)) {
            $this->notes = $data;
        }
    }
}   // end of Form
