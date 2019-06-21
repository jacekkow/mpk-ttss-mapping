<?php
require_once(__DIR__.'/vendor/autoload.php');
require_once(__DIR__.'/lib/database.php');
require_once(__DIR__.'/lib/fetch.php');
require_once(__DIR__.'/lib/mapper.php');
require_once(__DIR__.'/lib/vehicle_types.php');
require_once(__DIR__.'/config.php');

foreach($sources as $name => $source) {
	$logger = new Monolog\Logger('fetch_'.$name);
	try {
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
		if(abs($timeDifference) > 120) {
			throw new Exception('TTSS timestamp difference ('.$timeDifference.'s) is too high, aborting!');
		}
		
		$mapper->loadGTFSRT($source['gtfsrt_file']);
		$timeDifference = time() - $mapper->getGTFSRTDate();
		if(abs($timeDifference) > 120) {
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
		foreach($mapping as $id => $num) {
			$dbVehicle = $db->getById($id);
			if($dbVehicle) {
				$maxWeight = max($maxWeight, (int)$dbVehicle['weight']);
				if($num === $dbVehicle['num']) {
					$correct += 1;
				} else {
					$incorrect += 1;
				}
				continue;
			}
			
			$dbVehicle = $db->getByNum($num);
			if($dbVehicle && $dbVehicle['id'] !== $id) {
				$old += 1;
			}
		}

		$logger->info('Weight: '.$weight.', correct: '.$correct.', incorrect: '.$incorrect.', old: '.$old);
		
		if($incorrect > $correct && $maxWeight > $weight) {
			throw new Exception('Ignoring result due to better data already present');
		}
		
		$db->addMapping($mapping);
		
		$jsonContent = [];
		foreach($db->getAll() as $vehicle) {
			$jsonContent[$vehicle['id']] = $source['mapper']($vehicle['num']);
		}
		
		$json = json_encode($jsonContent);
		if(!file_put_contents($source['result_temp'], $json)) {
			throw new Exception('Result save failed');
		}
		rename($source['result_temp'], $source['result']);
		$logger->info('Finished');
	} catch(Throwable $e) {
		$logger->error($e->getMessage(), ['exception' => $e, 'exception_string' => (string)$e]);
	}
}
