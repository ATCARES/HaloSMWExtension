<?php
/*
 * Copyright (C) Vulcan Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program.If not, see <http://www.gnu.org/licenses/>.
 *
 */
/**
 *
 * @author Kai Kuehn
 *
 */
class SRFDeletePropertyOperation extends SRFRefactoringOperation {

	var $property;
	var $options;
	var $affectedPages;

	public function __construct($category, $options) {
		parent::__construct();
		$this->property = Title::newFromText($category, SMW_NS_PROPERTY);
		$this->options = $options;
	}

	public function getWork() {

		$num = $this->getWorkForProperty($this->property);

		if ($this->isOptionSet('sref_includeSubproperties', $this->options)) {
			$subproperties = $store->getSubProperties($this->property);
			foreach($subproperties as $s) {
				$num += $this->getWorkForProperty($s);
			}
		}

		return $num;
	}

	public function queryAffectedPages() {
		return $this->getAffectedPagesForProperty($this->property);
	}

	private function getWorkForProperty($property) {
		$affectedPages = $this->getAffectedPagesForProperty($property);
		$num = $this->isOptionSet('sref_removeInstancesUsingProperty', $this->options) ? count($affectedPages['instances']) : 0;
		$num += count($affectedPages['queries']) + count($affectedPages['instances']);
	}

	private function getAffectedPagesForProperty() {
		// calculate only once
		if (!is_null($this->affectedPages)) return $this->affectedPages;
		$store = smwfGetSemanticStore();
		$smwstore = smwfGetStore();

		// get all instances using $this->property as annotation
		$propertyDi = SMWDIProperty::newFromUserLabel($this->property->getText());
		$pageDIs = $smwstore->getAllPropertySubjects($propertyDi);
		$instances = array();
		foreach($pageDIs as $di) {
			$instances[] = $di->getTitle();
		}

		// get all direct subproperties of $this->property
		$directSubProperties = array();
		$dsp = $store->getDirectSubProperties($this->property);
		foreach($dsp as $tuple) {
			list($property, $hasChildren) = $tuple;
			$directSubProperties[] = $property;
		}

		// get all queries $this->property is used in
		$queries = array();
		$queryMetadataPattern = new SMWQMQueryMetadata(true);
		$queryMetadataPattern->instanceOccurences = array($this->property->getPrefixedText() => true);
		$queryMetadataPattern->propertyConditions = array($this->property->getText() => true);
		$queryMetadataPattern->propertyPrintRequests = array($this->property->getText() => true);

		$qmr = SMWQMQueryManagementHandler::getInstance()->searchQueries($queryMetadataPattern);
		foreach($qmr as $s) {
			$queries[] = Title::newFromText($s->usedInArticle);
		}

		$this->affectedPages = array();
		$this->affectedPages['instances'] = $instances;
		$this->affectedPages['queries'] = $queries;
		$this->affectedPages['directSubProperties'] = $directSubProperties;

		return $this->affectedPages;
	}

