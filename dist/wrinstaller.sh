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
APACHE_CONFIG_PRESET_NAME="httpd24f8.conf"
APACHE_CONFIG_NAME="httpd.conf"
PHP_CONFIG_PRESET="php8.ini"
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
                   140_6K "FreeBSD 14.0 amd64"\
                   133_6K "FreeBSD 13.3 amd64"\
                   132_6E "FreeBSD 13.2 amd64"\
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
140_6K)
# 14.0K contains PHP 8.3 binaries
#APACHE_CONFIG_PRESET_NAME="httpd24f8.conf"
#PHP_CONFIG_PRESET="php8.ini"
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

# database specific issues handling
case $ARCH in
133_6K)
# MySQL 8.0 requires custom config
cp -R dist/presets/freebsd/80_my.cnf /usr/local/etc/mysql/my.cnf 
$DIALOG --infobox "MySQL 8.0 config replaced" 4 60
;;

140_6K)
# MySQL 8.0 requires custom config
cp -R dist/presets/freebsd/80_my.cnf /usr/local/etc/mysql/my.cnf 
$DIALOG --infobox "MySQL 8.0 config replaced" 4 60
;;
esac

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
 # START of Linux installation scripts here
    DIALOG="dialog"
INSTALLER_LOG="/var/log/wrinstaller.log"

#initial repos update
echo "Preparing to installation.."
apt update >>  ${INSTALLER_LOG}  2>&1
apt -y upgrade >>  ${INSTALLER_LOG}  2>&1

#installation of basic software required for installer
echo "Installing basic software required for Debianstaller.."
apt install -y dialog >> ${INSTALLER_LOG}  2>&1
apt install -y net-tools >> ${INSTALLER_LOG}  2>&1
apt install -y gnupg2 >> ${INSTALLER_LOG}  2>&1


$DIALOG --menu "Choose your Linux distribution" 16 50 8 \
                   DEB121 "Debian 12.1 Bookworm"\
        2> /tmp/wrarch
clear

ARCH=`cat /tmp/wrarch`

case $ARCH in 
DEB121)
#some remote paths here
FETCH="/usr/bin/wget"
APACHE_VERSION="apache24"
APACHE_DATA_PATH="/var/www/html/"
APACHE_CONFIG_DIR="/etc/apache2/"
APACHE_INIT_SCRIPT="/usr/sbin/service apache2"
APACHE_CONFIG_PRESET_NAME="debi12_apache2.conf"
APACHE_CONFIG_NAME="apache2.conf"
PHP_CONFIG_PRESET="php82.ini"
MYSQL_INIT_SCRIPT="/usr/sbin/service mariadb"
CACHE_INIT_SCRIPT="/usr/sbin/service memcached"
WR_WEB_DIR="wr/"
INSTALLER_WORK_DIR="/usr/local/wrinstaller/"

DL_WR_URL="http://wolfrecorder.com/"
DL_WR_NAME="wr.tgz"

# config interface section 
clear
$DIALOG --title "WolfRecorder NVR installation" --msgbox "This wizard helps you to install WolfRecorder of the latest stable version to CLEAN (!) Linux distribution" 10 50
clear

#new or migration installation
clear
$DIALOG --menu "Type of WolfRecorder installation" 10 75 8 \
                   NEW "This is new WolfRecorder installation"\
                   MIG "Migrating existing WolfRecorder setup from another host"\
            2> /tmp/insttype

clear


#some passwords generation or manual input
PASSW_MODE=`cat /tmp/insttype`

case $PASSW_MODE in
NEW)
#generating mysql password
GEN_MYS_PASS=`dd if=/dev/urandom count=128 bs=1 2>&1 | md5sum | cut -b-8`
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


# install binary packages from repos
$DIALOG --infobox "Software installation is in progress. This takes a while." 4 70

#MariaDB setup
apt install -y software-properties-common dirmngr >> ${INSTALLER_LOG} 2>&1
$DIALOG --infobox "Installing MariaDB" 4 60
$DIALOG --infobox "Installing MariaDB." 4 60
$DIALOG --infobox "Installing MariaDB.." 4 60
$DIALOG --infobox "Installing MariaDB..." 4 60
apt install -y mariadb-server >> ${INSTALLER_LOG} 2>&1
$DIALOG --infobox "Installing MariaDB...." 4 60
apt install -y mariadb-client >> ${INSTALLER_LOG} 2>&1
$DIALOG --infobox "Installing MariaDB....." 4 60
apt install -y libmariadb-dev >> ${INSTALLER_LOG} 2>&1
$DIALOG --infobox "Installing MariaDB......" 4 60
apt install -y default-libmysqlclient-dev >> ${INSTALLER_LOG} 2>&1

