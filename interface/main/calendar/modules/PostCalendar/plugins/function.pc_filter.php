<?php

declare(strict_types=1);

/**
 *  $Id$
 *
 *  PostCalendar::PostNuke Events Calendar Module
 *  Copyright (C) 2002  The PostCalendar Team
 *  http://postcalendar.tv
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *  To read the license please read the docs/license.txt or visit
 *  http://www.gnu.org/copyleft/gpl.html
 *
 */
function smarty_function_pc_filter($args, &$smarty): void
{
    extract($args);
    unset($args);

    if (empty($type)) {
        trigger_error("pc_filter: missing 'type' parameter", E_USER_WARNING);
        return;
    }

    $Date = postcalendar_getDate();
    if (!isset($y)) {
        $y = substr($Date, 0, 4);
    }

    if (!isset($m)) {
        $m = substr($Date, 4, 2);
    }

    if (!isset($d)) {
        $d = substr($Date, 6, 2);
    }

    pnVarCleanFromInput('tplview');
    $viewtype = pnVarCleanFromInput('viewtype');
    $pc_username = pnVarCleanFromInput('pc_username');

    if (!isset($viewtype)) {
        $viewtype = _SETTING_DEFAULT_VIEW;
    }

    $types = explode(',', $type);
    $output = new pnHTML();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);

    $modinfo = pnModGetInfo(pnModGetIDFromName(__POSTCALENDAR__));
    pnVarPrepForOS($modinfo['directory']);
    unset($modinfo);
    $pcTemplate = pnVarPrepForOS(_SETTING_TEMPLATE);
    if (empty($pcTemplate)) {
        $pcTemplate = 'default';
    }

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    //================================================================
    //  build the username filter pulldown
    //================================================================
    if (in_array('user', $types)) {
        @define('_PC_FORM_USERNAME', true);
        $sql = "SELECT DISTINCT users.username, users.lname, users.fname
	 			FROM $pntable[postcalendar_events], users where users.id=pc_aid
				ORDER BY pc_aid";

        $result = $dbconn->Execute($sql);
        if ($result !== false) {
            $useroptions  = sprintf("<select multiple='multiple' size='3' name=\"pc_username[]\" class=\"%s\">", $class);
            $useroptions .= sprintf('<option value="" class="%s">', $class) . _PC_FILTER_USERS . "</option>";
            $selected = $pc_username == '__PC_ALL__' ? 'selected="selected"' : '';
            $useroptions .= sprintf('<option value="__PC_ALL__" class="%s" %s>', $class, $selected) . _PC_FILTER_USERS_ALL . "</option>";
            for (; !$result->EOF; $result->MoveNext()) {
                $sel = $pc_username == $result->fields[0] ? 'selected="selected"' : '';
                $useroptions .= '<option value="' . $result->fields[0] . sprintf('" %s class="%s">', $sel, $class) . $result->fields[1] . ", " . $result->fields[2] . "</option>";
            }

            $useroptions .= '</select>';
            $result->Close();
        }
    }

    //================================================================
    //  build the category filter pulldown
    //================================================================
    if (in_array('category', $types)) {
        @define('_PC_FORM_CATEGORY', true);
        $category = pnVarCleanFromInput('pc_category');
        $categories = pnModAPIFunc(__POSTCALENDAR__, 'user', 'getCategories');
        $catoptions  = sprintf('<select name="pc_category" class="%s">', $class);
        $catoptions .= sprintf('<option value="" class="%s">', $class) . _PC_FILTER_CATEGORY . "</option>";
        foreach ($categories as $c) {
            $sel = $category == $c['id'] ? 'selected="selected"' : '';
            $catoptions .= sprintf('<option value="%s" %s class="%s">', $c[id], $sel, $class) . xl_appt_category($c[name]) . "</option>";
        }

        $catoptions .= '</select>';
    }

    //================================================================
    //  build the topic filter pulldown
    //================================================================
    if (in_array('topic', $types) && _SETTING_DISPLAY_TOPICS) {
        @define('_PC_FORM_TOPIC', true);
        $topic = pnVarCleanFromInput('pc_topic');
        $topics = pnModAPIFunc(__POSTCALENDAR__, 'user', 'getTopics');
        $topoptions  = sprintf('<select name="pc_topic" class="%s">', $class);
        $topoptions .= sprintf('<option value="" class="%s">', $class) . _PC_FILTER_TOPIC . "</option>";
        foreach ($topics as $t) {
            $sel = $topic == $t['id'] ? 'selected="selected"' : '';
            $topoptions .= sprintf('<option value="%s" %s class="%s">%s</option>', $t[id], $sel, $class, $t[text]);
        }

        $topoptions .= '</select>';
    } else {
        $topoptions = '';
    }

    //================================================================
    //  build it in the correct order
    //================================================================
    if (!isset($label)) {
        $label = _PC_TPL_VIEW_SUBMIT;
    }

    $submit = sprintf('<input type="submit" valign="middle" name="submit" value="%s" class="%s" />', $label, $class);
    $orderArray = array('user' => $useroptions, 'category' => $catoptions, 'topic' => $topoptions, 'jump' => $submit);

    if (isset($order)) {
        $newOrder = array();
        $order = explode(',', $order);
        foreach ($order as $tmp_order) {
            $newOrder[] = $orderArray[$tmp_order];
        }

        foreach ($orderArray as $key => $old_order) {
            if (!in_array($key, $newOrder)) {
                $newOrder[] = $orderArray[$old_order];
            }
        }

        $order = $newOrder;
    } else {
        $order = $orderArray;
    }

    foreach ($order as $element) {
        echo $element;
    }

    if (!in_array('user', $types)) {
        echo $output->FormHidden('pc_username', $pc_username);
    }
}
