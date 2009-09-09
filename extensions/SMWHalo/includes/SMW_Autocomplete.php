<?php
/*  Copyright 2007, ontoprise GmbH
 *  This file is part of the halo-Extension.
 *
 *   The halo-Extension is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   The halo-Extension is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * Created on 21.02.2007
 * Author: KK
 * AutoCompletion Dispatcher
 */

// Register AJAX functions
if ( !defined( 'MEDIAWIKI' ) ) die;

global $wgAjaxExportList;
$wgAjaxExportList[] = 'smwf_ac_AutoCompletionDispatcher';
$wgAjaxExportList[] = 'smwf_ac_AutoCompletionOptions';


define('SMW_AC_NORESULT', "noResult");
define('SMW_AC_MAX_RESULTS', 15);

$smwhgAutoCompletionStore = null;

global $smwgHaloIP;
require_once( $smwgHaloIP . "/includes/SMW_DBHelper.php");
require_once( $smwgHaloIP . "/includes/SMW_Autocomplete_Storage.php");

/*
 * Dispatches an auto-completion request.
 *
 * Gets the user input splitted up in 2 parts:
 * Right part: consisting of all chars left from cursor which may belong
 * to a article's title.
 * Left part: the rest until [[. Left part may be empty! In this case,
 * no context could be identified.
 *
 * Example: user types [[category:Me and presses Ctrl+^
 * $userInputToMatch = Me
 * $userContext = [[category:
 *
 * Returns: xml representation with titles and type of entities.
 */
function smwf_ac_AutoCompletionDispatcher($articleName, $userInputToMatch, $userContext, $constraints) {
	global $smwgSemanticAC, $wgLang;

	smwLog(($userContext != null ? $userContext : "").$userInputToMatch, "AC", "activated", $articleName);
	// remove common namespaces from user input
	$userInputToMatch = AutoCompletionRequester::removeCommonNamespaces($userInputToMatch);
	// remove whitespaces from user input and replace with underscores
	$userInputToMatch = str_replace(" ","_",$userInputToMatch);

	// Check for context or not
	if ($userContext == null || $userContext == "" || !AutoCompletionRequester::isContext($userContext)) {
		// no context: that means only non-semantic AC is possible. Maybe a $constraints string is specified
		if ($constraints == null || $constraints == 'null') {
			// if no constraints defined, search for (nearly) all pages.
			global $wgExtraNamespaces;
			$namespaces = array_unique(array_merge(array(SMW_NS_PROPERTY, NS_CATEGORY, NS_MAIN, NS_TEMPLATE, SMW_NS_TYPE), array_keys($wgExtraNamespaces)));
			$pages = AutoCompletionHandler::executeCommand("namespace: ".implode(",", $namespaces), $userInputToMatch);

		} else {
			// otherwise use constraints
			$pages = AutoCompletionHandler::executeCommand($constraints, $userInputToMatch);
            
			// Fallback, if commands yield nothing. Deactivated now
			/*if (empty($pages)) {
				// fallback to standard search (namespace)
				global $wgExtraNamespaces;
				$namespaces = array_unique(array_merge(array(SMW_NS_PROPERTY, NS_CATEGORY, NS_MAIN, NS_TEMPLATE, SMW_NS_TYPE), array_keys($wgExtraNamespaces)));
				$pages = AutoCompletionHandler::executeCommand("namespace: ".implode(",", $namespaces), $userInputToMatch);
			}*/
		}
		$result = AutoCompletionRequester::encapsulateAsXML($pages);
		return $result;
	} else if (stripos($userContext, "[[") === 0){
		// semantic context
		// decide according to context which autocompletion is appropriate
			
		// ------------------------
		// 1. category case
		// ------------------------
		if (stripos(strtolower($userContext), strtolower($wgLang->getNsText(NS_CATEGORY)).":") > 0) {
			$result = AutoCompletionRequester::getCategoryProposals($userInputToMatch);
			AutoCompletionRequester::logResult($result, $articleName);
			return $result;
		}
			
		// ------------------------------------------------
		// 2./3. property target case / property value case
		// ------------------------------------------------
		else if (stripos($userContext,":=") > 0 || stripos($userContext,"::") > 0) {

			$propertyTargets = AutoCompletionRequester::getPropertyTargetProposals($userContext, $userInputToMatch);

			$attributeValues = AutoCompletionRequester::getPropertyValueProposals($userContext, $userInputToMatch);

			// if there is a unit or possible values, show them. Otherwise show instances.
			$result = $attributeValues != SMW_AC_NORESULT ? $attributeValues : $propertyTargets;
			AutoCompletionRequester::logResult($result, $articleName);
			return $result;


			// --------------------------------
			// 4.property name case
			// --------------------------------
		} else {
			$result = AutoCompletionRequester::getPropertyProposals($articleName, $userInputToMatch);
			AutoCompletionRequester::logResult($result, $articleName);
			return $result;

		}

	} else if (stripos($userContext, "{{") === 0) {
		// template context
		$result = AutoCompletionRequester::getTemplateProposals($userContext, $userInputToMatch);
		AutoCompletionRequester::logResult($result, $articleName);
		return $result;

	}
}

