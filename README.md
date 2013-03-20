digitalDownload
==============

## Short description
With this class you can manage downloads of file by your website visitors with a code optionally limited by number of use and/or limited by time. You can also create a label with your code printed over an image and download it using a pdf file. I also provided a simple system with a form to use this class if you don't need specific necessities

## Requirements
* > PHP 5.3 
* MySql database
* PDO support by PHP
* writeOverImage class - https://github.com/vincenzodibiaggio/writeOverImage - provided
* FPDF class - http://www.fpdf.org/ - provided
* png, jpeg, gif, bmp image type for labels
* ttf fonts for labels - A lot of free fonts are here: http://www.free-fonts-ttf.org/true-type-fonts/

## To-do: support various pdf formats, support download resume.

## Usage
The file 'index.php' is used to provide a public form to insert the code and for the installation (see usage below).

## Settings:

File 'digitalDownload.php':

#### Installation
* Set your host: protected $host = 'general.dev'; // change this with your domain name
* If you do install digitalDownload set to 1: public $install	= 1; // Set to 1 to reinstall and regenerate the codes, 0 otherwise
* Set 1 to create labels : public $createLabel = 1; // Create labels at the end of installation
* Set 1 to create Pdf with all the labels: public $createPdf = 0; // Create a pdf with the labels at the end of installation
* Set your database data on const DD_DB_HOST, const DD_DB_USER, const DD_DB_PASS, const DD_DB_DATABASE, const DD_DB_CODES_TABLE, const DD_DB_LOG_TABLE
* Set the filename can be downloaded listed in the 'download' diretctory: public $fileToDownload 
* Set the lenght of codes used to download the file: public $codeLenght
* Set the number of codes will be generated: public $codeNum
* Set the number (if you want) to set the limit the number of downloads using a single code: public $limitNumDownload (0 = unlimited)
* Set the number (if you want) to set the limit in hours for the validity of the code after the first download: public $limitByHour (0 = unlimited)

#### Label creation

* Set the image path where it found the background file and where the labels will be saved: public $backgroundImagePath
* Set the RGB color of string will be writed over the background: public $stringColorRed, public $stringColorGreen, public $stringColorBlue
* Set the fontsize of string: public $fontSize
* Set the angle of string: public $stringAngle (0 = horizontal, 90 = vertical, etc)
* Set the point x where the string will be written: public $startX
* Set the point y where the string will be written: public $startY
* Set the font file will be used: public $fontName
* Set the string will be written: public $stringToWrite
* Set the background file will be used for the labels: public $backgroundFileName

#### PDF creation
Will be generated a pdf with A4 format

* Set the orientation: public $pdfOrientation
* Set the DPI: public $pdfDpi
* Set if ONLY the PDF will be regenerated during installation (NOT labels and codes): public $regeneratePdf
* Set if you want download directly the pdf after installation: public $downloadPdf = 0; // At the end of installation, or regeneration of pdf: 0 = download directly the pdf, 1 = save pdf in labels directory with name 'labels.pdf'
* Set the left margin: public $pdfMarginX
* Set the top margin: public $pdfMarginY

#### Output
* Set EOF: public $eof
* Set title's prefix: public $titleOpen
* Set title's postfix public $titleClose


#### Let's go!
After installation, to permit file download, set to '0' public $install and to '1' public $downloadsAllowed


















