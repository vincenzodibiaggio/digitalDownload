<?php
/**
* @license
*
* digitalDownload
*
* Copyright (c) 2013 Vincenzo Di Biaggio
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
* 		* Redistributions of source code must retain the above copyright
* 			notice, this list of conditions and the following disclaimer.
* 		* Redistributions in binary form must reproduce the above copyright
* 			notice, this list of conditions and the following disclaimer in the
* 			documentation and/or other materials provided with the distribution.
* 		* Neither the name of the <organization> nor the
* 			names of its contributors may be used to endorse or promote products
* 			derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
* ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*
* @author vincenzodb - vincenzodibiaggio - aniceweb@gmail.com - http://vincenzodb.com/
*
*/

namespace DigitalDownload;

require 'extSrc/writeOverImage.php';
require 'extSrc/taylorPdf.php';

use writeOverImage\writeOverImage;

define('DD_DIR', __DIR__.'/');
// If you can, put the download directory outside of a public directory
define('DD_PATH', DD_DIR.'download/');
define('DD_L_PATH', DD_DIR.'labels/');

class DigitalDownload
{	
	/**
	 * 
	 *  ::: CONFIG :::
	 * 
	 */
	
	protected $host = 'general.dev'; // change this with your domain name
	
	public $install	= 1; // Set to 1 to reinstall and regenerate the codes, 0 otherwise
	public $downloadsAllowed	= 1; // Set to 1 to allow downloads, 0 otherwise
	public $createLabel = 1; // Create labels at the end of installation
	public $createPdf = 0; // Create a pdf with the labels at the end of installation
	public $pdfOrientation = 'L'; // P or L
	public $pdfDpi = 300; 
	public $regeneratePdf = 0; // During installation regenerate ONLY the pdf, not the labels
	public $downloadPdf = 0; // At the end of installation, or regeneration of pdf: 0 = download directly the pdf, 1 = save pdf in labels directory with name 'labels.pdf'
	public $pdfMarginX = 10; // Left margin
	public $pdfMarginY = 10; // Right margin
	
	public $fullPath= DD_DIR;
	public $downloadDirectory = DD_PATH;
	public $labelsDirectory = DD_L_PATH;
	public $backgroundFileName = 'digitaldownload.gif';
	public $fileToDownload = 'ajax-loader.gif'; // File to download listed in the 'download' directory
	
	public $codeLenght = 10; // how many long are the code string?
	public $codeNum = 2; // how many codes?
	public $limitNumDownload = 5; // how many time a code is valid? 0 = unlimited 
	public $limitByHour = 2; // hour numbers from first use to expire 0 = unlimited
	
	const DD_DB_HOST = 'host';
	const DD_DB_USER = 'user';
	const DD_DB_PASS =  'pass';
	const DD_DB_DATABASE = 'database';
	const DD_DB_CODES_TABLE = 'DD_codes';
	const DD_DB_LOG_TABLE = 'DD_downloads_logger';
	
	
	public $eof	= "<br/>";
	public $titleOpen 	= "<h3>";
	public $titleClose = "</h3>";
	
	/**
	 * 
	 *  ::: END CONFIG :::
	 * 
	 */
	
	
	private static $dbConnection = null;
	protected $code;
	protected $codeId;
	protected $userIp;
	protected $downloadDate;
	protected $return;
	protected $responseCode;
	
	
	
	public function __construct() {}
	
	
	/**
	 * Let's go!
	 */
	public function download($code){
		try {
			$date = new \DateTime('now', new \DateTimeZone('UTC'));
			$now = $date->format('Y-m-d H:i:s');
			$dateLimit = '0000-00-00 00:00:00';
			
			$conn = self::createDbConnection();
				
			$query = 'SELECT id, date_limit, download_numbers, downloads_number_limit FROM '.self::DD_DB_CODES_TABLE.' WHERE code = :code LIMIT 1;';
			$stmt = $conn->prepare($query);
			$stmt->bindParam(':code', $code, \PDO::PARAM_STR, $this->codeLenght);
			$stmt->execute();
			$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			if (count($result))
			{
				if ( $this->limitNumDownload !== 0 && $result[0]['download_numbers'] >= $result[0]['downloads_number_limit'])
				{
					$this->responseCode = 203;
					$this->return = 'With this code you have reached the limit by number';
				}
				else if ( $this->limitByHour !== 0 && $result[0]['download_numbers'] > 0 && $now >= $result[0]['date_limit'])
				{
					$this->responseCode = 203;
					$this->return = 'With this code you have reached the limit by time';
				}
				else 
				{
					$this->code = $code;
					$this->codeId = $result[0]['id'];
					$this->makeDownload();
				}
			}
			else {
				$this->responseCode = 203;
				$this->return = 'No results found with this code or code are invalid.';
			}
				
			self::closeDbConnetion();
				
		} catch( \PDOException $e) {
			$this->responseCode = 203;
			$this->return = 'ERROR: '.$e->getMessage();
		}
	}
	
	
	public function logDownload()
	{
		try {
			$obj = unserialize($_SESSION['for_log']);
			
			$date = new \DateTime('now', new \DateTimeZone('UTC'));
			$now = $date->format('Y-m-d H:i:s');
			
			$conn = self::createDbConnection();
			$stmt = $conn->prepare('INSERT INTO '.self::DD_DB_LOG_TABLE.' (code_id, code, user_ip, dowload_date) VALUES(:codeId , :code , :userIp , :downloadDate);');
			$stmt->execute( array( ':codeId' 		=> $obj->codeId,
									':code' 		=> $obj->code,
									':userIp' 		=> $_SERVER['REMOTE_ADDR'],
									':downloadDate' => $now) );
		} catch (PDOException $e) {
			$this->responseCode = 204;
			$this->return = 'ERROR: '.$e->getMessage();
		}
		
		return true;
	}
	
