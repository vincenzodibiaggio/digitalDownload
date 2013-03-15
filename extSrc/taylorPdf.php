<?php
require 'fpdf.php';

class taylorPdf extends FPDF
{
	function Header()
	{
		$this->SetFont('Arial', 'B', 5);
		$this->Cell(50,10, 'Generated with DigitalDownload ()',0,0,'C');
	}
}