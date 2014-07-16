<?php
/**
 * @defgroup Drupal2PDF Drupal2PDF
 * @brief Module to create PDF documents from curricula data
 * 
 * This module contains all files, classes and methods that provide 
 * the functionality for automatically generating PDF documents from the
 * imported curricula data.
 */
/**
 * @file
 * @ingroup Drupal2PDF
 * @brief PDF document generation from curricula data
 *
 * This file contains all necessary functionality for
 * automatically generating PDF documents from the
 * imported curricula data.
 *
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @version 1.0.0 2014-07-15
 * @since Commit b9342d94 on 2014-06-30
 *       
 * @see overviewPDF
 */
include_once dirname ( __FILE__ ) . '/tcpdf/tcpdf.php';
include_once dirname ( __FILE__ ) . '/content_manager.inc.php';

/**
 * @ingroup Drupal2PDF
 * @brief Class for PDF document generation from curricula data
 *
 * This class provides the functionality for automatically
 * generating a PDF document from a curriculum.
 *
 * It is publicly accessible through the @ref createPDF()
 * method, which initiates and guides the PDF generation.
 *
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @version 1.0.0
 * @since Commit b9342d94 on 2014-06-30
 *       
 * @see createPDF()
 * @todo Add creation date and time to footer
 */
class overviewPDF extends TCPDF {
	/**
	 * @brief Array of indices for continuous indexation of headings
	 * Every key in this array represents an indexation level
	 * (e.g.
	 * level 2 for 1.1, level 3 for 1.1.1) and every value
	 * in the array the current index for that level.
	 *
	 * @since Commit b9342d94 on 2014-06-30
	 * @see getNextIndex()
	 */
	private $aIndices = array ();
	
	/**
	 * @brief Generates Footer for each page
	 *
	 * This method draws a 1px line across the entire page 15mm above the bottom
	 * and writes the page number in the format "current page/total pages" into the bottom right corner.
	 *
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit b9342d94 on 2014-06-30
	 *       
	 * @see TCPDF::Footer()
	 */
	public function Footer() {
		$this->setFont ( 'times', '', 12 );
		$this->setY ( - 15 );
		$this->Line ( $this->getMargins ()['left'], $this->GetY (), $this->getPageDimensions ()['wk'] - $this->getMargins ()['right'], $this->GetY () );
		$this->Cell ( 0, 0, $this->getAliasRightShift () . $this->getAliasNumPage () . '/' . $this->getAliasNbPages (), 0, 0, 'R' );
	}
	
	/**
	 * @brief Gets index of the desired level
	 *
	 * This utility method manages the @ref $aIndices array and determines
	 * what heading index comes next in the given @p $iLevel (e.g. 1, 2, 3 etc.).
	 *
	 * If some levels have been skipped (e.g. the first call to this method
	 * is with <code>$iLevel = 3</code>), it fills up the missing array values with 1.
	 *
	 * After it has determined the correct index, it increments the corresponding value in the array by 1.
	 *
	 * @param integer $iLevel
	 *        	Level for which the index is wanted
	 * @return The next index on the given level. One single integer (i.e. not "1.1.2")
	 *        
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 *        
	 * @since Commit b9342d94 on 2014-06-30
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
	 * @brief Determines height of HTML Code
	 *
	 * This utility method prints @e $sHTML to a test instance of @ref overviewPDF and determines what height the HTML code has if printed.
	 *
	 * This method is needed for evaluating whether an HTML table would fit into the remaining space on the page or if it would be broken into two pages.
	 *
	 * @return The height of the HTML code
	 *        
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit b9342d94 on 2014-06-30
	 *       
	 * @see printFach()
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
	 * @brief Gets the full y-position in the document
	 *
	 * This utility method determines the current y position
	 * in relation to the beginning of the document, not just
	 * the current page (like @link TCPDF::GetY() GetY()@endlink),
	 * excluding top and bottom margins.
	 *
	 * This method is needed to compare positions in the document across pages.
	 *
	 * @return Full y-position in the entire document (not just on this page), excluding margins
	 *        
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit b9342d94 on 2014-06-30
	 *       
	 * @see getHTMLHeight()
	 * @see TCPDF::GetY()
	 */
	private function getTotalY() {
		return ($this->PageNo () - 1) * ($this->getPageHeight () - $this->getMargins ()['top'] - $this->getMargins ()['bottom']) + $this->GetY ();
	}
	
