<?php
abstract class VehicleTypes {
	protected $typesByNumber = [];
	
	protected function __construct($data, $defaultLow=NULL) {
		$data = explode("\n", trim($data));
		foreach($data as $line) {
			$line = explode("\t", trim($line));
			for($i = (int)$line[0]; $i <= (int)$line[1]; $i++) {
				$this->typesByNumber[$i] = [
					'num' => $line[2] . str_pad($i, 3, '0', STR_PAD_LEFT),
					'type' => $line[3],
					'low' => (int)(isset($line[4]) ? $line[4] : $defaultLow),
				];
			}
		}
	}
	
	public function getByNumber($id) {
		$id = intval($id, 10);
		return $this->typesByNumber[$id] ?? [
			'num' => '??' . str_pad($id, 3, '0', STR_PAD_LEFT),
			'type' => '?',
			'low' => NULL,
		];
	}
}
