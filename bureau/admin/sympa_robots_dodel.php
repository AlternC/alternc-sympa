<?php
/*
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2000-2022 by the AlternC Development Team.
 https://alternc.org/
 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ----------------------------------------------------------------------
 Purpose of file: Delete a Sympa mailing-lists virtual robot
 ----------------------------------------------------------------------
*/

require_once("../class/config.php");

$fields = array (
	"id"     => array ("request", "integer", ""),
	"go"     => array ("request", "string", ""),
	"cancel"     => array ("request", "string", ""),
);
getFields($fields);


if (isset($cancel) && $cancel) {
    header("Location: /sympa_robots.php");
    exit();
}

$r=$sympa->delete_robot($id);

include("sympa_robots.php");
exit();


