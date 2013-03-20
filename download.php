<?php

include(__DIR__.'/digitalDownload.php');
use DigitalDownload\DigitalDownload;

$dd = new DigitalDownload();
$dd->install = 0;
$dd->downloadsAllowed = 1;

if ( 1 == $dd->downloadsAllowed && 0 == $dd->install )
{
	session_start();
	if (!isset($_SESSION['abracadabra']) || !isset($_REQUEST['code'])) { die('The spell has failed :(');}
	
	$code = $_REQUEST['inputCode'];
	
	if ($_SESSION['abracadabra'] !== $_REQUEST['code']) {
		$f['result'] = 'ko';
		$f['message'] = 'The spell has failed :(';
		session_write_close();
		header("HTTP/1.0 ".$dd->giveResponseCode());
		echo json_encode($f);
		
	}
	else {
		$_SESSION['ctrl'] = sha1($_SESSION['abracadabra']);
		$dd->download($code);
		$f['result'] = 'ok';
		$f['message'] = $dd->giveReturn();
		header("HTTP/1.0 ".$dd->giveResponseCode());
		echo json_encode($f);
	}
	
}
else 
{
	$f['result'] = 'ko';
	$f['message'] = 'Download not allowed';
	session_write_close();
	header("HTTP/1.0 203");
	echo json_encode($f);
}