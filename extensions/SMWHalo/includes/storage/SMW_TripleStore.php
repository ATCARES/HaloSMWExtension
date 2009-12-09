<?php
global $smwgIP, $smwgHaloIP;
require_once( "$smwgIP/includes/storage/SMW_Store.php" );
require_once( "$smwgHaloIP/includes/storage/SMW_RuleStore.php" );
require_once( "$smwgHaloIP/includes/storage/stompclient/Stomp.php" );
require_once( "$smwgHaloIP/includes/storage/SMW_RESTWebserviceConnector.php" );
require_once( "$smwgHaloIP/includes/storage/SMW_HaloQueryResult.php" );
require_once( "$smwgHaloIP/includes/storage/SMW_TS_Helper.php" );

/**
 * Triple store connector class.
 *
 * This class is a wrapper around the default SMWStore class. It delegates all
 * read operations to the default implementation. Write operation, namely:
 *
 *  1. updateData
 *  2. deleteSubject
 *  3. changeTitle
 *  4. setup
 *  5. drop
 *
 * are delegated too, but also sent to a MessageBroker supporting the Stomp protocol.
 * All commands are written in the SPARUL(1) syntax.
 *
 * SPARQL queries are sent to the triple store via webservice (SPARQL endpoint). ASK
 * queries are delgated to default SMWStore.
 *
 * (1) refer to http://jena.hpl.hp.com/~afs/SPARQL-Update.html
 *
 * Configuration in LocalSettings.php:
 *
 *  $smwgMessageBroker: The name or IP of the message broker
 *  $smwgWebserviceEndpoint: The name or IP of the SPARQL endpoint (with port if not 80)
 *
 * @author: Kai
 */

class SMWTripleStore extends SMWStore {



	public static $fullSemanticData;
	private $tsNamespace;


	/**
	 * Creates and initializes Triple store connector.
	 *
	 * @param SMWStore $smwstore All calls are delegated to this implementation.
	 */
	function __construct() {
		global $smwgBaseStore;
		$this->smwstore = new $smwgBaseStore;
		$this->tsNamespace = new TSNamespaces();
	}



	///// Reading methods /////
	// delegate to default implementation

	function getSemanticData($subject, $filter = false) {
		return $this->smwstore->getSemanticData($subject, $filter);
	}


	function getPropertyValues($subject, SMWPropertyValue $property, $requestoptions = NULL, $outputformat = '') {
		return $this->smwstore->getPropertyValues($subject, $property, $requestoptions, $outputformat);
	}

	function getPropertySubjects(SMWPropertyValue $property, $value, $requestoptions = NULL) {
		return $this->smwstore->getPropertySubjects($property, $value, $requestoptions);
	}

	function getAllPropertySubjects(SMWPropertyValue $property, $requestoptions = NULL) {
		return $this->smwstore->getAllPropertySubjects($property, $requestoptions);
	}

	function getProperties($subject, $requestoptions = NULL) {
		return $this->smwstore->getProperties($subject, $requestoptions);
	}

	function getInProperties(SMWDataValue $object, $requestoptions = NULL) {
		return $this->smwstore->getInProperties($object, $requestoptions);
	}

	function getSMWPropertyID(SMWPropertyValue $property) {
		return $this->smwstore->getSMWPropertyID($property);
	}

	///// Writing methods /////

	function deleteSubject(Title $subject) {
		$this->smwstore->deleteSubject($subject);
		$subj_ns = $this->tsNamespace->getNSPrefix($subject->getNamespace());

		$unknownNSPrefixes = $this->tsNamespace->getUnknownNamespacePrefixes($subj_ns);


		// clear rules
		global $smwgEnableFlogicRules;
		if (isset($smwgEnableFlogicRules)) {
			$old_rules = SMWRuleStore::getInstance()->getRules($subject->getArticleId());
			SMWRuleStore::getInstance()->clearRules($subject->getArticleId());
		}
		global $smwgMessageBroker, $smwgTripleStoreGraph;
		try {
			$con = TSConnection::getConnector();
			$sparulCommands = array();
			$sparulCommands[] = TSNamespaces::getAllPrefixes().$unknownNSPrefixes."DELETE FROM <$smwgTripleStoreGraph> WHERE { $subj_ns:".$subject->getDBkey()." ?p ?o. }";
			if ($subject->getNamespace() == SMW_NS_PROPERTY) {
				$sparulCommands[] = TSNamespaces::getAllPrefixes().$unknownNSPrefixes."DELETE FROM <$smwgTripleStoreGraph> WHERE { ?s owl:onProperty ".$subj_ns.":".$subject->getDBkey().". }";
			}
			if (isset($smwgEnableFlogicRules)) {
				// delete old rules...
				foreach($old_rules as $ruleID) {
					$sparulCommands[] = "DELETE RULE $ruleID FROM <$smwgTripleStoreGraph>";
				}
			}
			$con->connect();
			$con->send("/topic/WIKI.TS.UPDATE", $sparulCommands);
			$con->disconnect();
		} catch(Exception $e) {

		}
	}

