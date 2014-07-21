<?php
/**
 * @defgroup CEUS2Drupal CEUS2Drupal
 * @brief Module for importing data from CEUS
 * 
 * This module is reponsible for requesting the data from the CEUS API and storing it in the drupal database.
 * It also implements the functions for the change management
 * 
 * @author Konstantinos Dafalias - kdafalias@gmail.com
 * @authors Jakob Strasser - jakob.strasser@telenet.be
 * @authors Markus Gutmayer - m.gutmayer@gmail.com
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 */

/**
 * @ingroup CEUS2Drupal
 * @file
 * @brief Imports data from CEUS
 *
 * This file manages the import of curricula data from CEUS.
 *
 * @author Konstantinos Dafalias - kdafalias@gmail.com
 * @authors Jakob Strasser - jakob.strasser@telenet.be
 * @authors Markus Gutmayer - m.gutmayer@gmail.com
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @version 1.0.0 2014-07-16
 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
 */
include_once __DIR__ . '/include/simple_html_dom.php';
include_once dirname ( __FILE__ ) . '/stukowin.install';
include_once dirname ( __FILE__ ) . '/content_manager.inc.php';

/**
 * @ingroup CEUS2Drupal
 * @brief Imports data from CEUS and stores it in the Drupal database
 *
 * This class is used to import data from the CEUS-API and saving them in the Drupal database. It also provides the change management functionality described in the system documentation.
 *
 * @author Konstantinos Dafalias - kdafalias@gmail.com
 * @authors Jakob Strasser - jakob.strasser@telenet.be
 * @authors Markus Gutmayer - m.gutmayer@gmail.com
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @version 1.0.0 2014-07-16
 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
 *       
 */
class ceus_importer {
	/**
	 * @brief Complete URL to CEUS API
	 *
	 * This variable contains the URL to the CEUS API as retreived from the module configuration.
	 *
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private $sCeusUrl;
	
	/**
	 * @brief Username for CEUS API
	 *
	 * This variable contains the username for the CEUS API as retreived from the module configuration.
	 *
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private $sUsername;
	
	/**
	 * @brief Password for CEUS API
	 *
	 * This variable contains the password for the CEUS API as retreived from the module configuration.
	 *
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private $sPassword;
	
	/**
	 * @brief CEUS authentication token
	 *
	 * This variable contains the authentication token as retreived from the CEUS API by calling its @c auth method.
	 *
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private $sAuthtoken;
	
	/**
	 * @brief Names of the API methods
	 *
	 * This array contains all the API method names for:
	 * + Getting an authorization token
	 * + Listing all curricula
	 * + Getting one curriculum tree
	 * + Getting one curriculum item
	 *
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private $aFiles = array (
			'AUTH' => 'auth.php',
			'LIST' => 'list.php',
			'CURR' => 'curr.php',
			'DETAIL' => 'detail.php' 
	);
	
	/**
	 * @brief Error message
	 *
	 * If an error has occurred, this variable contains the error message.
	 * 
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private $sError;
	
	/**
	 * @brief All supported languages
	 *
	 * This array contains the short names of all languages that should be imported.
	 *
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private $aLanguage = array (
			'de',
			'en' 
	);
	
	/**
	 * @brief All vocabularies for current curricula
	 *
	 * This array contains the respective Drupal vocabularies corresponding to the currently imported curricula.
	 *
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private $aVocabulary;
	
	/**
	 * @brief All Terms for vocabularies
	 *
	 * This array contains all the Drupal vocabulary terms associated with the curriculum currently being processed.
	 *
	 * The item key represents the CEUS id of one course.
	 *
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private $aTerms;
	
	/**
	 * @brief Import statistics
	 *
	 * This array contains all necessary import statistics, namely:
	 * + The number of courses loaded from CEUS
	 * + The number of new content nodes created (= new courses)
	 * + The number of content nodes that were updated during the import (= changes in CEUS)
	 * + The number of curricula loaded from CEUS
	 * + the number of relations automatically processed
	 *
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit 5ad002c8e7a233bc25ac0e1dcf2b9f62520281c1 on 2014-07-08
	 */
	private $aStats = array (
			'loaded' => 0,
			'new' => 0,
			'updated' => 0,
			'numcurrs' => 0,
			'relations' => 0 
	);
	
