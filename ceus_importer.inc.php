<<<<<<< HEAD
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
   * @var string Username for CEUS APIget as retreived from Module configuration 
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
  private function find_termid_by_field($sFieldtype, $sFieldcontent)
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
      return $oNode->term['und'][0]['tid'];
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
      $aTitlesA[$oElement->plaintext] = $oElement->plaintext;
      $aLinks[$oElement->plaintext] = $oElement->href;
    }
    foreach($aLI as $oElement)
    {
      $aTitlesLI[$oElement->plaintext] = $oElement->plaintext;
    }
    $aTitles = array_merge($aTitlesA,$aTitlesLI);
    // Step 2: try to get lva id by title
    $aIDs = array();
    foreach($aTitles as $i=>$sTitle) 
    {
      $iLVA = $this->find_termid_by_field ('title',$sTitle);
      $aIDs[$i] = $iLVA;
    }
    // Step 3: extract all a href and parse for <span id="code">
    foreach($aTitlesA as $i=>$sTitle) 
    {
      if(empty($aIDs[$i]))
      {
        $sCode = $this->parse_link_term_code($aLinks[$i]);
        $aIDs[$i] = $this->find_termid_by_field('code', $sCode);
      }
    }
    return $aIDs;
  }
  
  
  private function process_relations()
  {
    foreach($this->aRelations as $iID=>$sRelationfield)
    {
      $aBlub[$iID] = $this->get_term_ids($sRelationfield);
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
        if(!empty($aDetail['de']['voraussetzungen']) && $this->has_relation($aDetail['de']['voraussetzungen'])) $this->aRelations[$aDetail['de']['id']] = $aDetail['de']['voraussetzungen'];
        $sLvtyp = empty($aDetail['de']['lvtypshort']) ? '' : " ({$aDetail['de']['lvtypshort']})";
        $oTerm->name = $aDetail['de']['typename'].': '.$aDetail['de']['title'] . $sLvtyp;
        $oTerm->description = $aDetail['de']['id'];
        $oTerm->vid = $this->aVocabulary[$iCurriculumID];
        $oTerm->parent = $iParentID;
        $oTerm->weight = $iWeight;
        taxonomy_term_save($oTerm);
        $this->save_node($aDetail, $oTerm->tid);       
        $iWeight++;
          
        $this->get_details($aBranch['subtree'], $oTerm->tid, $iCurriculumID);
      }
      return "bla";
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
//        $this->process_relations();
        
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
=======
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
  private function find_termid_by_field($sFieldtype, $sFieldcontent)
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
      return $oNode->term['und'][0]['tid'];
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
      $aTitlesA[$oElement->plaintext] = $oElement->plaintext;
      $aLinks[$oElement->plaintext] = $oElement->href;
    }
    foreach($aLI as $oElement)
    {
      $aTitlesLI[$oElement->plaintext] = $oElement->plaintext;
    }
    $aTitles = array_merge($aTitlesA,$aTitlesLI);
    // Step 2: try to get lva id by title
    $aIDs = array();
    foreach($aTitles as $i=>$sTitle) 
    {
      $iLVA = $this->find_termid_by_field ('title',$sTitle);
      $aIDs[$i] = $iLVA;
    }
    // Step 3: extract all a href and parse for <span id="code">
    foreach($aTitlesA as $i=>$sTitle) 
    {
      if(empty($aIDs[$i]))
      {
        $sCode = $this->parse_link_term_code($aLinks[$i]);
        $aIDs[$i] = $this->find_termid_by_field('code', $sCode);
      }
    }
    return $aIDs;
  }
  
  
  private function process_relations()
  {
    foreach($this->aRelations as $iID=>$sRelationfield)
    {
      $aBlub[$iID] = $this->get_term_ids($sRelationfield);
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
        if(!empty($aDetail['de']['voraussetzungen']) && $this->has_relation($aDetail['de']['voraussetzungen'])) $this->aRelations[$aDetail['de']['id']] = $aDetail['de']['voraussetzungen'];
        $sLvtyp = empty($aDetail['de']['lvtypshort']) ? '' : " ({$aDetail['de']['lvtypshort']})";
        $oTerm->name = $aDetail['de']['typename'].': '.$aDetail['de']['title'] . $sLvtyp;
        $oTerm->description = $aDetail['de']['id'];
        $oTerm->vid = $this->aVocabulary[$iCurriculumID];
        $oTerm->parent = $iParentID;
        $oTerm->weight = $iWeight;
        taxonomy_term_save($oTerm);
        $this->save_node($aDetail, $oTerm->tid);       
        $iWeight++;
          
        $this->get_details($aBranch['subtree'], $oTerm->tid, $iCurriculumID);
      }
      return "bla";
    }
    else return false;
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
//        $this->process_relations();
        
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
>>>>>>> refs/remotes/origin/master
}
