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
 Purpose of file: ask for the required values to create a mailing-list robot
 ----------------------------------------------------------------------
*/

require_once("../class/config.php");
include_once("head.php");

if (!$quota->cancreate("sympa")) {
    require_once("main.php");
    exit();
}

// we get the list of possible domain names for our lists, and we ask for only those who are not ALREADY hosted robots.
$domlist = $sympa->prefix_list(true);
if (!count($domlist)) {
    $msg->raise("ALERT","sympa",_("You currently have no unused domain for which you can host sympa robots."));
    require_once("main.php");
    exit();
}

?>
<h3><?php __("Sympa Mailing-lists, setup of a domain name"); ?></h3>
<hr id="topbar"/>
<br />
<?php
    echo $msg->msg_html_all();

echo "<p>"._("If you want to use a domain name to host Sympa mailing-lists, you need to setup that domain here first. You can use any domain whose emails are hosted on your account to host mailing lists. You will also need a subdomain to host your web interface for those lists (eg: lists.example.com).")."</p>";
?>

<form method="post" action="sympa_robots_doadd.php" name="main" id="main" >
<?php csrf_get(); ?>
<table class="tedit">

<tr><th><label for="mail"><?php __("Which domain name will host the lists?"); ?></label></th></tr>
<tr><td><select class="inl" name="mail"><?php $sympa->select_prefix_list($mail,$sympa::SELECT_MX_UNUSED); ?></select></td></tr>

<tr><th><label for="web"><?php __("Which subdomain will be used for web access to the Mailing-lists management interface?"); ?></label></th></tr>
<tr><td>	<input type="text" class="int" id="websub" name="websub" value="<?php if (isset($websub)) ehe($websub); else __('lists'); ?>" size="20" maxlength="64" /><b>&nbsp;.&nbsp;</b><select class="inl" name="web"><?php $sympa->select_prefix_list($domain,$sympa::SELECT_WEB); ?></select></td></tr>

<tr><th><label for="listmasters"><?php __("Email of the domain lists super-administrators (1 per line)"); ?> </label></th></tr>
<tr><td><textarea cols="40" rows="6" class="int" id="listmasters" name="listmasters"><?php  if (isset($listmasters)) ehe($listmasters); ?></textarea></td></tr>

<tr class="trbtn"><td>
  <input type="submit" class="inb" name="submit" value="<?php __("Setup that domain to host Sympa Mailing-lists."); ?>"/>
  <input type="button" class="inb" name="cancel" value="<?php __("Cancel"); ?>" onclick="document.location='sympa_robots.php'"/>
</td></tr>
</table>
</form>

<script type="text/javascript">
  $(document).ready(function() {
    $('#domain').focus();
  });
</script>

<?php
include_once("foot.php");


