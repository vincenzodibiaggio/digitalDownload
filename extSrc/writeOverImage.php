<?php
namespace writeOverImage;

/**
* @license
*
* writeOverImage
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
class writeOverImage
{
	public $backgroundImagePath = ''; // image path with trailing slash
	public $backgroundImage = ''; // image name
	public $stringColorRed = 255;
	public $stringColorGreen = 255;
	public $stringColorBlue = 255;
	public $fontSize = 10;
	public $stringAngle = 0;
	public $startX = 0;
	public $startY = 0;
	public $fontName = 'helvetica.ttf'; // other free fonts here: http://www.free-fonts-ttf.org/true-type-fonts/
	public $stringToWrite = ''; // UTF-8 encoded string
	public $newFile = false; // if false overwrite base file, if true create a new file with provided filename
	public $newFileName = ''; // just the filename. extension will be added automatically
	public $outputDirectly = false; // if true send image directly, if false save image file
	
	protected $image;
	protected $mime;
	
	protected $output;
	
	function __construct()
	{
		try {
			putenv('GDFONTPATH=' . realpath('.'));
		} catch (Exception $e) {
			echo 'ERROR: '.$e->getMessage();
		}
	}
	

	
	/**
	 * Get base image with GD
	 * 
	 * @param string $imagePath
	 * @throws Exception
	 */
	private function getBaseImage($imagePath)
	{
		$info = getimagesize($imagePath);
		
		switch ($info['mime']) {
			case 'image/gif':
				$im = imagecreatefromgif($imagePath);
				break;
			case 'image/jpeg':
				$im = imagecreatefromjpeg($imagePath);
				break;
			case 'image/png':
				$im = imagecreatefrompng($imagePath);
				break;
			case 'image/bmp':
				$im = imagecreatefromwbmp($imagePath);
				break;
			default:
				throw new \Exception('Mime type not supported');
				break;
		}
		
		$this->mime = $info['mime'];
		
		return $im;
	}
	

	/**
	 * Create image. Overwrite existing file or create new or output directly
	 * 
	 * @throws Exception
	 */
	function createImage()
	{

		$this->image = $this->getBaseImage($this->backgroundImagePath.$this->backgroundImage);
		$this->backgroundImage = substr($this->backgroundImage, 0, strpos($this->backgroundImage, '.') -1);
		$stringColor = $this->createStringColor();
		$ext = $this->getFileExtension();
		
		try {
			// write text over the image
			Imagettftext($this->image, $this->fontSize, $this->stringAngle, $this->startX, $this->startY, $stringColor, $this->fontName, $this->stringToWrite);
			
			// manage output
			if ($this->outputDirectly)
			{
				// directly stream of data
				$this->output = NULL;
			}
			else {
				// save data in a file (new or overwrite)
				$this->output = ($this->newFile) ? $this->backgroundImagePath.$this->newFileName.$ext : $this->backgroundImagePath.$this->backgroundImage.$ext;
			}
			
			
			switch ($this->mime)
			{
				case 'image/gif':
					imagegif($this->image, $this->output);
					break;
				case 'image/jpeg':
					imagejpeg($this->image, $this->output, 75);
					break;
				case 'image/png':
					imagepng($this->image, $this->output, 7, PNG_ALL_FILTERS);
					break;
				case 'image/bmp':
					imagewbmp($this->image, $this->output);
					break;
				default:
					throw new \Exception('Mime type not supported');
					break;
			}
						
			ImageDestroy($this->image);
			
		} catch (Exception $e) {
			echo 'ERROR: '.$e->getMessage();
		}
		
	}
	
	
	
	/**
	 * Create color value for the string
	 * 
	 * @return number
	 */
	private function createStringColor()
	{
		try {
			$color = ImageColorAllocate($this->image, $this->stringColorRed, $this->stringColorGreen, $this->stringColorBlue);
		} catch (\Exception $e) {
			echo 'ERROR: '.$e->getMessage();
		}
		
		return $color;
	}
	
	
	
	/**
	 * Return a string with file extension based on mimetype (eg.: .gif)
	 * 
	 * @throws Exception
	 */
	private function getFileExtension()
	{
		switch ($this->mime)
		{
			case 'image/gif':
				return '.gif';
				break;
			case 'image/jpeg':
				return '.jpg';
				break;
			case 'image/png':
				return '.png';
				break;
			case 'image/bmp':
				return '.bmp';
				break;
			default:
				throw new \Exception('Mime type not supported');
				break;
		}
	}
}