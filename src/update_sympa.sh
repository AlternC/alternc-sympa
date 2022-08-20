#!/bin/bash
# This script look in the database wich mailman list should be CREATED / DELETED / PASSWORDED

# Source some configuration file
for CONFIG_FILE in \
  /etc/alternc/local.sh \
  /usr/lib/alternc/functions.sh
do
  if [ ! -r "$CONFIG_FILE" ]; then
    echo "Can't access $CONFIG_FILE."
    exit 1
  fi
  . "$CONFIG_FILE"
done

LOCK_FILE="/var/run/alternc/update_mailman"

MAILMAN_URL=$(get_variable_from_db mailman_url)
if [ -z "$MAILMAN_URL" -o "$MAILMAN_URL" = "0" ]; then
	MAILMAN_URL=$FQDN
fi

# Somes check before start operations
if [ `id -u` -ne 0 ]; then
  log_error "must be launched as root"
elif [ -f "$LOCK_FILE" ]; then
  process=$(ps f -p `cat "$LOCK_FILE"|tail -1`|tail -1|awk '{print $NF;}')
  if [ "$(basename $process)" = "$(basename "$0")" ] ; then
    log_error "last cron unfinished or stale lock file ($LOCK_FILE)."
  else
    rm "$LOCK_FILE"
  fi
fi

# If there is ionice, add it to the command line
ionice=""
ionice > /dev/null && ionice="ionice -c 3 "

# We lock the application
echo $$ > "$LOCK_FILE"

# List the lists to CREATE
mysql_query "SELECT id,list, name, domain, owner, password FROM mailman WHERE mailman_action='CREATE';"|while read id list name domain owner password ; do
  if [ -d "/var/lib/mailman/lists/$name" ]
    then
    mysql_query "UPDATE mailman SET password='', mailman_result='This list already exist', mailman_action='OK' WHERE id='$id';"
    else
      # Create the list : 
      sudo -u list -s /bin/bash /usr/lib/mailman/bin/newlist -q "$list@$domain" "$owner" "$password"
      if [ "$?" -eq "0" ]
      then
        mysql_query "UPDATE mailman SET password='', mailman_result='', mailman_action='OK' WHERE id='$id';"
        sudo -u list /usr/lib/mailman/bin/withlist -q -l -r set_url_alternc "$name" "$MAILMAN_URL"
      if [ "$?" -ne "0" ]
        # SetURL the list with the default fqdn to start: 
      then
        mysql_query "UPDATE mailman SET mailman_result='A fatal error happened when changing the list url', mailman_action='OK' WHERE id='$id';"
      else
        mysql_query "UPDATE mailman SET mailman_action='OK' WHERE id='$id';"
      fi
    fi
  fi
done

# List the lists to DELETE
mysql_query "SELECT id, list, name, domain FROM mailman WHERE mailman_action='DELETE';"|while read id list name domain ; do
  if [ ! -d "/var/lib/mailman/lists/$name" ]
    then
    mysql_query "UPDATE mailman SET mailman_result='This list does not exist', mailman_action='OK' WHERE id='$id';"
    else
# Delete the list : 
    mysql_query "UPDATE mailman SET mailman_action='DELETING' WHERE id='$id';"
    sudo -u list /usr/lib/mailman/bin/rmlist "$name"
    if [ "$?" -eq "0" ]
      then
      # Now delete the archives too ...
      sudo -u list /usr/lib/mailman/bin/rmlist -a "$name"
      mysql_query "DELETE FROM mailman WHERE id='$id';"
    else
      mysql_query "UPDATE mailman SET mailman_result='A fatal error happened when deleting the list', mailman_action='OK' WHERE id='$id';"
    fi
  fi
done


# List the lists to PASSWORD
mysql_query "SELECT id, list, name, domain, password FROM mailman WHERE mailman_action='PASSWORD';"|while read id list name domain password ; do
  if [ ! -d "/var/lib/mailman/lists/$name" ]
    then
    mysql_query "UPDATE mailman SET mailman_result='This list does not exist', mailman_action='OK', password='' WHERE id='$id';"
    else
# Password the list : 
    sudo -u list /usr/lib/mailman/bin/change_pw -l "$name" -p "$password"
    if [ "$?" -eq "0" ]
      then
      mysql_query "UPDATE mailman SET password='', mailman_result='', mailman_action='OK' WHERE id='$id';"
    else
      mysql_query "UPDATE mailman SET password='', mailman_result='A fatal error happened when changing the list password', mailman_action='OK' WHERE id='$id';"
    fi
  fi
done