	/**
	 * @brief Relations of every course (if available)
	 *
	 * This array contains all relations (recommended and needed) in their raw textual form
	 * It is filled during the import and evaluated at the end.
	 *
	 * The key is the CEUS id of the cours, the value contains the content text of the @c voraussetzungen field from CEUS.
	 *
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private $aRelations;
	
	/**
	 * @brief Constructor
	 *
	 * Creates a new instance of @ref ceus_importer and reads the CEUS API configuration data from Drupal.
	 *
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 *       
	 * @see $sCeusUrl
	 * @see $sUsername
	 * @see $sPassword
	 */
	public function __construct() {
		$this->sCeusUrl = variable_get ( 'stukowin_ceus_api_url' );
		$this->sUsername = variable_get ( 'stukowin_ceus_api_username' );
		$this->sPassword = variable_get ( 'stukowin_ceus_api_userpassword' );
	}
	
	/**
	 * @brief Checks the JSON returned by the CEUS API
	 *
	 * This method checks if the CEUS API server responded and if there was an error.
	 *
	 * @param string $sReturn
	 *        	JSON encoded string fetched from the CEUS API
	 *        	@retval "decoded array" Success
	 *        	@retval false An error occurred. The error is stored in the @ref $sError member
	 *        	
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private function check_return_value($sReturn) {
		if (empty ( $sReturn )) {
			$this->sError = 'Connection to server failed';
			return false;
		}
		$aReturn = drupal_json_decode ( $sReturn );
		if (! empty ( $aReturn ['error'] )) {
			$this->sError = $aReturn ['error'];
			return false;
		}
		return $aReturn;
	}
	
	/**
	 * @brief Connect to CEUS and receive Authtoken
	 *
	 * This function tries to connect to the CEUS-API server and receives and stores the authtoken.
	 * Returns true if an authtoken was recieved otherwise it returns false.
	 *
	 * @retval true Connection and authentication @b successful
	 * @retval false Connection and/or authentication @b failed
	 *
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	public function connect() {
		$sReturn = file_get_contents ( $this->sCeusUrl . '/' . $this->aFiles ['AUTH'] . '?username=' . $this->sUsername . '&password=' . $this->sPassword );
		if ($aReturn = $this->check_return_value ( $sReturn )) {
			$this->sAuthtoken = $aReturn ['authtoken'];
			return true;
		} else
			return false;
	}
	
	/**
	 * @brief Fetches a single course from CEUS
	 *
	 * This function fetches a single course from the CEUS API and returns all its details in an array.
	 *
	 * @param integer $iID
	 *        	CEUS id of the course
	 *        	
	 *        	@retval details 2-dimensional array containing the course details. 1. dimension = language, 2. dimension = details.
	 *        	@retval false An error occured while fetching the details
	 *        	
	 * @authors Konstantinos Dafalias - kdafalias@gmail.com
	 * @authors Jakob Strasser - jakob.strasser@telenet.be
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 *       
	 * @see get_details()
	 */
	private function get_detail($iID) {
		$aDetail = array ();
		foreach ( $this->aLanguage as $sLang ) {
			$sReturn = file_get_contents ( $this->sCeusUrl . '/' . $this->aFiles ['DETAIL'] . '?id=' . $iID . '&authtoken=' . $this->sAuthtoken . "&lang=$sLang" );
			if (! $aReturn = $this->check_return_value ( $sReturn ))
				return false;
			foreach ( $aReturn as $key => $val ) {
				if (is_string ( $val ))
					$aReturn [$key] = decode_entities ( $val );
			}
			$aDetail [$sLang] = $aReturn;
		}
		$this->aStats ['loaded'] ++;
		return $aDetail;
	}
	
