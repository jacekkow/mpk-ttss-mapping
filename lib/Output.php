<?php
class Output {
	static function createMapping($db, VehicleTypes $vehicleTypes, $saveConfig = FALSE) {
		$mapping = [];
		foreach($db->getAll() as $vehicle) {
			$mapping[$vehicle['id']] = $vehicleTypes->getByNumber($vehicle['num']);
		}
		
		if($saveConfig) {
			$json = json_encode($mapping);
			if(!file_put_contents($saveConfig['result_temp'], $json)) {
				throw new Exception('Result save failed');
			}
			rename($saveConfig['result_temp'], $saveConfig['result']);
		}
		
		return $mapping;
	}
	
	function createVehiclesList($trips, $mapping, $saveConfig = FALSE) {
		$lines = [];
		$vehicles = [];
		foreach($trips as $trip) {
			$vehicle = $mapping[$trip['id']] ?? [];
			$vehicle += ['trip' => $trip['id']];
			$lines[$trip['line']][] = [
				'trip' => $trip,
				'vehicle' => $vehicle,
			];
			$vehicles[$vehicle['type'] ?? '?'][] = $vehicle;
		}
		foreach($lines as &$line) {
			usort($line, function($a, $b) {
				return (substr($a['vehicle']['num'] ?? '', 2) <=> substr($b['vehicle']['num'] ?? '', 2)); 
			});
		}
		unset($line);
		ksort($lines);
		foreach($vehicles as &$vehicle) {
			usort($vehicle, function($a, $b) {
				return (substr($a['num'] ?? '', 2) <=> substr($b['num'] ?? '', 2));
			});
		}
		unset($vehicle);
		ksort($vehicles);
		
		if($saveConfig) {
			$twigLoader = new \Twig\Loader\FilesystemLoader(__DIR__.'/../templates');
			$twig = new \Twig\Environment($twigLoader);
			
			$vehiclesHtml = $twig->render('vehicles.html', [
				'lines' => $lines,
				'vehicles' => $vehicles,
				'prefix' => $saveConfig['prefix'],
			]);
			if(!file_put_contents($saveConfig['result_vehicles_temp'], $vehiclesHtml)) {
				throw new Exception('Vehicles save failed');
			}
			rename($saveConfig['result_vehicles_temp'], $saveConfig['result_vehicles']);
		}
		
		return $lines;
	}
}
