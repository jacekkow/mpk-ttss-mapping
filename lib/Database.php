<?php
class Database {
	private $pdo;
	private $addStatement;
	
	private $cacheId;
	private $cacheNum;
	
	public function __construct($file) {
		$this->pdo = new PDO('sqlite:'.$file);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		$this->pdo->query('CREATE TABLE IF NOT EXISTS vehicles2 (
			id INT PRIMARY KEY,
			num INT UNIQUE,
			weight INT,
			line VARCHAR,
			date INT
		)');
		try {
			$this->beginTransaction();
			$this->pdo->query('INSERT INTO vehicles2 SELECT id, num, weight, \'?\', \''.time().'\' FROM vehicles');
			$this->commit();
			$this->pdo->query('DROP TABLE vehicles');
		} catch(PDOException $e) {
			$this->rollback();
		}
		
		$this->addStatement = $this->pdo->prepare('INSERT OR REPLACE INTO vehicles2 (id, num, weight, line, date) VALUES (:id, :num, :weight, :line, :date)');
		
		$this->_cacheClear();
	}
	
	public function beginTransaction() {
		$this->pdo->beginTransaction();
	}
	
	public function commit() {
		$this->pdo->commit();
	}
	
	public function rollback() {
		$this->pdo->rollback();
	}
	
	protected function _cachePopulate() {
		if($this->cacheId === NULL) {
			$st = $this->pdo->prepare('SELECT * FROM vehicles2');
			$st->execute();
			$result = $st->fetchAll(PDO::FETCH_ASSOC);
			
			$this->cacheId = [];
			$this->cacheNum = [];
			foreach($result as $vehicle) {
				$this->_cacheAdd($vehicle);
			}
		}
	}
	
	protected function _cacheAdd($vehicle) {
		$this->_cachePopulate();
		$this->cacheId[$vehicle['id']] = $vehicle;
		$this->cacheNum[$vehicle['num']] = $vehicle;
	}
	
	protected function _cacheClear() {
		$this->cacheId = NULL;
		$this->cacheNum = NULL;
	}
	
	public function getAllById() {
		$this->_cachePopulate();
		return $this->cacheId;
	}
	
	public function getAllByNum() {
		$this->_cachePopulate();
		return $this->cacheNum;
	}
	
	public function getById($id) {
		$this->_cachePopulate();
		return $this->cacheId[$id] ?? NULL;
	}
	
	public function getByNum($num) {
		$this->_cachePopulate();
		return $this->cacheNum[$num] ?? NULL;
	}
	
	public function clear() {
		$this->pdo->query('DELETE FROM vehicles2');
		$this->_cacheClear();
	}
	
	public function add($id, $num, $weight, $line = NULL, $date = NULL) {
		$vehicle = [
			'id' => (string)$id,
			'num' => (string)$num,
			'weight' => (string)$weight,
			'line' => (string)($line ?? ''),
			'date' => (string)($date ?? time()),
		];
		$this->addStatement->execute($vehicle);
		$this->_cacheAdd($vehicle);
	}
	
	public function addMapping($vehiclesMapping, Mapper $mapper) {
		$this->beginTransaction();
		$weight = count($vehiclesMapping);
		foreach($vehiclesMapping as $id => $num) {
			$trip = $mapper->getTTSSVehicleTrip($id);
			$this->add($id, $num, $weight, ($trip['line'] ?? '?') . ' ' . ($trip['direction'] ?? '?'));
		}
		$this->commit();
	}
}
