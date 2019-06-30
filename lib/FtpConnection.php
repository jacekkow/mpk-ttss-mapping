<?php
class FtpConnection {
	private static $instances = [];
	private $connection;
	
	static function create(string $host, int $port=21, string $user='anonymous', string $pass='anonymous') : FtpConnection {
		$key = $host."\0".$port."\0".$user."\0".$pass;
		if(!isset(self::$instances[$key])) {
			self::$instances[$key] = new FtpConnection($host, $port, $user, $pass);
		}
		return self::$instances[$key];
	}
	
	private function __construct(string $host, int $port=21, string $user, string $pass) {
		$this->connection = ftp_connect($host, $port, 10);
		if($this->connection === FALSE) {
			throw new Exception('FTP connection failed');
		}
		if(!ftp_login($this->connection, $user, $pass)) {
			throw new Exception('FTP login failed');
		}
		if(!ftp_pasv($this->connection, TRUE)) {
			throw new Exception('Passive FTP request failed');
		}
	}
	
	public function __destruct() {
		ftp_close($this->connection);
	}
	
	public function size(string $file) : int {
		$remoteSize = ftp_size($this->connection, $file);
		if($remoteSize < 0) {
			throw new Exception('FTP file size fetch failed');
		}
		return $remoteSize;
	}
	
	
	public function mdtm(string $file) : int {
		$remoteTime = ftp_mdtm($this->connection, $file);
		if($remoteTime < 0) {
			throw new Exception('FTP modification time fetch failed');
		}
		return $remoteTime;
	}
	
	public function get(string $local_file, string $remote_file, int $mode = FTP_BINARY, int $resumepos = 0) : bool {
		$result = ftp_get($this->connection, $local_file, $remote_file, $mode, $resumepos);
		if($result === FALSE) {
			throw new Exception('FTP file get failed');
		}
		return $result;
	}
}
