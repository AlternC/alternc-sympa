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
 Purpose of file: Manage mailing-lists with Sympa
 ----------------------------------------------------------------------
*/

class m_sympa {


    /* ----------------------------------------------------------------- */
    /** 
     * Hook called by AlternC to tell which main menu element need adding for this module.
     */ 
    function hook_menu() {
        $obj = array(
            'title'       => _("Sympa Mailing lists"),
            'ico'         => 'images/sympa.png',
            'link'        => 'sympa_robots.php',
            'pos'         => 70,
        ) ;

        return $obj;
    }

    /* this function is used to give gettext hints of strings that are dynamically used and need translation :) */
    private function _dynamic_translation() {
        [_("sympa_status_OK"), _("sympa_status_DELETE"), _("sympa_status_DELETING"), _("sympa_status_CREATE"), _("sympa_status_REGENERATE")];
    }

    // constants used to select domains for dropdown lists
    const SELECT_MX=0; // returns domain for which we are the MX
    const SELECT_MX_UNUSED=1; // returns domain for which we are the MX but we didn't setup a robot yet
    const SELECT_WEB=2; // returns domain were we can host web interfaces
    
    /* ----------------------------------------------------------------- */
    /** Return the list of domains that may be used by sympa for the current account
     * @param $unused_only boolean if true, only return the domain names that are currently not setup as sympa virtual robots
     * @return array an array of domain names 
     */
    function prefix_list($which=SELECT_MX) {
        global $db,$msg,$cuid;
        $r=array();
        switch ($which) {
        case SELECT_MX:
            $q="SELECT domaine FROM domaines WHERE compte='$cuid' AND gesmx = 1 ORDER BY domaine;";
            break;
        case SELECT_MX_UNUSED:
            $q="SELECT d.domaine FROM domaines d LEFT JOIN sympa s ON s.mail_domain_id=d.id WHERE s.id IS NULL AND d.compte='$cuid' AND d.gesmx = 1 ORDER BY d.domaine;";
            break;
        case SELECT_WEB:
            $q="SELECT domaine FROM domaines WHERE compte='$cuid' ORDER BY domaine;";
            break;
        }            
        $db->query($q);
        while ($db->next_record()) {
            $r[]=$db->f("domaine");
        }
        return $r;
    }

    
    /* ----------------------------------------------------------------- */
    /** Echoes a select list options of the list of domains that may be used 
     * by sympa for the current account. 
     * @param $current string the item that will be selected in the list
     * @return array an array of domain names 
     */
    function select_prefix_list($current,$which=SELECT_MX) {
        global $db,$msg;
        $r=$this->prefix_list($which);
        reset($r);
        while (list($key,$val)=each($r)) {
            if ($current==$val) $c=" selected=\"selected\""; else $c="";
            echo "<option$c>$val</option>";
        }
        return true;
    }


    /* ----------------------------------------------------------------- */
    /** Return the list of currently installed robots
     * @return array an array of robots informations
     */
    function enum_robots() {
        global $db,$msg,$cuid;
        $r=array();
        $db->query("SELECT * FROM sympa WHERE uid='$cuid' ORDER BY mail;");
        while ($db->next_record()) {
            $r[]=$db->Record;
        }
        return $r;
    }


    /* ----------------------------------------------------------------- */
    /** Get all the informations for a robot
     * @param $id integer is the robot ID from alternc's database.
     * @return array an associative array with all the robot information
     * or false if an error occured.
     */
    function get_robot($id) {
        global $db, $msg, $cuid;
        $msg->log("sympa","get_robot", $cuid);

        $db->query("SELECT * FROM sympa WHERE uid=$cuid AND id =$id");
        $db->next_record();
        if (!$db->f("id")) {
            $msg->raise("ERROR","sympa",_("This list robot does not exist"));
            return false;
        }
        return $db->Record;   
    }


