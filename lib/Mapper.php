<?php
use transit_realtime\FeedMessage;

class Mapper {
	private $ttssDate = NULL;
	private $ttssTrips = [];
	private $ttssVehicleToTrip = [];
	private $gtfsrtDate = NULL;
	private $gtfsrtTrips = [];
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
			list($line, $direction) = explode(' ', $vehicle->name, 2);
			$trip = [
				'id' => (string)$vehicle->id,
				'line' => $line,
				'direction' => $direction,
				'latitude' => (float)$vehicle->latitude / 3600000.0,
				'longitude' => (float)$vehicle->longitude / 3600000.0,
			];
			$this->ttssTrips[(string)$vehicle->tripId] = $trip;
			$this->ttssVehicleToTrip[(string)$vehicle->id] = $trip;
		}
		ksort($this->ttssTrips);
	}
	
	public function getTTSSDate() {
		return $this->ttssDate / 1000.0;
	}
	
	public function getTTSSTrips() {
		return $this->ttssTrips;
	}
	
	public function getTTSSVehicleToTrip() {
		return $this->ttssVehicleToTrip;
	}
	
	public function getTTSSTrip($id) {
		return $this->ttssTrips[$id] ?? NULL;
	}
	
	public function getTTSSVehicleTrip($id) {
		return $this->ttssVehicleToTrip[$id] ?? NULL;
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
				'id' => (string)$entity->getId(),
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
	
	public function mapVehicleIdsUsingOffset($offset) {
		$result = [];
		foreach($this->gtfsrtTrips as $gtfsTripId => $gtfsTrip) {
			$ttssTripId = $gtfsTripId + $offset;
			if(isset($this->ttssTrips[$ttssTripId])) {
				$result[$this->ttssTrips[$ttssTripId]['id']] = $gtfsTrip['id'];
			}
		}
		return $result;
	}
}
