<?php

declare(strict_types=1);

use OpenEMR\Common\ORDataObject\ORDataObject;

/*
 * Copyright Medical Information Integration,LLC info@mi-squared.com
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * @file AbstractClickmapModel.php
 *
 * @brief This file contains the AbstractClickmapModel class, used to model form contents.
 *
 */

/* for $GLOBALS['srcdir','pid'] */
/* remember that include paths are calculated relative to the including script, not this file. */
require_once(dirname(__FILE__) . '/../globals.php');

/**
 * @class AbstractClickmapModel
 *
 * @brief code This class extends the OrDataObject class, which is used to model form data for a smarty generated form.
 *
 * This class extends the ORDataObject class, to model the contents of an image-based form.
 *
 */
abstract class AbstractClickmapModel extends ORDataObject
{
    /**
     * The row to persist information to/from.
     *
     * @var id
     */
    public $id;

    /**
     *
     * FIXME: either last modification date OR creation date?
     *
     * @var date
     */
    public $date;

    /**
     *
     * The unique identifier of the patient this form belongs to.
     *
     * @var pid
     */
    public $pid;

    /**
     *
     * required field in database table. not used, always defaulted to NULL.
     *
     * @var user
     */
    public $user;

    /**
     *
     * required field in database table. not used, always defaulted to NULL.
     *
     * @var groupname
     */
    public $groupname;

    /**
     *
     * required field in the database table. always defaulted to NULL.
     *
     * @var authorized
     */
    public $authorized;

    /**
     *
     * required field in the database table. always defaulted to NULL.
     *
     * @var activity
     */
    public $activity;

    /**
     *
     * The contents of our form, in one field.
     *
     * @var data
     */
    public $data;

    /**
     * @brief Initialize a newly created object belonging to this class
     *
     * @param table
     *  The sql table to persist form contents from/to.
     *
     * @param id
     *  The index of a row in the given table to initialize form contents from.
     */
    public function __construct($table, $id = "")
    {
        parent::__construct();

        /* Only accept numeric IDs as arguments. */
        if (is_numeric($id)) {
            $this->id = $id;
        } else {
            $id = "";
        }

        $this->date = date("Y-m-d H:i:s");
        $this->_table = $table;
        $this->data = "";
        $this->pid = $GLOBALS['pid'];
        if ($id != "") {
            $this->populate();
        }
    }

    /**
     * @brief Override this abstract function with your implementation of getTitle.
     *
     * @return The title of this form.
     */
    abstract public function getTitle();

    /**
     * @brief Override this abstract function with your implementation of getCode.
     *
     * @return A string thats a 'code' for this form.
     */
    abstract public function getCode();

    /**
     * @brief Fill in this object's members with the contents from the database representing the stored form.
     */
    public function populate(): void
    {
        /* Run our parent's implementation. */
        parent::populate();
    }

    /**
     * @brief Store the current structure members representing the form into the database.
     */
    public function persist(): void
    {
        /* Run our parent's implementation. */
        parent::persist();
    }

    /* The rest of this object consists of set_ and get_ pairs, for setting and getting the value of variables that are members of this object. */

    public function get_id()
    {
        return $this->id;
    }

    public function set_id($id): void
    {
        if (!empty($id) && is_numeric($id)) {
            $this->id = $id;
        } else {
            trigger_error('API violation: set function called with empty or non numeric string.', E_USER_WARNING);
        }
    }

    public function get_pid()
    {
        return $this->pid;
    }

    public function set_pid($pid): void
    {
        if (!empty($pid) && is_numeric($pid)) {
            $this->pid = $pid;
        } else {
            trigger_error('API violation: set function called with empty or non numeric string.', E_USER_WARNING);
        }
    }

    public function get_activity()
    {
        return $this->activity;
    }

    public function set_activity($tf): void
    {
        if (!empty($tf) && is_numeric($tf)) {
            $this->activity = $tf;
        } else {
            trigger_error('API violation: set function called with empty or non numeric string.', E_USER_WARNING);
        }
    }

    /* get_date()
     *
     */
    public function get_date()
    {
        return $this->date;
    }

    /* set_date()
     *
     */
    public function set_date($dt): void
    {
        if (!empty($dt)) {
            $this->date = $dt;
        } else {
            trigger_error('API violation: set function called with empty string.', E_USER_WARNING);
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
        } else {
            trigger_error('API violation: set function called with empty string.', E_USER_WARNING);
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
        } else {
            trigger_error('API violation: set function called with empty string.', E_USER_WARNING);
        }
    }
}