    /* ----------------------------------------------------------------- */
    /** Create a new robot for the current account:
     * @param $domain string the domain name that will receive emails
     * @param $webdomain string the domain name that will host the web interface.
     * @param $websubdomain string the subdomain part of the domain name that will host the web interface.
     * @param $listmasters the email addresses of the listmasters of that domain (1 or more required, 1 per line)
     * @return boolean TRUE if the list has been created, or FALSE if an error occured
     */
    function add_robot($domain,$webdomain,$websubdomain,$listmasters) {
        global $db,$msg,$quota,$mail,$cuid,$dom,$L_FQDN;
        $msg->log("sympa","add_robot",$domain." - " .$websubdomain.".".$webdomain." - ".str_replace("\n",",",$listmasters));

        // Check the quota
        if (!$quota->cancreate("sympa")) {
            $msg->raise("ERROR","sympa",_("You are not allowed to use sympa mailing-list. Contact your administrator if needed")); // quota
            return false;
        }

        /* check the domains, their owners and their status */
        $db->query("SELECT * FROM domaines WHERE domaine=".addslashes($domain)." AND compte=$cuid;");
        if (!$db->next_record()) {
            $msg->raise("ERROR","sympa",_("Domain not found"));
            return false;
        }
        if (!$db->f('gesmx')) {
            $msg->raise("ERROR","sympa",_("The domain's email is not hosted here"));
            return false;
        }
        $mail_domain_id=$db->f('id');
        $db->query("SELECT * FROM sympa WHERE mail_domain_id=".$mail_domain_id.";");
        if ($db->next_record()) { 
            $msg->raise("ERROR","sympa",_("This domain is already setup for Sympa"));
            return false;
        }
        
        /* now the web domain */
        $db->query("SELECT * FROM domaines WHERE domaine=".addslashes($webdomain)." AND compte=$cuid;");
        if (!$db->next_record()) {
            $msg->raise("ERROR","sympa",_("Web Domain not found"));
            return false;
        }
        $web_domain_id=$db->f('id');
        if (!checkfqdn($websubdomain)) {
            $msg->raise("ERROR","sympa",_("The sub-domain name is invalid"));
            return false;
        }

        /* check the listmasters list */
        $listmaster_checked="";
        $lm=explode("\n",$listmasters);
        foreach($lm as $one) {
            $one=trim($one);
            if (checkmail($one)) $listmaster_checked.=$one."\n";
        }
        if (!$listmaster_checked) {
            $msg->raise("ERROR","sympa",_("The super-admin list is empty or invalid. Please check"));
            return false;
        }
        
        /* all checks done, let's create the robot. */
        // 1. set the web subdomain
        $dom->lock();
        if (!$dom->set_sub_domain($web, $sub, "sympa-robot", '')) {
            $dom->unlock(); 
            $msg->raise("ERROR","sympa",_("Can't set the web sub-domain, please check this name is not already used."));
            return false;
        }
        $dom->unlock();

        // 2. create the robot (will be created by a cron)
        $db->query("INSERT INTO sympa SET uid=$cuid, mail='".addslashes($domain)."', mail_domain_id=$mail_domain_id, web='".addslashes($web)."', web_domain_id=$web_domain_id, websub='".addslashes($sub)."', listmasters='".addslashes($listmaster_checked)."', sympa_action='CREATE';");

        // 3. add the required wrapper (TODO)
        /*
        if(!$this->add_wrappers($domain)){
            return false;
        }
        */
        
        return true;
    }


