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
 Purpose of file: Confirm the deletion of a Sympa domain / virtual robot 
 ----------------------------------------------------------------------
*/

require_once("../class/config.php");
include_once("head.php");


$fields = array (
	"id"     => array ("request", "integer", ""),
);
getFields($fields);

$domain = $sympa->get_robot($id);
if (!$domain) {
    $msg->raise("ALERT","sympa",_("Can't find the requested domain on your account!"));
    require_once("sympa_robots.php");
    exit();
}

?>
<h3><?php __("Deleting a Sympa Domain name"); ?></h3>
<hr id="topbar"/>
<br />
 <?php 
    echo $msg->msg_html_all();

?>
<p><?php __("Please confirm that you want to delete the following domain name for Sympa Mailing Lists."); ?></p>

	<table class="tlist">
                <tr><th><?php __("Domain Name"); ?></th><td><?php echo $domain["mail"]; ?></td></tr>
                <tr><th><?php __("Web Interface URL"); ?></th><td><a href="<?php echo "https://".$domain["websub"].".".$domain["web"]."/"; ?>" target="_blank"><?php echo "https://".$domain["websub"].".".$domain["web"]."/"; ?></td></tr>
	</table>
<br />
<form method="post" action="sympa_robots_dodel.php">
<?php csrf_get(); ?>
<input type="hidden" id="id" name="id" value="<?php echo $id; ?>">
<input class="inb" type="submit" name="go" value="<?php __("Yes, delete this domain mailing-lists from Sympa."); ?>">
<input class="inb" type="submit" name="cancel" value="<?php __("No, don't delete this domain's mailing-lists on Sympa."); ?>">
</form>


<?php include_once("foot.php"); ?>
