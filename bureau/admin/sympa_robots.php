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
 Purpose of file: Show the Sympa Mailing-Lists Robots owned by the current user
 ----------------------------------------------------------------------
*/

require_once("../class/config.php");
include_once("head.php");

// If there is no installed domain, let's failed definitely !
if (count($sympa->prefix_list())==0) {
    $msg->raise("ALERT","sympa",_("No domain is installed on your account, you cannot create any mailing list!"));
?>
<h3><?php __("Sympa Mailing lists"); ?></h3>
<hr id="topbar"/>
<br />
<?php 
    echo $msg->msg_html_all();
    include_once("foot.php");
    exit();
}

if(!$r=$sympa->enum_robots()) {
  if ($quota->cancreate("sympa")) {
    require_once("sympa_robots_add.php"); 
    exit();
  } else {
    require_once("main.php");
    exit();
  }
} else {
	?>
<h3><?php __("Sympa Mailing lists"); ?></h3>
<hr id="topbar"/>
<br />
 <?php 
    echo $msg->msg_html_all();

if ($quota->cancreate("sympa")) {
?>
<p>
<span class="ina"><a href="sympa_robots_add.php"><?php __("Setup a domain for Sympa mailing-lists"); ?></a></span>
</p>
	<?php
    // TODO: code from here 
}
?>

	<table class="tlist">
<tr><th><?php __("Domain Name"); ?></th><th><?php __("Admin web interface"); ?></th><th><?php __("Domain Status"); ?><th><?php __("Actions"); ?></th></tr>
	<?php
	reset($r);
	$col=1;
	while (list($key,$val)=each($r)) {
		$col=3-$col;
		?>
		<tr class="lst<?php echo $col; ?>">
           <td>@<?php echo $val["mail"]; ?></td>
           <td><a class="ina" target="_blank" href="https://<?php echo $val["websub"].(($val["websub"])?".":"").$val["web"]; ?>"><?php echo $val["websub"].(($val["websub"])?".":"").$val["web"]; ?></a></td>        
		   <?php if ($val["sympa_action"]=="DELETE" || $val["sympa_action"]=="DELETING")  { ?>
            <td colspan="2"><?php __("This domain lists management is pending deletion, you can't do anything on it"); ?></td>
		   <?php } else { ?>
            <td><?php __("sympa_status_".$val["sympa_action"]); ?></td>
			<td><a class="ina" href="sympa_robots_edit.php?id=<?php echo $val["id"] ?>"><?php __("Edit"); ?></a> &nbsp; <a class="ina" href="sympa_robots_del.php?id=<?php echo $val["id"] ?>"><?php __("Delete"); ?></a></td>
	      <?php } ?>
		</tr>
		<?php
		}
	?>
	</table>
<br />

	<?php
}

?>


<?php include_once("foot.php"); ?>
