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
class SRFRenameCategoryOperation extends SRFRefactoringOperation {
	private $oldCategory;
	private $newCategory;

	private $affectedPages;



	public function __construct($oldCategory, $newCategory) {
		$this->oldCategory = Title::newFromText($oldCategory, NS_CATEGORY);
		$this->newCategory = Title::newFromText($newCategory, NS_CATEGORY);


	}

	public function getNumberOfAffectedPages() {
		$this->affectedPages = $this->queryAffectedPages();
		return count($this->affectedPages);
	}

	public function queryAffectedPages() {
		if (!is_null($this->affectedPages)) return $this->affectedPages;

		// get all pages using $this->oldCategory as category annotation
		$titles = array();
		$subjects = smwfGetSemanticStore()->getDirectInstances($this->oldCategory);
		foreach($subjects as $s) {
			$titles[] = $s;
		}

		$subjects = smwfGetSemanticStore()->getDirectSubCategories($this->oldCategory);
		foreach($subjects as $tuple) {
			list($s, $hasSubcategories) = $tuple;
			$titles[] = $s;
		}


		// get all pages using $this->oldCategory as property value
		$categoryDi = SMWDIWikiPage::newFromTitle($this->oldCategory);
		$properties = smwfGetStore()->getInProperties($categoryDi);
		foreach($properties as $p) {
			$subjects = smwfGetStore()->getPropertySubjects($p, $categoryDi);
			foreach($subjects as $s) {
				$titles[] = $s->getTitle();
			}
		}


		// get all pages which uses links to $this->oldCategory
		$subjects = $this->oldCategory->getLinksTo();
		foreach($subjects as $s) {
			$titles[] = $s;
		}


		// get all queries using $this->oldCategory
		$queries = array();
		$qrc_dopDi = SMWDIProperty::newFromUserLabel(QRC_DOC_LABEL);
		$categoryStringDi = new SMWDIString($this->oldCategory->getText());
		$subjects = smwfGetStore()->getPropertySubjects($qrc_dopDi, $categoryStringDi);
		foreach($subjects as $s) {
			$titles[] = $s->getTitle();
		}

		

		$this->affectedPages = SRFTools::makeTitleListUnique($titles);
		return $this->affectedPages;
	}

	public function refactor($save = true, & $logMessages) {

		$this->queryAffectedPages();

		foreach($this->affectedPages as $title) {

			$rev = Revision::newFromTitle($title);

			$wikitext = $this->changeContent($title, $rev->getRawText(), $logMessages);

			// stores article
			if ($save) {
				$a = new Article($title);
				$a->doEdit($wikitext, $rev->getRawComment(), EDIT_FORCE_BOT);
			}
			$logMessages[] = 'Content of "'.$title->getPrefixedText().'" changed.';
			if (!is_null($this->mBot)) $this->mBot->worked(1);
		}


	}

	/**
	 * Replaces old category with new.
	 * Callback method for array_walk
	 *
	 * @param string $title Prefixed title
	 * @param int $index
	 */
	public function replaceTitle(& $title, $index) {

		if ($title == ":".$this->oldCategory->getPrefixedText()) {
			$changed = true;
			$title = $this->newCategory->getPrefixedText();
		}
	}

	private function replaceCategoryInAnnotation($objects) {
		$changed = false;
		foreach($objects as $o){

			$name = $o->getName();
			if ($name == $this->oldCategory->getText()) {
				$o->setName($this->newCategory->getText());
				$changed = true;
			}

		}
		return true;
	}

	private function replaceCategoryInLink($objects) {
		$changed = false;
		foreach($objects as $o){
			$value = $o->getLink();

			if ($value == ":".$this->oldCategory->getPrefixedText()) {
				$o->setLink(":".$this->newCategory->getPrefixedText());
				$changed = true;
			}
		}
		return true;
	}


	public function changeContent($title, $wikitext, & $logMessages) {
		$pom = WOMProcessor::parseToWOM($wikitext);

		# iterate trough the annotations
		$objects = $pom->getObjectsByTypeID(WOM_TYPE_CATEGORY);
		$changedCategoryAnnotation = $this->replaceCategoryInAnnotation($objects);

		# iterate through the annotation values
		$objects = $pom->getObjectsByTypeID(WOM_TYPE_PROPERTY);
		$changedCategoryValue = $this->replaceValueInAnnotation($objects);


		# iterate trough the links
		$objects = $pom->getObjectsByTypeID(WOM_TYPE_LINK);
		$changedCategoryLink = $this->replaceCategoryInLink($objects);

		# iterate trough queries
		# better support for ASK would be nice
		$objects = $pom->getObjectsByTypeID(WOM_TYPE_PARSERFUNCTION);
		$changedQuery =true;
		foreach($objects as $o){
			if ($o->getFunctionKey() == 'ask') {
				$results = array();
				$this->findObjectByID($o, WOM_TYPE_CATEGORY, $results);
				$changedQuery = $changedQuery || $this->replaceCategoryInAnnotation($results);

				$results = array();
				$this->findObjectByID($o, WOM_TYPE_LINK, $results);
				$changedQuery = $changedQuery || $this->replaceCategoryInLink($results);

			}
		}

		# TODO: iterate through rules
		# not yet implemented in WOM*/

		$wikitext = $pom->getWikiText();

		if ($changedCategoryAnnotation) {
			$logMessages[$title->getPrefixedText()][] = new SRFLog("Changed category annotation at \$title", $title, $wikitext);
		}
		if ($changedCategoryValue) {
			$logMessages[$title->getPrefixedText()][] = new SRFLog("Changed category as annotation value at \$title", $title, $wikitext);
		}
		if ($changedCategoryLink) {
			$logMessages[$title->getPrefixedText()][] = new SRFLog("Changed link at \$title", $title, $wikitext);
		}
		if ($changedQuery) {
			$logMessages[$title->getPrefixedText()][] = new SRFLog("Changed query at \$title", $title, $wikitext);
		}
		return $wikitext;
	}
}