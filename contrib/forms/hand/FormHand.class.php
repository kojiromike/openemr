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
class FormHand extends ORDataObject
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

    public $left_1;

    public $left_2;

    public $left_3;

    public $right_1;

    public $right_2;

    public $right_3;

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

        $this->_table = "form_hand";
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

    //

    public function set_left_1($tf): void
    {
        $this->left_1 = $tf;
    }

    public function get_left_1()
    {
        return $this->left_1;
    }

    public function set_left_2($tf): void
    {
        $this->left_2 = $tf;
    }

    public function get_left_2()
    {
        return $this->left_2;
    }

    public function set_left_3($tf): void
    {
        $this->left_3 = $tf;
    }

    public function get_left_3()
    {
        return $this->left_3;
    }

    public function set_right_1($tf): void
    {
        $this->right_1 = $tf;
    }

    public function get_right_1()
    {
        return $this->right_1;
    }

    public function set_right_2($tf): void
    {
        $this->right_2 = $tf;
    }

    public function get_right_2()
    {
        return $this->right_2;
    }

    public function set_right_3($tf): void
    {
            $this->right_3 = $tf;
    }

    public function get_right_3()
    {
        return $this->right_3;
    }


    public $handedness;

    public function get_handedness()
    {
        return $this->handedness;
    }

    public function set_handedness($data): void
    {
        if (!empty($data)) {
            $this->handedness = $data;
        }
    }

    public function get_handedness_l(): string
    {
        return $this->handedness == "Left" ? "CHECKED" : "";
    }

    public function get_handedness_r(): string
    {
        return $this->handedness == "Right" ? "CHECKED" : "";
    }

    public function get_handedness_b(): string
    {
        return $this->handedness == "Both" ? "CHECKED" : "";
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
