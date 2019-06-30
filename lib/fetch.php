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
	
	$ftp = FtpConnection::create($url['host'], $url['port'], $url['user'], $url['pass']);
	$remoteSize = $ftp->size($url['path']);
	$remoteTime = $ftp->mdtm($url['path']);
	
	$updated = FALSE;
	
	if($localTime < $remoteTime || ($localTime == $remoteTime && $localSize != $remoteSize)) {
		if(file_exists($file.'.tmp')) {
			unlink($file.'.tmp');
		}
		$ftp->get($file.'.tmp', $url['path'], FTP_BINARY);
		touch($file.'.tmp', $remoteTime);
		if(!rename($file.'.tmp', $file)) {
			throw new Exception('Temporary file rename failed');
		}
		$updated = TRUE;
	}
	
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
