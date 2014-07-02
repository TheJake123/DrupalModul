<?php
include_once __DIR__ . '/simple_html_dom.php';
  include_once dirname(__FILE__).'/stukowin.install';


/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ceus_importer
{
  /**
   * @var string Complete URL to CEUS-API as retreived from Module configuration 
   */
  private $sCeusUrl;
  
  /**
   * @var string Username for CEUS API as retreived from Module configuration 
   */
  private $sUsername;
  
  /**
   * @var string Password for CEUS API as retreived from Module configuration 
   */
  private $sPassword;
  
  /**
   * @var string Last update date & time as received from CEUS
   */
  private $sLastupdate;
  
  /**
   * @var string Authtoken, retreived from CEUS API 
   */
  private $sAuthtoken;

  /**
   * @var array Name of the API files for getting authorization token, listing all curricula, getting one curriculum, getting one curriculum item 
   */
  private $aFiles = array('AUTH'=>'auth.php','LIST'=>'list.php','CURR'=>'curr.php','DETAIL'=>'detail.php');

  /**
   * @var string error message, if error has occurred 
   */
  private $sError;
  
  
  private $counter = 0;

  /**
   * @var array All languages supported 
   */
  private $aLanguage = array('de','en');
  
  /**
   * @var array all vocabularies for current curricula 
   */
  private $aVocabulary;
  
  /**
   * 
   * @var array array of relations that is filled during import and evaluated at the end; Key = LVA ID, value = content text of "voraussetzungen" field from CEUS 
   */
  private $aRelations;
  
  /**
   * Constructor reads configuration data
   */
  public function __construct() {
    $this->sCeusUrl = variable_get('stukowin_ceus_api_url');
    $this->sUsername = variable_get('stukowin_ceus_api_username');
    $this->sPassword = variable_get('stukowin_ceus_api_userpassword');
    $this->sLastupdate = variable_get('stukowin_ceus_api_lastupdate');
    set_time_limit(600);
  }
  
  /**
   * Checks if API server responded and if there was an error
   * 
   * @param string $sReturn JSON encoded String fetched from CEUS API server
   * @return mixed false if error occurred - error is stored in $sError member - decoded array if successful
   */
  private function check_return_value($sReturn)
  {
    if(empty($sReturn))
    {
      $this->sError = 'Connection to server failed';
      return false;
    }
    $aReturn = drupal_json_decode($sReturn);
    if(!empty($aReturn['error']))
    {
      $this->sError = $aError['error'];
      return false;
    }
    return $aReturn;
  }
  
  /**
   * Connect to CEUS and receive Authtoken
   * 
   * @return boolean
   */
  public function connect()
  {
    $sReturn = file_get_contents($this->sCeusUrl.'/'.$this->aFiles['AUTH'].'?username='.$this->sUsername.'&password='.$this->sPassword);
    if($aReturn = $this->check_return_value($sReturn))     
    {
      $this->sAuthtoken = $aReturn['authtoken'];
      return true;
    }
    else return false;

  }
  
  /**
   * Get single lva from CEUS
   * 
   * @param integer $iID LVA-CEUS-ID
   * @return boolean
   */
  private function get_detail($iID)
  {
    $aDetail = array();
    foreach ($this->aLanguage as $sLang)
    {
      $sReturn = file_get_contents($this->sCeusUrl.'/'.$this->aFiles['DETAIL'].'?id='.$iID.'&authtoken='.$this->sAuthtoken."&lang=$sLang");
      if(!$aReturn = $this->check_return_value($sReturn)) return false;
      $aDetail[$sLang] = $aReturn;
    }
    return $aDetail;
  }
  
  /**
   * Saves a content node for an lva
   * 
   * @param array $aDetail
   * @param array $tid Term-ID of Taxonomy item
   */
  private function save_node($aDetail, $tid)
  {
    $aOldNode = taxonomy_select_nodes($tid);
    if(!empty($aOldNode)) $iNodeID = $aOldNode[0]; 
    else $iNodeID = null;
    $oOldNode = node_load($iNodeID);
    // If same changedate, do nothing
    if(!empty($iNodeID) && ($aDetail['de']['changedate'] == $oOldNode->changedate['und'][0]['value'])) 
    {
      return true;
    }
    global $user;
    $oNode = new stdClass(); 
    $oNode->type = 'stukowin';
    node_object_prepare($oNode);
    // Fields of CEUS-DB to be copied
    $aMembers = array('code','ects','wst','verantname','verantemail','changedate','lvtypname','lvtypshort',
        'typename','ziele','lehrinhalte','voraussetzungen');
    $oNode->language = 'de';
    // Fields that can exist in English
    $aEnglishMembers = array('lvtypname','lvtypshort',
        'typename','ziele');
    if(!empty($aDetail['de']['type'])) $oNode->{'lvatype'}['und'][0]['value'] = $aDetail['de']['type'];
    foreach($aMembers as $sMember) 
    {
      if(!empty($aDetail['de'][$sMember])) $oNode->{$sMember}['und'][0]['value'] = $aDetail['de'][$sMember];
    }
    foreach($aEnglishMembers as $sMember)
    {
       if(!empty($aDetail['en'][$sMember])) $oNode->{$sMember}['en'][0]['value'] = $aDetail['en'][$sMember];
    }
    $oNode->title = $aDetail['de']['title'];
    $oNode->body['und'][0]['value'] = $aDetail['de']['lehrinhalte'];
    if(!empty($aDetail['en']['lehrinhalte'])) $oNode->body['en'][0]['value'] = $aDetail['en']['lehrinhalte'];
    $oNode->uid = $user->uid;
    $oNode->status = 1;
    $oNode->comment = 0;
    $oNode->promote = 0;
    $oNode->moderate = 0;
    $oNode->sticky = 0;
    $oNode->term['und'][0]['tid'] = $tid;
    if(!empty($iNodeID)) 
    {
      $oNode->nid = $iNodeID;
      $oNode->revision = true;
    }
    $oNode = node_submit($oNode);

    node_save($oNode);
    return $oNode->nid;
  }

  /**
   * Determines if a LVA has a relation 
   * when field is empty or begins with "kein"
   * 
   * @param string $sRelationfield content of "voraussetzungen" field
   * 
   * @return boolean has recommendations or prerequisites
   */
  private function has_relation($sRelationfield)
  {
    return !empty($sRelationfield) && strtolower(substr($sRelationfield,0, 4)) != 'kein';
  }
  
  /**
   * Parses linked web page and tries to extract code
   * 
   * @param type $sLink
   */
  private function parse_link_term_code($sLink)
  {
    $oHTML = file_get_html($sLink);
    $oElement = $oHTML->find('span[id=code]',0);
    // TODO: TID statt Code
    if(!empty($oElement)) return $oElement->plaintext;
    else return false;
  }
  
  /**
   * Looks for LVA content element with title or code, returns LVA CEUS ID if successful, false if not
   * @param string $sTitle LVA title
   * @return mixed term-ID or false
   */
  private function find_nodeid_by_field($sFieldtype, $sFieldcontent)
  {
    $oQuery = new EntityFieldQuery();
    if($sFieldtype == "code")
    {
      $aEntities = $oQuery->entityCondition('entity_type', 'node')
      ->propertyCondition('type', 'stukowin')
      ->fieldCondition($sFieldtype, 'value', $sFieldcontent, '=')
      ->propertyCondition('status', 1)
      ->range(0,1)
      ->execute();
    }
    else 
    { 
      $aEntities = $oQuery->entityCondition('entity_type', 'node')
      ->propertyCondition('type', 'stukowin')
      ->propertyCondition($sFieldtype, $sFieldcontent)
      ->propertyCondition('status', 1)
      ->range(0,1)
      ->execute();
    }
    if (!empty($aEntities['node'])) 
    {
      $aArray = array_keys($aEntities['node']);
      $aArray = array_shift($aArray);
      $oNode = node_load($aArray);
      return $oNode->nid;
    }
    else return false;
  }
  
  /**
   * Parses Relationfield and tries to extract
   * @param string $sRelationfield
   */
  private function get_term_ids($sRelationfield)
  {
    $oHTML = str_get_html($sRelationfield);
    // Step 1: extract all LVA names from <li> and <a> fields
    $aLI = $oHTML->find('li');
    $aA = $oHTML->find('a');
    $aTitlesA = array();
    $aTitlesLI = array();
    foreach($aA as $oElement)
    {
      $aTitlesA[$oElement->plaintext] = trim($oElement->plaintext);
      $aLinks[$oElement->plaintext] = $oElement->href;
    }
    foreach($aLI as $oElement)
    {
      $aTitlesLI[$oElement->plaintext] = trim($oElement->plaintext);
    }
    $aTitles = array_merge($aTitlesA,$aTitlesLI);
    // Step 2: try to get lva id by title
    $aIDs = array();
    foreach($aTitles as $i=>$sTitle) 
    {
      $iNodeID = $this->find_nodeid_by_field ('title',$sTitle);
      if(!empty($iNodeID)) $aIDs[trim($i)] = $iNodeID;
    }
    // Step 3: extract all a href and parse for <span id="code">
    foreach($aTitlesA as $i=>$sTitle) 
    {
      if(empty($aIDs[$i]))
      {
        $sCode = $this->parse_link_term_code($aLinks[$i]);
        $iNodeID = $this->find_nodeid_by_field('code', $sCode);
        if(!empty($iNodeID)) $aIDs[trim($i)] = $iNodeID;
      }
    }
    return $aIDs;
  }
  
  /**
   * Test method to extract all values from voraussetzungen and process them without having to reload
   * all data from CEUS server
   */
  private function test_make_relations()
  {
    $oQuery = new EntityFieldQuery();
      $aEntities = $oQuery->entityCondition('entity_type', 'node')
      ->propertyCondition('type', 'stukowin')
      ->fieldCondition('voraussetzungen', 'value', 'NULL', '!=')
      ->propertyOrderBy('nid')
      ->execute();
    if (!empty($aEntities['node'])) 
    {
      $aArray = array_keys($aEntities['node']);
      $aNodes = node_load_multiple($aArray);
      $this->aRelations = array();
      foreach($aNodes as $iNodeID=>$oNode)
      {
        if(substr($oNode->voraussetzungen['und'][0]['value'],0,5) != 'keine')
        {
          $aVoraussetzungen = $oNode->voraussetzungen['und'][0]['value'];
          if(!empty($aVoraussetzungen)) $this->aRelations[$iNodeID] = $aVoraussetzungen;
        }
      }
    }
    $this->process_relations();
  }
  
  /**
   * Parses the voraussetzungen field an generates Relations for required and suggested courses
   * and stores them in the node
   */
  private function process_relations()
  {
    $aSuggested = array();
    $aRequired = array();
    foreach($this->aRelations as $iID=>$sRelationfield)
    {
      if(strtolower(substr($sRelationfield,0,9))== 'empfohlen') 
      {
        $aNodeIDs = $this->get_term_ids($sRelationfield);
        if(!empty($aNodeIDs)) $aSuggested[$iID] = $aNodeIDs;
      }
      else 
      {
        $aNodeIDs = $this->get_term_ids($sRelationfield);
        if(!empty($aNodeIDs)) $aRequired[$iID] = $aNodeIDs;
      }
    }
    foreach($aRequired as $iNodeID=>$aReqNodeID)
    {
      $aReqs = array();
      $aRid = array();
      foreach($aReqNodeID as $iReqNodeID)
      {
        $aEndpoints = array(array('entity_type' => 'node', 'entity_id' => $iNodeID),
                            array('entity_type' => 'node', 'entity_id' => $iReqNodeID));
        $oRelation = relation_create('voraussetzung',$aEndpoints);
        $aRid[] = relation_save($oRelation);        
      }
      if(!empty($aRid)) 
      {
        $oNode = node_load($iNodeID);
        $oNode->voraussetzung['und'][0]['value']= $aRid;
        node_save($oNode);
      }
    }
    foreach($aSuggested as $iNodeID=>$aReqNodeID)
    {
      $aReqs = array();
      $aRid = array();
      foreach($aReqNodeID as $iReqNodeID)
      {
        $aEndpoints = array(array('entity_type' => 'node', 'entity_id' => $iNodeID),
                            array('entity_type' => 'node', 'entity_id' => $iReqNodeID));
        $oRelation = relation_create('empfehlung',$aEndpoints);
        $iRid[] = relation_save($oRelation);
      }
      if(!empty($aRid)) 
      {
        $oNode = node_load($iNodeID);
        $oNode->empfehlung['und'][0]['value']= $aRid;
        node_save($oNode);
      }
    }
  }
  
  /**
   * Recursive Method that traverses the tree of LVA ids that have been returned by CEUS
   * and reads the detail dataset for each node
   * A taxonomy term is being created for that item in the taxonomy tree
   * 
   * @param array $aTree Current subtree
   * @param integer $iParentID tid of parent term
   * @param integer $iCurriculumID CEUS-ID of curriculum
   * @return string|boolean
   */
  private function get_details($aTree, $iParentID, $iCurriculumID)
  {
    if(is_array($aTree) && count($aTree))
    {
      $iWeight = 0;
      $aTerms = taxonomy_get_tree($this->aVocabulary[$iCurriculumID]);
      foreach($aTerms as $oCurrTerm)
      {
        $aCurrTerms[$oCurrTerm->description] = $oCurrTerm;
      }
      foreach($aTree as $aBranch)
      {
        $aDetail = $this->get_detail($aBranch['id']);
        // Create new term if not in vocabulary
        $oTerm = new stdClass(); 
        if(!empty($aCurrTerms[$aDetail['de']['id']])) 
        {
          $oTerm->tid = $aCurrTerms[$aDetail['de']['id']]->tid;
        }
        $sLvtyp = empty($aDetail['de']['lvtypshort']) ? '' : " ({$aDetail['de']['lvtypshort']})";
        $oTerm->name = $aDetail['de']['typename'].': '.$aDetail['de']['title'] . $sLvtyp;
        $oTerm->description = $aDetail['de']['id'];
        $oTerm->vid = $this->aVocabulary[$iCurriculumID];
        $oTerm->parent = $iParentID;
        $oTerm->weight = $iWeight;
        taxonomy_term_save($oTerm);
        $iNodeID = $this->save_node($aDetail, $oTerm->tid);       
        if(!empty($aDetail['de']['voraussetzungen']) && $this->has_relation($aDetail['de']['voraussetzungen'])) $this->aRelations[$iNodeID] = $aDetail['de']['voraussetzungen'];
        $iWeight++;
          
        $this->get_details($aBranch['subtree'], $oTerm->tid, $iCurriculumID);
      }
      return true;
    }
    else return false;
  }
  
  
  /**
   * Main method: get current curriculum data from CEUS
   * 
   * @return string|boolean
   */
  public function get_curricula()
  {
    if($aCurriculaList = $this->get_curricula_list())
    {
      foreach($aCurriculaList as $aCurriculum)
      {
        $this->check_vocabulary($aCurriculum);
        $aCurrTree[$aCurriculum['id']] = $this->get_curriculum($aCurriculum['id']);
        $this->get_details($aCurrTree[$aCurriculum['id']]['tree'], 0, $aCurriculum['id']);
        $this->process_relations();
        
      }
      return 'Import successful';
    }
    else return false;
  }


  /**
   * Checks if a taxonomy vocabulary for the curriculum exists and creates it if not
   * @param array $aCurriculum Curriculum entry from CEUS
   */
  private function check_vocabulary($aCurriculum)
  {
    $oVocabulary = taxonomy_vocabulary_load(variable_get('ceus_importer_'.$aCurriculum['id'].'_vocabulary', 0)); 
    if (!$oVocabulary) 
    { 
      $aEdit = array( 
      'name' => $aCurriculum['name'] . ', ' . $aCurriculum['type'] . ' ' . $aCurriculum['version'], 
      'machine_name' => 'curriculum_'.$aCurriculum['id'], 
      'description' => $aCurriculum['name'] . ', ' . $aCurriculum['type'] . ' ' . $aCurriculum['version'], 
      'hierarchy' => 1, 
      'module' => 'ceus_importer', 
      'weight' => 10
      ); 
      $oVocabulary = (object) $aEdit; 
      $oVocabulary->{'faculty'}['und'][0]['value'] = $aCurriculum['faculty'];
      $oVocabulary->{'version'}['und'][0]['value'] = $aCurriculum['version'];
      $oVocabulary->{'currtype'}['und'][0]['value'] = $aCurriculum['type'];
      taxonomy_vocabulary_save($oVocabulary); 
      variable_set('ceus_importer_'.$aCurriculum['id'].'_vocabulary', $oVocabulary->vid);
    } 
    $this->aVocabulary[$aCurriculum['id']] = $oVocabulary->vid;
  }
  
  /**
   * Get list of all curricula (bachelor, master) from CEUS
   * @return array
   */
  private function get_curricula_list()
  {
    $sReturn = file_get_contents($this->sCeusUrl.'/'.$this->aFiles['LIST'].'?authtoken='.$this->sAuthtoken);
    return $this->check_return_value($sReturn);
  }
  
  /**
   * Get curriculum tree from CEUS
   * 
   * @param integer $iID CEUS-ID of curriculum
   * @return array multidimensional array of lva ids
   */
  private function get_curriculum($iID)
  {
    $sReturn = file_get_contents($this->sCeusUrl.'/'.$this->aFiles['CURR'].'?id='.$iID.'&authtoken='.$this->sAuthtoken);
    return $this->check_return_value($sReturn);
  }
  
  /**
   * Returns last error message
   * 
   * @return string
   */
  public function get_error() {
    return $this->sError;
  }
}