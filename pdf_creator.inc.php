<?php
include_once dirname ( __FILE__ ) . '/tcpdf/tcpdf.php';
include_once dirname ( __FILE__ ) . '/content_manager.inc.php';
class overviewPDF extends TCPDF {
	private $aIndices = array ();
	public function Header() {
		/*
		 * JKU Bild nicht erlaubt... if ($this->PageNo () === 1) { $fWidth = $this->getPageDimensions ()['wk']; $fHeight = $fWidth * 149 / 1253; $this->SetMargins ( PDF_MARGIN_LEFT, $fHeight ); $this->Image ( __DIR__ . '\Images\Header.jpg', 0, 0, $fWidth, $fHeight ); }
		 */
	}
	public function Footer() {
		$this->setFont ( 'times', '', 12 );
		$this->setY ( - 15 );
		$this->Line ( $this->getMargins ()['left'], $this->GetY (), $this->getPageDimensions ()['wk'] - $this->getMargins ()['right'], $this->GetY () );
		$this->Cell ( 0, 0, $this->getAliasRightShift () . $this->getAliasNumPage () . '/' . $this->getAliasNbPages (), 0, 0, 'R' );
	}
	private function getNextIndex($iLevel) {
		if (! array_key_exists ( $iLevel, $this->aIndices )) {
			for($i = 0; $i <= $iLevel; $i ++) {
				if (! array_key_exists ( $i, $this->aIndices ))
					$this->aIndices [$i] = 0;
			}
		} else {
			for($i = $iLevel + 1; array_key_exists ( $i, $this->aIndices ); $i ++) {
				$this->aIndices [$i] = 0;
			}
		}
		return ++ $this->aIndices [$iLevel];
	}
	private static function getHTMLHeight($sHTML) {
		$oPdf = new overviewPDF ();
		$oPdf->setFont ( 'times', '', 12 );
		$oPdf->setCellPaddings ( 0, 0, 0, 0 );
		$oPdf->AddPage ();
		$oPdf->writeHTML ( $sHTML );
		return $oPdf->getTotalY ();
	}
	private function getTotalY() {
		return ($this->PageNo () - 1) * ($this->getPageHeight () - $this->getMargins ()['top'] - $this->getMargins ()['bottom']) + $this->getY ();
	}
	/**
	 * Creates the PDF document and sends it to the client
	 */
	public function createPDF($iVID) {
		// create new PDF document
		$this->setFont ( 'times', '', 12 );
		$this->setCellPaddings ( 0, 0, 0, 0 );
		$oCurriculum = (new content_manager ())->getCurriculum ( $iVID );
		// set document information
		$this->SetCreator ( PDF_CREATOR );
		$this->SetAuthor ( 'StukoWIN' );
		$this->SetTitle ( 'Curriculum ' . 'Curriculum Wirtschaftsinformatik ' . $oCurriculum ['version'] );
		$this->SetSubject ( 'Curriculum Wirtschaftsinformatik' );
		$this->SetKeywords ( 'Curriculum, Übersicht, Wirtschaftsinformatik' );
		$this->AddPage ();
		$this->SetFontSize ( 45 );
		$this->MultiCell ( 0, 0, "" );
		$this->MultiCell ( 0, 0, $this->unhtmlentities ( "LVA &Uuml;bersicht" ), 0, 'C' );
		$this->Ln ();
		$this->MultiCell ( 0, 0, $oCurriculum ['type'], 0, 'C' );
		$this->Ln ();
		$this->MultiCell ( 0, 0, 'Wirtschaftsinformatik', 0, 'C' );
		$this->Ln ();
		$this->SetFontSize ( 20 );
		$this->MultiCell ( 0, 0, $oCurriculum ['faculty'], 0, 'C' );
		$this->SetFontSize ( 12 );
		$this->Ln ();
		$this->MultiCell ( 0, 0, 'Version ' . $oCurriculum ['version'], 0, 'C' );
		$this->printCurriculum ( $oCurriculum );
		$this->createTOCPage ();
		$sFilename = $this->getUniqueFileName ( $oCurriculum ['type'], $oCurriculum ['version'] );
		$this->Output ( $sFilename, 'F' );
		return 'PDF successfully created at ' . $sFilename;
	}
	/**
	 * Adds a Table of Contents Page to the PDF document
	 *
	 * @param unknown $pdf
	 *        	The PDF document to add to
	 */
	private function createTOCPage() {
		$this->addTOCPage ();
		$this->SetFont ( 'times', 'B', 15 );
		$this->MultiCell ( 0, 0, 'Inhalt', 0, 'L', 0, 1, '', '', true, 0 );
		$this->Ln ();
		$this->addTOC ( 2, 'times', '.', 'Inhalt', 'B' );
		$this->endTOCPage ();
	}
	/**
	 * Determines if a file with the standard filename already exists.
	 * If one exists, it appends a number and increases it until the name is not already taken.
	 *
	 * @param string $sCurrType
	 *        	The type of the curriculum (Bachelorstudium, Masterstudium)
	 * @param string $sCurrVersion
	 *        	The verision of the curriculum (e.g. 2013W)
	 */
	private function getUniqueFilename($sCurrType, $sCurrVersion) {
		$sCoreName = dirname ( __FILE__ ) . '/LVA-Übersicht Wirtschaftsinformatik ' . $sCurrType . ' ' . $sCurrVersion;
		$sFilename = $sCoreName . '.pdf';
		var_dump ( $sFilename );
		if (file_exists ( $sFilename )) {
			for($i = 1; file_exists ( $sFilename ); $i ++) {
				$sFilename = $sCoreName . '(' . $i . ').pdf';
			}
		}
		return $sFilename;
	}
	/**
	 * Prints a curriculum object and all its sub-objects to a PDF document
	 *
	 * @param unknown $pdf
	 *        	The PDF document to print to
	 * @param unknown $oCurriculum
	 *        	The curriculum object to print
	 */
	private function printCurriculum($oCurriculum) {
		$this->AddPage ();
		$aCourses = $this->getCourses ( $oCurriculum ['vid'] );
		$this->SetFontSize ( 20 );
		$this->printHeading ( strtoupper ( $oCurriculum ['type'] ), 0, false, 'C' );
		$this->SetFont ( 'times', '', 12 );
		$sHTML = <<<EOT
		<p>Es sind folgende F&auml;cher zu absolvieren:<p>
		<table border="1" style="padding-left:5px">
			<tr style="font-weight:bold">
				<th  width="90%">Fach</th>
				<th width="10%" align="center">ECTS</th>
			</tr>
EOT;
		foreach ( $aCourses as $oCourse ) {
			$sHTML .= '<tr nobr="true"><td>' . $oCourse->lva->title . '</td><td align="center">' . $oCourse->lva->ects . '</td></tr>';
		}
		$sHTML .= '</table>';
		$this->writeHTML ( $this->unhtmlentities ( $sHTML ) );
		foreach ( $aCourses as $oCourse ) {
			$this->printFach ( $oCourse );
		}
	}
	/**
	 * Prints a course object (Fach) and all its sub-objects to a PDF document
	 *
	 * @param myPDF $oPdf
	 *        	The PDF document to print to
	 * @param unknown $oFach
	 *        	The course object to print
	 */
	private function printFach($oFach) {
		// Generate HTML code for the overview table
		$sHTML = <<<EOT
		<p>Das Fach {$oFach->lva->title} gliedert sich in folgende Module/Lehrveranstaltungen:<p>
		<table border="1" style="padding-left:5px" nobr="true">
			<tr nobr="true" style="font-weight:bold">
				<th width="10%" align="center">LV-Typ</th>
				<th width="70%">Fach</th>
				<th width="10%" align="center">SSt.</th>
				<th width="10%" align="center">ECTS</th>
			</tr>
EOT;
		if (! property_exists ( $oFach, 'children' )) {
			$this->printHeading ( $oFach->lva->title, 1, true );
			return;
		}
		$aChildren = $oFach->children;
		foreach ( $aChildren as $oChild ) {
			$sHTML .= $this->generateTableRecHelper ( $oChild );
		}
		$sHTML .= '<tr nobr="true"><td></td><td align="right">Summe</td><td align="center">' . $oFach->lva->wst . '</td><td align="center">' . $oFach->lva->ects . '</td></tr>';
		$sHTML .= '</table>';
		$sHTML = $this->unhtmlentities ( $sHTML );
		if ($oFach->lva->ziele || $oFach->lva->lehrinhalte) {
			$this->checkPageBreak ( 30 );
			$this->printHeading ( $oFach->lva->title, 1, true );
			$this->printZieleInhalte ( $oFach );
			$this->checkPageBreak ( $this->getHTMLHeight ( $sHTML ) );
			$this->writeHTML ( $sHTML );
		}
		foreach ( $aChildren as $oChild ) {
			if (($oChild->lva->ziele || $oChild->lva->lehrinhalte) && $oChild->lva->lvatype && $oChild->lva->lvatype != '3') {
				$this->printHeading ( $oChild->lva->typename . ' ' . $oChild->lva->title, 2, true, 'L', false );
				$this->printZieleInhalte ( $oChild );
			}
		}
		$this->Ln ();
	}
	private function generateTableRecHelper($oCourse) {
		$sHTML = '';
		switch ($oCourse->lva->lvatype) {
			case '1' :
				$sHTML .= '<tr nobr="true" style="word-break: break-all;word-wrap:break-word;"><td></td><td><B>' . $oCourse->lva->typename . ' ' . $oCourse->lva->title . '</B></td><td></td><td></td></tr>';
				break;
			case '2' :
				$sHTML .= '<tr nobr="true" style="word-break: break-all;word-wrap:break-word;"><td></td><td><I>' . $oCourse->lva->typename . ' ' . $oCourse->lva->title . '</I></td><td></td><td></td></tr>';
				break;
			case '3' :
			default :
				$sHTML .= '<tr nobr="true" style="word-break: break-all;word-wrap:break-word;"><td align="center">' . $oCourse->lva->lvtypshort . '</td><td>' . $oCourse->lva->title . '</td><td align="center">' . $oCourse->lva->wst . '</td><td align="center">' . $oCourse->lva->ects . '</td></tr>';
				break;
		}
		if (property_exists ( $oCourse, 'children' ))
			foreach ( $oCourse->children as $oChild )
				$sHTML .= $this->generateTableRecHelper ( $oChild );
		return $sHTML;
	}
	private function printZieleInhalte($oCourse) {
		if ($oCourse->lva->ziele) {
			$this->SetFont ( 'times', 'B', 12 );
			$this->MultiCell ( 0, 0, 'Lehrziele', '', 'L', false, 1, '', '', true, 0, false, false );
			$this->SetFont ( 'times', '', 12 );
			$this->WriteHTML ( $oCourse->lva->ziele );
			$this->Ln ();
		}
		if ($oCourse->lva->lehrinhalte) {
			$this->SetFont ( 'times', 'B', 12 );
			$this->MultiCell ( 0, 0, 'Lehrinhalte', '', 'L', false, 1, '', '', true, 0, false, false );
			$this->SetFont ( 'times', '', 12 );
			$this->WriteHTML ( $oCourse->lva->lehrinhalte );
			$this->Ln ();
		}
	}
	/**
	 * Prints a heading to the PDF document and adds a bookmark for it
	 *
	 * @param myPDF $oPdf
	 *        	The PDF document to print to
	 * @param unknown $sText
	 *        	The heading text
	 * @param unknown $iLevel
	 *        	The level at which the heading should be created and the bookmark set
	 * @param unknown $bShowIndex
	 *        	true if the index should be shown in the heading
	 * @param string $sAlign
	 *        	Alignment of the heading. For allowed values see @see TCPDF::Multicell()
	 */
	private function printHeading($sText, $iLevel, $bShowIndex = true, $sAlign = 'L', $bAddBookmark = true) {
		$iIndex = $this->getNextIndex ( $iLevel );
		$sText = $this->unhtmlentities ( $sText );
		switch ($iLevel) {
			case 0 :
				$this->SetFont ( 'times', 'B', 20 );
				break;
			case 1 :
				$this->SetFont ( 'times', 'B', 14 );
				break;
			case 2 :
				$this->SetFont ( 'times', 'I', 12 );
				break;
			default :
				$this->SetFont ( 'times', '', 12 );
				break;
		}
		if ($bAddBookmark)
			$this->Bookmark ( ($bShowIndex ? $iIndex . ') ' : '') . $sText, $iLevel );
		$this->MultiCell ( 0, 0, ($bShowIndex ? $iIndex . ') ' : '') . $sText, '', $sAlign, false, 1, '', '', true, 0, false, false );
		$this->Ln ();
	}
	/**
	 * Gets all courses for a given curriculum id
	 *
	 * @param int $currId
	 *        	The id of the curriculum to get the courses from
	 * @return array $aCourses The array of all courses in the given curriculum
	 */
	private static function getCourses($currId) {
		return (new content_manager ())->taxonomy_get_nested_tree ( $currId );
	}
	
	/**
	 * Guarantees the existence of the specified attributes in the course and all its children.
	 *
	 * @param unknown $oCourse
	 *        	The course to assert the attributes in
	 */
	private static function assertAttributes($oCourse) {
		static $aRequiredFields = array (
				'title',
				'ects',
				'wst',
				'lvatype',
				'typename',
				'ziele',
				'lehrinhalte',
				'lvtypshort',
				'typename' 
		);
		if (! property_exists ( $oCourse, 'lva' ))
			$oCourse->lva = new stdClass ();
		foreach ( $aRequiredFields as $sRequiredField )
			if (! property_exists ( $oCourse->lva, $sRequiredField ) || ! $oCourse->lva->$sRequiredField)
				$oCourse->lva->$sRequiredField = '';
		if (property_exists ( $oCourse, 'children' ))
			foreach ( $oCourse->children as $oChild )
				overviewPDF::assertAttributes ( $oChild );
		if (property_exists ( $oCourse->lva, 'title' ))
			$oCourse->lva->title = (new TCPDF ())->unhtmlentities ( $oCourse->lva->title );
	}
}
