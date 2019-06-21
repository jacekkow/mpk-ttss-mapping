<?php
function createVehiclesList($trips, $mapping, $saveConfig = FALSE) {
	$lines = [];
	foreach($trips as $trip) {
		$lines[$trip['line']][] = [
			'trip' => $trip,
			'vehicle' => $mapping[$trip['id']] ?? [],
		];
	}
	foreach($lines as &$line) {
		usort($line, function($a, $b) {
			return (substr($a['vehicle']['num'] ?? '', 2) <=> substr($b['vehicle']['num'] ?? '', 2)); 
		});
	}
	unset($line);
	ksort($lines);
	
	if($saveConfig) {
		$twigLoader = new \Twig\Loader\FilesystemLoader(__DIR__.'/../templates');
		$twig = new \Twig\Environment($twigLoader);
		
		$vehiclesHtml = $twig->render('vehicles.html', [
			'lines' => $lines,
			'prefix' => $saveConfig['prefix'],
		]);
		if(!file_put_contents($saveConfig['result_vehicles_temp'], $vehiclesHtml)) {
			throw new Exception('Vehicles save failed');
		}
		rename($saveConfig['result_vehicles_temp'], $saveConfig['result_vehicles']);
	}
	
	return $lines;
}
