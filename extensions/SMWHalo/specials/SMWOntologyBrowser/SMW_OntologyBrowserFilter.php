<?php
/*
 * Created on 23.04.2007
 *
 * Author: kai
 * 
 * SMWOntologyBrowserFilter provides advanced filtering methods for OntologyBrowser
 * 
 * FilterBrowsing means that the user may directly access segments of the semantic model,
 * no matter where they are or if they are expanded. 
 * Thus, some methods are needed to expand the relevant part of the model.
 * 
 * All methods return XML as results which can be directly rendered by the client browser.
 */
 require_once("SMW_OntologyBrowserXMLGenerator.php");
 
 /**
  * SMWOntologyBrowserFilter provides global search mechanisms to access the semantic model. 
  * It returns XML data which can directly be displayed in the OntologyBrowser. 
  */
 class SMWOntologyBrowserFilter {
 	
 	/**
 	 * Filters for categories containg the given hint as substring (case-insensitive)
 	 * Returns the category tree from root to this found entities as xml string.
 	 * 
 	 * @return xml string (category tree)
 	 */
 	 function filterForCategories($categoryHint) {
 	 	
 	 	$reqfilter = new SMWRequestOptions();
 	 	$reqfilter->sort = true;
 		//$reqfilter->limit = MAX_RESULTS;
 		foreach($categoryHint as $hint) { 
 			$reqfilter->addStringCondition($hint, SMW_STRCOND_MID);
 		}
 		$reqfilter->isCaseSensitive = false;
 	 	$foundCategories = smwfGetSemanticStore()->getPages(array(NS_CATEGORY), $reqfilter);
 	 	
 	 	return $this->getCategoryTree($foundCategories);	 	
 	 }
 	 
 	 /**
 	  * Returns the category tree of all categories the given article is instance of.
 	  * 
 	  * @param $articleTitle article title
 	  * @return xml string (category tree)
 	  */
 	 function filterForCategoriesWithInstance(Title $articleTitle, $reqfilter) {
 	 	$categories = smwfGetSemanticStore()->getCategoriesForInstance($articleTitle, $reqfilter);
 	 	return $this->getCategoryTree($categories);	
 	 }
 	 
 	 /**
 	  * Returns the category tree of all categories the given property has a domain of.
 	  * 
 	  * @param $propertyTitle property title
 	  * @return xml string (category tree)
 	  */
 	 function filterForCategoriesWithProperty(Title $propertyTitle, $reqfilter) {
 	 	$categories = $this->getDomainCategories($propertyTitle, $reqfilter);
 	    return $this->getCategoryTree($categories);	
 	 }
 	 
 	 /**
 	 * Filters for instances containg the given hint as substring (case-insensitive)
 	 * Returns an instance list with all entities found 
 	 * 
 	 * @return xml string 
 	 */
 	 function filterForInstances($instanceHint) {
 	 	$reqfilter = new SMWRequestOptions();
 	 	$reqfilter->sort = true;
 		//$reqfilter->limit = MAX_RESULTS;
 		foreach($instanceHint as $hint) { 
 			$reqfilter->addStringCondition($hint, SMW_STRCOND_MID);
 		}
 		
 		$reqfilter->isCaseSensitive = false;
 	 	$foundInstances = smwfGetSemanticStore()->getPages(array(NS_MAIN), $reqfilter);
 	 	$result = "";
 	 	foreach($foundInstances as $instance) {
 	 		$title_esc = preg_replace("/\"/", "&quot;", $instance->getDBkey());
 	 		$result .= "<instance title=\"".$title_esc."\" img=\"$type.gif\" id=\"ID_$id$count\"/>";
 	 	}
	 	return $result == '' ? "<instanceList isEmpty=\"true\" textToDisplay=\"".wfMsg('smw_ob_no_instances')."\"/>"  : '<instanceList>'.$result.'</instanceList>';
 	 }
 	 
 	 /*function filterForInstancesUsingProperty(Title $property) {
 	 	 $instances = smwfGetStore()->getAllRelationSubjects($property);
 	 	 $result = "";
 	 	 foreach($instances as $instance) {
 	 		$result .= "<instance title=\"".$instance->getDBkey()."\" img=\"$type.gif\" id=\"ID_$id$count\"/>";
 	 	}
	 	return '<instanceList>'.$result.'</instanceList>';
 	 }*/
 	 
 	 /**
 	 * Filters for attribute containg the given hint as substring (case-insensitive)
 	 * Returns the attribute tree from root to this found entities as xml string.
 	 * 
 	 * @return xml string (attribute tree)
 	 */
 	 function filterForPropertyTree($attributeHint) {
 	 	$reqfilter = new SMWRequestOptions();
 	 	$reqfilter->sort = true;
 		//$reqfilter->limit = MAX_RESULTS;
 		foreach($attributeHint as $hint) { 
 			$reqfilter->addStringCondition($hint, SMW_STRCOND_MID);
 		}
 		
 		$reqfilter->isCaseSensitive = false;
 	 	$foundAttributes = smwfGetSemanticStore()->getPages(array(SMW_NS_PROPERTY), $reqfilter);
 	 	
 	 	// create root object
 	 	$root = new TreeObject(null);
 	 	
 	 	// get all paths to the root
 	 	$allPaths = array();
 	 	foreach($foundAttributes as $cat) {
 	 		$init_path = array();
 	 		$this->getAllPropertyPaths($cat, $init_path, $allPaths);
 	 	}
 	 	
 	 	// reverse paths
 	 	$reversedPaths = array();
 	 	foreach($allPaths as $path) {
 	 		$reversedPaths[] = array_reverse($path);
 	 	}
 	 		
 	 	// build tree of TreeObjects	
 	 	foreach($reversedPaths as $path) {
 	 		$node = $root;
 	 		foreach($path as $c) {
 	 			$node = $node->addChild($c);
 	 		}
 	 	}
 	 	
 	 	// sort first level
 	 	$root->sortChildren();
 	 	
 	 	// serialize tree as XML
 	 	$serializedXML = $root->serializeAsXML('propertyTreeElement');
 	 	return $serializedXML == '' ? "<result isEmpty=\"true\" textToDisplay=\"".wfMsg('smw_ob_no_attributes')."\"/>" : '<result>'.$serializedXML.'</result>'; 
	 	
 	 }
 	 
 	
 	 
 	/**
 	 * Filters for properties containg the given hint as substring (case-insensitive)
 	 * Returns an property list with all entities found 
 	 * 
 	 * @return xml string 
 	 */
 	  function filterForProperties($propertyHint) {
 	 	$reqfilter = new SMWRequestOptions();
 	 	$reqfilter->sort = true;
 		//$reqfilter->limit = MAX_RESULTS;
 		foreach($propertyHint as $hint) { 
 			$reqfilter->addStringCondition($hint, SMW_STRCOND_MID);
 		}
 		
 		$reqfilter->isCaseSensitive = false;
 	 	$foundProperties = smwfGetSemanticStore()->getPages(array(SMW_NS_ATTRIBUTE, SMW_NS_RELATION), $reqfilter);
 	 	return SMWOntologyBrowserXMLGenerator::encapsulateAsPropertyList($foundProperties, array());
 	 }
 	 
 	 /**
 	  * Returns the category tree for the given array of categories.
 	  */
 	 private function getCategoryTree($categories) {
 	 	// create root object
 	 	$root = new TreeObject(null);
 	 	
 	 	// get all paths to the root
 	 	$allPaths = array();
 	 	foreach($categories as $cat) {
 	 		$init_path = array();
 	 		$this->getAllCategoryPaths($cat, $init_path, $allPaths);
 	 	}
 	 	
 	 	// reverse paths
 	 	$reversedPaths = array();
 	 	foreach($allPaths as $path) {
 	 		$reversedPaths[] = array_reverse($path);
 	 	}
 	 		
 	 	// build tree of TreeObjects	
 	 	foreach($reversedPaths as $path) {
 	 		$node = $root;
 	 		foreach($path as $c) {
 	 			$node = $node->addChild($c);
 	 		}
 	 	}
 	 	
 	 	// sort first level
 	 	$root->sortChildren();
 	 	
 	 	// serialize tree as XML
 	 	$serializedXML = $root->serializeAsXML('conceptTreeElement');
 	 	return $serializedXML == '' ? "<result isEmpty=\"true\" textToDisplay=\"".wfMsg('smw_ob_no_categories')."\"/>"  : '<result>'.$serializedXML.'</result>'; 
 	 }
 	 
 	 /**
 	  * Returns all domain categories for a given property.
 	  */
 	 private function getDomainCategories($propertyTitle, $reqfilter) {
 	 	$domainRelation = smwfGetSemanticStore()->domainHintRelation;
 	    $categories = smwfGetStore()->getPropertyValues($propertyTitle, $domainRelation, $reqfilter);
 	    $result = array();
 	    foreach($categories as $value) {
 	    	if ($value instanceof SMWWikiPageValue) $result[] = $value->getTitle();
 	    }
 	    return $result;
 	 }
 	 /**
 	  * Detrmines all category paths from root to the given entity.
 	  * May be more than one in case of multiple inheritance.
 	  * 
 	  * @param $cat The category to determine path for
 	  * @param $path Must be an empty array
 	  * @param $allPaths Must be an empty array 
 	  */
 	 private function getAllCategoryPaths($cat, & $path, & $allPaths) {
    	 $path[] = $cat;
 	 	 $superCats = smwfGetSemanticStore()->getDirectSuperCategories($cat);
 	 	 foreach($superCats as $superCat) {
 	 	 	$cloneOfPath = array_clone($path);
 	 	 	$this->getAllCategoryPaths($superCat, $cloneOfPath, $allPaths);
 	 	 }
         if (count($superCats) == 0) $allPaths[] = $path;
 	 }
 	 
 	  /**
 	  * Detrmines all attribute paths from root to the given entity.
 	  * May be more than one in case of multiple inheritance.
 	  * 
 	  * @param $cat The category to determine path for
 	  * @param $path Must be an empty array
 	  * @param $allPaths Must be an empty array 
 	  */
 	 private function getAllPropertyPaths($att, & $path, & $allPaths) {
    	 $path[] = $att;
 	 	 $superCats = smwfGetSemanticStore()->getDirectSuperProperties($att);
 	 	 foreach($superCats as $superCat) {
 	 	 	$cloneOfPath = array_clone($path);
 	 	 	$this->getAllCategoryPaths($superCat, $cloneOfPath, $allPaths);
 	 	 }
         if (count($superCats) == 0) $allPaths[] = $path;
 	 }
 	 
 	 
 	 
 	
 }
 
 /**
  * TreeObject represents a node in a tree. 
  * It can have other TreeObjects as children.
  * 
  * A tree can be serialized as XML. 
  */
 class TreeObject {
 	private $title;
 	private $children;
 	
 	 public function __construct($title) {
 		$this->title = $title;
 		$this->children = array();
 	}
 	
 	public function getTitle() {
 		return $this->title;
 	}
 	
 	/**
 	 * Returns the children of the node.
 	 */
 	public function getChildren() {
 		return $this->children;
 	}
 	
 	/**
 	 * Adds a child node if it does not already exist.
 	 */
 	public function addChild($childTitle) {
 		if (!array_key_exists($childTitle->getText(), $this->children)) {
 			$this->children[$childTitle->getText()] = new TreeObject($childTitle);
 		} 
 		return $this->children[$childTitle->getText()];
 	}
 	
 	/**
 	 * Serializes the tree structure (without root node)
 	 */
 	public function serializeAsXML($type) {
 		$id = uniqid (rand());
		$count = 0;		
 		$result = "";
 		foreach($this->children as $title => $treeObject) {
 			$isExpanded = count($treeObject->children) == 0 ? "false" : "true";
 			$title_esc = preg_replace("/\"/", "&quot;", $treeObject->getTitle()->getDBkey());
			$result .= "<$type title=\"".$title_esc."\" img=\"$type.gif\" id=\"ID_$id$count\" expanded=\"$isExpanded\">";
 			$result .= $treeObject->serializeAsXML($type);
 			$result .= "</$type>";
			$count++;
 		}
 		return $result;
 	}
 	
 	/**
 	 * Sorts the children (possibly recursive!)
 	 */
 	public function sortChildren($recursive = true) {
 		if ($recursive) { 
 			$this->_sortChildren($this);
 		} else {
 			ksort($this->children);
 		}
 	}
 	
 	private function _sortChildren($treeObject) {
 		foreach($treeObject->children as $title => $to) {
 			$this->_sortChildren($to);
 		}
		ksort($treeObject->children);
 	}
 	
 }
 
 /**
  * Makes a shallow copy of the given source array.
  */
 function array_clone(& $src) {
 	$dst = array();
 	foreach($src as $e) {
 		$dst[] = $e;
 	}
 	return $dst;
 }
?>
