<?php
require_once(__DIR__.'/lib/fetch.php');
require_once(__DIR__.'/lib/mapper.php');

$logger = new Monolog\Logger('Parse changes');

$sources = [
	'buses' => [
		'gtfs' => 'ftp://ztp.krakow.pl/VehiclePositions_A.pb',
		'gtfs_file' => 'VehiclePositions_A.pb',
		'ttss' => 'http://91.223.13.70/internetservice/geoserviceDispatcher/services/vehicleinfo/vehicles',
		'ttss_file' => 'vehicles_A.json',
	],
];

foreach($sources as $name => $source) {
	$logger = new Monolog\Logger('fetch_'.$name);
	try {
		$logger->info('Fetching '.$name.' position data from FTP...');
		$updated = ftp_fetch_if_newer($source['gtfs'], __DIR__.'/data/'.$source['gtfs_file']);
		if(!$updated) {
			$logger->info('Nothing to do, remote file not newer than local one');
			continue;
		}
		
		$logger->info('Fetching '.$name.' positions from TTSS...');
		fetch($source['ttss'], __DIR__.'/data/'.$source['ttss_file']);
		
		$logger->info('Loading data...');
		$mapper = new Mapper();
		$mapper->loadTTSS(__DIR__.'/data/'.$source['ttss_file']);
		$mapper->loadGTFS(__DIR__.'/data/'.$source['gtfs_file']);
		
		$logger->info('Finding correct offset...');
		$offset = $mapper->findOffset();
		if($offset) {
			$logger->info('Got offset '.$offset.', creating mapping...');
			$mapping = $mapper->getMapping($offset);
			echo json_encode($mapping);
		}
		$logger->info('Finished');
	} catch(Throwable $e) {
		$logger->error($e->getMessage(), ['exception' => $e, 'exception_string' => (string)$e]);
	}
}
