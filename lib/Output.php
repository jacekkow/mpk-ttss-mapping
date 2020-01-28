<?php
class Output {
	private $db;
	private $mapper;
	private $vehicleTypes;
	
	function __construct(Database $db, Mapper $mapper, VehicleTypes $vehicleTypes) {
		$this->db = $db;
		$this->mapper = $mapper;
		$this->vehicleTypes = $vehicleTypes;
	}
	
	function createMapping($saveConfig = FALSE) {
		$mapping = [];
		foreach($this->db->getAllById() as $vehicle) {
			$mapping[$vehicle['id']] = $this->vehicleTypes->getByNumber($vehicle['num']);
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
	
	function createVehiclesList($fullMapping, $saveConfig = FALSE) {
		$trips = $this->mapper->getTTSSTrips();
		
		$lines = [];
		$vehicles = [];
		foreach($trips as $trip) {
			$vehicle = $fullMapping[$trip['id']] ?? [];
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
		
		$dbMapping = $this->db->getAllByNum();
		foreach($dbMapping as &$vehicle) {
			$vehicle['vehicle'] = $this->vehicleTypes->getByNumber($vehicle['num']);
		}
		unset($vehicle);
		ksort($dbMapping);
		
		if($saveConfig) {
			$twigLoader = new \Twig\Loader\FilesystemLoader(__DIR__.'/../templates');
			$twig = new \Twig\Environment($twigLoader);
			$twig->addExtension(new Twig_Extensions_Extension_Date());
			
			$vehiclesHtml = $twig->render('vehicles.html', [
				'lines' => $lines,
				'vehicles' => $vehicles,
				'prefix' => $saveConfig['prefix'],
				'mapping' => $dbMapping,
			]);
			if(!file_put_contents($saveConfig['result_vehicles_temp'], $vehiclesHtml)) {
				throw new Exception('Vehicles save failed');
			}
			rename($saveConfig['result_vehicles_temp'], $saveConfig['result_vehicles']);
		}
		
		return $lines;
	}
}
