#!/bin/sh

# 
# Per aspera ad astra
# 

OS_NAME=`uname`

case $OS_NAME in
FreeBSD)
echo "Acta, non verba"

DIALOG=${DIALOG=dialog}
FETCH="/usr/bin/fetch"
APACHE_VERSION="apache24"
APACHE_DATA_PATH="/usr/local/www/apache24/data/"
APACHE_CONFIG_DIR="/usr/local/etc/apache24/"
APACHE_INIT_SCRIPT="/usr/local/etc/rc.d/apache24"
APACHE_CONFIG_PRESET_NAME="httpd24f7.conf"
APACHE_CONFIG_NAME="httpd.conf"
PHP_CONFIG_PRESET="php.ini"

#some remote paths here
DL_PACKAGES_URL="http://wolfrecorder.com/packages/"
DL_PACKAGES_EXT=".tar.gz"
DL_WR_URL="http://wolfrecorder.com/"
DL_WR_NAME="wr.tgz"

set PATH=/usr/local/bin:/usr/local/sbin:$PATH

# config interface section 
clear
$DIALOG --title "WolfRecorder installation" --msgbox "This wizard helps you to install WolfRecorder of the latest stable version to CLEAN (!) FreeBSD distribution" 10 50
clear

#new or migration installation
clear
$DIALOG --menu "Type of WolfrRecorder installation" 10 75 8 \
                   NEW "This is new WolfRecorder installation"\
                   MIG "Migrating existing WolfRecorder setup from another host"\
            2> /tmp/insttype

clear

$DIALOG --menu "Choose FreeBSD version and architecture" 16 50 8 \
		   131_6T "FreeBSD 13.1 amd64"\
		   130_6T "FreeBSD 13.0 amd64"\
		   124_6T "FreeBSD 12.4 amd64"\
		   123_6T "FreeBSD 12.3 amd64"\
           131_3T "FreeBSD 13.1 i386"\
           124_6E "FreeBSD 12.4 amd64 PHP 8.2"\
 	    2> /tmp/wrarch
clear

#some passwords generation or manual input
PASSW_MODE=`cat /tmp/insttype`

case $PASSW_MODE in
NEW)
#generating mysql password
GEN_MYS_PASS=`dd if=/dev/urandom count=128 bs=1 2>&1 | md5 | cut -b-8`
echo "mys"${GEN_MYS_PASS} > /tmp/wrmypass

;;
MIG)
#request previous MySQL password
clear
$DIALOG --title "MySQL root password"  --inputbox "Enter your previous installation MySQL root password" 8 60 2> /tmp/wrmypass
clear
;;
esac

#
# End of FreeBSD setup
#
;;

Linux)
 # to be continued
;;
esac