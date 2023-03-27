#!/bin/sh
BASEPATH="/usr/local/www/apache24/data/wrsnaps/"

mkdir ${BASEPATH}/exported
cd ${BASEPATH}/tmp/
rm -fr ../wr_current.tgz > /dev/null
rm -fr ../RELEASE > /dev/null
rm -fr master.zip
/usr/local/bin/curl -k -s https://codeload.github.com/nightflyza/WolfRecorder/zip/main > main.zip
/usr/local/bin/unzip main.zip -d ../exported
cd ../exported/WolfRecorder-main/
rm -fr nbproject
tar cf - ./* | gzip > ../../wr_current.tgz
cp -R ./RELEASE ${BASEPATH}/RELEASE
cp -R ./dist/wrinstaller.sh ${BASEPATH}/wrinstaller_current.sh
cd ${BASEPATH}
rm -fr exported ./tmp/*
