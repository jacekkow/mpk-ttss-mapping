<?php
require_once(__DIR__.'/vendor/autoload.php');
require_once(__DIR__.'/config.php');

foreach($sources as $name => $source) {
	$logger = new Monolog\Logger('regenerate_'.$name);
	try {
		$logger->info('Regenerating '.$name.'...');
		$db = new Database($source['database']);
		$mapper = new Mapper();
		$mapper->loadTTSS($source['ttss_file']);
		$output = new Output($db, $mapper, $source['vehicle_types']);
		$fullMapping = $output->createMapping($source);
		$output->createVehiclesList($fullMapping, $source);
		$logger->info('Finished');
	} catch(Throwable $e) {
		$logger->error($e->getMessage(), ['exception' => $e, 'exception_string' => (string)$e]);
	}
}
