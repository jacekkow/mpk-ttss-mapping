<?php
function ftp_fetch_if_newer($url, $file = NULL) {
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
	if($file == NULL) {
		$file = basename($url['path']);
	}
	
	$localTime = -1;
	$localSize = -1;
	if(is_file($file)) {
		$localTime = filemtime($file);
		$localSize = filesize($file);
	}
	
	$ftp = ftp_connect($url['host'], $url['port'], 10);
	if($ftp === FALSE) {
		throw new Exception('FTP connection failed');
	}
	if(!ftp_login($ftp, $url['user'], $url['pass'])) {
		throw new Exception('FTP login failed');
	}
	if(!ftp_pasv($ftp, TRUE)) {
		throw new Exception('Passive FTP request failed');
	}
	$remoteSize = ftp_size($ftp, $url['path']);
	if($remoteSize < 0) {
		throw new Exception('FTP file size fetch failed');
	}
	$remoteTime = ftp_mdtm($ftp, $url['path']);
	if($remoteTime < 0) {
		throw new Exception('FTP modification time fetch failed');
	}
	
	$updated = FALSE;
	
	if($localTime < $remoteTime || ($localTime == $remoteTime && $localSize != $remoteSize)) {
		if(file_exists($file.'.tmp')) {
			unlink($file.'.tmp');
		}
		if(ftp_get($ftp, $file.'.tmp', $url['path'], FTP_BINARY)) {
			touch($file.'.tmp', $remoteTime);
			if(!rename($file.'.tmp', $file)) {
				throw new Exception('Temporary file rename failed');
			}
			$updated = TRUE;
		}
	}
	
	ftp_close($ftp);
	
	return $updated;
}

function fetch($url, $file = NULL) {
	if($file == NULL) {
		$file = basename($url['url']);
	}
	$data = file_get_contents($url);
	if($data === FALSE) {
		throw new Exception('URL fetch failed');
	}
	if(file_put_contents($file.'.tmp', $data) === FALSE) {
		throw new Exception('Temporary file creation failed');
	}
	if(!rename($file.'.tmp', $file)) {
		throw new Exception('Temporary file rename failed');
	}
}
