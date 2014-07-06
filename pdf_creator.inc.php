<?php
include_once dirname ( __FILE__ ) . '/tcpdf/tcpdf.php';
include_once dirname ( __FILE__ ) . '/content_manager.inc.php';

/**
 * Class for automatically generating a PDF document from a curriculum
 */
class overviewPDF extends TCPDF {
	/**
	 *
	 * @var array Array of indices for continuous indexation of headings
	 */
	private $aIndices = array ();
	
	/**
	 * Generates Footer for each page
	 */
	public function Footer() {
		$this->setFont ( 'times', '', 12 );
		$this->setY ( - 15 );
		$this->Line ( $this->getMargins ()['left'], $this->GetY (), $this->getPageDimensions ()['wk'] - $this->getMargins ()['right'], $this->GetY () );
		$this->Cell ( 0, 0, $this->getAliasRightShift () . $this->getAliasNumPage () . '/' . $this->getAliasNbPages (), 0, 0, 'R' );
	}
	
	/**
	 * Gets index of desired the level
	 *
	 * @param integer $iLevel
	 *        	level from which the index is wanted
	 * @return index
	 */
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
	
	/**
	 * Gets the height that the HTML code would need if printed to the PDF
	 *
	 * @return float The height of the HTML code
	 */
	private static function getHTMLHeight($sHTML) {
		$oPdf = new overviewPDF ();
		$oPdf->setFont ( 'times', '', 12 );
		$oPdf->setCellPaddings ( 0, 0, 0, 0 );
		$oPdf->AddPage ();
		$oPdf->writeHTML ( $sHTML );
		return $oPdf->getTotalY ();
	}
	
	/**
	 * Gets the total y
	 *
	 * @return float Total y-position in document (not just on this page)
	 */
	private function getTotalY() {
		return ($this->PageNo () - 1) * ($this->getPageHeight () - $this->getMargins ()['top'] - $this->getMargins ()['bottom']) + $this->getY ();
	}
	
	/**
	 * Creates the PDF document and saves it to the preconfigured path
	 *
	 * @param integer $iVID
	 *        	Drupal-ID of desired Curriculum
	 */
	public function createPDF($iVID) {
		// create new PDF document
		$this->setFont ( 'times', '', 12 );
		$this->setCellPaddings ( 0, 0, 0, 0 );
		$oCurriculum = (new content_manager ())->getCurriculum ( $iVID );
		// set document header information
		$this->SetCreator ( PDF_CREATOR );
		$this->SetAuthor ( 'StukoWIN' );
		$this->SetTitle ( 'Curriculum ' . 'Curriculum Wirtschaftsinformatik ' . $oCurriculum ['version'] );
		$this->SetSubject ( 'Curriculum Wirtschaftsinformatik' );
		$this->SetKeywords ( 'Curriculum, �bersicht, Wirtschaftsinformatik' );
		// set up first page
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
		// create document content
		$this->printCurriculum ( $oCurriculum );
		$this->createTOCPage ();
		// save document
		$sFilename = $this->getUniqueFileName ( overviewPDF::convertStringToValidFilename ( $oCurriculum ['type'], $oCurriculum ['version'] ) );
		$this->Output ( $sFilename, 'F' );
		return 'PDF successfully created at ' . $sFilename;
	}
	
	/**
	 * Needed for displaying errors in drupal itself
	 */
	public function Error($msg) {
		throw new Exception ( $msg );
	}
	
	/**
	 * Adds a table of contents page to the PDF document
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
	 * @return string $sFilename
	 *         name of the unique filename found
	 */
	private function getUniqueFilename($sCurrType, $sCurrVersion) {
		// Get save path
		$sPath = variable_get ( 'stukowin_pdf_path', DRUPAL_ROOT . '/sites/default/files/pdf/archive' );
		$sPath = rtrim ( $sPath, '/\\' );
		if (! file_exists ( $sPath ))
			mkdir ( variable_get ( 'stukowin_pdf_path' ), 0777, true );
			// Get file name
		$sCoreName = variable_get ( 'stukowin_pdf_name', 'Uebersicht %currtype% %version%' );
		$sCoreName = basename ( $sCoreName, '.php' );
		$sCoreName = str_replace ( '%currtype%', $sCurrType, $sCoreName );
		$sCoreName = str_replace ( '%version%', $sCurrVersion, $sCoreName );
		$sCoreName = $sPath . '/' . overViewPDF::convertStringToValidFilename ( $sCoreName );
		// Make file name unique
		$sFilename = $sCoreName . '.pdf';
		if (file_exists ( $sFilename )) {
			for($i = 1; file_exists ( $sFilename ); $i ++) {
				$sFilename = $sCoreName . '(' . $i . ').pdf';
			}
		}
		return $sFilename;
	}
	
