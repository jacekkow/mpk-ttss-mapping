<?php
require_once(__DIR__.'/vendor/autoload.php');
require_once(__DIR__.'/lib/output.php');
require_once(__DIR__.'/lib/vehicle_types.php');
require_once(__DIR__.'/config.php');

foreach($sources as $name => $source) {
	$logger = new Monolog\Logger('regenerate_'.$name);
	try {
		$logger->info('Regenerating '.$name.'...');
		$db = new Database($source['database']);
		createMapping($db, $source['mapper'], $source);
		$logger->info('Finished');
	} catch(Throwable $e) {
		$logger->error($e->getMessage(), ['exception' => $e, 'exception_string' => (string)$e]);
	}
}
