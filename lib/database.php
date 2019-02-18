<?php
class Database {
	private $pdo;
	private $getByIdStatement;
	private $getByNumStatement;
	private $addStatement;
	
	public function __construct($file) {
		$this->pdo = new PDO('sqlite:'.$file);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		$this->pdo->query('CREATE TABLE IF NOT EXISTS vehicles (
			id INT PRIMARY KEY,
			num INT UNIQUE,
			weight INT
		)');
		
		$this->getByIdStatement = $this->pdo->prepare('SELECT num, weight FROM vehicles WHERE id=? LIMIT 1');
		$this->getByNumStatement = $this->pdo->prepare('SELECT id, weight FROM vehicles WHERE num=? LIMIT 1');
		$this->addStatement = $this->pdo->prepare('INSERT OR REPLACE INTO vehicles (id, num, weight) VALUES (?, ?, ?)');
	}
	
	public function getById($id) {
		$this->getByIdStatement->execute([$id]);
		return $this->getByIdStatement->fetch();
	}
	
	public function getByNum($num) {
		$st = $this->getByNumStatement->execute([(int)substr($num, 2)]);
		return $this->getByNumStatement->fetch();
	}
	
	public function clear() {
		$this->pdo->query('DELETE FROM vehicles');
	}
	
	public function add($id, $num, $weight) {
		$this->addStatement->execute([$id, $num, $weight]);
	}
	
	public function addMapping($mapping) {
		$weight = count($mapping);
		foreach($mapping as $id => $vehicle) {
			$this->add($id, (int)substr($vehicle['num'], 2), $weight);
		}
	}
}
