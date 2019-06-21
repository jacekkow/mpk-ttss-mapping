<?php
class Database {
	private $pdo;
	private $addStatement;
	
	private $cacheId;
	private $cacheNum;
	
	public function __construct($file) {
		$this->pdo = new PDO('sqlite:'.$file);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		$this->pdo->query('CREATE TABLE IF NOT EXISTS vehicles (
			id INT PRIMARY KEY,
			num INT UNIQUE,
			weight INT
		)');
		
		$this->addStatement = $this->pdo->prepare('INSERT OR REPLACE INTO vehicles (id, num, weight) VALUES (:id, :num, :weight)');
		
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
			$st = $this->pdo->prepare('SELECT * FROM vehicles');
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
	
	public function getAll() {
		$this->_cachePopulate();
		return $this->cacheId;
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
		$this->pdo->query('DELETE FROM vehicles');
		$this->_cacheClear();
	}
	
	public function add($id, $num, $weight) {
		$vehicle = [
			'id' => (string)$id,
			'num' => (string)$num,
			'weight' => (string)$weight
		];
		$this->addStatement->execute($vehicle);
		$this->_cacheAdd($vehicle);
	}
	
	public function addMapping($mapping) {
		$this->beginTransaction();
		$weight = count($mapping);
		foreach($mapping as $id => $num) {
			$this->add($id, $num, $weight);
		}
		$this->commit();
	}
}
