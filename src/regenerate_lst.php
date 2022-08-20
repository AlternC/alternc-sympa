#!/usr/bin/php5
<?php
/*
   $Id: newone.php,v 1.6 2006/02/17 15:15:54 olivier Exp $
   ----------------------------------------------------------------------
   AlternC - Web Hosting System
   Copyright (C) 2002 by the AlternC Development Team.
   http://alternc.org/
   ----------------------------------------------------------------------
   Based on:
   Valentin Lacambre's web hosting softwares: http://altern.org/
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
   Original Author of file: Benjamin Sonntag
   Purpose of file: Create the first admin account on a new AlternC server
   ----------------------------------------------------------------------
 */

// On vérifie que mysql.so est bien chargé, sinon on essaye de le charger
if(!function_exists('mysql_connect'))  {
  if(!dl("mysql.so"))
    exit(1);
}

// Ne vérifie pas ma session :)
if(!chdir("/usr/share/alternc/panel")) exit(1);
require("/usr/share/alternc/panel/class/config_nochk.php");

// On passe super-admin
$admin->enabled=1;

$db->query("select * from mailman where mailman_action = 'REGENERATE-2';");
while($db->next_record()){
  $uid=$db->f('uid');
  $n=$db->f('name');
  $l=$db->f('list');
  $dom2=$db->f('domain');
  $id=$db->f('id');
  $mem->su($uid);
  if($mailman->add_wrapper_all($n,$l,$dom2)){
    $db->query("UPDATE mailman SET mailman_result='', mailman_action='OK' WHERE id='$id';");
  }else{
    $db->query("UPDATE mailman SET mailman_result='A fatal error happened when regenerating the list', mailman_action='OK' WHERE id=$id;");
  }
  $mem->unsu();
}


?>