/**
 * Return options
 */
function smwf_ac_AutoCompletionOptions() {
	global $wgUser;
	return $wgUser->getOption( "autotriggering" ) == 1 ? "auto" : "manual";
}

function &smwfGetAutoCompletionStore() {
	global $smwhgAutoCompletionStore, $smwgHaloIP;
	if ($smwhgAutoCompletionStore == NULL) {
		global $smwgBaseStore;
		switch ($smwgBaseStore) {
			case (SMW_STORE_TESTING):
				$smwhgAutoCompletionStore = null; // not implemented yet
				trigger_error('Testing store not implemented for HALO extension.');
				break;
			case ('SMWHaloStore2'): default:
				$smwhgAutoCompletionStore = new AutoCompletionStorageSQL2();
				break;
			case ('SMWHaloStore'): default:
				$smwhgAutoCompletionStore = new AutoCompletionStorageSQL();
				break;
		}
	}
	return $smwhgAutoCompletionStore;
}

/**
 * TODO: Document, including member functions
 */
class AutoCompletionRequester {



	/**
	 * Get category proposals matching $match.
	 */
	public static function getCategoryProposals($match) {
		$categories = smwfGetAutoCompletionStore()->getPages($match, array(NS_CATEGORY));
		return AutoCompletionRequester::encapsulateAsXML($categories);
	}

