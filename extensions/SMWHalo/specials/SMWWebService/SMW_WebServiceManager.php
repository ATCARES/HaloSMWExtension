<?php
/*  Copyright 2008, ontoprise GmbH
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

/**
 * This is the main entry file for the semantic Wiki Web Service extension.
 * 
 * @author Thomas Schweitzer
 * 
 */

// Include the settings file for the configuration of the Web Service extension.
global $smwgHaloIP;

require_once("$smwgHaloIP/specials/SMWWebService/SMW_WebServiceSettings.php");

// include the web service syntax parser
require_once("$smwgHaloIP/specials/SMWWebService/SMW_WebServiceUsage.php");


###
# If you already have custom namespaces on your site, insert
# $smwgWWSNamespaceIndex = ???;
# into your LocalSettings.php *before* including this file.
# The number ??? must be the smallest even namespace number
# that is not in use yet. However, it must not be smaller
# than 100. Semantic MediaWiki normally uses namespace numbers from 100 upwards.
##

// Register additional namespaces
if (!isset($smwgWWSNamespaceIndex)) {
	WebServiceManager::initWWSNamespaces(200);
} else {
	WebServiceManager::initWWSNamespaces();
}

global $wgLanguageCode;

smwfHaloInitContentLanguage($wgLanguageCode);
WebServiceManager::registerWWSNamespaces();

/**
 * This class contains the top level functionality of the Wiki Web Service
 * extensions. It
 * - provides access to the database
 * - creates instances of class WebService
 * - registers namespaces
 *
 */

class WebServiceManager {

	private static $mNewWebService = null;
	
	/**
	 * Register the WebServicePage for articles in the namespace 'WebService'.
	 *
	 * @return boolean
	 * 		Returns always <true>.
	 */
	static function showWebServicePage(&$title, &$article) {
		global $smwgHaloIP;
		if ($title->getNamespace() == SMW_NS_WEB_SERVICE) {
			require_once("$smwgHaloIP/specials/SMWWebService/SMW_WebServicePage.php");
			$article = new SMWWebServicePage($title);
		}
		return true;
	}
	
	/**
	 * Initializes the namespaces that are used by the Wiki Web Service extension.
	 * Normally the base index starts at 200. It must be an even number greater than
	 * than 100. However, by default Semantic MediaWiki uses the namespace indexes
	 * from 100 upwards. 
	 * 
	 * @param int $baseIndex
	 * 		Optional base index for all Wiki Web Service namespaces. The default is 200. 
	 */
	static function initWWSNamespaces($baseIndex = 200) {
		global $smwgWWSNamespaceIndex;
	
		if (!isset($smwgWWSNamespaceIndex)) {
			$smwgWWSNamespaceIndex = $baseIndex;
		}
	
		define('SMW_NS_WEB_SERVICE',       $smwgWWSNamespaceIndex);
		define('SMW_NS_WEB_SERVICE_TALK',  $smwgWWSNamespaceIndex+1);
	}
	
	/**
	 * Registers the new namespaces. Must be called after the language dependent
	 * messages have been installed.
	 *
	 */
	static function registerWWSNamespaces() {
		global $wgExtraNamespaces, $wgNamespaceAliases, $smwgHaloContLang;
		
		// Register namespace identifiers
		if (!is_array($wgExtraNamespaces)) { 
			$wgExtraNamespaces = array(); 
		}
		$wgExtraNamespaces = $wgExtraNamespaces + $smwgHaloContLang->getNamespaces();
		$wgNamespaceAliases = $wgNamespaceAliases + $smwgHaloContLang->getNamespaceAliases();
		
	}
	
