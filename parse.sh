#!/bin/bash

DIR=`dirname "$0"`
cd "$DIR"
DIR=`pwd`

cd "$DIR/data"
wget -O vehicles_A.json "http://91.223.13.70/internetservice/geoserviceDispatcher/services/vehicleinfo/vehicles"
wget -N "ftp://ztp.krakow.pl/GTFS_KRK_A.zip" "ftp://ztp.krakow.pl/VehiclePositions_A.pb"

cd "$DIR"
php parse.php > data/mapping_A.tmp
if [ -s data/mapping_A.tmp ]; then
	mv -f data/mapping_A.tmp data/mapping_A.json
fi