	/**
	 * Get Property target proposals. Consider special properties too
	 */
	public static function getPropertyTargetProposals($userContext, $match) {
		// special handling for special relations

		global $smwgContLang, $smwgHaloContLang, $smwgSemanticAC, $wgLang;
		$specialProperties = $smwgContLang->getPropertyLabels();
		$specialSchemaProperties = $smwgHaloContLang->getSpecialSchemaPropertyArray();

		// special properties
		if (stripos(strtolower($userContext), strtolower($specialProperties["_SUBP"])) > 0) {
			$pages = smwfGetAutoCompletionStore()->getPages($match, array(SMW_NS_PROPERTY));
			return AutoCompletionRequester::encapsulateAsXML($pages, true); // return namespace too!
		} else if (stripos(strtolower($userContext), strtolower($specialSchemaProperties[SMW_SSP_IS_INVERSE_OF])) > 0) {
			$pages = smwfGetAutoCompletionStore()->getPages($match, array(SMW_NS_PROPERTY));
			return AutoCompletionRequester::encapsulateAsXML($pages, true); // return namespace too!
		} else if (stripos(strtolower($userContext),strtolower($specialProperties["_TYPE"])) > 0) {
			// has type relation. First check for user types
			$pages = smwfGetAutoCompletionStore()->getPages($match, array(SMW_NS_TYPE));
			// then check builtin types
			$typeLabels = array_values(SMWDataValueFactory::getKnownTypeLabels());
			$lower_match = strtolower($match);
			foreach($typeLabels as $l) {
				if (strpos(strtolower($l), $lower_match) !== false) {
					$pages[] = Title::newFromText($l, SMW_NS_TYPE);
				}

			}

			return AutoCompletionRequester::encapsulateAsXML($pages, true); // return namespace too!
		} else if (stripos(strtolower($userContext),strtolower($specialSchemaProperties[SMW_SSP_HAS_DOMAIN_AND_RANGE_HINT])) > 0) {
			// has domain hint relation
			$pages = smwfGetAutoCompletionStore()->getPages($match, array(NS_CATEGORY));
			return AutoCompletionRequester::encapsulateAsXML($pages, true); // return namespace too!
		} else {
			if ($smwgSemanticAC) {
				if (stripos($userContext,":=") > 0) {
					$relationText = substr($userContext, 2, stripos($userContext,":=")-2);
				} else {
					$relationText = substr($userContext, 2, stripos($userContext,"::")-2);
				}
					
				$property = Title::newFromText($relationText, SMW_NS_PROPERTY);

					
				$domainRangeAnnotations = smwfGetStore()->getPropertyValues($property, smwfGetSemanticStore()->domainRangeHintProp);
					
				$pages = smwfGetAutoCompletionStore()->getInstanceAsTarget($match, $domainRangeAnnotations);
					
				if (count($pages) == 0) {
					// fallback to non semantic AC
					$pages = smwfGetAutoCompletionStore()->getPages($match, array(NS_MAIN));

				}
				return AutoCompletionRequester::encapsulateAsXML($pages);
			} else {
				// all others
				$pages = smwfGetAutoCompletionStore()->getPages($match, array(NS_MAIN));
				return AutoCompletionRequester::encapsulateAsXML($pages);
			}
		}
			
	}

	/**
	 * Get attribute values (units and enums)
	 */
	public static function getPropertyValueProposals($userContext, $userInput) {
			
		if (stripos($userContext,":=") > 0) {
			$propertyText = trim(substr($userContext, 2, stripos($userContext,":=")-2));
		} else {
			$propertyText = trim(substr($userContext, 2, stripos($userContext,"::")-2));
		}
		// try units first, then possible values
		$property = Title::newFromText($propertyText, SMW_NS_PROPERTY);
		$unitsList = smwfGetAutoCompletionStore()->getUnits($property, $userInput);
			
		if (count($unitsList) > 0) {
			$attvalues = AutoCompletionRequester::encapsulateEnumsOrUnitsAsXML($unitsList);
		} else {
			$possibleValues = smwfGetAutoCompletionStore()->getPossibleValues($property);
			$attvalues = AutoCompletionRequester::encapsulateEnumsOrUnitsAsXML($possibleValues);
		}
		return $attvalues;
	}

	/**
	 * Get property proposals. Consider special properties too.
	 */
	public static function getPropertyProposals($articleName, $match) {
		global $smwgSemanticAC, $wgLang;
		if ($smwgSemanticAC) {
			// get all categories of the article
			$articleTitle = Title::newFromText($articleName);
			$pages = smwfGetAutoCompletionStore()->getPropertyForInstance($match, $articleTitle, true);
			if (count($pages) == 0) {
				// fallback to non semantic AC
				$pages = smwfGetAutoCompletionStore()->getPages($match, array(SMW_NS_PROPERTY, NS_MAIN));

			}
		} else {
			$pages = smwfGetAutoCompletionStore()->getPages($match, array(SMW_NS_PROPERTY, NS_MAIN));
		}
		// special handling for special relations
		$specialMatches = array(); // keeps matches of special relations
		global $smwgContLang;
		$specialProperties = $smwgContLang->getPropertyLabels();
		if (stripos(strtolower($wgLang->getNsText(NS_CATEGORY)), strtolower($match)) !== false) {
			$specialMatches[] = Title::newFromText(strtolower($wgLang->getNsText(NS_CATEGORY)), NS_CATEGORY);
		}
		if (stripos(strtolower($specialProperties["_SUBP"]), preg_replace("/_/", " ", strtolower($match))) !== false) {
			$specialMatches[] = Title::newFromText($specialProperties["_SUBP"], SMW_NS_PROPERTY);
		}
			
		if (stripos(strtolower($specialProperties["_TYPE"]), preg_replace("/_/", " ", strtolower($match))) !== false) {
			$specialMatches[] = Title::newFromText($specialProperties["_TYPE"], SMW_NS_PROPERTY);
		}
		// make sure the special relations come first
		$pages = array_merge($specialMatches, $pages);

		return AutoCompletionRequester::encapsulateAsXML($pages);
	}