	public function refactor($save = true, & $logMessages) {
		$results = $this->queryAffectedPages();

		if (array_key_exists('sref_deleteProperty', $this->options) && $this->options['sref_deleteProperty'] == "true") {
			$a = new Article($this->property);
			$deleted = true;
			if ($save) {
				$deleted = SRFTools::deleteArticle($a);
			}
			if ($deleted) {
				$logMessages[$this->property->getPrefixedText()][] = new SRFLog('Article deleted',$this->property);
			} else {
				$logMessages[$this->property->getPrefixedText()][] = new SRFLog('Deletion failed',$this->property);
			}

		}


		if (array_key_exists('sref_removeInstancesUsingProperty', $this->options) && $this->options['sref_removeInstancesUsingProperty'] == "true") {
			foreach($this->affectedPages['instances'] as $i) {
				// if instances are completely removed, there is no need to remove annotations before

				$a = new Article($i);
				$deleted = true;
				if ($save) {
					$deleted = SRFTools::deleteArticle($a);
				}
				if ($deleted) {
					$logMessages[$i->getPrefixedText()][] = new SRFLog('Article deleted',$i);
				} else {
					$logMessages[$i->getPrefixedText()][] = new SRFLog('Deletion failed',$i);
				}


			}
		}

		$set = array_merge($this->affectedPages['instances'], $this->affectedPages['queries']);
		$set = SRFTools::makeTitleListUnique($set);

		foreach($set as $i) {
			$rev = Revision::newFromTitle($i);
			if (is_null($rev)) continue;
			$wikitext = $rev->getRawText();
			if (array_key_exists('sref_removePropertyAnnotations', $this->options) && $this->options['sref_removePropertyAnnotations'] == "true"
			&& SRFTools::containsTitle($i, $this->affectedPages['instances'])) {
				$wikitext = $this->removePropertyAnnotation($wikitext);
				$logMessages[$i->getPrefixedText()][] = new SRFLog('Removed property annotation',$i);

			}


			if (array_key_exists('sref_removeQueriesWithProperties', $this->options) && $this->options['sref_removeQueriesWithProperties'] == "true"
			&& SRFTools::containsTitle($i, $this->affectedPages['queries'])) {
				$wikitext = $this->removeQuery($wikitext);
				$logMessages[$i->getPrefixedText()][] = new SRFLog('Removed query',$i);

			}

			if ($save) {
				$status = $this->storeArticle($i, $wikitext, $rev->getRawComment());

				if (!$status->isGood()) {
					$logMessages[$i->getPrefixedText()][] = new SRFLog('Saving of $title failed due to: $1', $i, $wikitext, array($status->getWikiText()));
				}
			}
		}

		if (array_key_exists('sref_includeSubproperties', $this->options) && $this->options['sref_includeSubproperties'] == "true") {
			foreach($results['directSubcategories'] as $p) {
				$op = new SRFDeletePropertyOperation($p, $this->options);
				$op->refactor($save, $logMessages, $testData);
			}
		}
	}



	private function removeQuery($wikitext) {

		$wom = WOMProcessor::parseToWOM($wikitext);
		$toDelete = array();

		# iterate trough the annotations
		$objects = $wom->getObjectsByTypeID(WOM_TYPE_PARSERFUNCTION);

		foreach($objects as $o){
			$deleted = false;
			$results = array();
			$this->findObjectByID($o, WOM_TYPE_PROPERTY, $results);
			foreach($results as $c){
				$name = $c->getPropertyName();
				if ($name == $this->property->getText()) {
					$toDelete[] = $o->getObjectID();
					$deleted = true;
				}
			}

			if ($deleted) continue;

			// find printout
			$results = array();
			$this->findObjectByID($o, WOM_TYPE_PARAM_VALUE, $results);
			foreach($results as $paramValue) {
				$paramTexts = array();
				$this->findObjectByID($paramValue, WOM_TYPE_TEXT, $printouts);
				foreach($printouts as $po){
					$value = $po->getWikiText();
					$value = trim($value);
					if ($value == '?'.$this->oldProperty->getText()) {
						$toDelete[] = $o->getObjectID();
					}

				}
			}
		}

		$toDelete = array_unique($toDelete);
		foreach($toDelete as $id) {
			$wom->removePageObject($id);
		}

		$wikitext = $wom->getWikiText();
		return $wikitext;
	}

	private function removePropertyAnnotation($wikitext) {

		$wom = WOMProcessor::parseToWOM($wikitext);
		$toDelete = array();

		# iterate trough the annotations
		$objects = $wom->getObjectsByTypeID(WOM_TYPE_PROPERTY);
		foreach($objects as $o){

			$name = $o->getPropertyName();
			if ($name == $this->property->getText()) {
				$toDelete[] = $o;
			}

		}

		foreach($toDelete as $d) {
			$wom->removePageObject($d->getObjectID());
		}

		$wikitext = $wom->getWikiText();
		return $wikitext;
	}

}