	function updateData(SMWSemanticData $data) {
		$this->smwstore->updateData($data);

		$triples = array();

		$subject = $data->getSubject();
		
		// check for selective updates, ie. update only certain namespaces
		global $smwgUpdateTSOnNamespaces;
		if (isset($smwgUpdateTSOnNamespaces) && is_array($smwgUpdateTSOnNamespaces)) {
			if (!array_key_exists($subject->getNamespace(), $smwgUpdateTSOnNamespaces)) {
				return;
			}
		}

		$subj_ns = $this->tsNamespace->getNSPrefix($subject->getNamespace());
		$unknownNSPrefixes = "";
		$unknownNSPrefixes .= $this->tsNamespace->getUnknownNamespacePrefixes($subj_ns);


		//properties
		foreach($data->getProperties() as $key => $property) {
			$propertyValueArray = $data->getPropertyValues($property);
			$triplesFromHook = array();
			wfRunHooks('TripleStorePropertyUpdate', array(& $data, & $property, & $propertyValueArray, & $triplesFromHook));
			if ($triplesFromHook === false || count($triplesFromHook) > 0) {
				$triples = is_array($triplesFromHook) ? array_merge($triples, $triplesFromHook) : $triples;
				continue; // do not process normal triple generation, if hook provides triples.
			}

			// handle properties with special semantics
			if ($property->getPropertyID() == "_TYPE") {
				// ingore. handeled by SMW_TS_SchemaContributor or SMW_TS_SimpleContributor
				continue;
			} elseif ($property->getPropertyID() == "_CONV") {
				// ingore. handeled by category section below
				global $smwgContLang;
				$specialProperties = $smwgContLang->getPropertyLabels();
				$conversionPropertyLabel = str_replace(" ","_",$specialProperties['_CONV']);
				if ( $subject->getNamespace() == SMW_NS_TYPE ) {
					foreach($propertyValueArray as $value) {
						// parse conversion annotation format
						$measures = explode(",", $value->getXSDValue());

						// parse linear factor followed by (first) unit
						$firstMeasure = reset($measures);
						$indexOfWhitespace = strpos($firstMeasure, " ");
						if ($indexOfWhitespace === false) continue; // not a valid measure, ignore
						$factor = trim(substr($firstMeasure, 0, $indexOfWhitespace));
						$unit = trim(substr($firstMeasure, $indexOfWhitespace));
						$triples[] = array("type:".$subject->getDBkey(), "prop:".$conversionPropertyLabel, "\"$factor $unit\"");

						// add all aliases for this conversion factor using the same factor
						$nextMeasure = next($measures);
						while($nextMeasure !== false) {
							$nextMeasure = str_replace('"', '\"', $nextMeasure);
							$triples[] = array("type:".$subject->getDBkey(), "prop:".$conversionPropertyLabel, "\"$factor ".trim($nextMeasure)."\"");
							$nextMeasure = next($measures);
						}

					}
				}
				continue;
			}

			elseif ($property->getPropertyID() == "_INST") {
				// ingore. handeled by category section below
				continue;
			} elseif ($property->getPropertyID() == "_SUBC") {
				// ingore. handeled by category section below
				continue;
			} elseif ($property->getPropertyID() == "_REDI") {
				// ingore. handeled by redirect section below
				continue;
			} elseif ($property->getPropertyID() == "_SUBP") {
				if ( $subject->getNamespace() == SMW_NS_PROPERTY ) {
					foreach($propertyValueArray as $value) {
						$triples[] = array("prop:".$subject->getDBkey(), "rdfs:subPropertyOf", "prop:".$value->getDBkey());
					}

				}
				continue;
			}

			// there are other special properties which need not to be handled special
			// so they can be handled by the default machanism:
			foreach($propertyValueArray as $value) {
				if ($value->isValid()) {
					if ($value->getTypeID() == '_txt') {
						$triples[] = array($subj_ns.":".$subject->getDBkey(), "prop:".$property->getWikiPageValue()->getDBkey(), "\"".$this->escapeForStringLiteral($value->getXSDValue())."\"^^xsd:string");

					} elseif ($value->getTypeID() == '_wpg') {
						$obj_ns = $this->tsNamespace->getNSPrefix($value->getNamespace());
						$unknownNSPrefixes .= $this->tsNamespace->getUnknownNamespacePrefixes($obj_ns);
						$triples[] = array($subj_ns.":".$subject->getDBkey(), "prop:".$property->getWikiPageValue()->getDBkey(), $obj_ns.":".$value->getDBkey());

					} elseif ($value->getTypeID() == '__nry') {
						continue; // do not add nary properties
					} else {

						if ($value->getUnit() != '') {
							// attribute with unit value
							$triples[] = array($subj_ns.":".$subject->getDBkey(), "prop:".$property->getWikiPageValue()->getDBkey(), "\"".$value->getXSDValue()." ".$value->getUnit()."\"^^xsd:unit");
						} else {
							if (!is_null($property->getWikiPageValue())) {
								if ($value->getXSDValue() != NULL) {
									// attribute with textual value
									$xsdType = WikiTypeToXSD::getXSDType($property->getPropertyTypeID());
									$triples[] = array($subj_ns.":".$subject->getDBkey(), "prop:".$property->getWikiPageValue()->getDBkey(), "\"".$this->escapeForStringLiteral($value->getXSDValue())."\"^^$xsdType");
								} else if ($value->getNumericValue() != NULL) {
									// attribute with numeric value
									$triples[] = array($subj_ns.":".$subject->getDBkey(), "prop:".$property->getWikiPageValue()->getDBkey(), "\"".$value->getNumericValue()."\"^^xsd:double");
								}
							}
						}

					}
				}
			}



		}

		// categories
		$categories = self::$fullSemanticData->getCategories();
		if ($subject->getNamespace() == NS_CATEGORY) {
			foreach($categories as $c) {
				if ($c == NULL) continue;
				$triplesFromHook = array();
				wfRunHooks('TripleStoreCategoryUpdate', array(& $subject, & $c, & $triplesFromHook));
				if ($triplesFromHook === false || count($triplesFromHook) > 0) {
					$triples = is_array($triplesFromHook) ? array_merge($triples, $triplesFromHook) : $triples;
					continue;
				}
				$triples[] = array("cat:".$subject->getDBkey(), "rdfs:subClassOf", "cat:".$c->getDBkey());
			}
		} else {

			foreach($categories as $c) {
				if ($c == NULL) continue;
				$triplesFromHook = array();
				wfRunHooks('TripleStoreCategoryUpdate', array(& $subject, & $c, & $triplesFromHook));
				if ($triplesFromHook === false || count($triplesFromHook) > 0) {
					$triples = is_array($triplesFromHook) ? array_merge($triples, $triplesFromHook) : $triples;
					continue;
				}
				$triples[] = array($subj_ns.":".$subject->getDBkey(), "rdf:type", "cat:".$c->getDBkey());
			}
		}

		// rules
		global $smwgEnableFlogicRules;
		if (isset($smwgEnableFlogicRules)) {
			$new_rules = self::$fullSemanticData->getRules();
			$old_rules = SMWRuleStore::getInstance()->getRules($subject->getArticleId());
			SMWRuleStore::getInstance()->clearRules($subject->getArticleId());
			SMWRuleStore::getInstance()->addRules($subject->getArticleId(), $new_rules);
		}

		// redirects
		$redirects = self::$fullSemanticData->getRedirects();

		foreach($redirects as $r) {
			switch($subj_ns) {
				case SMW_NS_PROPERTY: $prop = "owl:equivalentProperty";
				case NS_CATEGORY: $prop = "owl:equivalentClass";
				case NS_MAIN: $prop = "owl:sameAs";
				default: continue;
			}
			$r_ns = $this->tsNamespace->getNSPrefix($r->getNamespace());
			$unknownNSPrefixes .= $this->tsNamespace->getUnknownNamespacePrefixes($r_ns);
			$triples[] = array($subj_ns.":".$subject->getDBkey(), $prop, $r_ns.":".$r->getDBkey());
		}

		// connect to MessageBroker and send commands
		global $smwgMessageBroker, $smwgTripleStoreGraph;
		try {
			$con = TSConnection::getConnector();
			$sparulCommands = array();
			$sparulCommands[] = TSNamespaces::getAllPrefixes().$unknownNSPrefixes."DELETE FROM <$smwgTripleStoreGraph> WHERE { $subj_ns:".$subject->getDBkey()." ?p ?o. }";
			if ($subject->getNamespace() == SMW_NS_PROPERTY) {
				// delete all property constraints too
				$sparulCommands[] = TSNamespaces::getAllPrefixes().$unknownNSPrefixes."DELETE FROM <$smwgTripleStoreGraph> WHERE { ?s owl:onProperty ".$subj_ns.":".$subject->getDBkey().". }";
			}
			$sparulCommands[] =  TSNamespaces::getAllPrefixes().$unknownNSPrefixes."INSERT INTO <$smwgTripleStoreGraph> { ".$this->implodeTriples($triples)." }";

			if (isset($smwgEnableFlogicRules)) {
				// delete old rules...
				foreach($old_rules as $ruleID) {
					$sparulCommands[] = "DELETE RULE $ruleID FROM <$smwgTripleStoreGraph>";
				}
				// ...and add new
				foreach($new_rules as $rule) {
					// The F-Logic parser does not accept linebreaks
					// => remove them
					list($ruleID, $ruleText, $native) = $rule;
					$ruleText = preg_replace("/[\n\r]/", " ", $ruleText);
					$nativeText = $native ? "NATIVE" : "";
					$sparulCommands[] = "INSERT $nativeText RULE $ruleID INTO <$smwgTripleStoreGraph> : \"".$this->escapeForStringLiteral($ruleText)."\"";
				}
			}
			$con->connect();
			$con->send("/topic/WIKI.TS.UPDATE", $sparulCommands);
			$con->disconnect();
		} catch(Exception $e) {
			// print something??
		}
	}