	/**
	 * Get template proposals.
	 */
	public static function getTemplateProposals($userContext, $match) {
		// template context
		// parse template paramters
		$templateParameters = explode("|", $userContext);
		if (count($templateParameters) > 1) {
			// if it is a parameter try all semantic namespaces
			$results = smwfGetAutoCompletionStore()->getPages($match, array(SMW_NS_PROPERTY, NS_MAIN));
			return AutoCompletionRequester::encapsulateAsXML($results);
		} else { // otherwise it is a template name
			$templates = smwfGetAutoCompletionStore()->getPages($match, array(NS_TEMPLATE));
			$matches = array();
			foreach($templates as $t) {
				$matches[] = array($t, false, TemplateReader::formatTemplateParameters($t));
			}
			return AutoCompletionRequester::encapsulateAsXML($matches, false);
		}
	}
	/**
	 * Heuristic to determine weather $userContext describes a semantic context or not.
	 */
	public static function isContext($userContext) {
		if (stripos($userContext, "{{") === 0 && stripos($userContext, "}}") === false) {
			return true;
		}
		if (stripos($userContext, "[[") === 0 && stripos($userContext, "]]") === false) {
			return true;
		}
		return false;
	}

	/**
	 * Encapsulate an array of Titles in a xml string
	 *
	 * @param $titles Array of Title
	 * @param $putNameSpaceInName If true system would return 'namespace:localname' otherwise 'localname'
	 * @param $extraData Extra data which is pasted behind the Title. (array sizes of $titles and $extraData must matched, if used.)
	 * @return xml string
	 */
	public static function encapsulateAsXML(array & $matches, $putNameSpaceInName = false) {
		
		if (empty($matches)) {
			return SMW_AC_NORESULT;
		}

		// at least 1 match
		$xmlResult = '';
		$extra = "";
		$inferred = false;
		$namespaceText = "";
		

		for($i = 0, $n = count($matches); $i < $n; $i++) {
			switch(count($matches[$i])) {
				case 1: $title = $matches[$i]; break;
				case 2: list($title, $inferred) = $matches[$i];break;
				case 3: list($title, $inferred, $extra) = $matches[$i];
			}
			if ($title == NULL) continue;
			
			$inferredAtt = $inferred ? 'inferred="true"' : 'inferred="false"';
			if (is_string($title)) {
				$typeAtt =  "type=\"-1\"";
				$content = $title;
			} else {
				$typeAtt = "type=\"".$title->getNamespace()."\"";
				$namespaceText = "nsText=\"".$title->getNsText()."\"";
                $content = ($putNameSpaceInName ? htmlspecialchars($title->getPrefixedDBkey()) : htmlspecialchars($title->getDBkey()));
			}
			$xmlResult .= "<match $typeAtt $inferredAtt $namespaceText>$content<extraContent>$extra</extraContent></match>";
		}

		return '<result>'.$xmlResult.'</result>';
	}

