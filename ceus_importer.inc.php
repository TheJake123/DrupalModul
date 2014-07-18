<?php
/**
 * @defgroup CEUS2Drupal CEUS2Drupal
 * @brief This module is reponsible for requesting the data form CEUS and storing the data in the drupal database.
 * It also implements the functions for the change management
 * 
 * @author Konstantinos Dafalias - kdafalias@gmail.com
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @author Markus Gutmayer
 * @author Werner Breuer
 * @since d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
 */

/**
 * @ingroup CEUS2Drupal
 * @file ceus_importer.inc.php
 * @brief Imports data from CEUS
 * 
 * This file manages the import of curricula data from CEUS.
 * 
 * @author Konstantinos Dafalias - kdafalias@gmail.com
 * @version 1.0.0 2014-07-16
 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
 */

include_once __DIR__ . '/simple_html_dom.php';
include_once dirname ( __FILE__ ) . '/stukowin.install';
include_once dirname ( __FILE__ ) . '/content_manager.inc.php';

/**
 * @ingroup CEUS2Drupal
 * @brief Imports data from CEUS and stores it in the Drupal DB
 *
 * This class is used to import data from the CEUS-API and for saving them in drupal database. It also provides the change management functionality
 *
 * @author Konstantinos Dafalias
 * @version 1.0.0 2014-07-16
 * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
 * 
 */
class ceus_importer {
	/**
	 *
	 * @var string Complete URL to CEUS-API as retreived from Module configuration
	 */
	private $sCeusUrl;
	
	/**
	 *
	 * @var string Username for CEUS API as retreived from Module configuration
	 */
	private $sUsername;
	
	/**
	 *
	 * @var string Password for CEUS API as retreived from Module configuration
	 */
	private $sPassword;
	
	/**
	 *
	 * @var string Authtoken, retreived from CEUS API
	 */
	private $sAuthtoken;
	
	/**
	 *
	 * @var array Name of the API files for getting authorization token, listing all curricula, getting one curriculum, getting one curriculum item
	 */
	private $aFiles = array (
			'AUTH' => 'auth.php',
			'LIST' => 'list.php',
			'CURR' => 'curr.php',
			'DETAIL' => 'detail.php' 
	);
	
	/**
	 *
	 * @var string error message, if error has occurred
	 */
	private $sError;
	private $counter = 0;
	
	/**
	 *
	 * @var array All languages supported
	 */
	private $aLanguage = array (
			'de',
			'en' 
	);
	
	/**
	 *
	 * @var array all vocabularies for current curricula
	 */
	private $aVocabulary;
	/**
	 *
	 * @var array all Terms for vocabularies
	 */
	private $aTerms;
	/**
	 *
	 * @var array all Import stats
	 */
	private $aStats;
	/**
	 *
	 * @var array array of relations that is filled during import and evaluated at the end; Key = LVA ID, value = content text of "voraussetzungen" field from CEUS
	 */
	private $aRelations;
	
	/**
         * @brief Constructor
         * 
	 * Constructor reads configuration data
         * 
         * @author Konstantinos Dafalias - kdafalias@gmail.com
         * @version 1.0.0 2014-07-16
         * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	public function __construct() {
		$this->sCeusUrl = variable_get ( 'stukowin_ceus_api_url' );
		$this->sUsername = variable_get ( 'stukowin_ceus_api_username' );
		$this->sPassword = variable_get ( 'stukowin_ceus_api_userpassword' );
	}
	
	/**
         * @brief Checks the return value from CEUS-API server
         * 
         * Checks if CEUS-API server responded and if there was an error
	 *
	 * @param string $sReturn
	 *        	JSON encoded String fetched from CEUS API server
	 * @return mixed false if error occurred - error is stored in $sError member - decoded array if successful
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
	 * @return boolean
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
	 * @brief Get single lva from CEUS
	 * This function gets a single LVA from the CEUS-API and saves all the details in an array.
         * On Success it returns the array containing the data from an single LVA otherwise it returns false
         * 
	 * @param integer $iID
	 *        	LVA-CEUS-ID
	 * @return array Complete data structure for lva; 2-dimensional, 1. dimension = language, 2. dimension = detail data 
         * 
         * @author Konstantinos Dafalias - kdafalias@gmail.com
         * @version 1.0.0 2014-07-16
         * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
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
	 * @brief Saves a content node for an LVA
	 * 
         * This function saves a content node for the given LVA. If a node already exists
         * it checks if the changedate has changed. If so a new version of this conent node is created.
         *  
	 * @param array $aDetail Data structure of 1 lva       	
	 * @param array $tid
	 *        	Term id of Taxonomy item
	 * @return integer Node id of the saved node
         * 
         * @author Konstantinos Dafalias - kdafalias@gmail.com
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
	 * @brief Checks if a LVA has relations
         * 
         * Determines if a LVA has a relation when field is empty or begins with "kein".
         * Returns true if there are any relations that need to be processed. Return false if the arent any.
	 *
	 * @param string $sRelationfield
	 *        	content of "voraussetzungen" field
	 *        	
	 * @return boolean true if has recommendations or prerequisites
         * 
         * @author Konstantinos Dafalias - kdafalias@gmail.com
         * @version 1.0.0 2014-07-16
         * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28
	 */
	private function has_relation($sRelationfield) {
		return ! empty ( $sRelationfield ) && strtolower ( substr ( $sRelationfield, 0, 4 ) ) != 'kein';
	}
	
