<?php
/**
 * @ingroup Stukowin_Module
 * @file
 * @brief Access to curricula data
 * 
 * This file contains all necessary functionality for
 * accessing the curricula data stored in drupal
 * 
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @authors Konstantinos Dafalias - kdafalias@gmail.com
 * @authors Jakob Strasser - jakob.strasser@telenet.be
 * @version 1.0.0 2014-07-07
 * @since Commit f90560aa796b39853beb42a521d6d94c86051c46 on 2014-06-28
 * 
 * @see content_manager
 */
include_once dirname ( __FILE__ ) . '/stukowin.install';

/**
 * @ingroup Stukowin_Module
 * @brief Access to drupal vocabularies and content nodes
 *
 * This class is used to fetch LVA-nodes and vocabulary trees from the drupal database. As it is a utility class, it has mainly @c public functions.
 *
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @authors Konstantinos Dafalias - kdafalias@gmail.com
 * @authors Jakob Strasser - jakob.strasser@telenet.be
 * @version 1.0.0 2014-07-07
 * @since Commit f90560aa796b39853beb42a521d6d94c86051c46 on 2014-06-28
 */
class content_manager {
	
	/**
	 * @brief Gets course details
	 *
	 * This method returns the drupal content node corresponding
	 * to the given @e $iNodeID in the given @e $sLang with all fields
	 * required for displaying the course either in the
	 * @ref pdf_creator.inc.php "PDF document" or in the @ref graph.js "curriculum display" (i.e. the fields set in @ref _stukowin_installed_fields()).
	 *
	 * @param integer $iNodeID
	 *        	Drupal id of the content node
	 * @param string $sLang
	 *        	Language to return. Default is 'de'.
	 * @return The selected course with all the needed attributes as properties
	 *        
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit 58a583aa830a1e2832126a782621f2ebd390d217 on 2014-06-29
	 *       
	 * @see _stukowin_installed_fields()
	 * @see taxonomy_get_nested_tree()
	 */
	public function get_return_node($iNodeID, $sLang = 'de') {
		if ($sLang = 'de')
			$sLang = 'und';
		$oNode = node_load ( $iNodeID );
		if (! $oNode) {
			return null;
		}
		$oReturnNode = new stdClass ();
		$oReturnNode->title = $oNode->title;
		$aFields = _stukowin_installed_fields ();
		foreach ( $aFields as $sKey => $aValue ) {
			if (! empty ( $oNode->{$sKey} [$sLang] [0] ['value'] ))
				$oReturnNode->{$sKey} = $oNode->{$sKey} [$sLang] [0] ['value'];
		}
		// Relations: Obligatory and recommended, only if index=1 (forward) index=0 is backward, we do not deliver this
		if (! empty ( $oNode->voraussetzung ['und'] ) && is_array ( $oNode->voraussetzung ['und'] )) {
			foreach ( $oNode->voraussetzung ['und'] as $aRelation ) {
				if (! empty ( $aRelation ['endpoints'] [1] ))
					$oReturnNode->voraussetzung [] = trim ( $aRelation ['endpoints'] [1], "a..zA..Z?:" );
			}
		}
		if (! empty ( $oNode->empfehlung ['und'] ) && is_array ( $oNode->empfehlung ['und'] )) {
			foreach ( $oNode->empfehlung ['und'] as $aRelation ) {
				if (! empty ( $aRelation ['endpoints'] [1] ))
					$oReturnNode->empfehlung [] = trim ( $aRelation ['endpoints'] [1], "a..zA..Z?:" );
			}
		}
		$oReturnNode->id = $iNodeID;
		return $oReturnNode;
	}
	
