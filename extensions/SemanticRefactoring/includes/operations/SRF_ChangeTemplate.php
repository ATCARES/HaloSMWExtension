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
class SRFChangeTemplateOperation extends SRFRefactoringOperation {

	private $instanceSet;
	private $template;
	private $old_parameter;
	private $new_parameter;
	 

	public function __construct($instanceSet, $template, $old_parameter, $new_parameter) {
		parent::__construct();
		foreach($instanceSet as $i) {
			$this->instanceSet[] = Title::newFromText($i);
		}
		$this->template = Title::newFromText($template, NS_TEMPLATE);
		$this->old_parameter = $old_parameter;
		$this->new_parameter = $new_parameter;
	}

	public function queryAffectedPages() {
		return $this->instanceSet;
	}

	public function getNumberOfAffectedPages() {
		return count($this->instanceSet);
	}

	public function refactor($save = true, & $logMessages) {
		foreach($this->instanceSet as $title) {
			if ($title->getNamespace() == SGA_NS_LOG) continue;
			$rev = Revision::newFromTitle($title);
			$wikitext = $this->changeContent($title, $rev->getRawText(), $logMessages);

			if (!is_null($this->mBot)) $this->mBot->worked(1);

			// stores article
			if ($save) {
				$status = $this->storeArticle($title, $wikitext, $rev->getRawComment());
				if (!$status->isGood()) {
					$logMessages[$title->getPrefixedText()][] = new SRFLog('Saving of $title failed due to: $1', $title, $wikitext, array($status->getWikiText()));
				}
			}
		}
	}


	public function changeContent($title, $wikitext, & $logMessages) {
		$pom = WOMProcessor::parseToWOM($wikitext);

		if (is_null($this->old_parameter) || is_null($this->new_parameter)) {
			return $pom->getWikiText();
		}
		
		# iterate trough the annotations
		$objects = $pom->getObjectsByTypeID(WOM_TYPE_TEMPLATE);

		foreach($objects as $o){

			$name = $o->getName();
			 
            $parameters=array();
			if ($name == $this->template->getText()) {
				$results = array();

				$this->findObjectByID($o, WOM_TYPE_TMPL_FIELD, $parameters);
				foreach($parameters as $p) {

					if ($p->getKey() == $this->old_parameter) {
						$p->setXMLAttribute('key', $this->new_parameter );
						$logMessages[$title->getPrefixedText()][] = new SRFLog("Changed parameter '$1' to '$2'", $title, "", array($this->old_parameter, $this->new_parameter));
					}
				}

			}

		}

		// calls sync() internally
		$wikitext = $pom->getWikiText();

		// set final wiki text
		foreach($logMessages as $title => $set) {
			foreach($set as $lm) {
				$lm->setWikiText($wikitext);
			}
		}

		return $wikitext;
	}
}