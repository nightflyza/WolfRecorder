#!/bin/sh

# 
# Acta, non verba
# 

OS_NAME=`uname`

case $OS_NAME in
FreeBSD)

DIALOG=${DIALOG=dialog}
FETCH="/usr/bin/fetch"
APACHE_VERSION="apache24"
APACHE_DATA_PATH="/usr/local/www/apache24/data/"
APACHE_CONFIG_DIR="/usr/local/etc/apache24/"
APACHE_INIT_SCRIPT="/usr/local/etc/rc.d/apache24"
APACHE_CONFIG_PRESET_NAME="httpd24f7.conf"
APACHE_CONFIG_NAME="httpd.conf"
PHP_CONFIG_PRESET="php.ini"
MYSQL_INIT_SCRIPT="/usr/local/etc/rc.d/mysql-server"
CACHE_INIT_SCRIPT="/usr/local/etc/rc.d/memcached"
WR_WEB_DIR="wr/"
INSTALLER_WORK_DIR="/usr/local/wrinstaller/"
INSTALLER_LOG="/var/log/wrinstaller.log"

#some remote paths here
DL_PACKAGES_URL="http://wolfrecorder.com/packages/"
DL_PACKAGES_EXT=".tar.gz"
DL_WR_URL="http://wolfrecorder.com/"
DL_WR_NAME="wr.tgz"

set PATH=/usr/local/bin:/usr/local/sbin:$PATH

# config interface section 
clear
$DIALOG --title "WolfRecorder NVR installation" --msgbox "This wizard helps you to install WolfRecorder of the latest stable version to CLEAN (!) FreeBSD distribution" 10 50
clear

#new or migration installation
clear
$DIALOG --menu "Type of WolfRecorder installation" 10 75 8 \
                   NEW "This is new WolfRecorder installation"\
                   MIG "Migrating existing WolfRecorder setup from another host"\
            2> /tmp/insttype

clear

$DIALOG --menu "Choose FreeBSD version and architecture" 16 50 8 \
                   132_6E "FreeBSD 13.2 amd64"\
		   131_6T "FreeBSD 13.1 amd64"\
                   131_3T "FreeBSD 13.1 i386"\
                   124_6T "FreeBSD 12.4 amd64"\
                   124_6E "FreeBSD 12.4 amd64"\
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
$DIALOG --title "WolfRecorder serial"  --inputbox "Enter your previous installation WolfRecorder serial number" 8 60 2> /tmp/wrsrl
;;
esac

#setting some opts for future
MYSQL_PASSWD=`cat /tmp/wrmypass`
ARCH=`cat /tmp/wrarch`
case $PASSW_MODE in
NEW)
WRSERIAL="AUTO"
;;
MIG)
WRSERIAL=`cat /tmp/wrsrl`
;;
esac

# cleaning temp files
rm -fr /tmp/wrarch
rm -fr /tmp/wrmypass
rm -fr /tmp/wrsrl
rm -fr /tmp/insttype

#last chance to exit
$DIALOG --title "Check settings"   --yesno "Are all of these settings correct? \n \n MySQL password: ${MYSQL_PASSWD} \n System: ${ARCH} \n WolfRecorder serial: ${WRSERIAL}\n" 10 60
AGREE=$?
clear

# confirm installation
case $AGREE in
0)
echo "Everything is okay! Installation is starting."

# preparing for installation
mkdir ${INSTALLER_WORK_DIR}
cd ${INSTALLER_WORK_DIR}

#######################################
#  Platform specific issues handling  #
#######################################

case $ARCH in
124_6E)
#12.4E contains PHP 8.2 binaries
APACHE_CONFIG_PRESET_NAME="httpd24f8.conf"
;;

132_6E)
#13.2E contains PHP 8.2 binaries
APACHE_CONFIG_PRESET_NAME="httpd24f8.conf"
;;
esac

#botstrapping pkg ng
pkg info

#check is FreeBSD installation clean
PKG_COUNT=`/usr/sbin/pkg info | /usr/bin/wc -l`
if [ $PKG_COUNT -ge 2 ]
then
echo "WRinstaller supports setup only for clean FreeBSD distribution. Installation is aborted."
exit
fi