	/**
	 * @brief Gets vocabulary tree
	 *
	 * This is a recursive function that reads an entire drupal vocabulary into a nested array.
	 * It can be called externally by giving the vocabulary id (vid) as the first parameter and leaving the other parameters empty.
	 *
	 * @param integer|array $vid_or_terms
	 *        	The vid of the curriculum to get. Default is an empty @c array
	 * @param integer $max_depth
	 *        	The maximum depth of the nested array. Default is @c NULL
	 * @param integer $parent
	 *        	The drupal term id (tid) of the next term's parent term. Default is @c 0
	 * @param array $parents_index
	 *        	An array of all parents that have been traversed by the method in earlier recursion steps. Default is an empty @c array
	 * @param integer $depth
	 *        	The current recursion depth. Defaul is @c 0
	 * @return The nested array of all courses in the vocabulary
	 *        
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit f90560aa796b39853beb42a521d6d94c86051c46 on 2014-06-28
	 */
	public function taxonomy_get_nested_tree($vid_or_terms = array(), $max_depth = NULL, $parent = 0, $parents_index = array(), $depth = 0) {
		if (! is_array ( $vid_or_terms )) {
			$vid_or_terms = taxonomy_get_tree ( $vid_or_terms );
		}
		$aFields = _stukowin_installed_fields ();
		
		$return = array ();
		foreach ( $vid_or_terms as $term ) {
			foreach ( $term->parents as $term_parent ) {
				if ($term_parent == $parent) {
					$aNode = taxonomy_select_nodes ( $term->tid );
					if (! empty ( $aNode )) {
						$iNodeID = $aNode [0];
					} else {
						$iNodeID = $term->description;
					}
					$oReturnNode = $this->get_return_node ( $iNodeID );
					if ($oReturnNode) {
						$term->lva = $oReturnNode;
						$term->id = $iNodeID;
					}
					$return [$term->tid] = $term;
				} else {
					$parents_index [$term_parent] [$term->tid] = $term;
				}
			}
		}
		
		foreach ( $return as &$term ) {
			if (isset ( $parents_index [$term->tid] ) && (is_null ( $max_depth ) || $depth < $max_depth)) {
				$term->children = $this->taxonomy_get_nested_tree ( $parents_index [$term->tid], $max_depth, $term->tid, $parents_index, $depth + 1 );
			}
		}
		return $return;
	}
	
	/**
	 * @brief Returns course as JSON object
	 *
	 * This method fetches a single course from the drupal database and returns it as JSON.
	 *
	 * @param integer $iNodeID
	 *        	Content node id of the desired course
	 *        	
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit f90560aa796b39853beb42a521d6d94c86051c46 on 2014-06-28
	 *       
	 * @see stukowin_get_lva()
	 */
	public function json_service_lva($iNodeID) {
		$oReturnNode = $this->get_return_node ( $iNodeID );
		if ($oReturnNode)
			drupal_json_output ( $oReturnNode );
		else {
			$oError = new StdClass ();
			$oError->error = "NodeID does not exist";
			drupal_json_output ( $oError );
		}
		die ();
	}
	
	/**
	 * @brief Gets multiple curriculula
	 *
	 * This method reads all curricula of curriculum type @e $sCurrType and vocabulary types
	 * @e $aVocabularyTypes in the language @e $sLang from the drupal database and returns them as an associative array.
	 *
	 * @param string $sCurrType
	 *        	The type of curriculum to get. Valid values are
	 *        	- Bachelorstudium
	 *        	- Masterstudium
	 * @param array $aVocabularyTypes
	 *        	The taxonomy types to get. Default is 'curriculum'. Valid values are
	 *        	- curriculum
	 *        	- itsv
	 *        	- schwerpunkt
	 * @param string $sLang
	 *        	The language to get the curricula in. Default is 'de'
	 * @return Associative array of selected curricula
	 *        
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit 190577568295b7682dc74a79c4fd478e9e33c639 on 2014-07-02
	 */
	public function getCurricula($sCurrType = '', $aVocabularyTypes = array('curriculum'), $sLang = 'de') {
		if ($sLang === 'de')
			$sLang = 'und';
		$aCurricula = array ();
		foreach ( $aVocabularyTypes as $VocabularyType ) {
			$query = new EntityFieldQuery ();
			$query->entityCondition ( 'entity_type', 'taxonomy_vocabulary', '=' )->propertyCondition ( 'machine_name', $VocabularyType . '_%', 'LIKE' )->propertyCondition ( 'weight', '0', '<' );
			$aVocabulary = $query->execute ();
			if (isset ( $aVocabulary ['taxonomy_vocabulary'] )) {
				foreach ( $aVocabulary ['taxonomy_vocabulary'] as $iVID => $aVID ) {
					$oVocabulary = taxonomy_vocabulary_load ( $iVID, 0 );
					if (! $sCurrType || $oVocabulary->currtype ['und'] [0] ['value'] == $sCurrType) {
						$aCurricula [] = array (
								'vid' => $oVocabulary->vid,
								'name' => $oVocabulary->name,
								'type' => $oVocabulary->currtype [$sLang] [0] ['value'],
								'faculty' => $oVocabulary->faculty [$sLang] [0] ['value'],
								'version' => $oVocabulary->version [$sLang] [0] ['value'] 
						);
					}
				}
			}
		}
		return $aCurricula;
	}
	