	function changeTitle(Title $oldtitle, Title $newtitle, $pageid, $redirid=0) {
		$this->smwstore->changeTitle($oldtitle, $newtitle, $pageid, $redirid);
		$unknownNSPrefixes = "";
		$old_ns = $this->tsNamespace->getNSPrefix($oldtitle->getNamespace());
		$unknownNSPrefixes .= $this->tsNamespace->getUnknownNamespacePrefixes($old_ns);

		$new_ns = $this->tsNamespace->getNSPrefix($newtitle->getNamespace());
		$unknownNSPrefixes .= $this->tsNamespace->getUnknownNamespacePrefixes($new_ns);

		// update local rule store
		global $smwgEnableFlogicRules;
		if (isset($smwgEnableFlogicRules)) {
			SMWRuleStore::getInstance()->updateRules($redirid, $pageid);
		}

		// update triple store
		global $smwgMessageBroker, $smwgTripleStoreGraph;
		try {
			$con = TSConnection::getConnector();

			$sparulCommands = array();
			$sparulCommands[] = TSNamespaces::getAllPrefixes().$unknownNSPrefixes."MODIFY <$smwgTripleStoreGraph> DELETE WHERE { $old_ns:".$oldtitle->getDBkey()." ?p ?o. } INSERT { $new_ns:".$newtitle->getDBkey()." ?p ?o. }";
			$sparulCommands[] = TSNamespaces::getAllPrefixes().$unknownNSPrefixes."MODIFY <$smwgTripleStoreGraph> DELETE WHERE { ?s $old_ns:".$oldtitle->getDBkey()." ?o. } INSERT { ?s $new_ns:".$newtitle->getDBkey()." ?o. }";
			$sparulCommands[] = TSNamespaces::getAllPrefixes().$unknownNSPrefixes."MODIFY <$smwgTripleStoreGraph> DELETE WHERE { ?s ?p $old_ns:".$oldtitle->getDBkey().". } INSERT { ?s ?p $new_ns:".$newtitle->getDBkey().". }";
			$con->connect();
			$con->send("/topic/WIKI.TS.UPDATE", $sparulCommands);
			$con->disconnect();
		} catch(Exception $e) {

		}
	}