	/**
	 *  Encapsulate an array of enums or units in a xml string.
	 */
	public static function encapsulateEnumsOrUnitsAsXML($arrayofEnumsOrUnits) {
		if (empty($arrayofEnumsOrUnits)) {
			return SMW_AC_NORESULT;
		}

		$xmlResult = '';
		foreach($arrayofEnumsOrUnits as $eou) {
			$xmlResult .= "<match type=\"500\">".htmlspecialchars($eou)."</match>";
		}
		return '<result>'.$xmlResult.'</result>';
	}





	/**
	 * Removes the common SMW namespace from $titleText.
	 */
	public static function removeCommonNamespaces($titleText) {
		global $smwgContLang;
		$namespaces = array_values($smwgContLang->getNamespaces());
		$regex = "";
		for ($i = 0, $n = count($namespaces); $i < $n; $i++) {
			if ($i < $n-1) {
				$regex .= $namespaces[$i].":|";
			} else {
				$regex .= $namespaces[$i].":";
			}
		}
		return preg_replace("/".$regex."/", "", $titleText);

	}


	public function logResult(& $result, $articleName) {
		if ($result == SMW_AC_NORESULT) {
			smwLog("","AC","no result", $articleName);
		} else {
			smwLog("","AC","opened", $articleName);
		}
	}

}

class TemplateReader {

	/**
	 * Format template parameters: One parameter per line. Adds separator pipe
	 */
	public static function formatTemplateParameters($template) {
		$result = "\n";
		$parameters = TemplateReader::getParameters($template);

		foreach($parameters as $param) {
			list($paramName, $defaultValue) = $param;
			$result .= !is_numeric($paramName) ? "|".$paramName."=$defaultValue\n" : "|\n";
		}
		return $result;
	}

	/**
	 * Get Template parameters as array of strings. Returns no doubles.
	 */
	private static function getParameters($template) {
		$rev = Revision::newFromTitle($template);
		$content = $rev->getText();
		$matches = array();
		$parameters = array();
		preg_match_all("/\{\{\{([^\}]*)\}\}\}/", $content, $matches);
		for($i = 0, $n = count($matches[1]); $i < $n; $i++) {
			$param = $matches[1][$i];
			if (!array_key_exists($param,$parameters)) {
				$parameters[$param] = explode("|",$param);
			}
		}
		return $parameters;
	}


}

/**
 * Handler for the auto-completion query syntax.
 *
 */
class AutoCompletionHandler {

	/**
	 * Parses auto-completion command.
	 *
	 * Syntax is:
	 *
	 *     1. S -> C [ '|' S ]
	 *     2. C -> CT ':' P
	 *     3. P -> PM [ ',' P ] | Epsilon;
	 *
	 *     CT is a command token, PM a parameter token.
	 *     (tokens are alphanumeric with special characters except comma and pipe)
	 *
	 * @param string $commandText
	 * @return array of ($command, $parameters)
	 */
	private static function parseCommand($commandText) {
		$result = array();
		$commands = explode("|", $commandText);
		foreach($commands as $c) {
			$sep = strpos($c, ":");
			if ($sep === false) continue; // something wrong with the command. ignore.
			$command = substr($c, 0, $sep);
			$params = substr($c, $sep + 1);
			if (!is_null($command) && !is_null($params)) {
			 $result[] = array($command, explode(",", $params));
			}
		}
		return $result;
	}

