#!/usr/bin/php -q
<?php

/*
  This script look in the database wich sympa robots should be CREATED / DELETED
  it can be launched every minute (with a lock)
*/

if (posix_getuid()!=0) {
    echo "FATAL: this crontab MUST be launched as root, since it's creating / deleting files for Sympa and Apache.\n";
    exit();
}

require("/usr/share/alternc/panel/class/config_nochk.php");
$admin->enabled=1;

$sympa->cron_update();