# install prebuilded binary packages
$DIALOG --infobox "Downloading binary packages." 4 60
$FETCH ${DL_PACKAGES_URL}${ARCH}${DL_PACKAGES_EXT}
#check is binary packages download has beed completed
if [ -f ${ARCH}${DL_PACKAGES_EXT} ];
then
$DIALOG --infobox "Binary packages download has been completed." 4 60
else
echo "=== Error: binary packages are not available. Installation is aborted. ==="
exit
fi

# unpacking and installing packages
$DIALOG --infobox "Unpacking binary packages." 4 60
tar zxvf ${ARCH}${DL_PACKAGES_EXT} 2>> ${INSTALLER_LOG}
cd ${ARCH}
$DIALOG --infobox "Software installation is in progress. This takes a while." 4 70
ls -1 | xargs -n 1 pkg add >> ${INSTALLER_LOG}
$DIALOG --infobox "Binary packages installation has been completed." 4 60

################################################
# Downloading and unpacking WolfRecorder distro
################################################


$DIALOG --infobox "WolfRecorder download, unpacking and installation is in progress." 4 60
#back to installation directory
cd ${INSTALLER_WORK_DIR}
# downloading distro
$FETCH ${DL_WR_URL}${DL_WR_NAME}
#check is wolfrecorder distro download complete
if [ -f ${DL_WR_NAME} ];
then
$DIALOG --infobox "WolfRecorder download has been completed." 4 60
else
echo "=== Error: WolfRecorder release is not available. Installation is aborted. ==="
exit
fi

mkdir ${APACHE_DATA_PATH}${WR_WEB_DIR}
cp -R ${DL_WR_NAME} ${APACHE_DATA_PATH}${WR_WEB_DIR}
cd ${APACHE_DATA_PATH}${WR_WEB_DIR}

tar zxvf ${DL_WR_NAME} 2>> ${INSTALLER_LOG}
chmod -R 777 content/ config/ exports/ howl/

# setting up config presets
cp -R dist/presets/freebsd/${APACHE_CONFIG_PRESET_NAME} ${APACHE_CONFIG_DIR}${APACHE_CONFIG_NAME}
cp -R dist/presets/freebsd/${PHP_CONFIG_PRESET} /usr/local/etc/php.ini
cat dist/presets/freebsd/rc.preconf >> /etc/rc.conf
cat dist/presets/freebsd/sysctl.preconf >> /etc/sysctl.conf
cat dist/presets/freebsd/loader.preconf >> /boot/loader.conf
cp -R dist/presets/freebsd/firewall.conf /etc/firewall.conf
chmod a+x /etc/firewall.conf

# setting up default web awesomeness
cp -R dist/landing/index.html ${APACHE_DATA_PATH}/index.html
cp -R dist/landing/bg.gif ${APACHE_DATA_PATH}/

# start reqired services
$DIALOG --infobox "Starting web server.." 4 60
${APACHE_INIT_SCRIPT} start 2>> ${INSTALLER_LOG}
$DIALOG --infobox "Starting database server.." 4 60
${MYSQL_INIT_SCRIPT} start 2>> ${INSTALLER_LOG}
$DIALOG --infobox "Starting caching server.." 4 60
${CACHE_INIT_SCRIPT} start 2>> ${INSTALLER_LOG}

#Setting MySQL root password
mysqladmin -u root password ${MYSQL_PASSWD} 2>> ${INSTALLER_LOG}


# updating passwords and login in mysql.ini
perl -e "s/mylogin/root/g" -pi ./config/mysql.ini
perl -e "s/newpassword/${MYSQL_PASSWD}/g" -pi ./config/mysql.ini

# creating wr database
$DIALOG --infobox "Creating initial WolfRecorder DB" 4 60
cat dist/dumps/wolfrecorder.sql | /usr/local/bin/mysql -u root --password=${MYSQL_PASSWD} 2>> ${INSTALLER_LOG}

# creation default storage
$DIALOG --infobox "Creating default storage" 4 60
cat dist/dumps/defaultstorage.sql | /usr/local/bin/mysql -u root  -p wr --password=${MYSQL_PASSWD} 2>> ${INSTALLER_LOG}
mkdir /wrstorage
chmod 777 /wrstorage

