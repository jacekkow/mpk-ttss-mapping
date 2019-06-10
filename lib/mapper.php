<?php
require_once(__DIR__.'/../vendor/autoload.php');
require_once(__DIR__.'/vehicle_types.php');

use transit_realtime\FeedMessage;

class Mapper {
	private $ttssDate = NULL;
	private $ttssTrips = [];
	private $gtfsrtDate = NULL;
	private $gtfsrtTrips = [];
	private $gtfsTrips = [];
	private $gtfsRoutes = [];
	private $logger = NULL;
	
	private $specialNames = [
		'Zjazd do zajezdni',
		'Przejazd techniczny',
		'Wyjazd na trasę',
	];
	
	public function __construct() {
		$this->logger = new Monolog\Logger(__CLASS__);
	}
	
	public static function convertTripId($tripId) {
		$tripId = explode('_', $tripId);
		if($tripId[0] != 'block') return;
		if($tripId[2] != 'trip') return;
		return 4096 * (int)$tripId[1] + (int)$tripId[3];
	}
	
	public function loadTTSS($file) {
		$ttss = json_decode(file_get_contents($file));
		$this->ttssDate = $ttss->lastUpdate;
		foreach($ttss->vehicles as $vehicle) {
			if(isset($vehicle->isDeleted) && $vehicle->isDeleted) continue;
			if(!isset($vehicle->tripId) || !$vehicle->tripId) continue;
			if(!isset($vehicle->name) || !$vehicle->name) continue;
			if(!isset($vehicle->latitude) || !$vehicle->latitude) continue;
			if(!isset($vehicle->longitude) || !$vehicle->longitude) continue;
			foreach($this->specialNames as $name) {
				if(substr($vehicle->name, -strlen($name)) == $name) {
					continue;
				}
			}
			$name = explode(' ', $vehicle->name, 2);
			$this->ttssTrips[(int)$vehicle->tripId] = [
				'id' => $vehicle->id,
				'line' => $name[0],
				'direction' => $name[1],
				'latitude' => (float)$vehicle->latitude / 3600000.0,
				'longitude' => (float)$vehicle->longitude / 3600000.0,
			];
		}
		ksort($this->ttssTrips);
	}
	
	public function getTTSSDate() {
		return $this->ttssDate / 1000.0;
	}
	
	public function loadGTFS($file) {
		$buffer_size = 512;
		
		$routes = fopen('phar://'.$file.'/routes.txt', 'r');
		$route_header = fgetcsv($routes, $buffer_size);
		while(($route = fgetcsv($routes, $buffer_size)) !== FALSE) {
			$this->gtfsRoutes[$route[0]] = $route[2];
		}
		fclose($routes);
		
		$trips = fopen('phar://'.$file.'/trips.txt', 'r');
		$trip_header = fgetcsv($trips, $buffer_size);
		var_dump($trip_header);
		while(($trip = fgetcsv($trips, $buffer_size)) !== FALSE) {
			$this->gtfsTrips[$trip[0]] = [
				'line' => $this->gtfsRoutes[$trip[1]] ?? NULL,
				'route' => $trip[1],
				'direction' => $trip[3],
			];
		}
		fclose($trips);
	}
	
	public function loadGTFSRT($file) {
		$data = file_get_contents($file);
		$feed = new FeedMessage();
		$feed->parse($data);
		$this->gtfsrtDate = $feed->header->timestamp;
		foreach ($feed->getEntityList() as $entity) {
			$vehiclePosition = $entity->getVehicle();
			$position = $vehiclePosition->getPosition();
			$vehicle = $vehiclePosition->getVehicle();
			$trip = $vehiclePosition->getTrip();
			$tripId = $trip->getTripId();
			$this->gtfsrtTrips[self::convertTripId($tripId)] = [
				'id' => $entity->getId(),
				'num' => $vehicle->getLicensePlate(),
				'tripId' => $tripId,
				'latitude' => $position->getLatitude(),
				'longitude' => $position->getLongitude(),
			];
		}
		ksort($this->gtfsrtTrips);
	}
	
	public function getGTFSRTDate() {
		return $this->gtfsrtDate;
	}
	
	public function mapUsingCoords() {
		$level = 0.002;
		foreach($this->gtfsrtTrips as $gkey => $gtrip) {
			$trip = $this->gtfsTrips[$gtrip['tripId']] ?? NULL;
			
			if($trip === NULL) continue;
			
			foreach($this->ttssTrips as $jkey => $jtrip) {
				echo $gtrip['latitude'].' '.$jtrip['latitude']."\n";
				echo $gtrip['longitude'].' '.$jtrip['longitude']."\n";
				echo $jtrip['line'].' '.$trip['line']."\n";
				echo "\n";
				
				if($jtrip['line'] != $trip['line']) continue;
				if(abs($gtrip['latitude'] - $jtrip['latitude']) > $level) continue;
				if(abs($gtrip['longitude'] - $jtrip['longitude']) < $level) continue;
				
				echo 'MATCH '.$gtrip['num'].' '.$gkey.' '.$jkey.' ('.($jkey-$gkey).')'."\n";
			}
		}
	}
	
	public function findOffset() {
		if(count($this->ttssTrips) == 0 || count($this->gtfsrtTrips) == 0) {
			return NULL;
		}
		
		$ttssTripIds = array_keys($this->ttssTrips);
		$gtfsTripIds = array_keys($this->gtfsrtTrips);
		
		$possibleOffsets = [];
		for($i = 0; $i < count($this->ttssTrips); $i++) {
			for($j = 0; $j < count($this->gtfsrtTrips); $j++) {
				$possibleOffsets[$ttssTripIds[$i] - $gtfsTripIds[$j]] = TRUE;
			}
		}
		$possibleOffsets = array_keys($possibleOffsets);
		
		$bestOffset = 0;
		$maxMatched = 0;
		$options = 0;
		
		foreach($possibleOffsets as $offset) {
			$matched = 0;
			
			foreach($gtfsTripIds as $tripId) {
				$tripId += $offset;
				if(isset($this->ttssTrips[$tripId])) {
					$matched++;
				}
			}
			
			if($matched > $maxMatched) {
				$bestOffset = $offset;
				$maxMatched = $matched;
				$options = 1;
			} elseif($matched == $maxMatched) {
				$options++;
			}
		}
		
		if($options != 1) {
			throw new Exception('Found '.$options.' possible mappings!');
		}
		return $bestOffset;
	}
	
	public function mapUsingOffset($offset) {
		$result = [];
		foreach($this->gtfsrtTrips as $gtfsTripId => $gtfsTrip) {
			$ttssTripId = $gtfsTripId + $offset;
			if(isset($this->ttssTrips[$ttssTripId])) {
				$data = numToTypeB($gtfsTrip['id']);
				$num = $gtfsTrip['num'];
				if(!is_array($data) || !isset($data['num'])) {
					$data = [
						'num' => $num,
						'low' => 2,
					];
				} elseif($data['num'] != $num) {
					// Ignore due to incorrect depot markings in the data
					//$this->logger->warn('Got '.$num.', database has '.$data['num']);
				}
				$result[$this->ttssTrips[$ttssTripId]['id']] = $data;
			}
		}
		return $result;
	}
}
