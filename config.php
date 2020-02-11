<?php
$sources = [
	'bus' => [
		'gtfsrt' => 'ftp://ztp.krakow.pl/pliki-gtfs/VehiclePositions_A.pb',
		'gtfsrt_file' => 'VehiclePositions_A.pb',
		'ttss' => 'http://ttss.mpk.krakow.pl/internetservice/geoserviceDispatcher/services/vehicleinfo/vehicles',
		'ttss_file' => 'vehicles_A.json',
		'database' => 'mapping_A.sqlite3',
		'result' => 'mapping_A.json',
		'result_vehicles' => 'vehicles_A.html',
		'vehicle_types' => new BusTypes(),
		'prefix' => 'b',
	],
	'tram' => [
		'gtfsrt' => 'ftp://ztp.krakow.pl/pliki-gtfs/VehiclePositions_T.pb',
		'gtfsrt_file' => 'VehiclePositions_T.pb',
		'ttss' => 'http://www.ttss.krakow.pl/internetservice/geoserviceDispatcher/services/vehicleinfo/vehicles?positionType=CORRECTED',
		'ttss_file' => 'vehicles_T.json',
		'database' => 'mapping_T.sqlite3',
		'result' => 'mapping_T.json',
		'result_vehicles' => 'vehicles_T.html',
		'vehicle_types' => new TramTypes(),
		'prefix' => 't',
	],
];

foreach($sources as $name => &$source) {
	foreach(['gtfsrt_file', 'ttss_file', 'database', 'result', 'result_vehicles'] as $field) {
		$source[$field] = __DIR__.'/data/'.$source[$field];
	}
	
	$source['result_temp'] = $source['result'].'.tmp';
	$source['result_vehicles_temp'] = $source['result_vehicles'].'.tmp';
}
unset($source);
