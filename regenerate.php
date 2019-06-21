<?php
require_once(__DIR__.'/vendor/autoload.php');
require_once(__DIR__.'/lib/database.php');
require_once(__DIR__.'/lib/vehicle_types.php');
require_once(__DIR__.'/config.php');

foreach($sources as $name => $source) {
	$logger = new Monolog\Logger('regenerate_'.$name);
	try {
		$logger->info('Regenerating '.$name.'...');
		
		$db = new Database($source['database']);
		
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
