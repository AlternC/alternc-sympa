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
 Purpose of file: ask for the required values to EDIT a mailing-list robot
 ----------------------------------------------------------------------
*/

require_once("../class/config.php");
include_once("head.php");


if (!$quota->cancreate("sympa")) {
    require_once("main.php");
    exit();
}

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
if (!count($_POST)) {
    foreach($domain as $k=>$v) $$k=$v; // dereference as if it was posted.
}

?>
<h3><?php __("Sympa Mailing-lists, change the setup of a domain name"); ?></h3>
<hr id="topbar"/>
<br />
<?php
    echo $msg->msg_html_all();

echo "<p>"._("This form allows you to change the settings of a domain name for which you have Sympa mailing-list already setup.")."</p>";
?>

<form method="post" action="sympa_robots_doedit.php" name="main" id="main" >
                                                                   <input type="hidden" name="id" value="<?php echo $id; ?>"/>
<?php csrf_get(); ?>
<table class="tedit">

<tr><th><label for="mail"><?php __("Domain name of the lists"); ?></label></th></tr>
<tr><td><b><?php ehe($mail); ?></b></td></tr>

<tr><th><label for="web"><?php __("Subdomain used for web access to the Mailing-lists management interface"); ?></label><br /><small><?php __("If you change it, the old one will be set as a redirect to the new one.<br>Changing it is not recommended though, once you published it for list owners or subscribers."); ?></small></th></tr>
<tr><td>	<input type="text" class="int" id="websub" name="websub" value="<?php if (isset($websub)) ehe($websub); else __('lists'); ?>" size="20" maxlength="64" /><b>&nbsp;.&nbsp;</b><select class="inl" name="web"><?php $sympa->select_prefix_list($web,$sympa::SELECT_WEB); ?></select></td></tr>

<tr><th><label for="listmasters"><?php __("Email of the domain lists super-administrators (1 per line, 1 minimum)"); ?></label><br /><small><?php __("You can change this list as you like. If you add someone whose email is not yet enrolled in the server, <br>this person can create an account via the web interface to access its listmasters privileges"); ?></small></th></tr>
<tr><td><textarea cols="40" rows="6" class="int" id="listmasters" name="listmasters"><?php  if (isset($listmasters)) ehe($listmasters); ?></textarea></td></tr>

<tr class="trbtn"><td>
  <input type="submit" class="inb" name="submit" value="<?php __("Edit this domain's Sympa settings."); ?>"/>
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='sympa_robots.php'"/>
</td></tr>
</table>
</form>

<script type="text/javascript">
  $(document).ready(function() {
    $('#listmasters').focus();
  });
</script>

<?php
include_once("foot.php");