	///// Query answering /////

	function getQueryResult(SMWQuery $query) {
		global $wgServer, $wgScript, $smwgWebserviceUser, $smwgWebservicePassword, $smwgDeployVersion, $smwgUseLocalhostForWSDL;

		// handle only SPARQL queries and delegate all others
		if ($query instanceof SMWSPARQLQuery) {
			wfRunHooks('RewriteSparqlQuery', array(&$query) );

			if ($query->getQueryString() == "") {
				$sqr = new SMWHaloQueryResult(array(), $query, false);
				$sqr->addErrors(array(wfMsgForContent('hacl_sp_empty_query')));
				return $sqr;
			}
			if (!isset($smwgDeployVersion) || !$smwgDeployVersion) ini_set("soap.wsdl_cache_enabled", "0");  //set for debugging
			if (isset($smwgUseLocalhostForWSDL) && $smwgUseLocalhostForWSDL === true) $host = "http://localhost"; else $host = $wgServer;
			$client = new SoapClient("$host$wgScript?action=ajax&rs=smwf_ws_getWSDL&rsargs[]=get_sparql", array('login'=>$smwgWebserviceUser, 'password'=>$smwgWebservicePassword));

			try {
				global $smwgTripleStoreGraph;
				if (stripos(trim($query->getQueryString()), 'SELECT') === 0 || stripos(trim($query->getQueryString()), 'PREFIX') === 0) {
					// SPARQL, attach common prefixes
					$response = $client->query(TSNamespaces::getAllPrefixes().$query->getQueryString(), $smwgTripleStoreGraph, $this->serializeParams($query));
				} else {

					// do not attach anything
					$response = $client->query($query->getQueryString(), $smwgTripleStoreGraph, $this->serializeParams($query));

				}

					
				global $smwgSPARQLResultEncoding;
				// PHP strings are always interpreted in ISO-8859-1 but may be actually encoded in
				// another charset.
				if (isset($smwgSPARQLResultEncoding) && $smwgSPARQLResultEncoding == 'UTF-8') {
					$response = utf8_decode($response);
				}

				$queryResult = $this->parseSPARQLXMLResult($query, $response);


			} catch(Exception $e) {
				//              var_dump($e);
				$sqr = new SMWHaloQueryResult(array(), $query, false);
				$sqr->addErrors(array($e->getMessage()));
				return $sqr;
			}

			wfRunHooks('FilterQueryResults', array(&$queryResult) );

			switch ($query->querymode) {

				case SMWQuery::MODE_COUNT:
					$queryResult = $queryResult->getCount();
					break;
				default:

					break;
			}
			return $queryResult;

		} else {
			return $this->smwstore->getQueryResult($query);
		}
	}

	///// Special page functions /////
	// delegate to default implementation
	function getPropertiesSpecial($requestoptions = NULL) {
		return $this->smwstore->getPropertiesSpecial($requestoptions);
	}

	function getUnusedPropertiesSpecial($requestoptions = NULL) {
		return $this->smwstore->getUnusedPropertiesSpecial($requestoptions);
	}

	function getWantedPropertiesSpecial($requestoptions = NULL) {
		return $this->smwstore->getWantedPropertiesSpecial($requestoptions);
	}

	function getStatistics() {
		return $this->smwstore->getStatistics();
	}

	///// Setup store /////

	function setup($verbose = true) {
		$this->smwstore->setup($verbose);

	}