# List the lists to GETURL
mysql_query "SELECT id, list, name, domain FROM mailman WHERE mailman_action='GETURL';"|while read id list name domain ; do
  if [ ! -d "/var/lib/mailman/lists/$name" ]
    then
    mysql_query "UPDATE mailman SET mailman_result='This list does not exist', mailman_action='OK' WHERE id='$id';"
  else
# Get the list's URL : 
    URL=`sudo -u list /usr/lib/mailman/bin/withlist -q -l -r get_url_alternc "$name"  2>/dev/null `
    if [ "$?" -eq "0" ]
      then
      mysql_query "UPDATE mailman SET mailman_result='', mailman_action='OK', url='$URL' WHERE id='$id';"
    else
      mysql_query "UPDATE mailman SET mailman_result='A fatal error happened when getting the list url', mailman_action='OK' WHERE id='$id';"
    fi
  fi
done


# List the lists to REGENERATE
mysql_query "SELECT id, list, name, domain, url FROM mailman WHERE mailman_action='REGENERATE';"|while read id list name domain url ; do
#non virtual lists
if [ "$list" == "$name" ]; then
  if [ ! -d "/var/lib/mailman/lists/$list" ]
    then
    mysql_query "UPDATE mailman SET mailman_result='This list does not exist', mailman_action='OK' WHERE id='$id';"
  else
   	sudo -u list /usr/lib/mailman/bin/withlist -q -l -r set_url_alternc "$name" "$MAILMAN_URL"
    if [ "$?" -eq "0" ]
      then
      mysql_query "UPDATE mailman SET mailman_result='', mailman_action='OK' WHERE id='$id';"
    else
      mysql_query "UPDATE mailman SET mailman_result='A fatal error happened when changing the list url', mailman_action='OK' WHERE id='$id';"
    fi
  fi
else
#virtual lists
  if [ ! -d "/var/lib/mailman/lists/$name" ];then
  #virtual list just just virtualised ( /var/lib/mailman/lists/$list exists )
    sudo -u list /usr/lib/mailman/bin/withlist -q -l -r set_url_alternc "$name" "$MAILMAN_URL"
    if [ "$?" -eq "0" ]
      then
      mysql_query "UPDATE mailman SET mailman_result='', mailman_action='OK' WHERE id='$id';"
    else
      mysql_query "UPDATE mailman SET mailman_result='A fatal error happened when changing the list url', mailman_action='OK' WHERE id='$id';"
    fi
  #move the list to match its new name : 
    mv "/var/lib/mailman/lists/$list" "/var/lib/mailman/lists/$name"
    mv "/var/lib/mailman/archives/private/$list" "/var/lib/mailman/archives/private/$name"
    mv "/var/lib/mailman/archives/private/$list.mbox" "/var/lib/mailman/archives/private/$name.mbox"
    mv "/var/lib/mailman/archives/private/$name.mbox/$list.mbox" "/var/lib/mailman/archives/private/$name.mbox/$name.mbox"
    if [ -e "/var/lib/mailman/archives/private/$name.mbox/$name.mbox" ];then
      /usr/lib/mailman/bin/arch --wipe $name
      if [ "$?" -eq "0" ]
        then
        mysql_query "UPDATE mailman SET mailman_result='', mailman_action='REGENERATE-2' WHERE id='$id';"
      else
        mysql_query "UPDATE mailman SET mailman_result='A fatal error happened when regenerating the list', mailman_action='OK' WHERE id='$id';"
      fi
    fi
  else
    mysql_query "UPDATE mailman SET mailman_result='', mailman_action='REGENERATE-2' WHERE id='$id';"
  fi
fi
done

# List the lists to SETURL
mysql_query "SELECT id, list, name, domain, url FROM mailman WHERE mailman_action='SETURL';"|while read id list name domain url ; do
  if [ ! -d "/var/lib/mailman/lists/$name" ]
    then
    mysql_query "UPDATE mailman SET mailman_result='This list does not exist', mailman_action='OK' WHERE id='$id';"
  else
# SetURL the list : 
    	sudo -u list /usr/lib/mailman/bin/withlist -q -l -r set_url_alternc "$name" "$MAILMAN_URL"
    if [ "$?" -eq "0" ]
      then
      mysql_query "UPDATE mailman SET mailman_result='', mailman_action='OK' WHERE id='$id';"
    else
      mysql_query "UPDATE mailman SET mailman_result='A fatal error happened when changing the list url', mailman_action='OK' WHERE id='$id';"
    fi
  fi
done

# Delete the lock
rm -f "$LOCK_FILE"