	protected function makeDownload()
	{
		$date = new \DateTime('now', new \DateTimeZone('UTC'));
		$now = $date->format('Y-m-d H:i:s');
		
		$conn = self::createDbConnection();
		$stmt = $conn->prepare('SELECT download_numbers FROM '.self::DD_DB_CODES_TABLE.' WHERE id = '.$this->codeId.' LIMIT 1;');
		$stmt->execute();
		
		$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		$updateFirstDate = '';
		$updateLimitDate = '';
		
		// on first use I update relative table field
		if( 0 == $result[0]['download_numbers'])
		{
			$updateFirstDate = ' , first_use_date = \''.$now.'\'';				
		}
		
		
		if ( $this->limitByHour !== 0)
		{
			// if I have hour limit on first download I set this limit on database
			if( 0 == $result[0]['download_numbers'])
			{
				$dateLimit = $date->add( new \DateInterval('PT'.$this->limitByHour.'H'));
				$l = $dateLimit->format('Y-m-d H:i:s');
				$updateLimitDate = ' , date_limit = \''.$l.'\'';
			}
		}
		
		$conn =self::createDbConnection();
		$conn->exec("UPDATE ".self::DD_DB_CODES_TABLE." SET 
					download_numbers = download_numbers + 1 ".$updateFirstDate.$updateLimitDate." WHERE id = ".$this->codeId.";");
		
		
		$this->responseCode = 200;
		$this->return = 'giveMeTheFile.php?ctrl='.$_SESSION['ctrl'];
		$_SESSION['for_log'] = serialize($this);
	}
	
	/**
	 * Create or retreive db connection
	 */
	private static function createDbConnection()
	{
		if (self::$dbConnection == null)
		{
			try {
				self::$dbConnection = new \PDO('mysql:host='.self::DD_DB_HOST.';dbname='.self::DD_DB_DATABASE.'', self::DD_DB_USER, self::DD_DB_PASS);
				self::$dbConnection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			
				return self::$dbConnection;
			
			} catch(PDOException $e) {
				$this->responseCode = 204;
				$this->return = 'ERROR: ' . $e->getMessage();
			}
		}
		else
		{
			return self::$dbConnection;
		}
	}
	
	
	
	/**
     * Close db connection 
	 */
	private static function closeDbConnetion()
	{
		if (self::$dbConnection == null)
		{
			return true;
		}
		else 
		{
			self::$dbConnection = null;
			return true;
		}
	}
	
	
	
	/**
	 * Installation!
	 */
	public function installDigitalDownload()
	{
		if( $this->regeneratePdf){
			
			$message = '';
			$message .= $this->titleOpen.'Digital Download will generate the pdf with labels'.$this->titleClose.$this->eof;
			$message .= $this->eof;
			echo $message;
			
			self::generatePdf();
			
			exit();
		}
		
		try {
			
			$conn = self::createDbConnection();
			
			// create the tables
			$conn->exec("DROP TABLE IF EXISTS `".self::DD_DB_CODES_TABLE."`;");
			$conn->exec("CREATE TABLE IF NOT EXISTS `".self::DD_DB_CODES_TABLE."` (
					`id` bigint(20) NOT NULL AUTO_INCREMENT,
					`code` varchar(".$this->codeLenght.") NOT NULL,
					`first_use_date` datetime NOT NULL,
					`date_limit` datetime NOT NULL,
					`download_numbers` int(11) NOT NULL,
					`downloads_number_limit` int(11) NOT NULL,
					PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
			
			
			$conn->exec("DROP TABLE IF EXISTS ".self::DD_DB_LOG_TABLE.";");
			$conn->exec("CREATE TABLE IF NOT EXISTS ".self::DD_DB_LOG_TABLE." (
						  `id` int(11) NOT NULL AUTO_INCREMENT,
						  `code_id` int(11) NOT NULL,
						  `code` varchar(255) NOT NULL,
						  `user_ip` varchar(15) NOT NULL,
						  `dowload_date` datetime NOT NULL,
						  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
						  PRIMARY KEY (`id`)
						) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
		
		
			// generate codes
		
			for ($i = 0; $i < $this->codeNum; $i++)
			{
				$r = rand(0,1000000000000000);
				$code = substr(strtoupper(sha1(sha1(($r)))), 1, $this->codeLenght);
		
				$conn->exec("INSERT INTO ".self::DD_DB_CODES_TABLE." (code, first_use_date, date_limit, download_numbers, downloads_number_limit)
						VALUES ('".$code."', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0', '".$this->limitNumDownload."');");
			}
		
			self::closeDbConnetion();
			
			$message = '';
			$message .= $this->titleOpen.'Digital Download is installed'.$this->titleClose.$this->eof;
			$message .= 'The installer has generated '.$this->codeNum.' codes'.$this->eof;
			
			if ($this->limitNumDownload == 0)
			{
				$message .= 'Download is not limited on count'.$this->eof;
			}
			else {
				$message .= 'Every code can be used '.$this->limitNumDownload.$this->eof;
			}
			
			if ($this->limitByHour == 0)
			{
				$message .= 'Download is not limited on time'.$this->eof;
			}
			else {
				$message .= 'Every code can be used for '.$this->limitByHour.' after first download before it expire'.$this->eof;
			}
			
			echo $message;
			
			// generate labels
			if ($this->createLabel)
			{
				$message = '';
				$message .= $this->titleOpen.'Digital Download will generate labels'.$this->titleClose.$this->eof;
				$message .= $this->eof;
				echo $message;
				
				self::generateLabels();
				
				// generate pdf with all images
				if ($this->createPdf)
				{
					$message = '';
					$message .= $this->titleOpen.'Digital Download will generate the pdf with labels'.$this->titleClose.$this->eof;
					$message .= $this->eof;
					echo $message;
						
					self::generatePdf();
				}
			}
		}
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	
	
	
	private function generatePdf()
	{
		if ($handle = opendir($this->labelsDirectory)) {
			
			if('P' == $this->pdfOrientation) {
				$pageWidth = '595';
				$pageHeight = '841';
			}
			elseif ('L' == $this->pdfOrientation) {
				$pageWidth = '841';
				$pageHeight = '595';
			}
			else { throw new Exception('PDF Orientation is wrong'); }
			
			$pdf = new \taylorPdf($this->pdfOrientation, "pt", "A4");
			
			$first = 1;
			$addPage = 0;
			
			$marginX = $this->pdfMarginX;
			$marginY = $this->pdfMarginY;
			$pageX = $this->pdfMarginX;
			$pageY = $this->pdfMarginY;
			
			
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && $file != $this->backgroundFileName && substr($file, -3) !== 'pdf') {
					
					list($width, $height, $type, $attr) = getimagesize($this->labelsDirectory.$file);
					
					if(1 == $first) {
						$pdf->AddPage();
						$pageX = $this->pdfMarginX;
						$pageY = $this->pdfMarginY;
					}
					else {
						$pageX = $pageX + $this->pdfMarginX;
					}
					
					$width = $width * 72 / $this->pdfDpi;
					$height = $height * 72 / $this->pdfDpi;
					
					if( ($pageX + $width) >= $pageWidth ){
						$pageX = $this->pdfMarginX;
						$pageY = $pageY + $this->pdfMarginY + $height;
					}
					
					if( ($pageY + $height) >= $pageHeight)
					{
						$pdf->AddPage();
						$pageX = $this->pdfMarginX;
						$pageY = $this->pdfMarginY;
					}
					
					$pdf->Image($this->labelsDirectory.$file,$pageX,$pageY, $width, $height);
					$pageX = $pageX + $width;
					$first = 0;
					
				}
				
			}
			closedir($handle);
			
			if($this->downloadPdf)
			{
				$pdf->Output($this->labelsDirectory.'/labels.pdf','F');
				$pdf->Output();
			}
			else 
			{
				$pdf->Output($this->labelsDirectory.'/labels.pdf','F');
			}
		}
		
	}
	
	
	
	private function generateLabels()
	{
		try {
			$conn = self::createDbConnection();
			$stmt = $conn->prepare('SELECT code FROM '.self::DD_DB_CODES_TABLE.';');
			$stmt->execute();
			
			$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
			if (count($result))
			{
				foreach ($result AS $r)
				{
					$image = new writeOverImage();
					
					$image->backgroundImagePath = 'labels/'; // image path with trailing slash
					$image->backgroundImage = $this->backgroundFileName; // image name
					$image->stringColorRed = 0;
					$image->stringColorGreen = 0;
					$image->stringColorBlue = 0;
					$image->fontSize = 30;
					$image->stringAngle = 0;
					$image->startX = 380;
					$image->startY = 374;
					$image->fontName = 'georgia_italic.ttf'; // other free fonts here: http://www.free-fonts-ttf.org/true-type-fonts/
					$image->stringToWrite = $r['code']; // UTF-8 encoded string
					$image->newFile = true; // if false overwrite base file, if true create a new file with provided filename
					$image->newFileName = 'label_'.$r['code']; // just the filename. extension will be added automatically
					$image->outputDirectly = false; // if true send image directly, if false save image file
					
					$image->createImage();

				}
			}
			else {
				echo 'No results found.';
			}
			
		} catch (\PDOException $e) {
			echo 'ERROR: '.$e->getMessage();
		}
	}
	
	
	
	public function giveReturn() {
		return $this->return;
	}
	public function giveResponseCode() {
		return $this->responseCode;
	}
}