$DIALOG --infobox "MariaDB installed" 4 60
mariadb --version >> ${INSTALLER_LOG} 2>&1

systemctl start mariadb  >> ${INSTALLER_LOG} 2>&1
systemctl enable mariadb  >> ${INSTALLER_LOG} 2>&1

$DIALOG --infobox "MariaDB startup enabled" 4 60

$DIALOG --infobox "Installing some required software" 4 60
apt install -y expat >> ${INSTALLER_LOG} 2>&1
apt install -y libexpat1-dev >> ${INSTALLER_LOG} 2>&1
apt install -y sudo >> ${INSTALLER_LOG} 2>&1
apt install -y curl >> ${INSTALLER_LOG} 2>&1
$DIALOG --infobox "Installing Apache server" 4 60
apt install -y apache2 >> ${INSTALLER_LOG} 2>&1
apt install -y libapache2-mod-php8.2 >> ${INSTALLER_LOG} 2>&1
$DIALOG --infobox "Installing misc software" 4 60
apt install -y build-essential >> ${INSTALLER_LOG} 2>&1
apt install -y libxmlrpc-c++8-dev >> ${INSTALLER_LOG} 2>&1
apt install -y ipset >> ${INSTALLER_LOG} 2>&1
$DIALOG --infobox "Installing memory caching servers" 4 60
apt install -y memcached >> ${INSTALLER_LOG} 2>&1
apt install -y redis >> ${INSTALLER_LOG} 2>&1
$DIALOG --infobox "Installing PHP and required extensions" 4 60
apt install -y php8.2-cli >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-mysql >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-mysqli >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-mbstring >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-bcmath >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-curl >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-gd >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-snmp >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-soap >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-zip >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-imap >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-json >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-tokenizer >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-xml >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-xmlreader >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-xmlwriter >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-simplexml >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-sqlite3 >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-sockets >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-opcache >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-json >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-pdo >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-pdo-sqlite >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-phar >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-posix >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-memcached >> ${INSTALLER_LOG} 2>&1
apt install -y php8.2-redis >> ${INSTALLER_LOG} 2>&1
$DIALOG --infobox "Installing ffmpeg" 4 60
apt install -y ffmpeg >> ${INSTALLER_LOG} 2>&1
$DIALOG --infobox "Installing some optional software" 4 60
apt install -y graphviz >> ${INSTALLER_LOG} 2>&1
apt install -y vim-tiny >> ${INSTALLER_LOG} 2>&1
apt install -y arping >> ${INSTALLER_LOG} 2>&1
apt install -y elinks >> ${INSTALLER_LOG} 2>&1
apt install -y mc >> ${INSTALLER_LOG} 2>&1
apt install -y nano >> ${INSTALLER_LOG} 2>&1
apt install -y nmap >> ${INSTALLER_LOG} 2>&1
apt install -y mtr >> ${INSTALLER_LOG} 2>&1
apt install -y expect >> ${INSTALLER_LOG} 2>&1
apt install -y bwm-ng >> ${INSTALLER_LOG} 2>&1
apt install -y git >> ${INSTALLER_LOG} 2>&1
apt install -y netdiag >> ${INSTALLER_LOG} 2>&1
apt install -y htop >> ${INSTALLER_LOG} 2>&1
apt install -y rsyslog >> ${INSTALLER_LOG} 2>&1

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

tar zxvf ${DL_WR_NAME} >> ${INSTALLER_LOG} 2>&1
chmod -R 777 content/ config/ exports/ howl/

# setting up config presets
cp -R dist/presets/debian121/${APACHE_CONFIG_PRESET_NAME} ${APACHE_CONFIG_DIR}${APACHE_CONFIG_NAME}
cp -R dist/presets/debian121/${PHP_CONFIG_PRESET} /etc/php/8.2/apache2/php.ini
cp -R dist/presets/debian121/000-default.conf ${APACHE_CONFIG_DIR}sites-enabled/000-default.conf

