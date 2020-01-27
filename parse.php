<?php
require_once(__DIR__.'/vendor/autoload.php');
require_once(__DIR__.'/config.php');

foreach($sources as $name => $source) {
	$logger = new Monolog\Logger('fetch_'.$name);
	try {
		$logger->info('Fetching '.$name.' GTFS position data ...');
		$updated = Fetch::auto($source['gtfsrt'], $source['gtfsrt_file']);
		if(!$updated) {
			$logger->info('Nothing to do, remote file not newer than local one');
			continue;
		}
		
		$logger->info('Fetching '.$name.' TTSS position data...');
		Fetch::auto($source['ttss'], $source['ttss_file']);
		
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
		$mapping = $mapper->mapVehicleIdsUsingOffset($offset);
		
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
		
		$output = new Output($db, $mapper, $source['vehicle_types']);
		
		$logger->info('Saving mapping...');
		
		$db->addMapping($mapping, $mapper);
		
		$fullMapping = $output->createMapping($source);
		
		$logger->info('Creating vehicle list...');
		
		$output->createVehiclesList($fullMapping, $source);
		
		$logger->info('Finished');
	} catch(Throwable $e) {
		$logger->error($e->getMessage(), ['exception' => $e, 'exception_string' => (string)$e]);
	}
}