	/**
	 * @brief Saves a course as a Drupal content node
	 *
	 * If a node already exists, the function checks if the changedate has changed.
	 * If so, a new version of this conent node is created.
	 * If not, nothing is changed
	 *
	 * @param array $aDetail
	 *        	Array of course details as returned by @ref get_detail()
	 * @param array $tid
	 *        	Drupal vocabulary term id of the vocabulary term corresponding to the given course
	 * @return Node id of the saved content node
	 *        
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @authors Jakob Strasser - jakob.strasser@telenet.be
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private function save_node($aDetail, $tid) {
		$aNodes = taxonomy_select_nodes ( $tid );
		if (! empty ( $aNodes ))
			$iNodeID = $aNodes [0];
		else {
			$iNodeID = null;
		}
		$oNode = node_load ( $iNodeID );
		// If same changedate, do nothing
		if (! empty ( $iNodeID ) && ($aDetail ['de'] ['changedate'] == $oNode->changedate ['und'] [0] ['value'])) {
			return $iNodeID;
		}
		global $user;
		// If LVA is new, new node is created
		if (empty ( $oNode )) {
			$this->aStats ['new'] ++;
			$oNode = new stdClass ();
		} else {
			$this->aStats ['updated'] ++;
		}
		$oNode->type = 'stukowin';
		node_object_prepare ( $oNode );
		// Fields of CEUS-DB to be copied
		$aMembers = array (
				'code',
				'ects',
				'wst',
				'verantname',
				'verantemail',
				'changedate',
				'lvtypname',
				'lvtypshort',
				'typename',
				'ziele',
				'lehrinhalte',
				'voraussetzungen' 
		);
		$oNode->language = 'de';
		// Fields that can exist in English
		$aEnglishMembers = array (
				'lvtypname',
				'lvtypshort',
				'typename',
				'ziele' 
		);
		if (! empty ( $aDetail ['de'] ['type'] ))
			$oNode->{'lvatype'} ['und'] [0] ['value'] = $aDetail ['de'] ['type'];
		if (! empty ( $aDetail ['de'] ['id'] ))
			$oNode->{'ceusid'} ['und'] [0] ['value'] = $aDetail ['de'] ['id'];
		foreach ( $aMembers as $sMember ) {
			if (! empty ( $aDetail ['de'] [$sMember] ))
				$oNode->{$sMember} ['und'] [0] ['value'] = $aDetail ['de'] [$sMember];
		}
		foreach ( $aEnglishMembers as $sMember ) {
			if (! empty ( $aDetail ['en'] [$sMember] ))
				$oNode->{$sMember} ['en'] [0] ['value'] = $aDetail ['en'] [$sMember];
		}
		$oNode->title = $aDetail ['de'] ['title'];
		$oNode->body ['und'] [0] ['value'] = $aDetail ['de'] ['lehrinhalte'];
		if (! empty ( $aDetail ['en'] ['lehrinhalte'] ))
			$oNode->body ['en'] [0] ['value'] = $aDetail ['en'] ['lehrinhalte'];
		$oNode->uid = $user->uid;
		$oNode->status = 1;
		$oNode->comment = 0;
		$oNode->promote = 0;
		$oNode->moderate = 0;
		$oNode->sticky = 0;
		$oNode->term ['und'] [0] ['tid'] = $tid;
		if (! empty ( $iNodeID )) {
			$oNode->nid = $iNodeID;
			$oNode->revision = true;
		}
		$oNode = node_submit ( $oNode );
		node_save ( $oNode );
		if (! empty ( $aDetail ['de'] ['voraussetzungen'] ) && $this->has_relation ( $aDetail ['de'] ['voraussetzungen'] ))
			$this->aRelations [$oNode->nid] = $aDetail ['de'] ['voraussetzungen'];
		return $oNode->nid;
	}
	
	/**
	 * @brief Checks if a course has recommendations or prerequisites
	 *
	 * This function determines if a course has a relation with another course.
	 * This is the case when the @e $sRelationfield is not empty and does not begin with "kein" (case insensitive).
	 * Returns true if there are any relations that need to be processed. Return false if the arent any.
	 *
	 * @param string $sRelationfield
	 *        	Content of the courses @c voraussetzungen field
	 *        	
	 *        	@retval true The course has recommendations or prerequisites
	 *        	@retval false The course does @b not have recommendations or prerequisites
	 *        	
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private function has_relation($sRelationfield) {
		return ! empty ( $sRelationfield ) && strtolower ( substr ( $sRelationfield, 0, 4 ) ) != 'kein';
	}
	
	/**
	 * @brief Parses linked web page and tries to extract a course code
	 *
	 * This function tries to extract the course code (e.g. @e 1FENKF) from website behind the given @e $sLink and returns it.
	 *
	 * @param string $sLink
	 *        	HTML-Code with Link to CEUS entry
	 *        	@retval "course code" The extracted code
	 *        	@retval false The extraction was not successful
	 *        	
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 * @see get_term_ids()
	 */
	private function parse_link_term_code($sLink) {
		$oHTML = file_get_html ( $sLink );
		$oElement = $oHTML->find ( 'span[id=code]', 0 );
		if (! empty ( $oElement ))
			return $oElement->plaintext;
		else
			return false;
	}
	
