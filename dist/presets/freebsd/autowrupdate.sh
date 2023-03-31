#!/bin/sh

######################## CONFIG SECTION ########################

#dialog
DIALOG="/usr/bin/dialog"

#fetch software
FETCH="/usr/bin/fetch"

#tar binary
TAR="/usr/bin/tar"

# path to your apache data dir
APACHE_DATA_PATH="/usr/local/www/apache24/data/"

# wolf recorder path
WOLFRECORDER_PATH="wr/"

#update log file
LOG_FILE="/var/log/wolfrecorderupdate.log"

#restore point dir
RESTORE_POINT="/tmp/wr_restore"

#defaults
WOLFRECORDER_RELEASE_URL="http://WolfRecorder.com/"
WOLFRECORDER_RELEASE_NAME="wr.tgz"

######################## INTERFACE SECTION ####################

if [ $# -ne 1 ]
then
#interactive mode
$DIALOG --title "WolfRecorder update" --msgbox "This wizard help you to update your WolfRecorder installation to the the latest stable or current development release" 10 40
clear
$DIALOG --menu "Choose a WolfRecorder release branch to which you want to update." 11 65 6 \
 	   	   STABLE "WolfRecorder latest stable release (recommended)"\
 	   	   CURRENT "WolfRecorder current development snapshot"\
            2> /tmp/auprelease
clear

BRANCH=`cat /tmp/auprelease`
rm -fr /tmp/auprelease

#last chance to exit
$DIALOG --title "Check settings"   --yesno "Are all of these settings correct? \n \n WolfRecorder release: ${BRANCH}\n Installation full path: ${APACHE_DATA_PATH}${WOLFRECORDER_PATH}\n" 9 70
AGREE=$?
clear

else
#getting branch from CLI 1st param in batch mode
BRANCH=$1
AGREE="0"
fi

case $BRANCH in
STABLE)
WOLFRECORDER_RELEASE_URL="http://WolfRecorder.com/"
WOLFRECORDER_RELEASE_NAME="wr.tgz"
;;

CURRENT)
WOLFRECORDER_RELEASE_URL="http://snaps.wolfrecorder.com/"
WOLFRECORDER_RELEASE_NAME="wr_current.tgz"
;;
esac


######################## END OF CONFIG ########################
case $AGREE in
0)
echo "=== Start WolfRecorder auto update ==="
cd ${APACHE_DATA_PATH}${WOLFRECORDER_PATH}

echo "=== Downloading new release ==="
$FETCH ${WOLFRECORDER_RELEASE_URL}${WOLFRECORDER_RELEASE_NAME}

if [ -f ${WOLFRECORDER_RELEASE_NAME} ];
then

echo "=== Creating restore point ==="
mkdir ${RESTORE_POINT} 2> /dev/null
rm -fr ${RESTORE_POINT}/*

echo "=== Move new release to safe place ==="
cp -R ${WOLFRECORDER_RELEASE_NAME} ${RESTORE_POINT}/

echo "=== Backup current data ==="

mkdir ${RESTORE_POINT}/config
mkdir ${RESTORE_POINT}/content
mkdir ${RESTORE_POINT}/howl


# backup of actual configs and administrators
cp .htaccess ${RESTORE_POINT}/ 2> /dev/null
cp favicon.ico ${RESTORE_POINT}/ 2> /dev/null

cp ./config/alter.ini ${RESTORE_POINT}/config/
cp ./config/mysql.ini ${RESTORE_POINT}/config/
cp ./config/ymaps.ini ${RESTORE_POINT}/config/
cp ./config/yalf.ini ${RESTORE_POINT}/config/
cp ./config/binpaths.ini ${RESTORE_POINT}/config/
cp -R ./content/users ${RESTORE_POINT}/content/
cp -R ./content/backups ${RESTORE_POINT}/content/
cp -R ./config/mymodeltemplates ${RESTORE_POINT}/config/
cp -R ./howl/* ${RESTORE_POINT}/howl/


echo "=== web directory cleanup ==="
rm -fr ${APACHE_DATA_PATH}${WOLFRECORDER_PATH}/*

echo "=== Unpacking new release ==="
cp  -R ${RESTORE_POINT}/${WOLFRECORDER_RELEASE_NAME} ${APACHE_DATA_PATH}${WOLFRECORDER_PATH}/
echo ${BRANCH} >> ${LOG_FILE}
echo `date` >> ${LOG_FILE}
echo "====================" >> ${LOG_FILE}
$TAR zxvf ${WOLFRECORDER_RELEASE_NAME} 2>> ${LOG_FILE}
rm -fr ${WOLFRECORDER_RELEASE_NAME}

echo "=== Restoring configs ==="
cp -R ${RESTORE_POINT}/* ./
rm -fr ${WOLFRECORDER_RELEASE_NAME}

echo "=== Setting FS permissions ==="
chmod -R 777 content/ config/ exports/ howl/

echo "=== Updating autoupdater ==="
cp -R ./dist/presets/freebsd/autowrupdate.sh /bin/

echo "=== Executing post-install API callback ==="
/bin/wrapi "autoupdatehook" 2>> ${LOG_FILE}

echo "=== Deleting restore poing ==="
rm -fr ${RESTORE_POINT}
NEW_RELEASE=`cat RELEASE`
echo "SUCCESS: WolfRecorder update successfully completed. Now your installation release is: ${NEW_RELEASE}"

#release file not dowloaded
else
echo "ERROR: No new WolfRecoder release file found, update aborted"
fi

;;
1)
echo "Update has been canceled"
exit
;;
esac