	/**
	 * @brief Gets a unique and valid machine name
	 *
	 * This function asserts that a machine name is valid and adds incrementing numbers at the end until it is also unique.
	 *
	 * @param string $sCoreName
	 *        	The initial name
	 * @return The unique and valid machine name
	 *        
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit 190577568295b7682dc74a79c4fd478e9e33c639 on 2014-07-02
	 *       
	 * @see stukowin_taxonomy_menu_submit()
	 * @see ceus_importer::check_vocabulary()
	 */
	public function getUniqueMachineName($sCoreName) {
		$sMachineName = preg_replace ( "/[^a-z0-9_]+/i", "", $sCoreName );
		$sMachineName = strtolower ( $sMachineName );
		$aExistingNames = array ();
		$query = new EntityFieldQuery ();
		$query->entityCondition ( 'entity_type', 'taxonomy_vocabulary', '=' )->propertyCondition ( 'machine_name', $sCoreName . '%', 'LIKE' );
		$aVocabulary = $query->execute ();
		if (isset ( $aVocabulary ['taxonomy_vocabulary'] )) {
			foreach ( $aVocabulary ['taxonomy_vocabulary'] as $iVID => $aVID ) {
				$oVocabulary = taxonomy_vocabulary_load ( $iVID, 0 );
				$aExistingNames [] = $oVocabulary->machine_name;
			}
			$a = 1;
			for($i = 0; $i < count ( $aExistingNames ); $i ++) {
				if ($aExistingNames [$i] == $sMachineName) {
					$i = - 1;
					$sMachineName = $sCoreName . (substr ( $sCoreName, - 1 ) == '_' ? '' : '_') . $a;
					$a ++;
				}
			}
		}
		return $sMachineName;
	}
	
	/**
	 * @brief Gets one the curriculum
	 *
	 * This function fetches the curriculum object with the given vocabulary id (@e $iVID) from the database.
	 *
	 * @b Note: it does not fetch the vocabulary tree, just its meta-information.
	 *
	 * @param integer $iVID
	 *        	The vid of the curriculum vocabulary
	 * @return An associative array representing the curriculum object
	 *        
	 * @author Jakob Strasser - jakob.strasser@telenet.be
	 * @since Commit 190577568295b7682dc74a79c4fd478e9e33c639 on 2014-07-02
	 */
	public function getCurriculum($iVID) {
		$oVocabulary = taxonomy_vocabulary_load ( $iVID, 0 );
		$oCurriculum = array (
				'vid' => $oVocabulary->vid,
				'name' => $oVocabulary->name,
				'machine_name' => $oVocabulary->machine_name,
				'type' => $oVocabulary->currtype ['und'] [0] ['value'],
				'faculty' => $oVocabulary->faculty ['und'] [0] ['value'],
				'version' => $oVocabulary->version ['und'] [0] ['value'] 
		);
		return $oCurriculum;
	}
	
	/**
	 * @brief Returns curriculum tree as JSON array
	 *
	 * This method gets the entire tree of the curriculum with the vocabulary id @c $iVID and returns it as a JSON array.
	 *
	 * @param integer $iVID
	 *        	Drupal vocabulary id of the desired curriculum
	 *        	
	 * @author Konstantinos Dafalias - kdafalias@gmail.com
	 * @since Commit f90560aa796b39853beb42a521d6d94c86051c46 on 2014-06-28
	 *       
	 * @see taxonomy_get_nested_tree()
	 */
	public function json_service_curriculum($iVID) {
		$aTerms = $this->taxonomy_get_nested_tree ( $iVID );
		// json_encode glaubt ein Array ist ein Objekt, mit dem Trick unten versteht es auch PHP
		array_unshift ( $aTerms, 'blabla' );
		array_shift ( $aTerms );
		drupal_json_output ( $aTerms );
		die ();
	}
}