	/**
	 * @brief Looks for Drupal content node by title or code
	 *
	 * This function searches for the Drupal content node corresponding to a given course code or course title.
	 *
	 * @param string $sFieldtype
	 *        	The name of the field to search in. This can be either "code" or "title".
	 * @param string $sFieldcontent
	 *        	The search term to look for
	 *        	@retval nid The node's id
	 *        	@retval false No content node with this title or code could be found
	 *        	
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 *       
	 * @see get_term_ids()
	 */
	private function find_nodeid_by_field($sFieldtype, $sFieldcontent) {
		$oQuery = new EntityFieldQuery ();
		if ($sFieldtype == "code") {
			$aEntities = $oQuery->entityCondition ( 'entity_type', 'node' )->propertyCondition ( 'type', 'stukowin' )->fieldCondition ( $sFieldtype, 'value', $sFieldcontent, '=' )->propertyCondition ( 'status', 1 )->range ( 0, 1 )->execute ();
		} else {
			$aEntities = $oQuery->entityCondition ( 'entity_type', 'node' )->propertyCondition ( 'type', 'stukowin' )->propertyCondition ( $sFieldtype, $sFieldcontent )->propertyCondition ( 'status', 1 )->range ( 0, 1 )->execute ();
		}
		if (! empty ( $aEntities ['node'] )) {
			$aArray = array_keys ( $aEntities ['node'] );
			$aArray = array_shift ( $aArray );
			$oNode = node_load ( $aArray );
			return $oNode->nid;
		} else
			return false;
	}
	
	/**
	 * @brief Parses the @c voraussetzungen field of a course and tries to extract the relation
	 * 
	 * This function loops through all links and list items in the @c voraussetzugnen field and tries to extracts the referenced courses.
	 * It does this in 3 steps:
	 * 1. Extract the course names
	 * 2. Try to get the Drupal content node id through the course title
	 * 3. Try to get the Drupal content node id through the course code
	 *
	 * @param string $sRelationfield
	 *        	Textual content of the @c voraussetzungen field as received from CEUS
	 * @return Drupal content node ids of related courses
	 *        
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 * 
	 * @see find_nodeid_by_field()
	 * @see parse_link_term_code()
	 */
	private function get_term_ids($sRelationfield) {
		$oHTML = str_get_html ( $sRelationfield );
		// Step 1: extract all LVA names from <li> and <a> fields
		$aLI = $oHTML->find ( 'li' );
		$aA = $oHTML->find ( 'a' );
		$aTitlesA = array ();
		$aTitlesLI = array ();
		foreach ( $aA as $oElement ) {
			$aTitlesA [$oElement->plaintext] = trim ( $oElement->plaintext );
			$aLinks [$oElement->plaintext] = $oElement->href;
		}
		foreach ( $aLI as $oElement ) {
			$aTitlesLI [$oElement->plaintext] = trim ( $oElement->plaintext );
		}
		$aTitles = array_merge ( $aTitlesA, $aTitlesLI );
		// Step 2: try to get lva id by title
		$aIDs = array ();
		foreach ( $aTitles as $i => $sTitle ) {
			$iNodeID = $this->find_nodeid_by_field ( 'title', $sTitle );
			if (! empty ( $iNodeID ))
				$aIDs [trim ( $i )] = $iNodeID;
		}
		// Step 3: extract all a href and parse for <span id="code">
		foreach ( $aTitlesA as $i => $sTitle ) {
			if (empty ( $aIDs [$i] )) {
				$sCode = $this->parse_link_term_code ( $aLinks [$i] );
				$iNodeID = $this->find_nodeid_by_field ( 'code', $sCode );
				if (! empty ( $iNodeID ))
					$aIDs [trim ( $i )] = $iNodeID;
			}
		}
		return $aIDs;
	}
	