	function initialize($verbose = true) {
		global $smwgMessageBroker, $smwgTripleStoreGraph, $wgDBtype, $wgDBport, $wgDBserver, $wgDBname, $wgDBuser, $wgDBpassword, $wgDBprefix, $wgLanguageCode, $smwgBaseStore, $smwgIgnoreSchema, $smwgNamespaceIndex;
		$ignoreSchema = isset($smwgIgnoreSchema) && $smwgIgnoreSchema === true ? "true" : "false";
		try {
			$con = TSConnection::getConnector();
			$sparulCommands = array();
			$sparulCommands[] = "DROP <$smwgTripleStoreGraph>"; // drop may fail. don't worry
			$sparulCommands[] = "CREATE <$smwgTripleStoreGraph>";
			$sparulCommands[] = "LOAD smw://".urlencode($wgDBuser).":".urlencode($wgDBpassword)."@$wgDBserver:$wgDBport/$wgDBname?lang=$wgLanguageCode&smwstore=$smwgBaseStore&ignoreSchema=$ignoreSchema&smwnsindex=$smwgNamespaceIndex#".urlencode($wgDBprefix)." INTO <$smwgTripleStoreGraph>";
			$con->connect();
			$con->send("/topic/WIKI.TS.UPDATE", $sparulCommands);
			$con->disconnect();
		} catch(Exception $e) {

		}
	}


	function drop($verbose = true) {
		$this->smwstore->drop($verbose);
	}

	function refreshData(&$index, $count, $namespaces = false, $usejobs = true) {
		$this->smwstore->refreshData($index, $count, $namespaces, $usejobs);
	}

	public function getSMWPageID($title, $namespace, $iw, $canonical=true) {
		return $this->smwstore->getSMWPageID($title, $namespace, $iw, $canonical);
	}



	// Helper methods





	/**
	 * Implodes triples separated by a dot for SPARUL commands.
	 *
	 * @param array of $triples
	 * @return string
	 */
	protected function implodeTriples($triples) {
		$result = "";
		foreach($triples as $t) {
			$result .= implode(" ", $t);
			$result .= ". ";
		}
		return $result;
	}





	/**
	 * Escapes double quotes, backslash and line feeds for a SPARUL string literal.
	 *
	 * @param string $literal
	 * @return string
	 */
	protected function escapeForStringLiteral($literal) {
		return str_replace(array("\\", "\"", "\n", "\r"), array("\\\\", "\\\"", "\\n" ,"\\r"), $literal);
	}

	/**
	 * Unquotes a string
	 *
	 * @param String $literal
	 * @return String
	 */
	protected function unquote($literal) {
		$trimed_lit = trim($literal);
		if (stripos($trimed_lit, "\"") === 0 && strrpos($trimed_lit, "\"") === strlen($trimed_lit)-1) {
			$substr = substr($trimed_lit, 1, strlen($trimed_lit)-2);
			return str_replace("\\\"", "\"", $substr);
		}
		return $trimed_lit;
	}



	/**
	 * Removes type hint, e.g. "....."^^xsd:type gets to "....."
	 *
	 * @param string $literal
	 * @return string
	 */
	protected function removeXSDType($literal) {
		$pos = strpos($literal, "^^");
		return $pos !== false ? substr($literal, 0, $pos) : $literal;
	}

