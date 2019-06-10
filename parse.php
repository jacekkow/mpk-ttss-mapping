<?php
require_once(__DIR__.'/lib/database.php');
require_once(__DIR__.'/lib/fetch.php');
require_once(__DIR__.'/lib/mapper.php');

$logger = new Monolog\Logger('Parse changes');

$sources = [
	'bus' => [
		'gtfsrt' => 'ftp://ztp.krakow.pl/VehiclePositions_A.pb',
		'gtfsrt_file' => 'VehiclePositions_A.pb',
		'ttss' => 'http://91.223.13.70/internetservice/geoserviceDispatcher/services/vehicleinfo/vehicles',
		'ttss_file' => 'vehicles_A.json',
		'database' => 'mapping_A.sqlite3',
		'result' => 'mapping_A.json',
	],
];

foreach($sources as $name => $source) {
	$logger = new Monolog\Logger('fetch_'.$name);
	try {
		foreach(['gtfsrt_file', 'ttss_file', 'database', 'result'] as $field) {
			$source[$field] = __DIR__.'/data/'.$source[$field];
		}
		$source['result_temp'] = $source['result'].'.tmp';
		
		$logger->info('Fetching '.$name.' position data from FTP...');
		$updated = ftp_fetch_if_newer($source['gtfsrt'], $source['gtfsrt_file']);
		if(!$updated) {
			$logger->info('Nothing to do, remote file not newer than local one');
			continue;
		}
		
		$logger->info('Fetching '.$name.' position data from TTSS...');
		fetch($source['ttss'], $source['ttss_file']);
		
		$logger->info('Loading data...');
		$mapper = new Mapper();
		
		$mapper->loadTTSS($source['ttss_file']);
		$timeDifference = time() - $mapper->getTTSSDate();
		if(abs($timeDifference) > 60) {
			throw new Exception('TTSS timestamp difference ('.$timeDifference.'s) is too high, aborting!');
		}
		
		$mapper->loadGTFSRT($source['gtfsrt_file']);
		$timeDifference = time() - $mapper->getGTFSRTDate();
		if(abs($timeDifference) > 60) {
			throw new Exception('GTFSRT timestamp difference ('.$timeDifference.'s) is too high, aborting!');
		}
		
		$db = new Database($source['database']);
		
		$logger->info('Finding correct offset...');
		$offset = $mapper->findOffset();
		if(!$offset) {
			throw new Exception('Offset not found');
		}
		
		$logger->info('Got offset '.$offset.', creating mapping...');
		$mapping = $mapper->mapUsingOffset($offset);
		
		$logger->info('Checking the data for correctness...');
		$weight = count($mapping);
		
		$correct = 0;
		$incorrect = 0;
		$old = 0;
		$maxWeight = 0;
		foreach($mapping as $id => $vehicle) {
			$dbVehicle = $db->getById($id);
			if($dbVehicle) {
				$maxWeight = max($maxWeight, $dbVehicle['weight']);
				if((int)substr($vehicle['num'], 2) == (int)$dbVehicle['num']) {
					$correct += 1;
				} else {
					$incorrect += 1;
				}
				continue;
			}
			
			$dbVehicle = $db->getByNum($vehicle['num']);
			if($dbVehicle && $dbVehicle['id'] != $id) {
				$old += 1;
			}
		}
		$logger->info('Weight: '.$weight.', correct: '.$correct.', incorrect: '.$incorrect.', old: '.$old);
		
		$previousMapping = NULL;
		if($incorrect > $correct && $maxWeight > $weight) {
			throw new Exception('Ignoring result due to better data already present');
		} elseif($old > $correct) {
			$logger->warn('Replacing DB data with the new mapping');
			$db->clear();
		} else {
			$previousMapping = @json_decode(@file_get_contents($source['result']), TRUE);
		}
		
		$db->addMapping($mapping);
		
		if(is_array($previousMapping)) {
			$logger->info('Merging previous data with current mapping');
			$mapping = $mapping + $previousMapping;
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
