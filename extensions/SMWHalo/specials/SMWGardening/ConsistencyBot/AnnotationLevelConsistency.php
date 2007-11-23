<?php
/*
 * Created on 23.05.2007
 *
 * Author: kai
 */
global $smwgHaloIP;
require_once("GraphEdge.php"); 
require_once("$smwgHaloIP/includes/SMW_GraphHelper.php"); 

 class AnnotationLevelConsistency {
 	 	
 	private $bot;
 	private $delay; 
 	
 	// Category/Property Graph. It is cached for the whole consistency checks.
 	private $categoryGraph;
 	private $propertyGraph;
 	
 	// GardeningIssue store
 	private $gi_store;
 	
 	// Important: Attribute values (primitives) are always syntactically 
 	// correct when they are in the database. So only relations
 	// will be checked.
 	
 	public function AnnotationLevelConsistency(& $bot, $delay) {
 		$this->bot = $bot;
 		$this->delay = $delay;
 		
 		$this->categoryGraph = smwfGetSemanticStore()->getCategoryInheritanceGraph();
 		$this->propertyGraph = smwfGetSemanticStore()->getPropertyInheritanceGraph();
 		$this->gi_store = SMWGardening::getGardeningIssuesAccess();
 	}
 	/**
 	 * Checks if property annotations uses schema consistent values
 	 */
 	public function checkPropertyAnnotations() {
 		global $smwgContLang;
 	 		
 		$properties = smwfGetSemanticStore()->getPages(array(SMW_NS_PROPERTY), NULL, true);
 		
 		$work = count($properties);
 		$cnt = 0;
 		print "\n";
 		$this->bot->addSubTask(count($properties));
 		foreach($properties as $r) {
 			if ($this->delay > 0) {
 				usleep($this->delay);
 			}
 			$this->bot->worked(1);
 			$cnt++;
 			if ($cnt % 10 == 1 || $cnt == $work) { 
 				print "\x08\x08\x08\x08".number_format($cnt/$work*100, 0)."% ";
 			}
 			
 			if (smwfGetSemanticStore()->domainRangeHintRelation->equals($r) 
 					|| smwfGetSemanticStore()->minCard->equals($r) 
 					|| smwfGetSemanticStore()->maxCard->equals($r)
 					|| smwfGetSemanticStore()->inverseOf->equals($r)) {
 						// ignore builtin properties
 						continue;
 			}
 			
 			// get domain and range categories of property
 			$domainRangeAnnotations = smwfGetStore()->getPropertyValues($r, smwfGetSemanticStore()->domainRangeHintRelation);
 			
 			
 			if (empty($domainRangeAnnotations)) {
 				// if there are no range categories defined, try to find a super relation with defined range categories
 				$domainRangeAnnotations = smwfGetSemanticStore()->getDomainsAndRangesOfSuperProperty($this->propertyGraph, $r);
 			}
 			
 			if (empty($domainRangeAnnotations)) {
 				// if it's still empty, there's no domain or range defined at all. In this case, simply skip it in order not to pollute the consistency log.
 				continue;
 			}
 			
 			// get annotation subjects for the property.
 			$allRelationSubjects = smwfGetStore()->getAllPropertySubjects($r);
 			
 			// check domain only once.
 			$domainChecked = false;
 			
 			// iterate over all property subjects
 			foreach($allRelationSubjects as $subject) { 
 				
 				if ($subject == null) {
 					continue;
 				}
 				
 				$categoriesOfSubject = smwfGetSemanticStore()->getCategoriesForInstance($subject);
 				
 				list($domain_cov_results, $domainCorrect) = $this->checkDomain($categoriesOfSubject, $domainRangeAnnotations);
				if (!$domainCorrect) {
					$this->gi_store->addGardeningIssueAboutArticles($this->bot->getBotID(), SMW_GARDISSUE_WRONG_DOMAIN_VALUE, $subject, $r );
				}
				
 				// get property value for a given instance
 				$relationTargets = smwfGetStore()->getPropertyValues($subject, $r);
 				
 				foreach($relationTargets as $target) {
 					
 					// decide which type and do consistency checks
 					if ($target instanceof SMWWikiPageValue) {  // binary relation 
 						$rd_target = smwfGetSemanticStore()->getRedirectTarget($target->getTitle());
	 					$categoriesOfObject = smwfGetSemanticStore()->getCategoriesForInstance($rd_target);
 						$rangeCorrect = $this->checkRange($domain_cov_results, $categoriesOfObject, $domainRangeAnnotations);
 						if (!$rangeCorrect) {
 							$this->gi_store->addGardeningIssueAboutArticles($this->bot->getBotID(), SMW_GARDISSUE_WRONG_TARGET_VALUE, $subject, $r, $rd_target != NULL ? $rd_target->getDBkey() : NULL);
 						}	 						
 						
 					} else if ($target instanceof SMWNAryValue) { // n-ary relation
 						
 								$explodedValues = $target->getDVs();
 								$explodedTypes = explode(";", $target->getDVTypeIDs());
 								//print_r($explodedTypes);
 								//get all range instances and check if their categories are subcategories of the range categories.
 								for($i = 0, $n = count($explodedTypes); $i < $n; $i++) {
 									if ($explodedValues[$i] == NULL) {
 										$this->gi_store->addGardeningIssueAboutArticles($this->bot->getBotID(), SMW_GARD_ISSUE_MISSING_PARAM, $subject, $r, $i);
 										
 									} else {
 										
 										if ($explodedValues[$i]->getTypeID() == '_wpg') { 
 											$rd_target = smwfGetSemanticStore()->getRedirectTarget($explodedValues[$i]->getTitle());
 											$categoriesOfObject = smwfGetSemanticStore()->getCategoriesForInstance($rd_target);
					 						$rangeCorrect = $this->checkRange($domain_cov_results, $categoriesOfObject, $domainRangeAnnotations);
					 						if (!$rangeCorrect) {
					 							$this->gi_store->addGardeningIssueAboutArticles($this->bot->getBotID(), SMW_GARDISSUE_WRONG_TARGET_VALUE, $subject, $r, $rd_target != NULL ? $rd_target->getDBkey() : NULL);
					 						}	
 										}
 									}
 								}
 						} else {
 							// Normally, one would check attribute values here, but they are always correctly validated during SAVE.
 							// Otherwise the annotation would not appear in the database. *Exception*: wrong units
 					
 														
					 		break; // always break the loop, because an attribute annotation is representative for all others.
 						}
 					
 									
 				} 
 			}
 		}
 		
 	}
 	
 	/**
 	 * Checks weather subject and object matches a domain/range pair.
 	 * 
 	 * @param subject Title
 	 * @param object Title
 	 * @param $domainRange SMWNaryValue
 	 */
 	private function checkRange($domain_cov_results, $categoriesOfObject, $domainRange) {
 		 		
 	
 		$result = false;
 		for($i = 0, $n = count($domainRange); $i < $n; $i++) {
 			if (!$domain_cov_results[$i]) continue;
 			$domRanVal = $domainRange[$i]; 
 			$rangeCorrect = false;
 			$dvs = $domRanVal->getDVs();
 				
 			$rangeCat  = $dvs[1] != NULL ? $dvs[1]->getTitle() : NULL;
 			
 			
 			if ($rangeCat == NULL) {
 				$rangeCorrect = true;
 			}
 			if ($rangeCat != NULL) {
 				// check range
 				
 				foreach($categoriesOfObject as $coo) {
 					$rangeCorrect |= (GraphHelper::checkForPath($this->categoryGraph, $coo->getArticleID(), $rangeCat->getArticleID()));
 					if ($rangeCorrect) break;
 				}
 			}
 		
 			$result |= $rangeCorrect;
 		}
 		return $result;
 	}
 	
 	/**
 	 * Checks weather subject matches a domain/range pair.
 	 */
 	private function checkDomain($categoriesOfSubject, $domainRange) {
 	
 		
 		
 		$results = array();
 		$domainCorrect = false;
 		foreach($domainRange as $domRanVal) { 
 			$domainCorrect = false;
 		
 			$dvs = $domRanVal->getDVs();
 			$domainCat = $dvs[0] != NULL ? $dvs[0]->getTitle() : NULL;	
 		
 			if ($domainCat == NULL) {
 				$domainCorrect = true;
 			}
 		
 			if ($domainCat != NULL){
 				//check domain
 				
 				foreach($categoriesOfSubject as $coi) {
 					$domainCorrect |= (GraphHelper::checkForPath($this->categoryGraph, $coi->getArticleID(), $domainCat->getArticleID()));
 					if ($domainCorrect) break;
 				}
 			}
 			$results[] = $domainCorrect;
 			$domainCorrect |= $domainCorrect;
 		}
 		return array($results, $domainCorrect);
 	}
 	
 	
 	/**
 	 * Checks if number of property appearances in articles are schema-consistent.
 	 */
 	public function checkAnnotationCardinalities() {
 		global $smwgContLang;
 		
 		// get all properties
 		$properties = smwfGetSemanticStore()->getPages(array(SMW_NS_PROPERTY), NULL, true);
 		$this->bot->addSubTask(count($properties));
 		foreach($properties as $a) {
 			if ($this->delay > 0) {
 				usleep($this->delay);
 			}
 			$this->bot->worked(1);
 			
 			// ignore builtin properties
 			if (smwfGetSemanticStore()->minCard->equals($a) 
 					|| smwfGetSemanticStore()->maxCard->equals($a)
 					|| smwfGetSemanticStore()->domainRangeHintRelation->equals($a) 
 					|| smwfGetSemanticStore()->inverseOf->equals($a)) {
 						continue;
 			}
 			
 			// get minimum cardinality
 			$minCardArray = smwfGetStore()->getPropertyValues($a, smwfGetSemanticStore()->minCard);
 			
 			if (empty($minCardArray)) {
 				// if it does not exist, get minimum cardinality from superproperty
 				$minCards = smwfGetSemanticStore()->getMinCardinalityOfSuperProperty($this->propertyGraph, $a);
 			} else {
 				// assume there's only one defined. If not it will be found in co-variance checker anyway
 				$minCards = $minCardArray[0]->getXSDValue() + 0;
 			}
 			
 			// get maximum cardinality
 			$maxCardsArray = smwfGetStore()->getPropertyValues($a, smwfGetSemanticStore()->maxCard);
 			
 			if (empty($maxCardsArray)) {
 				// if it does not exist, get maximum cardinality from superproperty
 				$maxCards = smwfGetSemanticStore()->getMaxCardinalityOfSuperProperty($this->propertyGraph, $a);
 				
 			} else {
 				// assume there's only one defined. If not it will be found in co-variance checker anyway
 				$maxCards = $maxCardsArray[0]->getXSDValue() + 0;
 			}
 			
 			if ($minCards == CARDINALITY_MIN && $maxCards == CARDINALITY_UNLIMITED) {
 				// default case: no check needed, so skip it.
 				continue;
 			}
 			
 			// get domains
 			$domainRangeAnnotations = smwfGetStore()->getPropertyValues($a, smwfGetSemanticStore()->domainRangeHintRelation);
 			 			
 			if (empty($domainRangeAnnotations)) {
 				// if there are no domain categories defined, try to find a super property with defined domain categories
 				$domainRangeAnnotations = smwfGetSemanticStore()->getDomainsAndRangesOfSuperProperty($this->propertyGraph, $a);
 			}
 			
 			// get redirects of property
 			$redirects = smwfGetSemanticStore()->getRedirectPages($a);
 			
 			foreach($domainRangeAnnotations as $domRan) {
 				$dvs = $domRan->getDVs();
 				if ($dvs[0] == NULL) continue; // ignore annotations with missing domain
 				$domainCategory = $dvs[0]->getTitle();
 				$instances = smwfGetSemanticStore()->getInstances($domainCategory);
 				
 				foreach($instances as $subject) { // check indirect instances
 					if ($subject[0] == null) {
 						continue;
	 				}
	 				
	 				// get all annoations for a subject and a property
	 				$allAttributeForSubject = smwfGetStore()->getPropertyValues($subject[0], $a);
	 				foreach($redirects as $rd) {
	 					$allAttributeForSubject = array_merge($allAttributeForSubject, smwfGetStore()->getPropertyValues($subject[0], $rd));
	 				}
	 				$num = count($allAttributeForSubject);
	 				
	 				// compare number of appearance with defined cardinality
	 				if ($num < $minCards) {
	 					if (!$this->gi_store->existsGardeningIssue($this->bot->getBotID(), SMW_GARDISSUE_TOO_LOW_CARD, NULL, $subject[0], $a)) {
	 						$this->gi_store->addGardeningIssueAboutArticles($this->bot->getBotID(), SMW_GARDISSUE_TOO_LOW_CARD, $subject[0], $a, $num);
	 					}
					} 
					if ($num > $maxCards) {
						if (!$this->gi_store->existsGardeningIssue($this->bot->getBotID(), SMW_GARDISSUE_TOO_HIGH_CARD, NULL, $subject[0], $a)) {
							$this->gi_store->addGardeningIssueAboutArticles($this->bot->getBotID(), SMW_GARDISSUE_TOO_HIGH_CARD, $subject[0], $a, $num);
						}
					}
 				}
 			}
 						
 		}
  		
 	}
 	
 	/**
 	 * Checks if all annotations with units have proper units (such defined by 'corresponds to' relations).
 	 */
 	public function checkUnits() {
 		// check attribute annotation cardinalities
 		$types = smwfGetSemanticStore()->getPages(array(SMW_NS_TYPE));
 		$this->bot->addSubTask(count($types));
 		foreach($types as $type) {
 			if ($this->delay > 0) {
 				usleep($this->delay);
 			}
 			$this->bot->worked(1);
 		
 			$units = smwfGetSemanticStore()->getDistinctUnits($type);
 			$conversion_factors = smwfGetStore()->getSpecialValues($type, SMW_SP_CONVERSION_FACTOR);
 			$si_conversion_factors = smwfGetStore()->getSpecialValues($type, SMW_SP_CONVERSION_FACTOR_SI);
 			
 			$correct_unit = false;
 			foreach($units as $u) {
 				if ($u == NULL) continue;
 				foreach($conversion_factors as $c) {
 					$correct_unit |= $this->unitMatches($u, $c);
 				}
 				foreach($si_conversion_factors as $c) {
 					$correct_unit |= $this->unitMatches($u, $c);
 				}
 			}
 			if (!$correct_unit) {
 				$annotations = smwfGetSemanticStore()->getAnnotationsWithUnit($type, $u);
 			
 				foreach($annotations as $a) {
 					$this->gi_store->addGardeningIssueAboutArticles($this->bot->getBotID(), SMW_GARDISSUE_WRONG_UNIT, $a[0], $a[1], $u);
 				}
 			}
 		}
 	}
 	
 	private function unitMatches($unit, $con_fac) {
 		$matches = array();
 		preg_match("/(\d*\.?\d+)(.*)/", $con_fac, $matches);
 		return (strtolower(trim($matches[2][0])) == strtolower($unit));
 	}
 }
?>