	/**
	 * Parses a SPARQL XML-Result and returns an SMWHaloQueryResult.
	 *
	 * @param SMWQuery $query
	 * @param xml string $sparqlXMLResult
	 * @return SMWHaloQueryResult
	 */
	protected function parseSPARQLXMLResult(& $query, & $sparqlXMLResult) {

		// parse xml results
      
		$dom = simplexml_load_string($sparqlXMLResult);
		$variables = $dom->xpath('//variable');
		$results = $dom->xpath('//result');

		// if no results return empty result object
		if (count($results) == 0) return new SMWHaloQueryResult(array(), $query);

		$variableSet = array();
		foreach($variables as $var) {
			$variableSet[] = (string) $var->attributes()->name;
		}

		// PrinterRequests to use
		$prs = array();

		// Use PrintRequests to determine which variable denotes what type of entity. If no PrintRequest is given use first result row
		// (which exist!) to determine which variable denotes what type of entity.


		// maps print requests (variable name) to result columns ( var_name => index )
		$mapPRTOColumns = array();

		// use user-given PrintRequests if possible
		$print_requests = $query->getDescription()->getPrintRequests();
		
		// rewrite printrequests in case of property chains
        $rewritten_prs = array();
        $toUnset = array();
        foreach($print_requests as $id => $pr) {
            $data = $pr->getData();
            if ($data instanceof Title) { // property chain appear as Title
                $titleText = $data->getText();
                $chain = explode(".",$titleText);
               
                if (count($chain) > 1) {
                    $newtitle = Title::newFromText($chain[count($chain)-1], SMW_NS_PROPERTY);
                    if ($newtitle->exists()) {
                    	$newlabel = $pr->getLabel() != $titleText ? $pr->getLabel() : $newtitle->getText();
                    	$newData = SMWPropertyValue::makeUserProperty($newtitle->getText());
                    } else {
                    	$newlabel = $pr->getLabel() != $titleText ? $pr->getLabel() : $newtitle->getText();
                        $newData = $newtitle;
                    }
                    $newid = str_replace($titleText, $newtitle->getText(), $id); 
                    $rewritten_prs[$newid] = new SMWPrintRequest(SMWPrintRequest::PRINT_PROP, $newlabel, $newData, $pr->getOutputFormat());
                    $rewritten_prs[$newid]->getHash();
                    $toUnset[] = $id;
                }
            }
        }
        foreach($toUnset as $tu) {
        	unset($print_requests[$tu]);
        }
        $print_requests = array_merge($print_requests, $rewritten_prs);
        // rewriting end
		
		$hasMainColumn = false;
		$index = 0;
		if ($query->fromASK) {

			// SPARQL query which was transformed from ASK
			// x variable is handeled specially as main variable
			foreach($print_requests as $pr) {

				$data = $pr->getData();
				if ($data == NULL) { // main column
					$hasMainColumn = true;
					if (in_array('_X_', $variableSet)) { // x is missing for INSTANCE queries
						$mapPRTOColumns['_X_'] = $index;
						$prs[] = $pr;
						$index++;
					}

				} else  {
					// make sure that variables get truncated for SPARQL compatibility when used with ASK.
					$label = $data instanceof Title ? $data->getDBkey() : $data->getXSDValue();
					//preg_match("/[A-Z][\\w_]*/", $label, $matches);
					$mapPRTOColumns[$label] = $index;
					$prs[] = $pr;
					$index++;
				}

			}
		} else {

			// native SPARQL query
			foreach($print_requests as $pr) {

				$data = $pr->getData();
				if ($data != NULL) {
					$label = $data instanceof Title ? $data->getDBkey() : $data->getXSDValue();
					$mapPRTOColumns[$label] = $index;
					$prs[] = $pr;
					$index++;
				}

			}
		}


		// generate PrintRequests for all bindings (if they do not exist already)
		$var_index = 0;
		$bindings = $results[0]->children()->binding;
		foreach ($bindings as $b) {
			$var_name = ucfirst((string) $variables[$var_index]->attributes()->name);
			$var_index++;

			// if no mainlabel, do not create a printrequest for _X_ (instance variable for ASK-converted queries)
			if ($query->mainLabelMissing && $var_name == "_X_") {
				continue;
			}
			// do not generate new PrintRequest if already given
			if ($this->containsPrintRequest($var_name, $print_requests, $query)) continue;

			// otherwise create one
			$data = SMWPropertyValue::makeUserProperty($var_name);
			$prs[] = new SMWPrintRequest(SMWPrintRequest::PRINT_THIS, str_replace("_"," ",$var_name), $data);


			$mapPRTOColumns[$var_name] = $index;
			$index++;
		}

		// Query result object
		$queryResult = new SMWHaloQueryResult($prs, $query, (count($results) > $query->getLimit()));
        
		
		// create and add result rows
		// iterate result rows and add an SMWResultArray object for each field

		foreach ($results as $r) {
			$row = array();
			$columnIndex = 0; // column = n-th XML binding node

			$children = $r->children(); // $chilren->binding denote all binding nodes
			foreach ($children->binding as $b) {

				$var_name = ucfirst((string) $children[$columnIndex]->attributes()->name);
				if (!$hasMainColumn && $var_name == '_X_') {

					$columnIndex++;
					continue;
				}
				$resultColumn = $mapPRTOColumns[$var_name];

				$allValues = array();

				$bindingsChildren = $b->children();
				$uris = array();

				foreach($bindingsChildren->uri as $sv) {
					$uris[] = array((string) $sv, (string) $sv->attributes()->provenance);
				}
				if (!empty($uris)) {
					
					$this->addURIToResult($uris, $prs[$resultColumn], $allValues);
				} else {
					$literals = array();
					
					foreach($bindingsChildren->literal as $sv) {
						$literals[] = array((string) $sv, (string) $sv->attributes()->datatype, (string) $sv->attributes()->provenance);
					}
					if (!empty($literals)) $this->addLiteralToResult($literals, $prs[$resultColumn], $allValues);
				}
				// note: ignore bnodes

				$columnIndex++;
				$row[$resultColumn] = new SMWResultArray($allValues, $prs[$resultColumn]);
			}

			ksort($row);
			$queryResult->addRow($row);
			 
		}
        
		return $queryResult;
	}

	/**
	 * Add an URI to an array of results
	 *
	 * @param string $sv A single value
	 * @param PrintRequest prs
	 * @param array & $allValues
	 */
	protected function addURIToResult($uris, $prs, & $allValues) {

		foreach($uris as $uri) {
			list($sv, $provenance) = $uri;
			$nsFound = false;
			foreach (TSNamespaces::getAllNamespaces() as $nsIndsex => $ns) {
				if (stripos($sv, $ns) === 0) {
					$allValues[] = $this->createSMWDataValue($sv, $ns, $nsIndsex);
					$nsFound = true;
				}
			}

			if ($nsFound) continue;

			// result with unknown namespace
			if (stripos($sv, TSNamespaces::$UNKNOWN_NS) === 0) {

				if (empty($sv)) {
					$v = SMWDataValueFactory::newTypeIDValue('_wpg');
					if (!is_null($provenance)) $v->setProvenance($provenance);
					$allValues[] = $v;
				} else {
					$startNS = strlen(TSNamespaces::$UNKNOWN_NS);
					$length = strpos($sv, "#") - $startNS;
					$ns = intval(substr($sv, $startNS, $length));

					$local = substr($sv, strpos($sv, "#")+1);

					$title = Title::newFromText($local, $ns);
					$v = SMWDataValueFactory::newTypeIDValue('_wpg');
					$v->setValues($title->getDBkey(), $ns, $title->getArticleID());
					if (!is_null($provenance)) $v->setProvenance($provenance);
					$allValues[] = $v;
				}
			} else {
				// external URI
				$v = SMWDataValueFactory::newTypeIDValue('_uri');
				$v->setXSDValue($sv);
				if (!is_null($provenance)) $v->setProvenance($provenance);
				$allValues[] = $v;
				
			}
		}
	}