# first install flag setup for the future
touch ./exports/FIRST_INSTALL
chmod 777 ./exports/FIRST_INSTALL

# unpacking wrapi preset
cp -R dist/wrap/wrapi /bin/
chmod a+x /bin/wrapi
$DIALOG --infobox "remote API wrapper installed" 4 60

# updating sudoers
echo "User_Alias WOLFRECORDER = www" >> /usr/local/etc/sudoers
echo "WOLFRECORDER         ALL = NOPASSWD: ALL" >> /usr/local/etc/sudoers

#disabling mysql>=5.6 strict trans tables in various config locations
if [ -f /usr/local/my.cnf ];
then
perl -e "s/,STRICT_TRANS_TABLES//g" -pi /usr/local/my.cnf
$DIALOG --infobox "Disabling MySQL STRICT_TRANS_TABLES in /usr/local/my.cnf done" 4 60
else
$DIALOG --infobox "Looks like no MySQL STRICT_TRANS_TABLES disable required in /usr/local/my.cnf" 4 60
fi

if [ -f /usr/local/etc/my.cnf ];
then
perl -e "s/,STRICT_TRANS_TABLES//g" -pi /usr/local/etc/my.cnf
$DIALOG --infobox "Disabling MySQL STRICT_TRANS_TABLES in /usr/local/etc/my.cnf done" 4 60
else
$DIALOG --infobox "Looks like no MySQL STRICT_TRANS_TABLES disable required in /usr/local/etc/my.cnf" 4 60
fi

if [ -f /usr/local/etc/mysql/my.cnf ];
then
perl -e "s/,STRICT_TRANS_TABLES//g" -pi /usr/local/etc/mysql/my.cnf
$DIALOG --infobox "Disabling MySQL STRICT_TRANS_TABLES in /usr/local/etc/mysql/my.cnf done" 4 60
else
$DIALOG --infobox "Looks like no MySQL STRICT_TRANS_TABLES disable required in /usr/local/etc/mysql/my.cnf" 4 60
fi

#initial crontab configuration
cd ${APACHE_DATA_PATH}${WR_WEB_DIR}
if [ -f ./dist/crontab/crontab.preconf ];
then
#generating new WolfRecorder serial or using predefined
case $PASSW_MODE in
NEW)
/usr/local/bin/curl -o /dev/null "http://127.0.0.1/${WR_WEB_DIR}?module=remoteapi&action=identify&param=save" 2>> ${INSTALLER_LOG}
NEW_WRSERIAL=`cat ./exports/wrserial`
$DIALOG --infobox "New WolfRecorder serial generated: ${NEW_WRSERIAL}" 4 60
;;
MIG)
NEW_WRSERIAL=${WRSERIAL}
$DIALOG --infobox "Using WolfRecorder serial: ${NEW_WRSERIAL}" 4 60
;;
esac

#loading default crontab preset
crontab ./dist/crontab/crontab.preconf
$DIALOG --infobox "Installing default crontab preset" 4 60
# updating serial in wrapi wrapper
perl -e "s/WR00000000000000000000000000000000/${NEW_WRSERIAL}/g" -pi /bin/wrapi
$DIALOG --infobox "New serial installed into wrapi wrapper" 4 60
else
echo "Looks like this WolfRecorder release is not supporting automatic crontab configuration"
fi

# Setting up autoupdate sctipt
cp -R ./dist/presets/freebsd/autowrupdate.sh /bin/
chmod a+x /bin/autowrupdate.sh

#cleaning up installer work directory
cd /
rm -fr ${INSTALLER_WORK_DIR}

$DIALOG --title "WolfRecorder installation has been completed" --msgbox "Now you can access your web-interface by address http://server_ip/${WR_WEB_DIR} with login and password: admin/demo. Please reboot your server to check correct startup of all services" 15 50

# Finishing installation

;;
#cancel installstion
1)
echo "Installation has been aborted"
exit
;;
esac
#
# End of FreeBSD setup
#
;;

Linux)
 echo "Coming soon..."
 # to be continued
;;
esac