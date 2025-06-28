#!/bin/sh

# CLI batch installation script for Debian 12 Bookworm

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

echo "Preparing to installation.."
apt update 
apt -y upgrade 

echo "Installing basic software required.."
apt install -y net-tools 
apt install -y gnupg2 
apt install -y dialog


GEN_MYS_PASS=`dd if=/dev/urandom count=128 bs=1 2>&1 | md5sum | cut -b-8`
echo "mys"${GEN_MYS_PASS} > /tmp/wrmypass
MYSQL_PASSWD=`cat /tmp/wrmypass`
rm -fr /tmp/wrmypass

echo "Everything is okay! Installation is starting."

mkdir ${INSTALLER_WORK_DIR}
cd ${INSTALLER_WORK_DIR}


apt install -y software-properties-common dirmngr 
apt install -y mariadb-server 
apt install -y mariadb-client 
apt install -y libmariadb-dev 
apt install -y default-libmysqlclient-dev 

mariadb --version 

systemctl start mariadb  
systemctl enable mariadb  

apt install -y expat 
apt install -y libexpat1-dev 
apt install -y sudo 
apt install -y curl 
apt install -y apache2 
apt install -y libapache2-mod-php8.2 
apt install -y build-essential 
apt install -y libxmlrpc-c++8-dev 
apt install -y ipset 
apt install -y memcached 
apt install -y redis 
apt install -y php8.2-cli 
apt install -y php8.2-mysql 
apt install -y php8.2-mysqli 
apt install -y php8.2-mbstring 
apt install -y php8.2-bcmath 
apt install -y php8.2-curl 
apt install -y php8.2-gd 
apt install -y php8.2-snmp 
apt install -y php8.2-soap 
apt install -y php8.2-zip 
apt install -y php8.2-imap 
apt install -y php8.2-tokenizer 
apt install -y php8.2-xml 
apt install -y php8.2-xmlreader 
apt install -y php8.2-xmlwriter 
apt install -y php8.2-simplexml 
apt install -y php8.2-sqlite3 
apt install -y php8.2-sockets 
apt install -y php8.2-opcache 
apt install -y php8.2-pdo 
apt install -y php8.2-pdo-sqlite 
apt install -y php8.2-phar 
apt install -y php8.2-posix 
apt install -y php8.2-memcached 
apt install -y php8.2-redis 
apt install -y ffmpeg 
apt install -y graphviz 
apt install -y vim-tiny 
apt install -y elinks 
apt install -y mc 
apt install -y nano 
apt install -y nmap 
apt install -y mtr 
apt install -y expect 
apt install -y git 
apt install -y netdiag 
apt install -y htop 
apt install -y rsyslog 


cd ${INSTALLER_WORK_DIR}
$FETCH ${DL_WR_URL}${DL_WR_NAME}
if [ -f ${DL_WR_NAME} ];
then
echo "=== Success: WolfRecorder release downloaded. ==="
else
echo "=== Error: WolfRecorder release is not available. Installation is aborted. ==="
exit
fi

mkdir ${APACHE_DATA_PATH}${WR_WEB_DIR}
cp -R ${DL_WR_NAME} ${APACHE_DATA_PATH}${WR_WEB_DIR}
cd ${APACHE_DATA_PATH}${WR_WEB_DIR}
tar zxvf ${DL_WR_NAME} 
chmod -R 777 content/ config/ exports/ howl/
cp -R dist/presets/debian121/${APACHE_CONFIG_PRESET_NAME} ${APACHE_CONFIG_DIR}${APACHE_CONFIG_NAME}
cp -R dist/presets/debian121/${PHP_CONFIG_PRESET} /etc/php/8.2/apache2/php.ini
cp -R dist/presets/debian121/000-default.conf ${APACHE_CONFIG_DIR}sites-enabled/000-default.conf
cp -R dist/landing/index.html ${APACHE_DATA_PATH}/index.html
cp -R dist/landing/bg.gif ${APACHE_DATA_PATH}/
${APACHE_INIT_SCRIPT} start 
${MYSQL_INIT_SCRIPT} start 
${CACHE_INIT_SCRIPT} start 

mysqladmin -u root password ${MYSQL_PASSWD} 

perl -e "s/mylogin/root/g" -pi ./config/mysql.ini
perl -e "s/newpassword/${MYSQL_PASSWD}/g" -pi ./config/mysql.ini

cp -R dist/presets/debian121/binpaths.ini ./config/binpaths.ini
cat dist/dumps/wolfrecorder.sql | /usr/bin/mysql -u root --password=${MYSQL_PASSWD} 
cat dist/dumps/defaultstorage.sql | /usr/bin/mysql -u root  -p wr --password=${MYSQL_PASSWD} 
mkdir /wrstorage
chmod 777 /wrstorage
touch ./exports/FIRST_INSTALL
chmod 777 ./exports/FIRST_INSTALL
cp -R dist/wrap/deb121_wrapi /bin/wrapi
chmod a+x /bin/wrapi
echo "User_Alias WOLFRECORDER = www-data" >> /etc/sudoers.d/wolfrecorder
echo "WOLFRECORDER         ALL = NOPASSWD: ALL" >> /etc/sudoers.d/wolfrecorder
/usr/sbin/a2enmod headers
/usr/sbin/a2enmod expires
${APACHE_INIT_SCRIPT} restart 

cd ${APACHE_DATA_PATH}${WR_WEB_DIR}
/usr/bin/curl -o /dev/null "http://127.0.0.1/${WR_WEB_DIR}?module=remoteapi&action=identify&param=save" 
sleep 3
NEW_WRSERIAL=`cat ./exports/wrserial`

if [ -n "$NEW_WRSERIAL" ];
then
echo "OK: new WolfRecorder serial ${NEW_WRSERIAL}" 
else
echo "No new WolfRecorder serial generated: ${NEW_WRSERIAL}"
exit
fi

crontab ./dist/crontab/crontab.preconf
perl -e "s/WR00000000000000000000000000000000/${NEW_WRSERIAL}/g" -pi /bin/wrapi
cp -R ./dist/presets/debian121/autowrupdate.sh /bin/
chmod a+x /bin/autowrupdate.sh

cd /
rm -fr ${INSTALLER_WORK_DIR}

echo "WolfRecorder installation has been completed"
