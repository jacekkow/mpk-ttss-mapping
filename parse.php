<?php
require('vendor/autoload.php');
require('common.php');

use transit_realtime\FeedMessage;

class IdMapper {
	private $jsonTrips = [];
	private $gtfsTrips = [];
	
	private $specialNames = [
		'Zjazd do zajezdni',
		'Przejazd techniczny',
		'Wyjazd na trasę',
	];
	
	public static function convertTripId($tripId) {
		$tripId = explode('_', $tripId);
		if($tripId[0] != 'block') return;
		if($tripId[2] != 'trip') return;
		return 4096 * (int)$tripId[1] + (int)$tripId[3];
	}
	
	public function loadJson($file) {
		$json = json_decode(file_get_contents($file));
		foreach($json->vehicles as $vehicle) {
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
			$this->jsonTrips[(int)$vehicle->tripId] = [
				'id' => $vehicle->id,
				'latitude' => (float)$vehicle->latitude / 3600000.0,
				'longitude' => (float)$vehicle->longitude / 3600000.0,
			];
		}
		ksort($this->jsonTrips);
	}
	
	public function loadGtfs($file) {
		$data = file_get_contents($file);
		$feed = new FeedMessage();
		$feed->parse($data);
		foreach ($feed->getEntityList() as $entity) {
			$vehiclePosition = $entity->getVehicle();
			$position = $vehiclePosition->getPosition();
			$vehicle = $vehiclePosition->getVehicle();
			$trip = $vehiclePosition->getTrip();
			$tripId = $trip->getTripId();
			$this->gtfsTrips[self::convertTripId($tripId)] = [
				'id' => $entity->getId(),
				'num' => $vehicle->getLicensePlate(),
				'tripId' => $tripId,
				'latitude' => $position->getLatitude(),
				'longitude' => $position->getLongitude(),
			];
		}
		ksort($this->gtfsTrips);
	}
	
	public function findOffset() {
		if(count($this->jsonTrips) == 0 || count($this->gtfsTrips) == 0) {
			return NULL;
		}
		
		$jsonTripIds = array_keys($this->jsonTrips);
		$gtfsTripIds = array_keys($this->gtfsTrips);
		
		$possibleOffsets = [];
		for($i = 0; $i < count($this->jsonTrips); $i++) {
			for($j = 0; $j < count($this->gtfsTrips); $j++) {
				$possibleOffsets[$jsonTripIds[$i] - $gtfsTripIds[$j]] = TRUE;
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
				if(isset($this->jsonTrips[$tripId])) {
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
			fwrite(STDERR, 'Found '.$options.' possible mappings!'."\n");
			return FALSE;
		}
		return $bestOffset;
	}
	
	public function getMapping($offset) {
		$result = [];
		foreach($this->gtfsTrips as $gtfsTripId => $gtfsTrip) {
			$jsonTripId = $gtfsTripId + $offset;
			if(isset($this->jsonTrips[$jsonTripId])) {
				$data = numToTypeB($gtfsTrip['id']);
				$num = $gtfsTrip['num'];
				if($data['num'] != $num) {
					// Ignore due to incorrect depot markings in the data
					//fwrite(STDERR, 'Got '.$num.', database has '.$data['num']."\n");
				}
				$result[$jsonTripId] = $data;
			}
		}
		return $result;
	}
}

$mapper = new IdMapper();
$mapper->loadJson('./data/vehicles_A.json');
$mapper->loadGtfs('./data/VehiclePositions_A.pb');
$offset = $mapper->findOffset();
if($offset) {
	echo json_encode($mapper->getMapping($offset));
}