	/**
	 * Executes a series of auto-completion commands and stops when it
	 * has a found at least one result. Except when it matches local values.
	 *
	 * @param string $command
	 * @param substring $userInput
	 * @return array of Title, array of (Title, inferred) or array of (Title, inferred, extraContent)
	 */
	public static function executeCommand($command, $userInput) {
		$parsedCommands = self::parseCommand($command);
		$acStore = smwfGetAutoCompletionStore();

		$result = array();
		foreach($parsedCommands as $c) {
			list($commandText, $params) = $c;
            
			if ($commandText == 'values') {
				foreach($params as $p) {
					if (stripos($p, $userInput) !== false) $result[] = $p;
				}
				// continue to fill in results if possible
			} else if ($commandText == 'fixvalues') {
                foreach($params as $p) {
                    $result[] = $p;
                }
                // continue to fill in results if possible
            } else if ($commandText == 'schema-property-domain') {
				if (smwf_om_userCan($params[0], 'read') == 'true') {
					$category = Title::newFromText($params[0]);
					if (!is_null($category)) $result = array_merge($result, $acStore->getPropertyForCategory($userInput, $category));
				}
				if (!empty($result)) break;
			} else if ($commandText == 'schema-property-range-instance') {
				if (smwf_om_userCan($params[0], 'read') == 'true') {
					$instance = Title::newFromText($params[0]);
					if (!is_null($instance)) $result = array_merge($result, $acStore->getPropertyForInstance($userInput, $instance, false));
				}
				if (!empty($result)) break;
			} else if ($commandText == 'annotation-property') {
				if (smwf_om_userCan($params[0], 'read') == 'true') {
					$category = Title::newFromText($params[0]);
					if (!is_null($category)) $result = array_merge($result, $acStore->getPropertyForAnnotation($userInput, $category, false));
				}
				if (!empty($result)) break;
			} else if ($commandText == 'annotation-value') {
				if (smwf_om_userCan($params[0], 'read') == 'true') {
					$property = Title::newFromText($params[0]);
	                if (!is_null($property)) $result = array_merge($result, $acStore->getValueForAnnotation($userInput, $property));
				}
                if (!empty($result)) break;
            } else if ($commandText == 'namespace') {
				$result = array_merge($result, smwfGetAutoCompletionStore()->getPages($userInput, $params));
			} else if ($commandText == 'lexical') {
				$result = array_merge($result, smwfGetAutoCompletionStore()->getPages($userInput));
			} else if ($commandText == 'schema-property-type') {
				$datatype = $params[0];
				$result = smwfGetAutoCompletionStore()->getPropertyWithType($userInput, $datatype);
				if (empty($pages)) {
					global $smwgContLang;
					$dtl = $smwgContLang->getDatatypeLabels();
					$result = array_merge($result, smwfGetAutoCompletionStore()->getPropertyWithType($userInput, $dtl['_str']));
				}
			} else if ($commandText == 'ask') {
				$query = $params[0];
    
				if (!isset($params[1]) || $params[1] == 'main') {
					$column = "_var0";
				} else {
					$column = strtoupper(substr($params[1],0,1)).substr($params[1],1);
					$column = str_replace(" ", "_", $column);
				}

				$xmlResult = self::runASKQuery($query, $column);
					
				$dom = simplexml_load_string($xmlResult);
				$queryResults = $dom->xpath('//binding[@name="'.$column.'"]');

				// make titles but eliminate duplicates before
				$textTitles = array();
				foreach($queryResults as $r) {
					if (empty($userInput) || stripos((string) $r[0], $userInput) !== false) {
						$textTitles[] = (string) $r[0];
					}
				}
				$textTitles = array_unique($textTitles);
				foreach($textTitles as $r) {
					if (smwf_om_userCan($r, 'read') == 'true') {
						$result[] = Title::newFromText($r);
					}
				}

			}


		}
		return $result;
	}

	private static function runASKQuery($rawquery, $column) {
		global $smwgResultFormats, $smwgHaloIP;
		require_once "$smwgHaloIP/includes/queryprinters/SMW_QP_XML.php";
		$smwgResultFormats['xml'] = 'SMWXMLResultPrinter';

		// add query as first rawparam

		$rawparams[] = $rawquery;
		if ($column != "_var0") $rawparams[] = "?$column";

		// parse params and answer query
		SMWQueryProcessor::processFunctionParams($rawparams,$querystring,$params,$printouts);
		$params['format'] = "xml";

		if ($column != "_var0") $params['sort'] = $column;
		return SMWQueryProcessor::getResultFromQueryString($querystring,$params,$printouts, SMW_OUTPUT_FILE);
			
	}


}