	/**
	 * @brief Creates the PDF document
	 *
	 * This method is the main public method of the @ref overviewPDF class.
	 * It manages the entire document generation and saves the PDF to the preconfigured path.
	 * The steps for creating the document are as follows:
	 * 1. Set up PDF document
	 * 2. Set document meta information
	 * 3. Set up title page
	 * 4. Initiate printing of individual subjects
	 * 5. Add index page
	 * 6. Save the document
	 *
	 * @param integer $iVID
	 *        	Drupal vocabulary id of the desired Curriculum
	 * @return Success message: 'PDF successfully created at ' and the filepath
	 *        
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit b9342d94 on 2014-06-30
	 *       
	 * @see stukowin_pdf_menu()
	 * @see stukowin_pdf_menu_submit()
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
		$this->SetKeywords ( 'Curriculum, Wirtschaftsinformatik' );
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
		$sFilename = $this->getUniqueFileName ( $oCurriculum ['type'], $oCurriculum ['version'] );
		$this->Output ( $sFilename, 'F' );
		return 'PDF successfully created at ' . $sFilename;
	}
	
	/**
	 * @brief Throws exception in case of an error
	 *
	 * This method is needed for displaying errors as drupal messages,
	 * due to TCPDF normally displaying errors by itself and the dying.
	 *
	 * @param string $msg
	 *        	The error message to throw the ecxeption with
	 * @throws Exception A new exception with the given message
	 *        
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit e4fa523c on 2014-07-02
	 *       
	 * @see TCPDF::Error()
	 */
	public function Error($msg) {
		throw new Exception ( $msg );
	}
	
