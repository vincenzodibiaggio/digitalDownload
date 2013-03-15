<?php

include(__DIR__.'/digitalDownload.php');
use DigitalDownload\DigitalDownload;

if (DigitalDownload::DD_DOWNLOAD_ALLOWED == 1 && DigitalDownload::DD_INSTALL == 0)
{
	session_start();
	if (!isset($_SESSION['abracadabra']) || !isset($_REQUEST['code'])) { die('The spell has failed :(');}
	
	$code = $_REQUEST['inputCode'];
	
	if ($_SESSION['abracadabra'] !== $_REQUEST['code']) {
		$f['result'] = 'ko';
		$f['message'] = 'The spell has failed :(';
		session_write_close();
		http_response_code($dd->giveResponseCode());
		echo json_encode($f);
	}
	else {
		$_SESSION['ctrl'] = sha1($_SESSION['abracadabra']);
		session_write_close();
		$dd = new DigitalDownload($code);
		$f['result'] = 'ok';
		$f['message'] = $dd->giveReturn();
		http_response_code($dd->giveResponseCode());
		echo json_encode($f);
	}
	
}
else 
{
	echo "Download not allowed";
}