    /* ----------------------------------------------------------------- */
    /** This function should be launched by cron as root every once in a while 
     * (1min is fine as long as you use a flock) 
     * it is creating or deleting virtual robots as required.
     * it is logging into syslog as AlternC-Sympa
     */
    function cron_update() {
        global $db;
        $somethingchanged=false;
        openlog("[AlternC-Sympa]",null,LOG_USER);
        
        // Robots creation
        $db->query("SELECT * FROM sympa WHERE sympa_action='CREATE';");
        while ($db->next_record()) {
            $creates[]=$db->Record;
        }
        foreach($creates as $create) {
            $weburl = $create["websub"].(($create["websub"])?".":"").$create["web"];
            syslog(LOG_INFO,"Creating Sympa virtual robot for host ".$create["mail"]." and web interface https://".$weburl);

            mkdir("/etc/sympa/".$create["mail"],0770);
            chown("/etc/sympa/".$create["mail"],"sympa");
            chgrp("/etc/sympa/".$create["mail"],"sympa");
            mkdir("/var/lib/sympa/list_data/".$create["mail"],0770);
            chown("/var/lib/sympa/list_data/".$create["mail"],"sympa");
            chgrp("/var/lib/sympa/list_data/".$create["mail"],"sympa");
            
            $listmasters = implode(",",explode("\n",$create["listmasters"]));
            file_put_contents("/etc/sympa/".$create["mail"]."/robot.conf","#
# Sympa robot configuration for ".$create["mail"]."
#
domain ".$create["mail"]."
listmaster ".$listmasters."
wwsympa_url https:///".$weburl."/wws/
title   Sympa Mailing List Service
default_home  home
create_list listmaster
");
            $somethingchanged=true;
            $code="OK";
            $result="";
            $db->query("UPDATE sympa SET sympa_action='$code', sympa_result='$result' WHERE id=".$create["id"].";"); 
        }

        // Robots destruction
        $db->query("SELECT * FROM sympa WHERE sympa_action='DELETE';");
        while ($db->next_record()) {
            $deletes[]=$db->Record;
        }
        foreach($deletes as $delete) {
            $weburl = $delete["websub"].(($delete["websub"])?".":"").$delete["web"];
            syslog(LOG_INFO,"Deleting Sympa virtual robot for host ".$delete["mail"]." and web interface https://".$weburl);

            exec("rm -rf ".escapeshellarg("/etc/sympa/".$delete["mail"]));
            exec("rm -rf ".escapeshellarg("/var/lib/sympa/list_data/".$delete["mail"]));
            
            $somethingchanged=true;
            $db->query("DELETE FROM sympa WHERE id=".$delete["id"].";"); 
        }
        
        
        if ($somethingchanged) {
            $this->restart_sympa();
        }
        
    }


    /* ----------------------------------------------------------------- */
    /** Restart all sympa services
     * MUST be launched as root of course
     */
    function restart_sympa() {
            putenv("PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin");

            $out=[];
            exec("service sympa restart 2>&1",$out,$res);
            if ($res!=0) {
                syslog(LOG_ERR,"Can't restart sympa, please check, output was\n".implode("\n",$out));
            } else {
                syslog(LOG_INFO,"Sympa restarted after a robot creation / destruction");
            }

            $out=[];
            exec("service sympasoap restart 2>&1",$out,$res);
            if ($res!=0) {
                syslog(LOG_ERR,"Can't restart sympasoap, please check, output was\n".implode("\n",$out));
            } else {
                syslog(LOG_INFO,"Sympasoap restarted after a robot creation / destruction");
            }

            $out=[];
            exec("service wwsympa restart 2>&1",$out,$res);
            if ($res!=0) {
                syslog(LOG_ERR,"Can't restart wwsympa, please check, output was\n".implode("\n",$out));
            } else {
                syslog(LOG_INFO,"WWSympa restarted after a robot creation / destruction");
            }
    }

    
    /* ----------------------------------------------------------------- */
    /** Delete a virtual robot
     * @param $id integer the id number of the robot in alternc's database
     * @return boolean TRUE if the robot has been deleted or FALSE if an error occured
     */
    function delete_robot($id) {
        global $db,$msg,$dom,$mail,$cuid;
        $msg->log("sympa","delete_robot",$id);
        // We delete robot only in the current member's account
        $db->query("SELECT * FROM sympa WHERE id=$id and uid='$cuid';");
        $db->next_record();
        if (!$db->f("id")) {
            $msg->raise("ERROR","sympa",_("This robot does not exist"));
            return false;
        }
        if ($db->f("sympa_action")!='OK') {
            $msg->raise("ERROR","sympa",_("This domain has pending action, you cannot delete it"));
            return false;
        }
        $db->query("UPDATE sympa SET sympa_action='DELETE' WHERE id=$id");
        
        return true;
    }



    /* ----------------------------------------------------------------- */
    /** Quota name
     */
    function hook_quota_names() {
        return array("sympa"=>_("Mailing lists (Sympa)"));
    }


    /* ----------------------------------------------------------------- */
    /** This function is a hook who is called each time a domain is uninstalled
     * in an account (or when we select "gesmx = no" in the domain panel.)
     * @param string $dom_id Domaine to delete
     * @return boolean TRUE if the domain has been deleted from sympa
     * @access private
     */
    function hook_dom_del_mx_domain($dom_id) {
        global $msg,$dom;
        // if there is a robot, delete it
        $db->query("SELECT * FROM sympa WHERE mail_domain_id=".$dom_id.";");
        if ($db->next_record()) {
            $this->delete_all_lists($db->f("id"));
            $db->query("UPDATE sympa SET sympa_action='DELETE' WHERE mail_domain_id=".$dom_id.";");
        }
        return true;
    }


    /* ----------------------------------------------------------------- */
    /** Delete all lists for a robot. Not coded as of now (see TODO) 
     * but will be needed for a proper release :) 
     */
    function delete_all_lists($id) {
        // TODO: code me :) 
    }

    
    /* ----------------------------------------------------------------- */
    /** Returns the quota for the current account as an array
     * @return array an array with used (key 'u') and totally available (key 't') quota for the current account.
     * or FALSE if an error occured
     * @access private
     */ 
    function hook_quota_get() {
        global $msg,$cuid,$db;
        $msg->log("sympa","getquota");
        $q=Array("name"=>"sympa", "description"=>_("Sympa Mailing lists"), "used"=>0);
        $db->query("SELECT COUNT(*) AS cnt FROM sympa WHERE uid='$cuid'");
        if ($db->next_record()) {
            $q['used']=($db->f("cnt")!=0);
        }
        return $q;
    }

        



} /* Class m_sympa */