	/**
	 * @brief Creates Drupal relations out of CEUS relations
	 * 
	 * This procedure loops through @ref $aRelations,
	 * parses the @c voraussetzungen field of each course,
	 * generates relations for required and suggested courses
	 * and stores them in the node
	 *
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 * 
	 * @see get_term_ids()
	 */
	private function process_relations() {
		if (! ($this->aRelations) || empty ( $this->aRelations ))
			return;
		$aSuggested = array ();
		$aRequired = array ();
		foreach ( $this->aRelations as $iID => $sRelationfield ) {
			if (strtolower ( substr ( $sRelationfield, 0, 9 ) ) == 'empfohlen') {
				$aNodeIDs = $this->get_term_ids ( $sRelationfield );
				if (! empty ( $aNodeIDs ))
					$aSuggested [$iID] = $aNodeIDs;
			} else {
				$aNodeIDs = $this->get_term_ids ( $sRelationfield );
				if (! empty ( $aNodeIDs ))
					$aRequired [$iID] = $aNodeIDs;
			}
		}
		foreach ( $aRequired as $iNodeID => $aReqNodeID ) {
			$aReqs = array ();
			$aRid = array ();
			foreach ( $aReqNodeID as $iReqNodeID ) {
				$aEndpoints = array (
						array (
								'entity_type' => 'node',
								'entity_id' => $iNodeID 
						),
						array (
								'entity_type' => 'node',
								'entity_id' => $iReqNodeID 
						) 
				);
				try {
					$oRelation = relation_create ( 'voraussetzung', $aEndpoints );
				} catch ( Exception $e ) {
					drupal_set_message ( "Error while creating required dependency between course " . $iNodeID . " and course " . $iReqNodeID . ": " . $e . getMessage (), "error" );
				}
				try {
					$aRid [] = relation_save ( $oRelation );
					$this->aStats ['relations'] ++;
				} catch ( Exception $e ) {
					drupal_set_message ( "Error while saving required dependency between course " . $iNodeID . " and course " . $iReqNodeID . ": " . $e . getMessage (), "error" );
				}
			}
			if (! empty ( $aRid )) {
				$oNode = node_load ( $iNodeID );
				$oNode->voraussetzung ['und'] [0] ['value'] = $aRid;
				node_save ( $oNode );
			}
		}
		foreach ( $aSuggested as $iNodeID => $aReqNodeID ) {
			$aReqs = array ();
			$aRid = array ();
			foreach ( $aReqNodeID as $iReqNodeID ) {
				$aEndpoints = array (
						array (
								'entity_type' => 'node',
								'entity_id' => $iNodeID 
						),
						array (
								'entity_type' => 'node',
								'entity_id' => $iReqNodeID 
						) 
				);
				try {
					$oRelation = relation_create ( 'empfehlung', $aEndpoints );
				} catch ( Exception $e ) {
					drupal_set_message ( "Error while creating suggested dependency between course " . $iNodeID . " and course " . $iReqNodeID . ": " . $e . getMessage (), "error" );
				}
				try {
					$iRid [] = relation_save ( $oRelation );
					$this->aStats ['relations'] ++;
				} catch ( Exception $e ) {
					drupal_set_message ( "Error while saving suggested dependency between course " . $iNodeID . " and course " . $iReqNodeID . ": " . $e . getMessage (), "error" );
				}
			}
			if (! empty ( $aRid )) {
				$oNode = node_load ( $iNodeID );
				$oNode->empfehlung ['und'] [0] ['value'] = $aRid;
				node_save ( $oNode );
			}
		}
	}
	