	/**
	 * Prints a curriculum object and all its courses to the PDF document
	 *
	 * @param object $oCurriculum
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
			if (property_exists ( $oCourse, 'lva' ))
				$sHTML .= '<tr nobr="true"><td>' . $oCourse->lva->title . '</td><td align="center">' . $oCourse->lva->ects . '</td></tr>';
			else
				$sHTML .= '<tr nobr="true"><td><I>' . $oCourse->name . '</I></td><td align="center"></td></tr>';
		}
		$sHTML .= '</table>';
		$this->writeHTML ( $this->unhtmlentities ( $sHTML ) );
		foreach ( $aCourses as $oCourse ) {
			$this->printTopLevel ( $oCourse );
		}
	}
	
	/**
	 * Helper function that checks if an object is a structure element (1.
	 * Semester, 2. Semester etc.) or a course and calls {@link printFach()} if it is a course object
	 *
	 * @param object $oTopLevel
	 *        	The object to check
	 */
	private function printTopLevel($oTopLevel) {
		if (property_exists ( $oTopLevel, 'lva' )) {
			$this->printFach ( $oTopLevel );
		} else if (property_exists ( $oTopLevel, 'children' )) {
			$this->printHeading ( $oTopLevel->name, 0, false );
			foreach ( $oTopLevel->children as $oChild ) {
				$this->printFach ( $oChild );
			}
		}
	}
	
	/**
	 * Prints a subject, an overview for it and all its sub-courses to the PDF document
	 *
	 * @param object $oFach
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
		foreach ( $aChildren as $oChild )
			$sHTML .= $this->generateTableRecHelper ( $oChild );
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
		foreach ( $aChildren as $oChild )
			if (($oChild->lva->ziele || $oChild->lva->lehrinhalte) && $oChild->lva->lvatype && $oChild->lva->lvatype != '3') {
				$this->printHeading ( $oChild->lva->typename . ' ' . $oChild->lva->title, 2, true, 'L', false );
				$this->printZieleInhalte ( $oChild );
			}
		$this->Ln ();
	}
	
	/**
	 * Helper function for generating a subject overview table that traverses the nested array of courses down to the leaves and creates a table row for each course
	 *
	 * @param object $oCourse
	 *        	The course to generate the table html code for
	 * @return string The table's html code
	 */
	private function generateTableRecHelper($oCourse) {
		$sHTML = '';
		switch ($oCourse->lva->lvatype) {
			// Subject
			case '1' :
				$sName = $oCourse->lva->typename . ' ' . $oCourse->lva->title;
				{
					$sHTML .= '<tr nobr="true"><td></td><td><B>' . $this->splitBoldTextIntoLines ( $sName, 123 ) . '</B></td><td></td><td></td></tr>';
				}
				break;
			// Module
			case '2' :
				$sHTML .= '<tr nobr="true"><td></td><td><I>' . $oCourse->lva->typename . ' ' . $oCourse->lva->title . '</I></td><td></td><td></td></tr>';
				break;
			// Course
			case '3' :
			default :
				$sHTML .= '<tr nobr="true"><td align="center">' . $oCourse->lva->lvtypshort . '</td><td>' . $oCourse->lva->title . '</td><td align="center">' . $oCourse->lva->wst . '</td><td align="center">' . $oCourse->lva->ects . '</td></tr>';
				break;
		}
		if (property_exists ( $oCourse, 'children' ))
			foreach ( $oCourse->children as $oChild )
				$sHTML .= $this->generateTableRecHelper ( $oChild );
		return $sHTML;
	}
	
	/**
	 * Inserts line breaks into a string if it would exceed a certain width if printed as bold text.
	 * This method is needed in {@link generateTableRecHelper()} because {@see TCPDF} does not break the words correctly in a table if the text is written in a <<B>> tag. If one word alone is too long, it is not splitted.
	 *
	 * @param string $sText
	 *        	The text to split
	 * @param $iMaxWidth The
	 *        	The maximum width a line is allowed to have
	 * @return string The string with <br> tags inserted whenever necessary
	 */
	private function splitBoldTextIntoLines($sText, $iMaxWidth) {
		$aWords = preg_split ( '/\s+/', $sText );
		if ($this->getStringWidth ( $sText, '', 'B' ) < $iMaxWidth)
			return $sText;
		return $this->splitRecHelper ( $aWords, 0, $iMaxWidth );
	}
	