	/**
	 * Initialized the wiki web service extension:
	 * - installs the extended representation of articles in the namespace 
	 *   'WebService'.
	 *
	 */
	static function initWikiWebServiceExtension() {
		global $wgRequest, $wgHooks, $wgParser;
		$action = $wgRequest->getVal('action');
	
		if ($action == 'ajax') {
			// Do not install the extension for ajax calls
			return;
		}
			
		// Install the extended representation of articles in the namespace 'WebService'.
		$wgHooks['ArticleFromTitle'][] = 'WebServiceManager::showWebServicePage';
		
		$wgParser->setHook('WebService', 'WebServiceManager::wwsdParserHook');
		$wgHooks['ArticleSaveComplete'][] = 'WebServiceManager::articleSavedHook';
		
		
		
		//--- TEST
				
/*		
		global $smwgHaloIP;
		require_once("$smwgHaloIP/specials/SMWWebService/SMW_WebService.php");
		
		$wwsd = '<webservice name="Weather">'.
				'    <uri name="http://weather.example.com/weather.wsdl" />'.
				'    <protocol>SOAP</protocol>'.
				'    <method name="getTemperature" />'.
				'    <parameter name="zipCode" defaultValue="98101" optional="false" path="cityWeather.zipCode" />'.
				'    <parameter name="zipCode" defaultValue="98100" optional="false" path="cityWeather.zipCode" />'.
				'    <result name="temp">'.
				'        <part name="celsius" path="cityWeather.temperature.celsius" />'.
				'        <part name="fahrenheit" path="cityWeather.temperature.fahrenheit" />'.
				'    </result>'.
				'    <result name="temperature">'.
				'        <part name="c" path="cityWeather.temperature.celsius" />'.
				'        <part name="f" path="cityWeather.temperature.fahrenheit" />'.
				'    </result>'.
				'    <displayPolicy>'.
				'        <once />'.
				'    </displayPolicy>'.
				'    <queryPolicy>'.
				'        <maxAge value="1440"/>'.
				'        <delay value="1"/>'.
				'    </queryPolicy>'.
				'    <spanOfLife value="180" expiresAfterUpdate="true"/>'.
				'</webservice>';

		$ws = WebService::newFromWWSD($wwsd);
		
		$ws = new WebService('MySecondWebService', 'http://some.uri.com', 'SOAP', 'getValue',
		                     '<parameters> </parameters>', '<result> </result>',
							 1000, 2000, 0, 90, false, true);
		$ws->store();
		$ws = WebService::newFromName('MySecondWebService');
		$id = $ws->getArticleID();
		$ws = WebService::newFromID($id);
		
		for ($i = 1900; $i < 2000; ++$i) {
			WSStorage::getDatabase()->addWSArticle($id, 142, $i);
		}
		for ($i = 2000; $i < 2100; ++$i) {
			WSStorage::getDatabase()->addWSProperty($i ,$id, 142, $i);
		}
*/
		//--- TEST
		
	}

	
	/**
	 * This function is called, when a <WebService>-tag for a WWSD has been 
	 * found in an article. If the content of the definition is correct, and
	 * if the namespace of the article is the WebService namespace, the WWSD
	 * will be stored in the database.
	 *
	 * @param string $input
	 * 		The content of the tag
	 * @param array $args
	 * 		Array of attributes in the tag
	 * @param Parser $parser
	 * 		The wiki text parser
	 * @return string
	 * 		The text to be rendered
	 */
	public static function wwsdParserHook($input, $args, $parser) {
		global $smwgHaloIP;
		require_once("$smwgHaloIP/specials/SMWWebService/SMW_WebService.php");
		
		$attr = "";
		foreach ($args as $k => $v) {
			$attr .= " ". $k . '="' . $v . '"';
		}
		$completeWWSD =
			"<WebService$attr>".
			$input.
			"</WebService>\n";
			
		$notice = '';
		$name = $parser->mTitle->getText();
		$id = $parser->mTitle->getArticleID();
		$ws = WebService::newFromWWSD($name, $completeWWSD);
		if (!is_array($ws)) {
			// A web service object was returned. Validate the definition
			// with respect to the WSDL.
			$res = $ws->validateWithWSDL();
			if (is_array($res)) {
				// Error messages were returned
				$ws = $res;
			}
		}
		if (is_array($ws)) {
			// Errors within the WWSD => show them as a bullet list
			$msg = '<b>'.wfMsg('smw_wws_wwsd_errors').'</b><ul>';
			foreach ($ws as $err) {
				$msg .= '<li>'.$err.'</li>';
			}
			$msg .= '</ul>';
			return "<pre>\n".htmlspecialchars($completeWWSD)."\n</pre><br />". $msg;
		} else {
			if ($parser->mTitle->getNamespace() == SMW_NS_WEB_SERVICE) {
				// store the WWSD in the database in the hook function <articleSavedHook>.
				self::$mNewWebService = $ws;
			} else {
				// add message: namespace webService needed.
				$notice = "<b>".wfMsg('smw_wws_wwsd_needs_namespace')."</b>";
			}
		}
		return  "<pre>\n".htmlspecialchars($completeWWSD)."\n</pre>".$notice;
	}
	
	/**
	 * Stores the previously parsed WWSD in the database. This function is a hook for
	 * 'ArticleSaveComplete.'
	 *
	 * @param Article $article
	 * @param User $user
	 * @param string $text
	 * @return boolean true
	 */
	public static function articleSavedHook(&$article, &$user, &$text) {
		if (self::$mNewWebService) {
			self::$mNewWebService->store();
		}
		return true;
	}
	
	/**
	 * Creates the database tables that are used by the web service extension.
	 *
	 */
	public static function initDatabaseTables() {
		global $smwgHaloIP;
		require_once("$smwgHaloIP/specials/SMWWebService/SMW_WSStorage.php");
		WSStorage::getDatabase()->initDatabaseTables();	
	}
}

?>