	/**
	 * @brief Adds a table of contents page to the PDF document
	 *
	 * This method is called at the end of the document creation,
	 * after all subjects have been printed to the document,
	 * and inserts a table of contents (index) page as the second page in the document.
	 *
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit b9342d94 on 2014-06-30
	 *       
	 * @see TCPDF::addTOCPage()
	 * @see printHeading()
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
	 * @brief Creates a unique filename
	 *
	 * Determines if a file with the standard filename as defined in the module settings already exists.
	 * If one exists, it appends a number and increases it until the name is not already taken.
	 *
	 * Also, it creates the directory into which the document
	 * should be saved according to the module settings (if it does not already exist).
	 *
	 * @param string $sCurrType
	 *        	The type of the curriculum (Bachelorstudium, Masterstudium)
	 * @param string $sCurrVersion
	 *        	The version of the curriculum (e.g. 2013W)
	 * @return The unique filename
	 *        
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit e8704fdc45 on 2014-06-30
	 *       
	 * @see stukowin_admin()
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
		$sCoreName = overviewPDF::convertStringToValidFilename ( $sCoreName );
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
	 * @brief Prints a curriculum object and all its courses to the PDF document
	 *
	 * This method does the following things:
	 * 1. Print the curriculum name as a heading
	 * 2. Create an overview table with all the subjects it contains
	 * 3. Print the details of each subject to the document
	 *
	 * @param object $oCurriculum
	 *        	The curriculum object to print
	 *        	
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit b9342d94 on 2014-06-30
	 *       
	 * @todo Overview table with structural elements
	 */
	private function printCurriculum($oCurriculum) {
		$this->AddPage ();
		$aCourses = $this->getCourses ( $oCurriculum ['vid'] );
		$this->SetFontSize ( 20 );
		$this->printHeading ( strtoupper ( $oCurriculum ['type'] ), 0, false, 'C' );
		$this->SetFont ( 'times', '', 12 );
		// Print overview table
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
			else {
				$sHTML .= '<tr nobr="true"><td><I>' . $oCourse->name . '</I></td><td align="center"></td></tr>';
				foreach ( $oCourse->children as $oChild ) {
					$sHTML .= '<tr nobr="true"><td>' . $oCourse->lva->title . '</td><td align="center">' . $oCourse->lva->ects . '</td></tr>';
				}
			}
		}
		$sHTML .= '</table>';
		$this->writeHTML ( $this->unhtmlentities ( $sHTML ) );
		// Print details of each course
		foreach ( $aCourses as $oCourse ) {
			$this->printTopLevel ( $oCourse );
		}
	}
	
	/**
	 * @brief Decides whether to print a structural element or a subject
	 *
	 * This is a dispatcher method that checks if an object is a structural element (1. Semester, 2. Semester etc.) or a course.
	 * If calls @ref printHeading() if it is a structural element and then
	 * @ref printFach() for all its children or just @ref printFach() if it is a course object.
	 *
	 * @param object $oTopLevel
	 *        	The object to check
	 *        	
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit a311596e on 2014-07-02
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
	 * @brief Prints a subject to the PDF document
	 *
	 * This method prints the subject title and details, an overview of all its subcourses and their details to the document.
	 *
	 * @param object $oFach
	 *        	The course object to print
	 *        	
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit b9342d94 on 2014-06-30
	 */
	private function printFach($oFach) {
		if (! property_exists ( $oFach, 'children' )) {
			$this->printHeading ( $oFach->lva->title, 1, true );
			return;
		}
		// Print overview table
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
		// Print details of each subcourse
		foreach ( $aChildren as $oChild )
			if (($oChild->lva->ziele || $oChild->lva->lehrinhalte) && $oChild->lva->lvatype && $oChild->lva->lvatype != '3') {
				$this->printHeading ( $oChild->lva->typename . ' ' . $oChild->lva->title, 2, true, 'L', false );
				$this->printZieleInhalte ( $oChild );
			}
		$this->Ln ();
	}
	
	/**
	 * @brief Recursive function for generating overview table
	 *
	 * This is a recursive helper function for generating a subject overview table.
	 * It traverses the nested array of courses down to the leaves and creates a table row for each course.
	 * - Subjects are printed in bold
	 * - Modules are printed in italics
	 * - Courses are printed in normal text
	 *
	 * @param object $oCourse
	 *        	The course to generate the table HTML code for
	 * @return The HTML code of the table rows for this course and all its children
	 *        
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit b9342d94 on 2014-06-30
	 *       
	 * @see printFach()
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
	 * @brief Inserts line breaks into bold text
	 *
	 * This method breaks the @e $sText into separate lines which are shorter than @e $iMaxWidth if printed as bold text.
	 * If one word alone is too long or the entire string is shorter than @e $iMaxWidth, it is not splitted.
	 *
	 * This method is needed in @ref generateTableRecHelper() because @ref TCPDF does not break the words correctly in a table if the text is written in a @<B@> tag.
	 * This caused issues where text would overflow its table cell to the right by a few cm.
	 *
	 * @param string $sText
	 *        	The text to split
	 * @param $iMaxWidth The
	 *        	The maximum width a line is allowed to have
	 * @return The @e $sText with @<br@> tags inserted whenever necessary
	 *        
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit 7e5b5459 on 2014-07-06
	 */
	private function splitBoldTextIntoLines($sText, $iMaxWidth) {
		$aWords = preg_split ( '/\s+/', $sText );
		if ($this->getStringWidth ( $sText, '', 'B' ) < $iMaxWidth)
			return $sText;
		return $this->splitRecHelper ( $aWords, 0, $iMaxWidth );
	}
	
	/**
	 * @brief Recursive helper function for @ref splitBoldTextIntoLines()
	 *
	 * @param array $aWords
	 *        	The array of words as split in @ref splitBoldTextIntoLines()
	 * @param integer $iCurrIndex
	 *        	The current index in the array
	 * @param integer $iMaxWidth
	 *        	The maximum width a line is allowed to have
	 *        	
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit 7e5b5459 on 2014-07-06
	 *       
	 * @see splitBoldTextIntoLines()
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
	 * @brief Prints the course goals and content of teaching and their respecitve headings to the document.
	 *
	 * If one of them is not set or empty, their heading is not printed either.
	 *
	 * @param object $oCourse
	 *        	The course to print the goals and contents for
	 *        	
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit b9342d94 on 2014-06-30
	 *       
	 * @see printFach()
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
	 * @brief Prints a heading to the PDF document
	 *
	 * This method manages headings in the document. It automatically indexes the headings using @ref getNextIndex() and optionally adds a bookmark for it.
	 *
	 * @param string $sText
	 *        	The heading text
	 * @param int $iLevel
	 *        	The level at which the heading should be created and the bookmark set
	 * @param boolean $bShowIndex
	 *        	@c true if the index should be shown in the heading
	 * @param string $sAlign
	 *        	Alignment of the heading. For allowed values see @ref TCPDF::MultiCell()
	 * @param boolean $bAddBookmark
	 *        	@c true if heading should be shown on the index page and bookmarked
	 *        	
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit b9342d94 on 2014-06-30
	 *       
	 * @see createTOCPage()
	 * @see getNextIndex()
	 */
	private function printHeading($sText, $iLevel, $bShowIndex = true, $sAlign = 'L', $bAddBookmark = true) {
		$iIndex = $this->getNextIndex ( $iLevel );
		$sText = $this->unhtmlentities ( $sText );
		switch ($iLevel) {
			// Curriculum title
			case 0 :
				$this->SetFont ( 'times', 'B', 20 );
				break;
			// Subject title
			case 1 :
				$this->SetFont ( 'times', 'B', 14 );
				break;
			// Module title
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
	 * @brief Gets all courses for a given curriculum id
	 *
	 * This method gets all the courses in a curriculum and prepares them for further processing in the PDF creation process.
	 *
	 * @param integer $currId
	 *        	The vocabulary id of the curriculum to get the courses from
	 * @return The nested array of all courses in the given curriculum
	 *        
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit b9342d94 on 2014-06-30
	 */
	private static function getCourses($currId) {
		$aCourses = (new content_manager ())->taxonomy_get_nested_tree ( $currId );
		foreach ( $aCourses as $oCourse ) {
			overviewPDF::assertAttributes ( $oCourse );
		}
		return $aCourses;
	}
	
	/**
	 * @brief Guarantees the existence of the specified attributes in the course and all its children.
	 *
	 * This is a recursive helper method which asserts that the following attributes exist in the course object:
	 * - title
	 * - ects
	 * - wst
	 * - lvatype
	 * - typename
	 * - ziele
	 * - lehrinhalte
	 * - lvtypshort
	 * - typename
	 *
	 * Needed so that the other methods do not have to check everytime an attribute is accessed.
	 *
	 * @param object $oCourse
	 *        	The course to assert the attributes for
	 *        	
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit b9342d94 on 2014-06-30
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
	 * @brief Utility method that formats a string into a valid file name
	 *
	 * @param string $sString
	 *        	The file name to validate
	 * @return The valid file name created from the input
	 *        
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit f157d512 on 2014-07-06
	 */
	private static function convertStringToValidFilename($sString) {
		if (strpos ( $sString = htmlentities ( $sString, ENT_QUOTES, 'UTF-8' ), '&' ) !== false) {
			$sString = html_entity_decode ( preg_replace ( '~&([a-z]{1,2})(?:acute|caron|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i', '$1', $sString ), ENT_QUOTES, 'UTF-8' );
		}
		return $sString;
	}
}
