<?php

include(__DIR__.'/digitalDownload.php');
use DigitalDownload\DigitalDownload;

$dd = new DigitalDownload();

if ( 1 == $dd->downloadsAllowed && 0 == $dd->install)
{
	session_start();
	
	if (!isset($_SESSION['abracadabra']) || !isset($_REQUEST['ctrl'])) {
		die('The spell has failed :(');
	}
	
	if (sha1($_SESSION['abracadabra']) !== $_REQUEST['ctrl']) {
		echo 'No magic found :(';
		session_write_close();
		session_destroy();
	}
	else {
		session_write_close();
		session_destroy();
		
		$fileName = $dd->downloadDirectory.$dd->fileToDownload;
		$fileSize = filesize($fileName);
		
		/**
		 * @todo permit download resume
		  */
		ignore_user_abort(true);
		set_time_limit(0);
		
		header('Content-Transfer-Encoding: binary');
		header("Content-Description: File Transfer");
		header('Content-Disposition: attachment; filename="' . basename($fileName) . "\";");
		header("Content-Type: application/octet-stream");
		header("Content-Length: ".$fileSize);
				
		ob_clean();
		flush();
		
		$file = @fopen($fileName,"rb");
		
		$this->logDownload();
		
		while(!feof($file))
		{
			$data = fread($file, 1204);
			echo $data;
			ob_flush();
			flush();
		}
	}
}
else
{
	echo "Download not allowed";
}