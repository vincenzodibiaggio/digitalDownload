<?php
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
	
	static $downloadsAllowed	= 1; // Set to 1 to allow downloads, 0 otherwise
	
	public $install	= 1; // Set to 1 to reinstall and regenerate the codes, 0 otherwise
	public $createLabel = 1; // Create labels at the end of installation
	public $createPdf = 1; // Create a pdf with the labels at the end of installation
	public $pdfOrientation = 'L'; // P or L
	public $pdfDpi = 300; 
	public $regeneratePdf = 1; // During installation regenerate ONLY the pdf, not the labels
	public $downloadPdf = 0; // At the end of installation, or regeneration of pdf: 0 = download directly the pdf, 1 = save pdf in labels directory with name 'labels.pdf'
	
	
	public $fullPath= DD_DIR;
	public $downloadDirectory = DD_PATH;
	public $labelsDirectory = DD_L_PATH;
	public $backgroundFileName = 'digitaldownload.gif';
	
	
	public $fileToDownload = 'ajax-loader.gif'; // File to download listed in the 'download' directory
	
	public $codeLenght = 10; // how many long are the code string?
	public $codeNum = 2; // how many codes?
	public $limitNumDownload = 0; // how many time a code is valid? 0 = unlimited 
	public $limitByHour = 0; // hour numbers from first use to expire 0 = unlimited
	
	const DD_DB_HOST = 'localhost';
	const DD_DB_USER = 'root';
	const DD_DB_PASS =  'fr3k3t3';
	const DD_DB_DATABASE = 'file_download_manager';
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
				
			$date = new \DateTime();
			$now = $date->format('Y-m-d H:i:s');
			$dateLimit = '0000-00-00 00:00:00';
				
			$conn = self::createDbConnection();
				
			$query = 'SELECT COUNT(1) AS allowed, id FROM '.self::DD_DB_CODES_TABLE.' WHERE `code` = :code';
				
			if ( self::DD_LIMIT_NUM_DOWNLOAD != 0)
			{
				$query .= ' AND download_numbers < downloads_number_limit';
			}
				
			if ( self::DD_LIMIT_TIME_DOWNLOAD != 0)
			{
				$dateLimit = $date->add(new \DateInterval('P'.self::DD_LIMIT_TIME_DOWNLOAD.'D'));
		
				$query .= " AND $now < $dateLimit";
			}
				
			$query .= ' LIMIT 1';
				
			$stmt = $conn->prepare($query);
			$stmt->execute( array(':code' => $code) );
				
			$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
			if (count($result) && $result[0]['allowed'] != 0)
			{
				$this->userIp = $_SERVER['REMOTE_ADDR'];
				$this->downloadDate = $now;
				$this->code = $code;
				$this->codeId = $result[0]['id'];
		
				$this->makeDownload();
			}
			else {
				$this->responseCode = 203;
				$this->return = 'No results found with this code or code are invalid.';
			}
				
			self::closeDbConnetion();
				
		} catch( \PDOException $e) {
			$this->responseCode = 204;
			$this->return = 'ERROR: '.$e->getMessage();
		}
	}
	
	
	protected function logDownload()
	{
		try {
			$conn = self::createDbConnection();
			$stmt = $conn->prepare('INSERT INTO '.self::DD_DB_LOG_TABLE.' (code_id, code, user_ip, dowload_date) VALUES(:codeId , :code , :userIp , :downloadDate);');
			$stmt->execute( array( ':codeId' 		=> $this->codeId,
									':code' 		=> $this->code,
									':userIp' 		=> $this->userIp,
									':downloadDate' => $this->downloadDate) );
		} catch (PDOException $e) {
			$this->responseCode = 204;
			$this->return = 'ERROR: '.$e->getMessage();
		}
		
		return true;
	}
	
	protected function makeDownload()
	{
		
		$conn =self::createDbConnection();
		$conn->exec("UPDATE ".self::DD_DB_CODES_TABLE." SET download_numbers = download_numbers + 1 WHERE id = ".$this->codeId.";");
		
		$this->responseCode = 200;
		$this->return = 'giveMeTheFile.php?ctrl='.$_SESSION['ctrl'];
		
	}
	
	public function giveReturn()
	{
		return $this->return;
	}
	public function giveResponseCode()
	{
		return $this->responseCode;
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
			$message .= $eof;
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
				$message .= 'Every code can be used '.self::DD_LIMIT_NUM_DOWNLOAD.$this->eof;
			}
			
			if ($this->limitByHour == 0)
			{
				$message .= 'Download is not limited on time'.$eof;
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
		if ($handle = opendir(self::DD_LABELS_DIRECTORY)) {
			
			$pdf = new \taylorPdf(self::DD_PDF_ORIENTATION, "pt", "A4");
			
			$first = 1;
			$newPage = 1;
			$marginX = 10;
			$marginY = 10;
			$addPage = 0;
			$pageX = 0;
			$pageY = 0;
			
			if('P' == self::DD_PDF_ORIENTATION) {
				$pageWidth = '595';
				$pageHeight = '841';
			}
			else {
				$pageWidth = '841';
				$pageHeight = '595';
			}
			
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && $file != self::DD_LABEL_BACKGROUND_FILENAME && substr($file, -3) !== 'pdf') {
					
					list($width, $height, $type, $attr) = getimagesize(self::DD_LABELS_DIRECTORY.$file);
					
					if(1 == $first) {
						$pdf->AddPage();
						$pageX = $marginX;
						$pageY = $marginY;
					}
					else {
						$pageX = $pageX + $marginX;
					}
					
					$width = $width * 72 / self::DD_PDF_DPI;
					$height = $height * 72 / self::DD_PDF_DPI;
					
					
					
					if( ($pageX + $width) >= $pageWidth ){
						$pageX = $marginX;
						$pageY = $pageY + $marginY + $height;
					}
					
					if( ($pageY + $height) >= $pageHeight)
					{
						$pdf->AddPage();
						$pageX = $marginX;
						$pageY = $marginY;
					}
					
					$pdf->Image(self::DD_LABELS_DIRECTORY.$file,$pageX,$pageY, $width, $height);
					$pageX = $pageX + $width;
					$first = 0;
					
					//echo ($pageX.'-'.$pageY.'<br>');
				}
				
			}
			closedir($handle);
			
			if(self::DD_DOWNLOAD_PDF)
			{
				$pdf->Output(self::DD_LABELS_DIRECTORY.'/labels.pdf','F');
				$pdf->Output();
			}
			else 
			{
				$pdf->Output(self::DD_LABELS_DIRECTORY.'/labels.pdf','F');
			}
		}
		
	}
	
	
	
	private static function generateLabels()
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
					$image->backgroundImage = self::DD_LABEL_BACKGROUND_FILENAME; // image name
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
}