#!/bin/sh
rm -fr wr.tgz RELEASE > /dev/null
wget http://snaps.wolfrecorder.com/wr_current.tgz
mv wr_current.tgz wr.tgz
tar -zxvf wr.tgz ./RELEASE
echo "=== Release Done ==="

rm -fr wrinstaller_current.tar.gz > /dev/null
wget http://snaps.wolfrecorder.com/wrinstaller_current.sh
mv wrinstaller_current.sh wrinstaller.sh
echo "=== WRinstaller builded ==="