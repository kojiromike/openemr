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
function smarty_function_pc_view_select($args): void
{
    @define('_PC_FORM_TEMPLATE', true);
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

    $prepared = pnVarCleanFromInput('tplview');
    $viewtype = pnVarCleanFromInput('viewtype');
    if (!isset($viewtype)) {
        $viewtype = _SETTING_DEFAULT_VIEW;
    }

    $modinfo = pnModGetInfo(pnModGetIDFromName(__POSTCALENDAR__));
    $mdir = pnVarPrepForOS($modinfo['directory']);
    unset($modinfo);
    $pcTemplate = pnVarPrepForOS(_SETTING_TEMPLATE);
    if (empty($pcTemplate)) {
        $pcTemplate = 'default';
    }

    $viewlist = array();
    $handle = opendir(sprintf('modules/%s/pntemplates/%s/views/%s', $mdir, $pcTemplate, $viewtype));

    $hide_list = array('.','..','CVS','index.html');
    while ($f = readdir($handle)) {
        if (!in_array($f, $hide_list)) {
            $viewlist[] = $f;
        }
    }

    closedir($handle);
    unset($no_list);
    sort($viewlist);
    $tcount = count($viewlist);
    //$options = "<select id=\"tplview\" name=\"tplview\" class=\"$args[class]\">"; - pennfirm
    $options = sprintf('<select id="tplview" name="viewtype" class="%s">', $args[class]);
    $selected = $prepared;
    for ($t = 0; $t < $tcount; ++$t) {
        $id = str_replace('.html', '', $viewlist[$t]);
        $sel = $selected == $id ? 'selected' : '';
        $options .= sprintf('<option value="%s" %s class="%s">%s</option>', $id, $sel, $args[class], $id);
    }

    $options .= '</select>';

    if (!isset($args['label'])) {
        $args['label'] = _PC_TPL_VIEW_SUBMIT;
    }

    $submit = '<input type="submit" valign="middle" name="submit" value="' . $args['label'] . '" class="' . $args['class'] . '" />';
    // build the form
    if ($t > 1) {
        echo $options,$submit;
    }
}
