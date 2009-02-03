<?php
/**
 * @author: Kai K�hn
 * 
 * Created on: 27.01.2009
 */
if( !defined( 'MEDIAWIKI' ) ) {
	echo("This file is an extension to the MediaWiki software and cannot be used standalone.\n");
	die(1);
}

define('US_HIGH_TOLERANCE', 0);
define('US_LOWTOLERANCE', 1);
define('US_EXACTMATCH', 2);

$wgExtensionCredits['unifiedsearch'][] = array(
        'name' => 'Unified search',
        'author' => 'Kai K�hn',
        'url' => 'http://sourceforge.net/projects/halo-extension/',
        'description' => 'Combining a Lucene backend with a title search',
);

global $wgExtensionFunctions, $wgHooks;

// use SMW_AddScripts hook from SMWHalo to make sure that Prototype is available.
$wgHooks['SMW_AddScripts'][]='wfUSAddHeader';
$wgExtensionFunctions[] = 'wfUSSetupExtension';

/**
 * Add javascripts and css files
 *
 * @param unknown_type $out
 * @return unknown
 */
function wfUSAddHeader(& $out) {
	global $wgScriptPath;
	$out->addLink(array(
                    'rel'   => 'stylesheet',
                    'type'  => 'text/css',
                    'media' => 'screen, projection',
                    'href'  => $wgScriptPath . '/extensions/UnifiedSearch/skin/unified_search.css'
                    ));
                    $out->addScript('<script type="text/javascript" src="'.$wgScriptPath . '/extensions/UnifiedSearch/scripts/unified_search.js"></script>');
                    return true;
}

/**
 * Initializes PermissionACL extension
 *
 * @return unknown
 */
function wfUSSetupExtension() {
	global $wgAutoloadClasses, $wgSpecialPages, $wgScriptPath, $wgHooks, $wgSpecialPageGroups;
	wfUSInitUserMessages();
	wfUSInitContentMessages();
	$dir = 'extensions/UnifiedSearch/';
	global $smwgHaloIP;
	$wgAutoloadClasses['SMWAdvRequestOptions'] = $smwgHaloIP . '/includes/SMW_DBHelper.php';
	$wgAutoloadClasses['USStore'] = $dir . 'storage/US_Store.php';
	$wgAutoloadClasses['SMWStore2Adv'] = $dir . 'storage/SMW_Store2Adv.php';
	$wgAutoloadClasses['SKOSVocabulary'] = $dir . 'SKOSVocabulary.php';
	$wgAutoloadClasses['USSpecialPage'] = $dir . 'UnifiedSearchSpecialPage.php';
	$wgAutoloadClasses['UnifiedSearchResultPrinter'] = $dir . 'UnifiedSearchResultPrinter.php';
	$wgAutoloadClasses['UnifiedSearchResult'] = $dir . 'UnifiedSearchResultPrinter.php';
	$wgAutoloadClasses['UnifiedSearchStatistics'] = $dir . 'UnifiedSearchStatistics.php';

	$wgAutoloadClasses['QueryExpander'] = $dir . 'QueryExpander.php';
	$wgAutoloadClasses['LuceneSearch'] = $dir . 'MWSearch/MWSearch_body.php';
	$wgAutoloadClasses['LuceneResult'] = $dir . 'MWSearch/MWSearch_body.php';
	$wgAutoloadClasses['LuceneSearchSet'] = $dir . 'MWSearch/MWSearch_body.php';
	
	$wgSpecialPages['UnifiedSearchStatistics'] = array('SMWSpecialPage','UnifiedSearchStatistics', 'smwfDoSpecialUSSearch', $dir . 'UnifiedSearchStatistics.php');
    //$wgSpecialPageGroups['UnifiedSearchStatistics'] = 'maintenance';
    
	$wgSpecialPages['Search'] = array('USSpecialPage');
	
	return true;
}

/**
 * Registers ACL messages.
 */
function wfUSInitUserMessages() {
	global $wgMessageCache, $wgLang, $IP;

	$usLangClass = 'US_Language' . str_replace( '-', '_', ucfirst( $wgLang->getCode() ) );

	if (file_exists($IP.'/extensions/UnifiedSearch/languages/'. $usLangClass . '.php')) {
		include_once('extensions/UnifiedSearch/languages/'. $usLangClass . '.php' );
	}
	// fallback if language not supported
	if ( !class_exists($usLangClass)) {
		include_once('extensions/UnifiedSearch/languages/US_LanguageEn.php' );
		$aclgHaloLang = new US_LanguageEn();
	} else {
		$aclgHaloLang = new $usLangClass();
	}
	$wgMessageCache->addMessages($aclgHaloLang->us_userMessages, $wgLang->getCode());


}

function wfUSInitContentMessages() {
	global $wgMessageCache, $wgLanguageCode, $IP;
	$usLangClass = 'US_Language' . str_replace( '-', '_', ucfirst( $wgLanguageCode) );
	if (file_exists($IP.'/extensions/UnifiedSearch/languages/'. $usLangClass . '.php')) {
		include_once($IP.'/extensions/UnifiedSearch/languages/'. $usLangClass . '.php' );
	}
	// fallback if language not supported
	if ( !class_exists($usLangClass)) {
		include_once('extensions/UnifiedSearch/languages/US_LanguageEn.php' );
		$aclgHaloLang = new US_LanguageEn();
	} else {
		$aclgHaloLang = new $usLangClass();
	}

	$wgMessageCache->addMessages($aclgHaloLang->us_contentMessages, $wgLanguageCode);

}

/**
 * Creates necessary ontology elements (SKOS)
 *
 */
function wfUSInitialize() {
	wfUSInitializeSKOSOntology();
	wfUSInitializeTables();
	return true;
}

function wfUSInitializeTables() {
	USStore::getStore()->setup(true);
}

function wfUSInitializeSKOSOntology() {
	global $smwgContLang, $smwgHaloContLang;
	$verbose = true;
	print ("Creating predefined SKOS properties...\n");
	foreach(SKOSVocabulary::$ALL as $id => $page) {
		if ($page instanceof Title) {
			$t = $page;
			$name = $t->getText();
			$text = "";
		} else if ($page instanceof SMWPropertyValue) {
			$t = Title::newFromText($page->getXSDValue(), SMW_NS_PROPERTY);
			$name = $t->getText();
			$propertyLabels = $smwgContLang->getPropertyLabels();
			$namespaces = $smwgContLang->getNamespaces();
			$datatypeLabels = $smwgContLang->getDatatypeLabels();
			$haloSchemaProperties = $smwgHaloContLang->getSpecialSchemaPropertyArray();
			$text = "\n\n[[".$propertyLabels['_TYPE']."::".$namespaces[SMW_NS_TYPE].":".$datatypeLabels[SKOSVocabulary::$TYPES[$id]]."]]";
			$text .= "\n\n[[".$haloSchemaProperties[SMW_SSP_HAS_DOMAIN_AND_RANGE_HINT]."::".SKOSVocabulary::$ALL['us_skos_term']->getPrefixedText()."]]";
		}

		$article = new Article($t);
		if (!$t->exists()) {
			$article->insertNewArticle($text, "", false, false);
			print ("   ... Create page ".$t->getNsText().":".$t->getText()."...\n");
		} else {
			// save article again. Necessary when storage implementation has switched.
			$rev = Revision::newFromTitle($t);
			$article->doEdit($rev->getRawText(), $rev->getRawComment(), EDIT_UPDATE | EDIT_FORCE_BOT);
			print ("   ... re-saved page ".$t->getNsText().":".$t->getText().".\n");
		}
	}
}

?>