	/**
	 * Recursive helper function for {@link splitBoldTextIntoLines()}
	 *
	 * @param array $aWords
	 *        	The array of words as split in {@link splitBoldTextIntoLines()}
	 * @param integer $iCurrIndex
	 *        	The current index in the array
	 * @param integer $iMaxWidth
	 *        	The maximum width a line is allowed to have
	 */
	private function splitRecHelper($aWords, $iCurrIndex, $iMaxWidth) {
		if ($this->getStringWidth ( $aWords [$iCurrIndex], '', 'B' ) > $iMaxWidth)
			return $aWords [$iCurrIndex] . '<br>' . $this->splitRecHelper ( $aWords, $iCurrIndex + 1, $iMaxWidth );
		$sRetString = '';
		for($i = $iCurrIndex; $i < count ( $aWords ); $i ++) {
			if ($this->getStringWidth ( $sRetString . ' ' . $aWords [$i], '', 'B' ) > $iMaxWidth)
				return $sRetString . '<br>' . $this->splitRecHelper ( $aWords, $i, $iMaxWidth );
			else
				$sRetString = $sRetString . ' ' . $aWords [$i];
		}
		return $sRetString;
	}
	
	/**
	 * Prints the course goals and content of teaching and their respecitve headings to the PDF document.
	 * If one of them is not set or empty, their heading is not printed either
	 *
	 * @param object $oCourse
	 *        	The course to print the goals and contents for
	 */
	private function printZieleInhalte($oCourse) {
		// Print goals
		if ($oCourse->lva->ziele) {
			$this->SetFont ( 'times', 'B', 12 );
			$this->MultiCell ( 0, 0, 'Lehrziele', '', 'L', false, 1, '', '', true, 0, false, false );
			$this->SetFont ( 'times', '', 12 );
			$this->WriteHTML ( $oCourse->lva->ziele );
			$this->Ln ();
		}
		// Print content of teaching
		if ($oCourse->lva->lehrinhalte) {
			$this->SetFont ( 'times', 'B', 12 );
			$this->MultiCell ( 0, 0, 'Lehrinhalte', '', 'L', false, 1, '', '', true, 0, false, false );
			$this->SetFont ( 'times', '', 12 );
			$this->WriteHTML ( $oCourse->lva->lehrinhalte );
			$this->Ln ();
		}
	}
	
	/**
	 * Prints a heading to the PDF document and optionally adds a bookmark for it
	 *
	 * @param string $sText
	 *        	The heading text
	 * @param int $iLevel
	 *        	The level at which the heading should be created and the bookmark set
	 * @param boolean $bShowIndex
	 *        	true if the index should be shown in the heading
	 * @param string $sAlign
	 *        	Alignment of the heading. For allowed values see {@link TCPDF::Multicell()}
	 * @param boolean $bAddBookmark
	 *        	true if heading should be in index / bookmarked
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
	 * @param integer $currId
	 *        	The id of the curriculum to get the courses from
	 * @return array The nested array of all courses in the given curriculum
	 */
	private static function getCourses($currId) {
		$aCourses = (new content_manager ())->taxonomy_get_nested_tree ( $currId );
		foreach ( $aCourses as $oCourse ) {
			overviewPDF::assertAttributes ( $oCourse );
		}
		return $aCourses;
	}
	
	/**
	 * Guarantees the existence of the specified attributes in the course and all its children.
	 * Needed so that the other methods do not have to check everytime an attribute is accessed.
	 *
	 * @param object $oCourse
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
		if (property_exists ( $oCourse, 'lva' )) {
			foreach ( $aRequiredFields as $sRequiredField )
				if (! property_exists ( $oCourse->lva, $sRequiredField ) || ! $oCourse->lva->$sRequiredField)
					$oCourse->lva->$sRequiredField = '';
			if (property_exists ( $oCourse->lva, 'title' ))
				$oCourse->lva->title = (new TCPDF ())->unhtmlentities ( $oCourse->lva->title );
		}
		if (property_exists ( $oCourse, 'children' ))
			foreach ( $oCourse->children as $oChild )
				overviewPDF::assertAttributes ( $oChild );
	}
	
	/**
	 * Utility method that formats a string into a valid file name
	 *
	 * @param string $sString
	 *        	The file name to validate
	 * @return string The valid file name created from the input
	 *        
	 */
	private static function convertStringToValidFilename($sString) {
		if (strpos ( $sString = htmlentities ( $string, ENT_QUOTES, 'UTF-8' ), '&' ) !== false) {
			$sString = html_entity_decode ( preg_replace ( '~&([a-z]{1,2})(?:acute|caron|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i', '$1', $string ), ENT_QUOTES, 'UTF-8' );
		}
		return $sString;
	}
}
