<?php
/*
 * Created on 18.06.2007
 *
 * Author: kai
 */
 global $smwgHaloIP;
 require_once("$smwgHaloIP/specials/SMWGardening/SMW_GardeningBot.php");
 require_once("$smwgHaloIP/specials/SMWGardening/SMW_ParameterObjects.php");
 
 

 
 class MissingAnnotationsBot extends GardeningBot {
 	
 	
 	function MissingAnnotationsBot() {
 		parent::GardeningBot("smw_missingannotationsbot");
 		
 	}
 	
 	public function getHelpText() {
 		return wfMsg('smw_gard_missingannot_docu');
 	}
 	
 	public function getLabel() {
 		return wfMsg($this->id);
 	}
 	
 	public function allowedForUserGroups() {
 		return array(SMW_GARD_GARDENERS, SMW_GARD_SYSOPS, SMW_GARD_ALL_USERS);
 	}
 	
 	/**
 	 * Returns an array of parameter objects
 	 */
 	public function createParameters() {
 		$param1 = new GardeningParamString('MA_PART_OF_NAME', wfMsg('smw_gard_missingannot_titlecontaining'), SMW_GARD_PARAM_OPTIONAL);
 		$param2 = new GardeningParamTitle('MA_CATEGORY_RESTRICTION', wfMsg('smw_gard_missingannot_restricttocategory'), SMW_GARD_PARAM_OPTIONAL);
 		$param2->setAutoCompletion(true);
 		$param2->setTypeHint(NS_CATEGORY);
 		return array($param1, $param2);
 	}
 	
 	/**
 	 * Do consistency checks and return a log as wiki markup.
 	 * Do not use echo when it is not running asynchronously.
 	 */
 	public function run($paramArray, $isAsync, $delay) {
 		$gi_store = SMWGardening::getGardeningIssuesAccess();
 		if (!$isAsync) {
 			echo 'Missing annotations bot should not be run synchronously! Abort bot.'; // do not externalize
 			return;
 		}
 		echo $this->getBotID()." started!\n";
 		$term = $paramArray['MA_PART_OF_NAME'];
 		$categoryRestriction = urldecode($paramArray['MA_CATEGORY_RESTRICTION']);
 		$notAnnotatedPages = array();
 		
 		echo "Checking for pages without annotations...\n";
 		if ($categoryRestriction == '') {
       		$notAnnotatedPages = $this->getPagesWithoutAnnotations($term == '' ? NULL : $term, NULL);
 		} else {
 			$categories = explode(";", $categoryRestriction);
 			foreach($categories as $c) {
 				$categoryDB = str_replace(" ", "_", trim($c)); 
 				$notAnnotatedPages = array_merge($notAnnotatedPages, $this->getPagesWithoutAnnotations($term == '' ? NULL : $term, $categoryDB));
 			}
 		}
       	
       	foreach($notAnnotatedPages as $page) {
       		$gi_store->addGardeningIssueAboutArticle('smw_missingannotationsbot', SMW_GARDISSUE_NOTANNOTATED_PAGE, $page);
       		echo $page->getText()."\n";
       	}
        
        echo "done!\n\n";
 		return '';
 		
 	}
 	
 	/**
 	 * Returns not annotated pages matching the $term (substring matching) or
 	 * which are members of the subcategories of $category.
 	 */
 	private function getPagesWithoutAnnotations($term = NULL, $category = NULL) {
 		$db =& wfGetDB( DB_MASTER );
 		$smw_attributes = $db->tableName('smw_attributes');
	 	$smw_relations = $db->tableName('smw_relations');
	 	$smw_nary = $db->tableName('smw_nary');	
	 	$mw_page = $db->tableName('page');
	 	$categorylinks = $db->tableName('categorylinks');
	 		
		$result = array();
		if ($category == NULL) { 
			if ($term == NULL) {
				$sql = 'SELECT page_title, page_namespace FROM '.$mw_page.' p LEFT JOIN '.$smw_attributes.' a ON a.subject_title=p.page_title ' .
																	 'LEFT JOIN '.$smw_relations.' r ON r.subject_title=p.page_title ' .
																	 'LEFT JOIN '.$smw_nary.' na ON na.subject_id=p.page_id ' .
																	
						'WHERE p.page_namespace = '.NS_MAIN.' AND a.subject_title IS NULL AND r.subject_title IS NULL AND na.subject_id IS NULL'; 
			} else {
				$sql = 'SELECT page_title, page_namespace FROM '.$mw_page.' p LEFT JOIN '.$smw_attributes.' a ON a.subject_title=p.page_title ' .
																	 'LEFT JOIN '.$smw_relations.' r ON r.subject_title=p.page_title ' .
																	 'LEFT JOIN '.$smw_nary.' na ON na.subject_id=p.page_id ' .
																	
						'WHERE p.page_namespace = '.NS_MAIN.' AND a.subject_title IS NULL AND r.subject_title IS NULL AND na.subject_id IS NULL AND page_title LIKE \'%'.$term.'%\'';
			}                 
			$res = $db->query($sql);
		
			if($db->numRows( $res ) > 0) {
				while($row = $db->fetchObject($res)) {
					
					$result[] = Title::newFromText($row->page_title, $row->page_namespace);
					
				}
			}
		
			$db->freeResult($res);
		} else {
			$categoryTitle = Title::newFromText($category, NS_CATEGORY);
			$subCats = $this->getSubCategories($categoryTitle);
			$subCats[] = $categoryTitle; // add super category title too
			foreach($subCats as $subCat) {
				if ($term == NULL) {
					$sql = 'SELECT page_title, page_namespace FROM '.$categorylinks.' c, '.$mw_page.' p LEFT JOIN '.$smw_attributes.' a ON a.subject_title=p.page_title ' .
																	 'LEFT JOIN '.$smw_relations.' r ON r.subject_title=p.page_title ' .
																	 'LEFT JOIN '.$smw_nary.' na ON na.subject_id=p.page_id ' .
																	
						'WHERE p.page_namespace = '.NS_MAIN.' AND a.subject_title IS NULL AND r.subject_title IS NULL AND na.subject_id IS NULL AND p.page_id = c.cl_from AND cl_to = '.$db->addQuotes($subCat->getDBkey());
				 	
				} else {
						$sql = 'SELECT page_title, page_namespace FROM '.$categorylinks.' c, '.$mw_page.' p LEFT JOIN '.$smw_attributes.' a ON a.subject_title=p.page_title ' .
																	 'LEFT JOIN '.$smw_relations.' r ON r.subject_title=p.page_title ' .
																	 'LEFT JOIN '.$smw_nary.' na ON na.subject_id=p.page_id ' .
																	 
						'WHERE p.page_namespace = '.NS_MAIN.' AND a.subject_title IS NULL AND r.subject_title IS NULL AND na.subject_id IS NULL AND p.page_id = c.cl_from AND cl_to = '.$db->addQuotes($subCat->getDBkey()).' AND page_title LIKE \'%'.$term.'%\'';
						
				}
				$res = $db->query($sql);
			
				if($db->numRows( $res ) > 0) {
					while($row = $db->fetchObject($res)) {
						$result[] = Title::newFromText($row->page_title, $row->page_namespace);
					}
				}
		
				$db->freeResult($res);
			}
		}
		return $result;
 	}
 	
 	private function getNamespaceText($page) {
 		global $smwgContLang, $wgLang;
 		$nsArray = $smwgContLang->getNamespaces();
 		if ($page->getNamespace() == NS_TEMPLATE || $page->getNamespace() == NS_CATEGORY) {
 			$ns = $wgLang->getNsText($page->getNamespace());
 				} else { 
 			$ns = $page->getNamespace() != NS_MAIN ? $nsArray[$page->getNamespace()] : "";
 		}
 		return $ns;
 	}
 	
 	private function getDirectSubCategories(Title $categoryTitle, $requestoptions = NULL) {
		$result = "";
		$db =& wfGetDB( DB_MASTER );
		$sql = 'page_namespace=' . NS_CATEGORY .
			   ' AND cl_to =' . $db->addQuotes($categoryTitle->getDBkey()) . ' AND cl_from = page_id';

		$res = $db->select(  array($db->tableName('page'), $db->tableName('categorylinks')), 
		                    'page_title',
		                    $sql, 'SMW::getDirectSubCategories', NULL );
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->page_title, NS_CATEGORY);
			}
		}
		$db->freeResult($res);
		return $result;
	}
	
	private function getSubCategories(Title $category) {
		$subCategories = $this->getDirectSubCategories($category);
		$result = array();
		foreach($subCategories as $subCat) {
			$result = array_merge($result, $this->getDirectSubCategories($subCat));
		}
		return array_merge($result, $subCategories);
	}
 }
 
 new MissingAnnotationsBot();
 
 define('SMW_NOTANNOTATED_BOT_BASE', 500);
 define('SMW_GARDISSUE_NOTANNOTATED_PAGE', SMW_NOTANNOTATED_BOT_BASE * 100 + 1);
 
 class MissingAnnotationsBotIssue extends GardeningIssue {
 	
 	public function __construct($bot_id, $gi_type, $t1_ns, $t1, $t2_ns, $t2, $value) {
 		parent::__construct($bot_id, $gi_type, $t1_ns, $t1, $t2_ns, $t2, $value);
 	}
 	
 	protected function getTextualRepresenation(& $skin,  $text1, $text2) {
 		
		switch($this->gi_type) {
			case SMW_GARDISSUE_NOTANNOTATED_PAGE:
				return wfMsg('smw_gardissue_notannotated_page', $text1);
			default: return NULL;
		}
 	}
 }
 
 class MissingAnnotationsBotFilter extends GardeningIssueFilter {
 	 	
 	
 	public function __construct() {
 		parent::__construct(SMW_NOTANNOTATED_BOT_BASE);
 		$this->gi_issue_classes = array(wfMsg('smw_gardissue_class_all'));
 	}
 	
 	public function getUserFilterControls($specialAttPage, $request) {
		return '';
	}
	
	
	public function getData($options, $request) {
		
		return parent::getData($options, $request);
	}
 }
?>
