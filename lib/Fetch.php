<?php
class Fetch {
	static function ftp($url, $file = NULL) {
		$url = parse_url($url);
		if(!isset($url['scheme']) || $url['scheme'] != 'ftp') {
			throw new Exception('Only FTP URLs are supported');
		}
		if(!isset($url['host'])) {
			throw new Exception('Hostname not present in the URL');
		}
		if(!isset($url['path'])) {
			throw new Exception('Path component not present in the URL');
		}
		if(!isset($url['port'])) {
			$url['port'] = 21;
		}
		if(!isset($url['user'])) {
			$url['user'] = 'anonymous';
		}
		if(!isset($url['pass'])) {
			$url['pass'] = 'anonymous@mpk.jacekk.net';
		}
		if($file === NULL) {
			$file = basename($url['path']);
		}
		
		$localTime = -1;
		$localSize = -1;
		if(is_file($file)) {
			$localTime = filemtime($file);
			$localSize = filesize($file);
		}
		
		$ftp = FtpConnection::create($url['host'], $url['port'], $url['user'], $url['pass']);
		$remoteSize = $ftp->size($url['path']);
		$remoteTime = $ftp->mdtm($url['path']);
		
		$updated = FALSE;
		
		if($localTime >= $remoteTime && $localSize == $remoteSize) {
			return FALSE;
		}
		
		if(file_exists($file.'.tmp')) {
			unlink($file.'.tmp');
		}
		$ftp->get($file.'.tmp', $url['path'], FTP_BINARY);
		touch($file.'.tmp', $remoteTime);
		if(!rename($file.'.tmp', $file)) {
			throw new Exception('Temporary file rename failed');
		}
		
		return TRUE;
	}
	
	static function parse_http_headers($headers) {
		$hasHeader = FALSE;
		foreach($headers as $header) {
			if(substr($header, 0, 5) === 'HTTP/') {
				$code = substr($header, 9, 3);
				if($code === '304') {
					return NULL;
				} elseif(substr($code, 0, 1) == '2') {
					$hasHeader = TRUE;
				}
			} elseif($hasHeader && strtolower(substr($header, 0, 15)) === 'last-modified: ') {
				return strptime(substr($header, 15), 'D, d M Y H:i:s T');
			}
		}
		return FALSE;
	}
	
	static function generic($url, $file = NULL) {
		if($file === NULL) {
			$file = basename($url['url']);
		}
		
		$context = [];
		if(is_file($file)) {
			$file_date = filemtime($file);
			$context['http'] = [
				'header' => [
					'If-Modified-Since: '.gmdate('D, d M Y H:i:s T', $file_date),
				],
			];
		}
		
		$data = file_get_contents($url, FALSE, stream_context_create($context));
		$remoteTime = FALSE;
		if(isset($http_response_header) && is_array($http_response_header)) {
			$remoteTime = self::parse_http_headers($http_response_header);
			if($remoteTime === NULL) {
				return FALSE;
			}
		}
		
		if($data === FALSE) {
			throw new Exception('URL fetch failed');
		}
		if(file_put_contents($file.'.tmp', $data) === FALSE) {
			throw new Exception('Temporary file creation failed');
		}
		if($remoteTime !== FALSE) {
			touch($file.'.tmp', $remoteTime);
		}
		if(!rename($file.'.tmp', $file)) {
			throw new Exception('Temporary file rename failed');
		}
		
		return TRUE;
	}
	
	static function auto($url, $file = NULL) {
		if($file === NULL) {
			$file = basename($url['url']);
		}
		if(substr($url, 0, 4) == 'ftp:') {
			return self::ftp($url, $file);
		}
		return self::generic($url, $file);
	}
}