	/**
	 * @brief Recursive function that traverses a curriculum tree
	 * 
	 * This method goes through all the courses of a curriculum that have been returned by CEUS recursively
	 * and reads the details for each course into the @ref $aTerms array.
	 * A vocabulary term is created for each item in the respective curriculum.
	 *
	 * @param array $aTree
	 *        	The current subtree in the curriculum
	 * @param integer $iParentID
	 *        	Drupal term id of this course's parent term
	 * @param integer $iCurriculumID
	 *        	CEUS id of the curriculum this tree belongs to
	 * @retval true Courses have been read
	 * @retval false The subtree has reached a leaf
	 * 
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 * 
	 * @see get_detail()
	 */
	private function get_details($aTree, $iParentID, $iCurriculumID) {
		if (is_array ( $aTree ) && count ( $aTree )) {
			$iWeight = 0;
			foreach ( $aTree as $aBranch ) {
				$aDetail = $this->get_detail ( $aBranch ['id'] );
				// Create new term if not in vocabulary
				$oTerm = new stdClass ();
				if (! empty ( $this->aTerms [$iCurriculumID] [$aDetail ['de'] ['id']] )) {
					$oTerm->tid = $this->aTerms [$iCurriculumID] [$aDetail ['de'] ['id']]->tid;
				}
				$sLvtyp = empty ( $aDetail ['de'] ['lvtypshort'] ) ? '' : " ({$aDetail['de']['lvtypshort']})";
				$oTerm->name = $aDetail ['de'] ['typename'] . ': ' . $aDetail ['de'] ['title'] . $sLvtyp;
				$oTerm->vid = $this->aVocabulary [$iCurriculumID];
				$oTerm->parent = $iParentID;
				$oTerm->weight = $iWeight;
				taxonomy_term_save ( $oTerm );
				$iNodeID = $this->save_node ( $aDetail, $oTerm->tid );
				
				$iWeight ++;
				$oTerm->description = $iNodeID;
				taxonomy_term_save ( $oTerm );
				$this->get_details ( $aBranch ['subtree'], $oTerm->tid, $iCurriculumID );
			}
			return true;
		} else
			return false;
	}
	
	/**
	 * @brief Main method: Imports curriculum data from CEUS
	 * 
	 * This is the main public method of this class. It does the following things:
	 * 1. Reset the statistics
	 * 2. Imports all curricula
	 * 3. Create a new vocabulary if none exists
	 * 4. Load course data from CEUS API
	 * 5. Process relations
	 * 6. Store everything into the Drupal database
	 *
	 * @retval "success message" The import was successful. This message contains the @ref $aStats "import statistics".
	 * @retval false An error has occured and the import was not successful.
	 *        
	 * @authors Konstantinos Dafalias - kdafalias@gmail.com
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 * 
	 * @see get_curricula_list()
	 * @see check_vocabulary()
	 * @see get_curriculum()
	 * @see get_details()
	 * @see process_relations()
	 */
	public function get_curricula() {
		// set time limit for this specific function higher than the limit configured in the php.ini file
		// needed to not get timeouts on slower hardware as this function usually needs 2-5 minutes to complete
		set_time_limit ( 0 );
		$this->aStats ['loaded'] = 0;
		$this->aStats ['new'] = 0;
		$this->aStats ['updated'] = 0;
		$this->aStats ['numcurrs'] = 0;
		$this->aStats ['relations'] = 0;
		if ($aCurriculaList = $this->get_curricula_list ()) {
			foreach ( $aCurriculaList as $aCurriculum ) {
				$this->aStats ['numcurrs'] ++;
				$this->check_vocabulary ( $aCurriculum );
				$aCurrTree [$aCurriculum ['id']] = $this->get_curriculum ( $aCurriculum ['id'] );
				$this->get_details ( $aCurrTree [$aCurriculum ['id']] ['tree'], 0, $aCurriculum ['id'] );
				$this->process_relations ();
			}
			return 'Import successful. <b>' . $this->aStats ['numcurrs'] . '</b> curricula with <b>' . $this->aStats ['loaded'] . '</b> courses loaded, <b>' . $this->aStats ['new'] . '</b> new nodes created, <b>' . $this->aStats ['updated'] . '</b> nodes updated and <b>' . $this->aStats ['relations'] . '</b> relations processed.';
		} else
			return false;
	}
	