# setting up default web awesomeness
cp -R dist/landing/index.html ${APACHE_DATA_PATH}/index.html
cp -R dist/landing/bg.gif ${APACHE_DATA_PATH}/

# start reqired services
$DIALOG --infobox "Starting web server.." 4 60
${APACHE_INIT_SCRIPT} start >> ${INSTALLER_LOG} 2>&1
$DIALOG --infobox "Starting database server.." 4 60
${MYSQL_INIT_SCRIPT} start >> ${INSTALLER_LOG} 2>&1
$DIALOG --infobox "Starting caching server.." 4 60
${CACHE_INIT_SCRIPT} start >> ${INSTALLER_LOG} 2>&1

#Setting MySQL root password
mysqladmin -u root password ${MYSQL_PASSWD} >> ${INSTALLER_LOG} 2>&1


# updating passwords and login in mysql.ini
perl -e "s/mylogin/root/g" -pi ./config/mysql.ini
perl -e "s/newpassword/${MYSQL_PASSWD}/g" -pi ./config/mysql.ini

# updating binary paths in binpaths.ini
cp -R dist/presets/debian121/binpaths.ini ./config/binpaths.ini

# creating wr database
$DIALOG --infobox "Creating initial WolfRecorder DB" 4 60
cat dist/dumps/wolfrecorder.sql | /usr/bin/mysql -u root --password=${MYSQL_PASSWD} >> ${INSTALLER_LOG} 2>&1

# creation default storage
$DIALOG --infobox "Creating default storage" 4 60
cat dist/dumps/defaultstorage.sql | /usr/bin/mysql -u root  -p wr --password=${MYSQL_PASSWD} >> ${INSTALLER_LOG} 2>&1
mkdir /wrstorage
chmod 777 /wrstorage

# first install flag setup for the future
touch ./exports/FIRST_INSTALL
chmod 777 ./exports/FIRST_INSTALL

# unpacking wrapi preset
cp -R dist/wrap/deb121_wrapi /bin/wrapi
chmod a+x /bin/wrapi
$DIALOG --infobox "remote API wrapper installed" 4 60

# updating sudoers
echo "User_Alias WOLFRECORDER = www-data" >> /etc/sudoers.d/wolfrecorder
echo "WOLFRECORDER         ALL = NOPASSWD: ALL" >> /etc/sudoers.d/wolfrecorder

#enabling required apache modules
/usr/sbin/a2enmod headers
/usr/sbin/a2enmod expires

#restarting apache
$DIALOG --infobox "Restarting web server.." 4 60
${APACHE_INIT_SCRIPT} restart >> ${INSTALLER_LOG} 2>&1

#initial crontab configuration
cd ${APACHE_DATA_PATH}${WR_WEB_DIR}
if [ -f ./dist/crontab/crontab.preconf ];
then
#generating new WolfRecorder serial or using predefined
case $PASSW_MODE in
NEW)
/usr/bin/curl -o /dev/null "http://127.0.0.1/${WR_WEB_DIR}?module=remoteapi&action=identify&param=save" >> ${INSTALLER_LOG} 2>&1
#waiting saving data
sleep 3
NEW_WRSERIAL=`cat ./exports/wrserial`
$DIALOG --infobox "New WolfRecorder serial generated: ${NEW_WRSERIAL}" 4 60
;;
MIG)
NEW_WRSERIAL=${WRSERIAL}
$DIALOG --infobox "Using WolfRecorder serial: ${NEW_WRSERIAL}" 4 60
;;
esac

if [ -n "$NEW_WRSERIAL" ];
then
echo "OK: new WolfRecorder serial ${NEW_WRSERIAL}" >> ${INSTALLER_LOG}  2>&1
else
$DIALOG --infobox "No new WolfRecorder serial generated: ${NEW_WRSERIAL}" 4 60
echo "Installation failed and aborted. Empty WolfRecorder serial. Retry your attempt."
echo "FATAL: empty new WolfRecorder serial" >> ${INSTALLER_LOG} 2>&1
exit
fi

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
cp -R ./dist/presets/debian121/autowrupdate.sh /bin/
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
# End of Debian 12.1 script here
#
;;
esac
 # END
;;
esac