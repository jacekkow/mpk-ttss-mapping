<?php
require_once(__DIR__.'/lib/database.php');
require_once(__DIR__.'/lib/fetch.php');
require_once(__DIR__.'/lib/mapper.php');

$logger = new Monolog\Logger('Parse changes');

$sources = [
	'buses' => [
		'gtfs' => 'ftp://ztp.krakow.pl/VehiclePositions_A.pb',
		'gtfs_file' => 'VehiclePositions_A.pb',
		'ttss' => 'http://91.223.13.70/internetservice/geoserviceDispatcher/services/vehicleinfo/vehicles',
		'ttss_file' => 'vehicles_A.json',
		'database' => 'mapping_A.sqlite3',
		'result' => 'mapping_A.json',
	],
];

foreach($sources as $name => $source) {
	$logger = new Monolog\Logger('fetch_'.$name);
	try {
		foreach(['gtfs_file', 'ttss_file', 'database', 'result'] as $field) {
			$source[$field] = __DIR__.'/data/'.$source[$field];
		}
		$source['result_temp'] = $source['result'].'.tmp';
		
		$logger->info('Fetching '.$name.' position data from FTP...');
		$updated = ftp_fetch_if_newer($source['gtfs'], $source['gtfs_file']);
		if(!$updated) {
			$logger->info('Nothing to do, remote file not newer than local one');
			continue;
		}
		
		$logger->info('Fetching '.$name.' positions from TTSS...');
		fetch($source['ttss'],$source['ttss_file']);
		
		$logger->info('Loading data...');
		$mapper = new Mapper();
		$mapper->loadTTSS($source['ttss_file']);
		$mapper->loadGTFS($source['gtfs_file']);
		
		$db = new Database($source['database']);
		
		$logger->info('Finding correct offset...');
		$offset = $mapper->findOffset();
		if(!$offset) {
			throw new Exception('Offset not found');
		}
		
		$logger->info('Got offset '.$offset.', creating mapping...');
		$mapping = $mapper->getMapping($offset);
		
		$logger->info('Checking the data for correctness...');
		$weight = count($mapping);
		$replace = 0;
		$ignore = 0;
		foreach($mapping as $id => $vehicle) {
			$dbVehicle = $db->getById($id);
			if($dbVehicle) {
				if((int)substr($vehicle['num'], 2) != (int)$dbVehicle['num']) {
					if($weight > $dbVehicle['weight']) {
						$replace += 1;
					} else {
						$ignore += 1;
					}
				}
				continue;
			}
			
			$dbVehicle = $db->getByNum($vehicle['num']);
			if($dbVehicle && $dbVehicle['id'] != $id) {
				$replace += 1;
			}
		}
		$logger->info('Weight: '.$weight.', ignore: '.$ignore.', replace: '.$replace);
		
		$previousMapping = NULL;
		if($ignore > 0 && $ignore >= $replace) {
			throw new Exception('Ignoring result due to better data already present');
		} elseif($replace > 0) {
			$logger->warn('Replacing DB data with the mapping');
			$db->clear();
		} else {
			$previousMapping = @json_decode(@file_get_contents($source['result']), TRUE);
		}
		
		$db->addMapping($mapping);
		
		if(is_array($previousMapping)) {
			$logger->info('Merging previous data with current mapping');
			$mapping = $previousMapping + $mapping;
			ksort($mapping);
		}
		
		$json = json_encode($mapping);
		if(!file_put_contents($source['result_temp'], $json)) {
			throw new Exception('Result save failed');
		}
		rename($source['result_temp'], $source['result']);
		$logger->info('Finished');
	} catch(Throwable $e) {
		$logger->error($e->getMessage(), ['exception' => $e, 'exception_string' => (string)$e]);
	}
}