	/**
	 * Add a literal to an array of results
	 *
	 * @param string $sv A single value
	 * @param PrintRequest prs
	 * @param array & $allValues
	 */
	protected function addLiteralToResult($literals, $prs, & $allValues) {
		foreach($literals as $literal) {

			list($literalValue, $literalType, $provenance) = $literal;
			$property = $prs->getData();
			if (!empty($literalValue)) {

				// create SMWDataValue either by property or if that is not possible by the given XSD type
				if ($property instanceof SMWPropertyValue ) {
					$value = SMWDataValueFactory::newPropertyObjectValue($prs->getData(), $literalValue);
				} else {
					$value = SMWDataValueFactory::newTypeIDValue(WikiTypeToXSD::getWikiType($literalType));
				}
				if ($value->getTypeID() == '_dat') { // exception for dateTime
					if ($literalValue != '') {
						// do not display time if it is 00:00:00
						if (substr($literalValue, -9) == 'T00:00:00') {
							$literalValue = substr($literalValue, 0, strpos($literalValue, "T"));
						}
						$value->setXSDValue(str_replace("-","/",$literalValue));
					}
				} else if ($value->getTypeID() == '_ema') { // exception for email
					$value->setXSDValue($literalValue);
				} else {
					$value->setUserValue($literalValue);
				}
			} else {

				if ($property instanceof SMWPropertyValue ) {
					$value = SMWDataValueFactory::newPropertyObjectValue($property);
				} else {
					$value = SMWDataValueFactory::newTypeIDValue('_wpg');

				}

			}
			if (!is_null($provenance)) $value->setProvenance($provenance);
			$allValues[] = $value;
		}
	}

	/**
	 * Creates  SWMDataValue object from a (possibly) merged result.
	 *
	 * @param string $sv
	 * @param string $nsFragment
	 * @param int $ns
	 * @return SMWDataValue
	 */
	protected function createSMWDataValue($sv, $nsFragment, $ns) {

		$local = substr($sv, strlen($nsFragment));
		$title = Title::newFromText($local, $ns);
		$v = SMWDataValueFactory::newTypeIDValue('_wpg');
		$v->setValues($title->getDBkey(), $ns, $title->getArticleID());
		return $v;

	}

	/**
	 * Serializes parameters and extraprintouts of SMWQuery.
	 * These informations are needed to generate a correct SPARQL query.
	 *
	 * @param SMWQuery $query
	 * @return String
	 */
	protected function serializeParams($query) {
		$result = "";
		$first = true;

		foreach ($query->getExtraPrintouts() as $printout) {
			if (!$first) $result .= "|";
			if ($printout->getData() == NULL) {
				$result .= "?=".$printout->getLabel();
			} else if ($printout->getData() instanceof Title) {
				$result .= "?".$printout->getData()->getDBkey()."=".$printout->getLabel();
			} else if ($printout->getData() instanceof SMWPropertyValue ) {
				$outputFormat = $printout->getOutputFormat() !== NULL ? "#".$printout->getOutputFormat() : "";
				$result .= "?".$printout->getData()->getXSDValue().$outputFormat."=".$printout->getLabel();
			}
			$first = false;
		}
		if ($query->getLimit() != NULL) {
			if (!$first) $result .= "|";
			$result .= "limit=".$query->getLimit();
			$first = false;
		}
		if ($query->getOffset() != NULL) {
			if (!$first) $result .= "|";
			$result .= "offset=".$query->getOffset();
			$first = false;
		}
		if ($query->sort) {
			if (!$first) $result .= "|";
			$first = false;
			$sort = "sort=";
			$order = "order=";
			$firstsort = true;
			foreach($query->sortkeys as $sortkey => $orderkey) {
				if (!$firstsort) { $sort .= ","; $order .= ",";  }
				$sort .= $sortkey;
				$order .= $orderkey;
				$firstsort = false;
			}
			$result .= $sort."|".$order;
		}

		if ($query->mergeResults === false) {
			if (!$first) $result .= "|";
			$result .= 'merge=false';
			$first = false;
		}
		return $result;
	}

	/**
	 * Returns true, if the given variable $var_name is represented by a PrintRequest in $prqs
	 *
	 * @param String $var_name
	 * @param array $prqs
	 * @return boolean
	 */
	protected function containsPrintRequest($var_name, array & $prqs, & $query) {
		$contains = false;
		foreach($prqs as $po) {
			if ($query->fromASK && $po->getData() == NULL && $var_name == '_X_') {
				return true;
			}
			if ($po->getData() != NULL) {
				$label = $po->getData() instanceof Title ? $po->getData()->getDBkey() : $po->getData()->getXSDValue();
				$contains |= strtolower($label) == strtolower($var_name);
			}

		}
		return $contains;
	}


}