	/**
	 * @brief Checks if a taxonomy vocabulary for the given curriculum exists and creates it if not
	 * 
	 * This function stores the corresponding vocabulary (either a new or an existing one) into the @ref $aVocabulary array.
	 * 
	 * @remark This method creates the new vocabulary with the default weight of @c 10, but only vocabularies with a weight below @c 0 will be shown publicly.
	 *
	 * @param array $aCurriculum
	 *        	Curriculum from CEUS as returned by @ref get_curricula_list()
	 *        	
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private function check_vocabulary($aCurriculum) {
		$oVocabulary = taxonomy_vocabulary_load ( variable_get ( 'ceus_importer_' . $aCurriculum ['typeshort'] . '_' . $aCurriculum ['version'] . '_vocabulary', 0 ) );
		$oContentManager = new content_manager ();
		if (! $oVocabulary) {
			$sMachineName = 'curriculum_' . $aCurriculum ['typeshort'] . '_' . $aCurriculum ['version'];
			$sMachineName = $oContentManager->getUniqueMachineName ( $sMachineName );
			$aEdit = array (
					'name' => $aCurriculum ['name'] . ', ' . $aCurriculum ['type'] . ' ' . $aCurriculum ['version'],
					'machine_name' => $sMachineName,
					'description' => $aCurriculum ['name'] . ', ' . $aCurriculum ['type'] . ' ' . $aCurriculum ['version'],
					'hierarchy' => 1,
					'module' => 'ceus_importer',
					'weight' => 10 
			);
			$oVocabulary = ( object ) $aEdit;
			$oVocabulary->{'faculty'} ['und'] [0] ['value'] = $aCurriculum ['faculty'];
			$oVocabulary->{'version'} ['und'] [0] ['value'] = $aCurriculum ['version'];
			$oVocabulary->{'currtype'} ['und'] [0] ['value'] = $aCurriculum ['type'];
			taxonomy_vocabulary_save ( $oVocabulary );
			variable_set ( 'ceus_importer_' . $aCurriculum ['typeshort'] . '_' . $aCurriculum ['version'] . '_vocabulary', $oVocabulary->vid );
		}
		$aTerms = taxonomy_get_tree ( $oVocabulary->vid );
		foreach ( $aTerms as $oCurrTerm ) {
			$oTermNode = $oContentManager->get_return_node ( $oCurrTerm->description );
			if ($oTermNode && ! empty ( $oTermNode ) && property_exists ( $oTermNode, 'ceusid' )) {
				$this->aTerms [$aCurriculum ['id']] [$oTermNode->ceusid] = $oCurrTerm;
			}
		}
		$this->aVocabulary [$aCurriculum ['id']] = $oVocabulary->vid;
	}
	
	/**
	 * @brief Gets a list of all curricula (bachelor, master) from CEUS
	 * 
	 * This function retreives a list of all available curricula from the CEUS API.
	 *
	 * @return Associative array containing all curricula
	 *        
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private function get_curricula_list() {
		$sReturn = file_get_contents ( $this->sCeusUrl . '/' . $this->aFiles ['LIST'] . '?authtoken=' . $this->sAuthtoken );
		return $this->check_return_value ( $sReturn );
	}
	
	/**
	 * @brief Gets one curriculum tree from CEUS
	 * 
	 * This function retreives the complete tree of one certain curriculum from the CEUS API
	 *
	 * @param integer $iID
	 *        	CEUS id of the curriculum
	 * @return Nested array of course ids
	 *        
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private function get_curriculum($iID) {
		$sReturn = file_get_contents ( $this->sCeusUrl . '/' . $this->aFiles ['CURR'] . '?id=' . $iID . '&authtoken=' . $this->sAuthtoken );
		return $this->check_return_value ( $sReturn );
	}
	
	/**
	 * @brief Returns last error message
	 * 
	 * This function acts as a public getter for the @ref $sError "error message".
	 *
	 * @return string
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @version 1.0.0 2014-07-16
	 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 * 
	 * @see $sError
	 */
	public function get_error() {
		return $this->sError;
	}
}
