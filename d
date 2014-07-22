[33mtag v1.0.2[m
Tagger: TheJake123 <zebra_streif@hotmail.com>
Date:   Tue Jul 22 19:49:10 2014 +0200

v1.0.2

[33mcommit 14ebcb8b852d0e05418f3434cbc3ef3dd7bd50a1[m
Author: TheJake123 <zebra_streif@hotmail.com>
Date:   Tue Jul 22 19:43:34 2014 +0200

    -Finished documentation
    -Added creation date to the footer of PDF documents
    -Repackaged module with minified js

[1mdiff --git a/Mainpage.dox b/Mainpage.dox[m
[1mindex 449227a..076eade 100644[m
[1m--- a/Mainpage.dox[m
[1m+++ b/Mainpage.dox[m
[36m@@ -21,7 +21,7 @@[m
 		-# @ref plugin[m
 		-# @ref graph[m
 	-# @ref Other[m
[31m--# Development[m
[32m+[m[32m-# @ref Development[m
 	-# @ref Authors[m
 	-# @ref versionnumbers[m
 	-# @ref versioncontrol[m
[36m@@ -171,15 +171,15 @@[m [mThe import of data from CEUS to the Drupal environment is implemented in this co[m
 The data model for the Drupal-internal representation of courses is defined in the module's @ref stukowin.install "install component", the necessary database tables are automatically created during the installation.[m
 Because the API URL and user credentials are prone to change, they need to be configurable. This is achieved in the @ref stukowin_admin() "admin menu":[m
 [m
[31m-@image html "Module Configuration.png" "Module Configuration Page"[m
[31m-@image latex "Module Configuration.eps" "Module Configuration Page"[m
[32m+[m[32m@image html "ModuleConfiguration.png" "Module Configuration Page"[m
[32m+[m[32m@image latex "ModuleConfiguration.eps" "Module Configuration Page" width=\textwidth[m
 [m
 @note Administrator rights are needed to configure the login details[m
 [m
[31m-The communication between this module and CEUS occurs as shown in the sequence diagram below:[m
[32m+[m[32m@htmlonly The communication between this module and CEUS occurs as shown in the sequence diagram below: @endhtmlonly[m
 [m
[31m-@image html "Sequence Diagram Import.svg" "Communication with CEUS"[m
[31m-@image latex "Sequence Diagram Import.eps" "Communication with CEUS"[m
[32m+[m[32m@image html "SequenceDiagramImport.svg" "Communication with CEUS"[m
[32m+[m[32m@latexonly The communication between this module and CEUS occurs as shown in the included \href[pdfnewwindow]{./SequenceDiagramImport.pdf}{SequenceDiagramImport.pdf} file. @endlatexonly[m
 [m
 The import from CEUS is performed roughly in the following steps:[m
 -# The @c auth token is requested from the CEUS API[m
[36m@@ -189,10 +189,8 @@[m [mThe import from CEUS is performed roughly in the following steps:[m
 -# During the first import, new Drupal vocabularies, vocabulary terms and content nodes are created[m
 -# We try our best to parse the field @c voraussetzungen and extract relations between courses (recommended/required)[m
 [m
[31m-This process is shown in the following flow chart, with the corresponding methods in @ref ceus_importer that perform each step:[m
[31m-[m
[31m-@image html "Flowchart CEUS2Drupal.svg" "Import Process"[m
[31m-@image latex "Flowchart CEUS2Drupal.eps" "Import Process"[m
[32m+[m[32m@htmlonly This process is shown in the following flow chart@endhtmlonly@latexonly This process is shown in the included \href[pdfnewwindow]{./FlowchartCEUS2Drupal.pdf}{FlowchartCEUS2Drupal.pdf} file@endlatexonly, with the corresponding methods in @ref ceus_importer that perform each step.[m
[32m+[m[32m@image html "FlowchartCEUS2Drupal.svg" "Import Process"[m
 [m
 Here is a quick textual description of the process:[m
 [m
[36m@@ -221,16 +219,16 @@[m [mThe functional requirements of this project make it necessary to periodically ex[m
 and giving administrators and moderators the ability to overwrite data on the other hand.[m
 @remark How to overwrite course data is described in the user documentation.[m
 [m
[31m-This creates the challenge of properly versioning the CMS content, which is why every import follows these rules:[m
[32m+[m[32m@htmlonly This creates the challenge of properly versioning the CMS content, which is why every import follows these rules: @endhtmlonly[m
 [m
 @image html "Changemanagement.svg" "Change Management"[m
[31m-@image latex "Changemanagement.eps" "Change Management"[m
[32m+[m[32m@latexonly This creates the challenge of properly versioning the CMS content, which is why every import follows the rules shown in the included \href[pdfnewwindow]{./Changemanagement.pdf}{Changemanagement.pdf} file @endlatexonly[m
 [m
 Every time a content node is created or updated, it gets a red @e New tag in the content overview (see image below), through which the administrator can easily see which nodes have been updated.[m
 In addition to this, the import returns a success message that tells the administrator how many nodes have been created or updated, so that one can easily tell if changes have occurred.[m
 [m
[31m-@image html "New Tag.png" "Red @e New Tag"[m
[31m-@image latex "New Tag.eps" "Red @e New Tag"[m
[32m+[m[32m@image html "NewTag.png" "Red @e New Tag"[m
[32m+[m[32m@image latex "NewTag.eps" "Red @e New Tag" width=\textwidth[m
 [m
 @subsection Drupal2ITSV[m
 As CEUS does not provide any information about fields of specialisation during the master studies and ideal courses of studies, henceforth called ITSV due to its German name,[m
[36m@@ -254,10 +252,10 @@[m [mFor this component to work properly, the necessary settings have to be made in a[m
 The administrator is provided with a new menu (at admin/settings/stukowin/pdf) where the curriculum to be archived can be selected. Once he clicks on "Create PDF", the PDF generation in overviewPDF::createPDF() is started.[m
 (All of the PDF generation code is contained in the @ref pdf_creator.inc.php file)[m
 [m
[31m-The PDF generation process will flow as follows:[m
[32m+[m[32m@htmlonly The PDF generation process will flow as follows: @endhtmlonly[m
 [m
[31m-@image html "Flowchart Drupal2PDF.svg" "PDF Creation"[m
[31m-@image latex "Flowchart Drupal2PDF.eps" "PDF Creation"[m
[32m+[m[32m@image html "FlowchartDrupal2PDF.svg" "PDF Creation"[m
[32m+[m[32m@latexonly The PDF generation process will flow as shown in the included \href[pdfnewwindow]{./FlowchartDrupal2PDF.pdf}{FlowchartDrupal2PDF.pdf} file. @endlatexonly[m
 [m
 @see Drupal2PDF[m
 [m
[36m@@ -295,7 +293,7 @@[m [mIt also registers click handlers for things such as expanding/reducing a course,[m
 As an example, the output could look like this:[m
 [m
 @image html "Drupal2AGG.png" "Graphical Representation"[m
[31m-@image latex "Drupal2AGG.eps" "Graphical Representation"[m
[32m+[m[32m@image latex "Drupal2AGG.eps" "Graphical Representation" width=\textwidth[m
 [m
 @see Drupal2AGG[m
 [m
[1mdiff --git a/ceus_importer.inc.php b/ceus_importer.inc.php[m
[1mindex 3527880..1a6ba8e 100644[m
[1m--- a/ceus_importer.inc.php[m
[1m+++ b/ceus_importer.inc.php[m
[36m@@ -4,7 +4,9 @@[m
  * @brief Module for importing data from CEUS[m
  * [m
  * This module is reponsible for requesting the data from the CEUS API and storing it in the drupal database.[m
[31m- * It also implements the functions for the change management[m
[32m+[m[32m * It also implements the functions for the change management.[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * For a more detailed description see @ref CEUS2Drupal[m
  * [m
  * @author Konstantinos Dafalias - kdafalias@gmail.com[m
  * @authors Jakob Strasser - jakob.strasser@telenet.be[m
[1mdiff --git a/js/graph.js b/js/graph.js[m
[1mindex ed96e83..30b9a9d 100644[m
[1m--- a/js/graph.js[m
[1m+++ b/js/graph.js[m
[36m@@ -6,6 +6,37 @@[m
  * the functionality for automatically generating a visual representation of the[m
  * imported curricula data.[m
  * [m
[32m+[m[32m * @section aggjson Drupal JSON Interface[m
[32m+[m[32m * To make the curricula data available to clients, several JSON interfaces have been implemented that make curricula publicly reachable.[m
[32m+[m[32m * This is needed so that the client browser can properly display the curricula.[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * The following interfaces are available:[m
[32m+[m[32m * Name            | Path              | Input Parameter                                                                                                                                                         | Description[m
[32m+[m[32m * --------------- | ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------[m
[32m+[m[32m * Curriculum List | stukowin/crclmlst | "currtype" : Type of curriculum to get ("Bachelorstudium" or "Masterstudium")<br>"taxtypes" : Types of vocabularies to get ("curriculum", "itsv" and/or "specialisation") | Returns a list of all curricula currently published (weight < 0), like @ref list[m
[32m+[m[32m * Curriculum Tree | stukowin/crclm    | Vocabulary id of the curriculum to get                                                                                                                                  | Returns the curriculum tree of one curriculum, containing all courses and their details[m
[32m+[m[32m * Single course   | stukowin/lva      | Node id of the course to get                                                                                                                                            | Returns the details of a single course[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * All of these interfaces are defined in the @ref stukowin.module file.[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * @section aggplugin CKEditor Plug-in[m
[32m+[m[32m * As the dynamic display of curricula in the browser requires a strict HTML document structure, we refrained from letting the administrator insert the code himself wherever a curriculum display was needed[m
[32m+[m[32m * and instead implemented a plug-in for the CKEditor (<https://www.drupal.org/project/ckeditor>) which inserts the code automatically on the click of a button.[m
[32m+[m[32m * The plug-in is registered with drupal in the @ref stukowin_ckeditor_plugin() method. It is then defined in the @ref plugin.js file[m
[32m+[m[32m * and the dialog for inserting a curriculum view is defined in the @ref stukowin_curriculum.js file.[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * @note In order for this plug-in to work properly, some settings have to be made in the CKEditor. This is described in the operator manual.[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * @section agggraph Graph.js[m
[32m+[m[32m * The main task of displaying the curricula in the client's browser is performed by this file. It fetches the data from the JSON interfaces described @ref json "above" and creates the DOM elements in the browser.[m
[32m+[m[32m * It also registers click handlers for things such as expanding/reducing a course, showing prerequisites and selecting a different curriculum.[m
[32m+[m[32m * @note The display layout is configured in the curriculum_stlye.css file (not included in this documentation)[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * As an example, the output could look like this:[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * @image html "Drupal2AGG.png" "Graphical Representation"[m
[32m+[m[32m * @image latex "Drupal2AGG.eps" "Graphical Representation" width=\textwidth[m
[32m+[m[32m *[m[41m [m
  * @authors Jakob Strasser - jakob.strasser@telenet.be[m
  * @authors Markus Gutmayer - m.gutmayer@gmail.com[m
  * @authors Werner Breuer - bluescreenwerner@gmail.com[m
[36m@@ -19,12 +50,21 @@[m
  * [m
  * This script is responsible for creating the proper html/css/js needed for[m
  * displaying the graphical representation of CEUS data.[m
[32m+[m[32m *[m[41m [m
  * In order for this script to work, a @<div@> with the id "main" and the tags[m
  * data-currtype and data-curriculums set needs to be present on the page.[m
[32m+[m[32m * Example:[m
[32m+[m[32m * @code {.html}[m
[32m+[m[32m * <div id="main" data-currtype="Bachelorstudium" data-curriculums="curriculum itsv specialisation">[m
[32m+[m[32m * </div>[m
[32m+[m[32m * @endcode[m
  * [m
[31m- * @image html "Studienplan Voraussetzung.png" "The graphical representation[m
[31m- *        will look approximately like this"[m
  * [m
[32m+[m[32m * The graphical representation will look approximately like this:[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * @image html "StudienplanVoraussetzung.png" "Graphical Display"[m
[32m+[m[32m * @image latex "StudienplanVoraussetzung.eps" "Graphical Display" width=\textwidth[m
[32m+[m
  * @author Jakob Strasser - jakob.strasser@telenet.be[m
  * @authors Markus Gutmayer - m.gutmayer@gmail.com[m
  * @authors Werner Breuer - bluescreenwerner@gmail.com[m
[1mdiff --git a/pdf_creator.inc.php b/pdf_creator.inc.php[m
[1mindex 31e0259..71641db 100644[m
[1m--- a/pdf_creator.inc.php[m
[1m+++ b/pdf_creator.inc.php[m
[36m@@ -7,6 +7,18 @@[m
  * the functionality for automatically generating PDF documents from the[m
  * imported curricula data.[m
  * [m
[32m+[m[32m * --------------------------------------------[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * In order to permanently and easily archive past curricula there is the option to automatically create a course overview PDF document from the imported courses.[m[41m [m
[32m+[m[32m * For this component to work properly, the necessary settings have to be made in advance (see module configuration image above).[m
[32m+[m[32m * The administrator is provided with a new menu (at admin/settings/stukowin/pdf) where the curriculum to be archived can be selected. Once he clicks on "Create PDF", the PDF generation in overviewPDF::createPDF() is started.[m
[32m+[m[32m * (All of the PDF generation code is contained in the @ref pdf_creator.inc.php file)[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * @htmlonly The PDF generation process will flow as follows: @endhtmlonly[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * @image html "FlowchartDrupal2PDF.svg" "PDF Creation"[m
[32m+[m[32m * @latexonly The PDF generation process will flow as shown in the included \href[pdfnewwindow]{./FlowchartDrupal2PDF.pdf}{FlowchartDrupal2PDF.pdf} file. @endlatexonly[m
[32m+[m[32m *[m[41m [m
  * @author Jakob Strasser - jakob.strasser@telenet.be[m
  * @authors Fabian Puehringer - f.puehringer@24speed.at[m
  */[m
[36m@@ -22,7 +34,7 @@[m
  *[m
  * @author Jakob Strasser - jakob.strasser@telenet.be[m
  * @authors Fabian Puehringer - f.puehringer@24speed.at[m
[31m- * @version 1.0.0 2014-07-15[m
[32m+[m[32m * @version 1.0.2 2014-07-22[m
  * @since Commit b9342d941b3f93e212f3f6af0823a07524dd5954 on 2014-06-30[m
  *       [m
  * @see overviewPDF[m
[36m@@ -42,11 +54,10 @@[m [minclude_once dirname ( __FILE__ ) . '/content_manager.inc.php';[m
  *[m
  * @author Jakob Strasser - jakob.strasser@telenet.be[m
  * @authors Fabian Puehringer - f.puehringer@24speed.at[m
[31m- * @version 1.0.0[m
[32m+[m[32m * @version 1.0.2 2014-07-22[m
  * @since Commit b9342d941b3f93e212f3f6af0823a07524dd5954 on 2014-06-30[m
  *       [m
  * @see createPDF()[m
[31m- * @todo Add creation date and time to footer[m
  */[m
 class overviewPDF extends TCPDF {[m
 	/**[m
[36m@@ -76,6 +87,7 @@[m [mclass overviewPDF extends TCPDF {[m
 		$this->setFont ( 'times', '', 12 );[m
 		$this->setY ( - 15 );[m
 		$this->Line ( $this->getMargins ()['left'], $this->GetY (), $this->getPageDimensions ()['wk'] - $this->getMargins ()['right'], $this->GetY () );[m
[32m+[m		[32m$this->Cell ( 0, 0, date('Y-m-d H:i:s'),0,0,'C');[m
 		$this->Cell ( 0, 0, $this->getAliasRightShift () . $this->getAliasNumPage () . '/' . $this->getAliasNbPages (), 0, 0, 'R' );[m
 	}[m
 	[m
[36m@@ -140,7 +152,7 @@[m [mclass overviewPDF extends TCPDF {[m
 	 *[m
 	 * This utility method determines the current y position[m
 	 * in relation to the beginning of the document, not just[m
[31m-	 * the current page (like @link TCPDF::GetY() GetY()@endlink),[m
[32m+[m	[32m * the current page (like TCPDF::GetY()),[m
 	 * excluding top and bottom margins.[m
 	 *[m
 	 * This method is needed to compare positions in the document across pages.[m
[36m@@ -312,8 +324,6 @@[m [mclass overviewPDF extends TCPDF {[m
 	 *        	[m
 	 * @author Jakob Strasser - jakob.strasser@telenet.be[m
 	 * @since Commit b9342d941b3f93e212f3f6af0823a07524dd5954 on 2014-06-30[m
[31m-	 *       [m
[31m-	 * @todo Overview table with structural elements[m
 	 */[m
 	private function printCurriculum($oCurriculum) {[m
 		$this->AddPage ();[m
[1mdiff --git a/stukowin-1.0.2.zip b/stukowin-1.0.2.zip[m
[1mnew file mode 100644[m
[1mindex 0000000..9328d18[m
Binary files /dev/null and b/stukowin-1.0.2.zip differ
[1mdiff --git a/stukowin.module b/stukowin.module[m
[1mindex 56ff981..2a29075 100644[m
[1m--- a/stukowin.module[m
[1m+++ b/stukowin.module[m
[36m@@ -4,7 +4,8 @@[m
  * @brief Module that contains core functionality[m
  * [m
  * This module contains all files, classes and methods that provide [m
[31m- * the core functionality of the Drupal module.[m
[32m+[m[32m * the core functionality of the Drupal module. The members of this group[m
[32m+[m[32m * ensure a working cooperation between all of the components and Drupal.[m
  * [m
  * @authors Konstantinos Dafalias - kdafalias@gmail.com[m
  * @authors Jakob Strasser - jakob.strasser@telenet.be[m
[36m@@ -20,6 +21,21 @@[m
  * the functionality for supporting the administrator when creating new Drupal vocabularies[m
  * that represent either an ITSV ("Idealtypischer Studienverlauf") or a specialisation (mainly for Master curricula).[m
  * [m
[32m+[m[32m * ----------------------------------------------[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * As CEUS does not provide any information about fields of specialisation during the master studies and ideal courses of studies, henceforth called ITSV due to its German name,[m
[32m+[m[32m * it was a project requirement that new curricula can be created by the administrator for such purposes.[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * A freely available Drupal module called Taxonomy Manager (<https://www.drupal.org/project/taxonomy_manager>) gives the administrator the ability to copy vocabulary terms from one vocabulary to another, which is most of the work.[m
[32m+[m[32m * Unfortunately, this process cannot be simplified any further. Nevertheless, we tried to at least automate the task of creating a new vocabulary, copying all of the information over from the source curriculum[m
[32m+[m[32m * and creating top-level terms (such as "1. Semester" etc.), tasks which will be performed every time a new ITSV or specialisation has to be created.[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * This component handles exactly that. It inserts a new menu item (at admin/settings/stukowin/taxonomy) where the administrator can select a source curriculum to base the new one on, select whether to create an ITSV or specialisation vocabulary,[m
[32m+[m[32m * enter a name and choose how many top-level terms should be inserted. Once the administrator has filled out the form, the new vocabulary is automatically created and the browser is redirected to the Taxonomy Manager's "Dual View",[m
[32m+[m[32m * where the administrator can begin copying courses into the new vocabulary.[m
[32m+[m[32m *[m[41m [m
[32m+[m[32m * @remark This component does not have its own file as it does not contain a lot of code. All of its functionality is in the @ref stukowin.module file.[m
[32m+[m[32m *[m[41m [m
  * @author Jakob Strasser - jakob.strasser@telenet.be[m
  * @authors Werner Breuer - bluescreenwerner@gmail.com[m
  * @authors Markus Gutmayer - m.gutmayer@gmail.com[m
[1mdiff --git a/stukowin.zip b/stukowin.zip[m
[1mdeleted file mode 100644[m
[1mindex b8d9cd8..0000000[m
Binary files a/stukowin.zip and /dev/null differ
