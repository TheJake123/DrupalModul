<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class content_manager
{
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
  private function taxonomy_get_nested_tree($vid_or_terms = array(), $max_depth = NULL, $parent = 0, $parents_index = array(), $depth = 0) {

    if (!is_array($vid_or_terms)) 
    {
      $vid_or_terms = taxonomy_get_tree($vid_or_terms);
    }
    $aFields = _stukowin_installed_fields();
    foreach ($vid_or_terms as $term) 
    {
      foreach ($term->parents as $term_parent) 
      {
        if ($term_parent == $parent) 
        {
          $aNode = taxonomy_select_nodes($term->tid);
          if(!empty($aNode)) 
          {
            $iNodeID = $aNode[0]; 
            $oNode = node_load($iNodeID);
            $oReturnNode = new stdClass();
            foreach($aFields as $sKey=>$aValue)
            {
              if(!empty($oNode->{$sKey}['und'][0]['value'])) $oReturnNode->{$sKey} = $oNode->{$sKey}['und'][0]['value'];
            }
            $term->ects = $oReturnNode->ects;
            $term->lva = $oReturnNode;
            $term->id = $iNodeID;
          }
          $return[$term->tid] = $term;
        }
        else 
        {
          $parents_index[$term_parent][$term->tid] = $term;
        }
      }
    }

    foreach ($return as &$term) 
    {
      if (isset($parents_index[$term->tid]) && (is_null($max_depth) || $depth < $max_depth)) 
      {
        $term->children = $this->taxonomy_get_nested_tree($parents_index[$term->tid], $max_depth, $term->tid, $parents_index, $depth + 1);
      }
    }
    return $return;
  }
  
  /**
   * 
   * @param integer $iNodeID Drupal-ID of desired node
   */
  public function json_service_lva($iNodeID)
  {
    $oNode = node_load($iNodeID);
    $oReturnNode = new stdClass();
    $aFields = _stukowin_installed_fields();
    
    foreach($aFields as $sKey=>$aValue)
    {
      if(!empty($oNode->{$sKey}['und'][0]['value'])) $oReturnNode->{$sKey} = $oNode->{$sKey}['und'][0]['value'];
    }
    $oReturnNode->id = $iNodeID;
    drupal_json_output($oReturnNode);die();
  }
  
  /**
   * JSON-Service: Returns list of all taxonomies with weight < 10 (all curricula related taxonomies)
   */
  public function json_service_curriculist()
  {
    $query = new EntityFieldQuery();
    $query
      ->entityCondition('entity_type', 'taxonomy_vocabulary', '=')
      ->propertyCondition('machine_name', 'curriculum_%', 'LIKE')
      ->propertyCondition('weight', '0', '<')
    ;
    $aVocabulary = $query->execute();
    foreach($aVocabulary['taxonomy_vocabulary'] as $iVID => $aVID)
    {
      $oVocabulary = taxonomy_vocabulary_load($iVID, 0); 
      $aCurricula[] = array('vid'=>$oVocabulary->vid, 'name'=>$oVocabulary->name, 'type'=>$oVocabulary->currtype['und'][0]['value'],
                            'faculty'=>$oVocabulary->faculty['und'][0]['value'],'version'=>$oVocabulary->version['und'][0]['value']);
    } 
    drupal_json_output($aCurricula);die();
  }
  
  /**
   * JSON Service for Curricula Tree
   * TODO: Taxonomieauswahl - Übergabe eigener service
   * TODO: COntent in den Tree
   */
  public function json_service_curriculum($iVID)
  {
    $aTerms = $this->taxonomy_get_nested_tree($iVID);
    drupal_json_output($aTerms);die();
  }
}