/**
 * Provides an abstraction for the connection to the triple store.
 * Currently, 3 connector types are supported:
 *
 *  1. MessageBroker
 *  2. REST webservice
 *  3. SOAP webservice
 *
 */
abstract class TSConnection {
	protected $con;

	protected static $_instance;
	/**
	 * Connects to the triplestore
	 *
	 */
	public abstract function connect();

	/**
	 * Disconnects from triplestore
	 *
	 */
	public abstract function disconnect();

	/**
	 * Sends SPARUL commands
	 *
	 * @param string $topic only relevant for a messagebroker.
	 * @param string or array of strings $commands
	 */
	public abstract function send($topic, $commands);

	public static function getConnector() {
		if (is_null(self::$_instance)) {
			global $smwgMessageBroker, $smwgWebserviceProtocol;

			if (isset($smwgMessageBroker)) {
				self::$_instance = new TSConnectorMessageBroker();
			} else if (isset($smwgWebserviceProtocol) && strtolower($smwgWebserviceProtocol) === 'rest') {
				self::$_instance = new TSConnectorRESTWebservice();

			} else {

				self::$_instance = new TSConnectorSOAPWebservice();
			}
		}
		return self::$_instance;
	}
}

/**
 * MessageBroker connector implementation.
 *
 */
class TSConnectorMessageBroker extends TSConnection {


	public function connect() {
		global $smwgMessageBroker;
		$this->con = new StompConnection("tcp://$smwgMessageBroker:61613");
		$this->con->connect();
	}


	public function disconnect() {
		$this->con->disconnect();
	}


	public function send($topic, $commands) {
		global $smwgSPARULUpdateEncoding;
		if (!is_array($commands)) {
			$enc_commands = isset($smwgSPARULUpdateEncoding) && $smwgSPARULUpdateEncoding === "UTF-8" ? utf8_encode($commands) : $commands;
			$this->con->send($topic, $enc_commands);
			return;
		}
		$commandStr = implode("|||",$commands);
		$enc_commands = isset($smwgSPARULUpdateEncoding) && $smwgSPARULUpdateEncoding === "UTF-8" ? utf8_encode($commandStr) : $commandStr;
		$this->con->send($topic, $enc_commands);
	}



}

/**
 * REST webservice connector implementation.
 *
 */
class TSConnectorRESTWebservice extends TSConnection {

	public function connect() {
		global $smwgWebserviceUser, $smwgWebservicePassword, $smwgWebserviceEndpoint;
		list($host, $port) = explode(":", $smwgWebserviceEndpoint);
		$credentials = isset($smwgWebserviceUser) ? $smwgWebserviceUser.":".$smwgWebservicePassword : "";
		$this->con = new RESTWebserviceConnector($host, $port, "/sparul", $credentials);
	}

	public function disconnect() {
		// do nothing. webservice calls use stateless HTTP protocol.
	}

	public function send($topic, $commands) {
		if (!is_array($commands)) {
			$enc_commands = isset($smwgSPARULUpdateEncoding) && $smwgSPARULUpdateEncoding === "UTF-8" ? utf8_encode($commands) : $commands;
			$enc_commands = '<sparul><command><![CDATA['.$enc_commands.']]></command></sparul>';
			$this->con->update($enc_commands);
			return;
		}
		$enc_commands = "<sparul>";
		foreach($commands as $c) {
			$enc_command = isset($smwgSPARULUpdateEncoding) && $smwgSPARULUpdateEncoding === "UTF-8" ? utf8_encode($c) : $c;
			$enc_commands .= "<command><![CDATA[$enc_command]]></command>";
		}
		$enc_commands .= "</sparul>";

		$this->con->update($enc_commands);
	}
}

/**
 * SOAP webservice connector implementation.
 *
 */
class TSConnectorSOAPWebservice extends TSConnection {

	public function connect() {
		global $smwgWebserviceUser, $smwgWebservicePassword, $wgServer, $wgScript, $smwgUseLocalhostForWSDL;
		if (!isset($smwgDeployVersion) || !$smwgDeployVersion) ini_set("soap.wsdl_cache_enabled", "0");  //set for debugging
		if (isset($smwgUseLocalhostForWSDL) && $smwgUseLocalhostForWSDL === true) $host = "http://localhost"; else $host = $wgServer;
		$this->con = new SoapClient("$host$wgScript?action=ajax&rs=smwf_ws_getWSDL&rsargs[]=get_sparul", array('login'=>$smwgWebserviceUser, 'password'=>$smwgWebservicePassword));
	}

	public function disconnect() {
		// do nothing. webservice calls use stateless HTTP protocol.
	}

	public function send($topic, $commands) {
		if (!is_array($commands)) {
			$enc_commands = isset($smwgSPARULUpdateEncoding) && $smwgSPARULUpdateEncoding === "UTF-8" ? utf8_encode($commands) : $commands;
			$this->con->update($enc_commands);
			return;
		}
		$commandStr = implode("|||",$commands);
		$enc_commands = isset($smwgSPARULUpdateEncoding) && $smwgSPARULUpdateEncoding === "UTF-8" ? utf8_encode($commandStr) : $commandStr;
		$this->con->update($enc_commands);
	}
}