	/**
	 * @brief Parses linked web page and tries to extract code
	 *
         * This function tries to extract the CEUS-ID from a given link and returns it.
         * Return false if there was no CEUS-ID to extract
         * 
	 * @param string $sLink HTML-Code with Link to CEUS entry
         * 
         * @author Konstantinos Dafalias - kdafalias@gmail.com
         * @version 1.0.0 2014-07-16
         * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28   	
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
	 * @brief Looks for LVA content element with title or code
         * Looks for LVA content element with title or code, returns LVA CEUS ID if successful, false if not
	 *
	 * @param string $sTitle
	 *        	LVA title
	 * @return mixed Term id or false
         * 
         * @author Konstantinos Dafalias - kdafalias@gmail.com
         * @version 1.0.0 2014-07-16
         * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28   	
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
	 * @brief Parses Relationfield and tries to extract
	 * This function tries to extract from the given relationfield all dependencies.
         * Does this in 3 steps:
         *      1. Extract the LVA names
         *      2. Try to get for the CEUS-ID for the LVA names
         *      3. Try to extract CEUS-ID from links
         * 
	 * @param string $sRelationfield Text content od relationfield as received from CEUS
         * @return array node-ids of related lva entries
         * 
         * @author Konstantinos Dafalias - kdafalias@gmail.com
         * @version 1.0.0 2014-07-16
         * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28   	
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
	 * @brief Private test method
         * 
         * Test method to extract all values from voraussetzungen and process them without having to reload
	 * all data from CEUS server
         * 
         * @author Konstantinos Dafalias - kdafalias@gmail.com
         * @version 1.0.0 2014-07-16
         * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28 
	 */
	private function test_make_relations() {
		$oQuery = new EntityFieldQuery ();
		$aEntities = $oQuery->entityCondition ( 'entity_type', 'node' )->propertyCondition ( 'type', 'stukowin' )->fieldCondition ( 'voraussetzungen', 'value', 'NULL', '!=' )->propertyOrderBy ( 'nid' )->execute ();
		if (! empty ( $aEntities ['node'] )) {
			$aArray = array_keys ( $aEntities ['node'] );
			$aNodes = node_load_multiple ( $aArray );
			$this->aRelations = array ();
			foreach ( $aNodes as $iNodeID => $oNode ) {
				if (substr ( $oNode->voraussetzungen ['und'] [0] ['value'], 0, 5 ) != 'keine') {
					$aVoraussetzungen = $oNode->voraussetzungen ['und'] [0] ['value'];
					if (! empty ( $aVoraussetzungen ))
						$this->aRelations [$iNodeID] = $aVoraussetzungen;
				}
			}
		}
		$this->process_relations ();
	}
	
	/**
         * @brief Creates Drupal relations out of CEUS relations
	 * Parses the voraussetzungen field an generates Relations for required and suggested courses
	 * and stores them in the node
         * 
         * @author Konstantinos Dafalias - kdafalias@gmail.com
         * @version 1.0.0 2014-07-16
         * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28 
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
	 * Recursive Method that traverses the tree of LVA ids that have been returned by CEUS
	 * and reads the detail dataset for each node
	 * A taxonomy term is being created for that item in the taxonomy tree
	 *
	 * @param array $aTree
	 *        	Current subtree
	 * @param integer $iParentID
	 *        	tid of parent term
	 * @param integer $iCurriculumID
	 *        	CEUS-ID of curriculum
	 * @return boolean
         * @author Konstantinos Dafalias - kdafalias@gmail.com
         * @version 1.0.0 2014-07-16
         * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28 
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
	 * @brief Main method: get current curriculum data from CEUS
	 * Main method that is called from stukowin.module
         * Loads current data from CEUS api, processes and stores them into Drupal 
         * 
	 * @return mixed Return message if successful, otherwise false
         * 
         * @author Konstantinos Dafalias - kdafalias@gmail.com
         * @version 1.0.0 2014-07-16
         * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28 
	 */
	public function get_curricula() {
                // set time limit for this specific function higher than the limit configured in the php.ini file
                // needed to not get timeouts on slower hardware as this function needs 2-5 minutes to complete
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
	 * @brief Checks if a taxonomy vocabulary for the curriculum exists and creates it if not
	 * Gets latest Curriculum and compares with stored curricula in the taxonomies
         * if new, curriculum is stowed in a new taxonomy
         * Default weight = 10, only weight below 0 will be displayed
         * 
	 * @param array $aCurriculum
	 *        	Curriculum entry from CEUS
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
	 * @brief Get list of all curricula (bachelor, master) from CEUS
	 * Retreives a list of all available Curricula in CEUS
         * 
	 * @return array Array containing all Curricula
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
	 * @brief Get curriculum tree from CEUS
         * Retreives complete tree of one certain curriculum from CEUS API
	 *
	 * @param integer $iID
	 *        	CEUS-ID of curriculum
	 * @return array multidimensional array of lva ids
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
	 * Getter for error message 
         * 
	 * @return string
         * @author Konstantinos Dafalias - kdafalias@gmail.com
         * @version 1.0.0 2014-07-16
         * @since Commit d179abcc5e05743086cd67cf1ce30b08923a7183 on 2014-06-28 
	 */
	public function get_error() {
		return $this->sError;
	}
}
