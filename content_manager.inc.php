<?php
include_once __DIR__ . '/simple_html_dom.php';
include_once dirname ( __FILE__ ) . '/stukowin.install';
/*
 * To change this license header, choose License Headers in Project Properties. To change this template file, choose Tools | Templates and open the template in the editor.
 */

/**
 */
class content_manager {
	
	/**
	 * Returns LVA-Content-Node with all fields required for display
	 *
	 * @param integer $iNodeID
	 *        	Drupal-Node-ID
	 * @return \stdClass
	 */
	private function get_return_node($iNodeID) {
		$oNode = node_load ( $iNodeID );
		$oReturnNode = new stdClass ();
		$oReturnNode->title = $oNode->title;
		$aFields = _stukowin_installed_fields ();
		foreach ( $aFields as $sKey => $aValue ) {
			if (! empty ( $oNode->{$sKey} ['und'] [0] ['value'] ))
				$oReturnNode->{$sKey} = $oNode->{$sKey} ['und'] [0] ['value'];
		}
		// Relations: Must and wouldbenice, only if index=1 (forward) index=0 is backward, we do not deliver this back
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
	 * Reads Taxonomy into nested array
	 *
	 * @param type $vid_or_terms        	
	 * @param type $max_depth        	
	 * @param type $parent        	
	 * @param type $parents_index        	
	 * @param type $depth        	
	 * @return type
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
						$oReturnNode = $this->get_return_node ( $iNodeID );
						$term->ects = $oReturnNode->ects;
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
	 *
	 * @param integer $iNodeID
	 *        	Drupal-ID of desired node
	 */
	public function json_service_lva($iNodeID) {
		$oReturnNode = $this->get_return_node ( $iNodeID );
		header ( 'Access-Control-Allow-Origin: *' );
		drupal_json_output ( $oReturnNode );
		die ();
	}
	
	/**
	 * Reads all curricula of types $aTypes and returns them as associative arrays
	 *
	 * @param string $sCurrType
	 *        	The type of curriculum to get. Valid values are {'Bachelorstudium','Masterstudium'}
	 * @param array $aTaxonomyTypes
	 *        	The types to get. Valid values are {'curriculum','itsv','schwerpunkt'}
	 * @param string $sLang
	 *        	= 'de' The language to get the curricula in (optional)
	 */
	public function getCurricula($sCurrType = '', $aTaxonomyTypes = array('curriculum'), $sLang = 'de') {
		if ($sLang === 'de')
			$sLang = 'und';
		$aCurricula = array ();
		foreach ( $aTaxonomyTypes as $sTaxonomyType ) {
			
			$query = new EntityFieldQuery ();
			$query->entityCondition ( 'entity_type', 'taxonomy_vocabulary', '=' )->propertyCondition ( 'machine_name', $sTaxonomyType . '_%', 'LIKE' )->propertyCondition ( 'weight', '0', '<' );
			$aVocabulary = $query->execute ();
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
		return $aCurricula;
	}
	
	/**
	 * Asserts that a machine name is unique or adds numbers at the end until it is unique.
	 *
	 * @param string $sCoreName
	 *        	The initial name
	 * @return string A unique machine name
	 */
	public function getUniqueMachineName($sCoreName) {
		$sMachineName = $sCoreName;
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
					$sMachineName = $sCoreName . '_' . $a;
					$a ++;
				}
			}
		}
		return $sMachineName;
	}
	
	/**
	 * Gets the curriculum with $iVID from the database
	 *
	 * @return array The curriculum object
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
	 * JSON Service for Curricula Tree
	 * TODO: Taxonomieauswahl - Übergabe eigener service
	 * TODO: COntent in den Tree
	 */
	public function json_service_curriculum($iVID) {
		$aTerms = $this->taxonomy_get_nested_tree ( $iVID );
		header ( 'Access-Control-Allow-Origin: *' );
		// PHP ist doof. json_encode glaubt ein Array ist ein Objekt, mit dem Trick unten versteht es auch PHP
		array_unshift ( $aTerms, 'blabla' );
		array_shift ( $aTerms );
		drupal_json_output ( $aTerms );
		die ();
	}
}
