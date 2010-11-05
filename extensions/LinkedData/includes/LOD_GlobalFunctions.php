<?php
/**
 * @file
 * @ingroup LinkedData
 */

/*  Copyright 2010, ontoprise GmbH
*  This file is part of the LinkedData-Extension.
*
*   The LinkedData-Extension is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; either version 3 of the License, or
*   (at your option) any later version.
*
*   The LinkedData-Extension is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


/**
 * This file contains global functions that are called from the LinkedData
 * extension.
 *
 * @author Thomas Schweitzer
 *
 */
if ( !defined( 'MEDIAWIKI' ) ) {
    die( "This file is part of the LinkedData extension. It is not a valid entry point.\n" );
}

require_once("$lodgIP/includes/LOD_AjaxConnector.php");

/**
 * Switch on LinkedData. This function must be called in
 * LocalSettings.php after LOD_Initialize.php was included and default values
 * that are defined there have been modified.
 * For readability, this is the only global function that does not adhere to the
 * naming conventions.
 *
 * This function installs the extension, sets up all autoloading, special pages
 * etc.
 */
function enableLinkedData() {
    global $lodgIP, $wgExtensionFunctions, $wgAutoloadClasses, $wgSpecialPages, 
           $wgSpecialPageGroups, $wgHooks, $wgExtensionMessagesFiles, 
           $wgJobClasses, $wgExtensionAliasesFiles;

    require_once("$lodgIP/includes/LOD_ParserFunctions.php");

    $wgExtensionFunctions[] = 'lodfSetupExtension';
    $wgHooks['LanguageGetMagic'][] = 'lodfAddMagicWords'; // setup names for parser functions (needed here)
    $wgExtensionMessagesFiles['LinkedData'] = $lodgIP . '/languages/LOD_Messages.php'; // register messages (requires MW=>1.11)

    // Register special pages aliases file
    $wgExtensionAliasesFiles['LinkedData'] = $lodgIP . '/languages/LOD_Aliases.php';

    ///// Set up autoloading; essentially all classes should be autoloaded!
	$wgAutoloadClasses['LODStorage'] = $lodgIP . '/includes/LOD_Storage.php';
    
	$wgAutoloadClasses['LODSourceDefinition'] = $lodgIP . '/includes/LODAdministration/LOD_SourceDefinition.php';
	$wgAutoloadClasses['LODAdministrationStore'] = $lodgIP . '/includes/LODAdministration/LOD_AdministrationStore.php';

	$wgAutoloadClasses['LODSparqlQueryResult']   = $lodgIP . '/storage/TripleStore/LOD_SparqlQueryResult.php';
	$wgAutoloadClasses['LODSparqlResultURI'] 	 = $lodgIP . '/storage/TripleStore/LOD_SparqlQueryResult.php';
	$wgAutoloadClasses['LODSparqlResultLiteral'] = $lodgIP . '/storage/TripleStore/LOD_SparqlQueryResult.php';
	$wgAutoloadClasses['LODTriple']            = $lodgIP . '/storage/TripleStore/LOD_Triple.php';
	$wgAutoloadClasses['LODTripleStoreAccess'] = $lodgIP . '/storage/TripleStore/LOD_TripleStoreAccess.php';
	$wgAutoloadClasses['LODPersistentTripleStoreAccess'] 
											   = $lodgIP . '/storage/TripleStore/LOD_PersistentTripleStoreAccess.php';
	
	$wgAutoloadClasses['ILODMappingStore']	= $lodgIP . '/includes/LODMapping/ILOD_MappingStore.php';
	$wgAutoloadClasses['LODMapping'] 		= $lodgIP . '/includes/LODMapping/LOD_Mapping.php';
	$wgAutoloadClasses['LODMappingStore'] 	= $lodgIP . '/includes/LODMapping/LOD_MappingStore.php';
	$wgAutoloadClasses['LODMappingTripleStore'] = $lodgIP . '/includes/LODMapping/LOD_MappingTripleStore.php';
	$wgAutoloadClasses['LODPersistentMappingStore'] = $lodgIP . '/includes/LODMapping/LOD_PersistentMappingStore.php';
	
	$wgAutoloadClasses['LODMLClassMapping'] = $lodgIP . '/includes/LODMLApi/LOD_MLClassMapping.php';
	$wgAutoloadClasses['LODMLEquivalentClassMapping'] = $lodgIP . '/includes/LODMLApi/LOD_MLEquivalentClassMapping.php';
	$wgAutoloadClasses['LODMLEquivalentPropertyMapping'] = $lodgIP . '/includes/LODMLApi/LOD_MLEquivalentPropertyMapping.php';
	$wgAutoloadClasses['LODMLMapping'] = $lodgIP . '/includes/LODMLApi/LOD_MLMapping.php';
	$wgAutoloadClasses['LODMLMappingLanguageAPI'] = $lodgIP . '/includes/LODMLApi/LOD_MLMappingLanguageAPI.php';
	$wgAutoloadClasses['LODMLPropertyMapping'] = $lodgIP . '/includes/LODMLApi/LOD_MLPropertyMapping.php';
	$wgAutoloadClasses['LODMLR2RMapping'] = $lodgIP . '/includes/LODMLApi/LOD_MLR2RMapping.php';
	$wgAutoloadClasses['LODMLStatementBasedMapping'] = $lodgIP . '/includes/LODMLApi/LOD_MLStatementBasedMapping.php';
	$wgAutoloadClasses['LODMLSubclassMapping'] = $lodgIP . '/includes/LODMLApi/LOD_MLSubclassMapping.php';
	$wgAutoloadClasses['LODMLSubpropertyMapping'] = $lodgIP . '/includes/LODMLApi/LOD_MLSubpropertyMapping.php';

	$wgAutoloadClasses['LODImporter'] = $lodgIP . '/includes/LODImport/LOD_Importer.php';
	
	//--- Non-existing page handler ---
	$wgAutoloadClasses['LODNonExistingPageHandler'] = $lodgIP . '/includes/LODWikiFrontend/LOD_NonExistingPageHandler.php';
	$wgAutoloadClasses['LODNonExistingPage'] = $lodgIP . '/includes/LODWikiFrontend/LOD_NonExistingPage.php';
	
	//--- Meta-data query printers ---
	$wgAutoloadClasses['LODMetaDataQueryPrinter'] = $lodgIP . '/includes/LODWikiFrontend/MetaDataQueryPrinter/LOD_MetaDataQueryPrinter.php';
	$wgAutoloadClasses['LODMetaDataPrinter'] = $lodgIP . '/includes/LODWikiFrontend/MetaDataQueryPrinter/LOD_MetaDataPrinter.php';
	$wgAutoloadClasses['LODMDPTable'] = $lodgIP . '/includes/LODWikiFrontend/MetaDataQueryPrinter/LOD_MDP_Table.php';
	$wgAutoloadClasses['LODMDPError'] = $lodgIP . '/includes/LODWikiFrontend/MetaDataQueryPrinter/LOD_MDP_Error.php';
	$wgAutoloadClasses['LODMDPXslt'] = $lodgIP . '/includes/LODWikiFrontend/MetaDataQueryPrinter/LOD_MDP_XSLT.php';
	
	//--- Derived data value classes for meta-data query printers ---
	$wgAutoloadClasses['LODStringValue'] = $lodgIP . '/includes/LODWikiFrontend/MetaDataQueryPrinter/LOD_DV_String.php';
	$wgAutoloadClasses['LODURIValue'] = $lodgIP . '/includes/LODWikiFrontend/MetaDataQueryPrinter/LOD_DV_URI.php';
	$wgAutoloadClasses['LODWikiPageValue'] = $lodgIP . '/includes/LODWikiFrontend/MetaDataQueryPrinter/LOD_DV_WikiPage.php';
	$wgAutoloadClasses['LODNumberValue'] = $lodgIP . '/includes/LODWikiFrontend/MetaDataQueryPrinter/LOD_DV_Number.php';
	$wgAutoloadClasses['LODTemperatureValue'] = $lodgIP . '/includes/LODWikiFrontend/MetaDataQueryPrinter/LOD_DV_Temperature.php';
	$wgAutoloadClasses['LODTimeValue'] = $lodgIP . '/includes/LODWikiFrontend/MetaDataQueryPrinter/LOD_DV_Time.php';
	$wgAutoloadClasses['LODBoolValue'] = $lodgIP . '/includes/LODWikiFrontend/MetaDataQueryPrinter/LOD_DV_Bool.php';
	$wgAutoloadClasses['LODRecordValue'] = $lodgIP . '/includes/LODWikiFrontend/MetaDataQueryPrinter/LOD_DV_Record.php';

	//--- Classes for prefix management ---
	$wgAutoloadClasses['LODPrefixManager'] = $lodgIP . '/includes/LODAccess/LOD_PrefixManager.php';
	
	//--- Classes for rating triples ---
	$wgAutoloadClasses['LODRatingAccess'] = $lodgIP . '/includes/LODAccess/LODRating/LOD_RatingAccess.php';
	$wgAutoloadClasses['LODRating']       = $lodgIP . '/includes/LODAccess/LODRating/LOD_Rating.php';
	$wgAutoloadClasses['LODRatingRewriter'] = $lodgIP . '/includes/LODAccess/LODRating/LOD_RatingRewriter.php';
	$wgAutoloadClasses['LODSparqlRatingSerializer'] = $lodgIP . '/includes/LODAccess/LODRating/LOD_SparqlRatingSerializer.php';
	$wgAutoloadClasses['LODRatingTripleInfo'] = $lodgIP . '/includes/LODAccess/LODRating/LOD_RatingTripleInfo.php';
	$wgAutoloadClasses['LODQueryAnalyzer'] = $lodgIP . '/includes/LODAccess/LODRating/LOD_QueryAnalyzer.php';

	//--- UI/HTML for rating triples ---
	$wgAutoloadClasses['LODQueryResultRatingUI'] = $lodgIP . '/includes/LODWikiFrontend/RatingUI/LOD_QueryResultRating.php';
	
	//--- SPARQL queries ---
	$wgAutoloadClasses['LODSparqlQueryVisitor'] = $lodgIP . '/includes/LODAccess/LODSparql/LOD_SparqlQueryVisitor.php';
	$wgAutoloadClasses['LODSparqlSerializerVisitor'] = $lodgIP . '/includes/LODAccess/LODSparql/LOD_SparqlSerializerVisitor.php';
	$wgAutoloadClasses['LODSparqlQueryParser'] = $lodgIP . '/includes/LODAccess/LODSparql/LOD_SparqlQueryParser.php';
	
    //--- Autoloading for exception classes ---
   	$wgAutoloadClasses['LODException']        = $lodgIP . '/exceptions/LOD_Exception.php';
   	$wgAutoloadClasses['LODMappingException'] = $lodgIP . '/exceptions/LOD_MappingException.php';
   	$wgAutoloadClasses['LODTSAException']     = $lodgIP . '/exceptions/LOD_TSAException.php';
   	$wgAutoloadClasses['LODPrefixManagerException'] = $lodgIP . '/exceptions/LOD_PrefixManagerException.php';
   	$wgAutoloadClasses['LODRatingException'] = $lodgIP . '/exceptions/LOD_RatingException.php';
   	
    //--- Autoloading for libraries ---
	
	// register query printers
	global $smwgResultFormats;
	$smwgResultFormats['ld_table'] = 'LODMetadataTablePrinter';
	$wgAutoloadClasses['LODMetadataTablePrinter'] = $lodgIP . '/includes/LODWikiFrontend/LOD_MetadataTablePrinter.php';
	
	// register special pages
	$wgAutoloadClasses['LODSourcesPage']       = $lodgIP . '/specials/LODSources/LOD_SpecialSources.php';
    $wgSpecialPages['LODSpecialSources']       = array( 'LODSourcesPage' );
    $wgSpecialPageGroups['LODSpecialSources']  = 'lod_group';

    return true;
}

/**
 * Do the actual initialisation of the extension. This is just a delayed init that
 * makes sure MediaWiki is set up properly before we add our stuff.
 *
 * The main things this function does are: register all hooks, set up extension
 * credits, and init some globals that are not for configuration settings.
 */
function lodfSetupExtension() {
    wfProfileIn('lodfSetupExtension');
    global $lodgIP, $wgHooks, $wgParser, $wgExtensionCredits,
    	   $wgLanguageCode, $wgVersion, $wgRequest, $wgContLang, $lodgNEPEnabled,
    	   $lodgEnableMetaDataQueryPrinter;

    //--- Register hooks ---
    global $wgHooks;
    
    $wgHooks['ArticleDelete'][]			= 'LODParserFunctions::articleDelete';
    $wgHooks['OutputPageBeforeHTML'][]	= 'LODParserFunctions::outputPageBeforeHTML';
    
    if ($lodgNEPEnabled) {
	 	$wgHooks['ArticleFromTitle'][]		= 'LODNonExistingPageHandler::onArticleFromTitle';
	    $wgHooks['EditFormPreloadText'][]	= 'LODNonExistingPageHandler::onEditFormPreloadText';
    }    

    lodfSetupMetaDataQueryPrinter();
    lodfSetupRating();
		    
    //--- Load messages---
    wfLoadExtensionMessages('LinkedData');
    
    ///// Register specials pages
    global $wgSpecialPages, $wgSpecialPageGroups;
//    $wgSpecialPages['LinkedData']      = array('LinkedDataSpecial');
//    $wgSpecialPageGroups['LinkedData'] = 'lod_group';

    //-- includes for Ajax calls --
/*
    global $wgUseAjax, $wgRequest;
    if ($wgUseAjax && $wgRequest->getVal('action') == 'ajax' ) {
		$funcName = isset( $_POST["rs"] ) 
						? $_POST["rs"] 
						: (isset( $_GET["rs"] ) ? $_GET["rs"] : NULL);
    	if (strpos($funcName, 'lod') === 0) {
			require_once('LOD_....php');
    	}
    }
*/    
    //--- credits (see "Special:Version") ---
    $wgExtensionCredits['other'][]= array(
        'name'=>'LinkedData',
        'version'=>LOD_LINKEDDATA_VERSION,
        'author'=>"Thomas Schweitzer",
        'url'=>'http://smwforum.ontoprise.de',
        'description' => 'Embed linked data in your wiki.');

    // Register autocompletion icon
//    $wgHooks['smwhACNamespaceMappings'][] = 'lodfRegisterACIcon';

    wfProfileOut('lodfSetupExtension');
    
    // Configure the store(s) for this extension
    lodfInitStores();
    
    return true;
}

/**********************************************/
/***** namespace settings                 *****/
/**********************************************/

/**
 * Init the additional namespaces used by LinkedData. The
 * parameter denotes the least unused even namespace ID that is
 * greater or equal to 100.
 */
function lodfInitNamespaces() {

    global $lodgNamespaceIndex, $wgExtraNamespaces, $wgNamespaceAliases,
    $wgNamespacesWithSubpages, $wgLanguageCode, $lodgContLang;

    if (!isset($lodgNamespaceIndex)) {
        $lodgNamespaceIndex = 500;
    }

    // Constants for namespace "LOD"
    define('LOD_NS_LOD',       $lodgNamespaceIndex);
    define('LOD_NS_LOD_TALK',  $lodgNamespaceIndex+1);
    
    // Constants for namespace "Mapping"
    define('LOD_NS_MAPPING',       $lodgNamespaceIndex+2);
    define('LOD_NS_MAPPING_TALK',  $lodgNamespaceIndex+3);

    lodfInitContentLanguage($wgLanguageCode);

    // Register namespace identifiers
    if (!is_array($wgExtraNamespaces)) {
        $wgExtraNamespaces=array();
    }
    $namespaces = $lodgContLang->getNamespaces();
    $namespacealiases = $lodgContLang->getNamespaceAliases();
    $wgExtraNamespaces = $wgExtraNamespaces + $namespaces;
    $wgNamespaceAliases = $wgNamespaceAliases + $namespacealiases;

    // Support subpages for the namespace ACL
    $wgNamespacesWithSubpages = $wgNamespacesWithSubpages + array(
        LOD_NS_LOD => true,
        LOD_NS_LOD_TALK => true
    );
}


/**********************************************/
/***** language settings                  *****/
/**********************************************/

/**
 * Set up (possibly localised) names for LinkedData
 */
function lodfAddMagicWords(&$magicWords, $langCode) {
//	$magicWords['ask']     = array( 0, 'ask' );
    return true;
}

/**
 * Initialise a global language object for content language. This
 * must happen early on, even before user language is known, to
 * determine labels for additional namespaces. In contrast, messages
 * can be initialised much later when they are actually needed.
 */
function lodfInitContentLanguage($langcode) {
    global $lodgIP, $lodgContLang;
    if (!empty($lodgContLang)) {
        return;
    }
    wfProfileIn('lodfInitContentLanguage');

    $lodContLangFile = 'LOD_Language' . str_replace( '-', '_', ucfirst( $langcode ) );
    $lodContLangClass = 'LODLanguage' . str_replace( '-', '_', ucfirst( $langcode ) );
    if (file_exists($lodgIP . '/languages/'. $lodContLangFile . '.php')) {
        include_once( $lodgIP . '/languages/'. $lodContLangFile . '.php' );
    }

    // fallback if language not supported
    if ( !class_exists($lodContLangClass)) {
        include_once($lodgIP . '/languages/LOD_LanguageEn.php');
        $lodContLangClass = 'LODLanguageEn';
    }
    $lodgContLang = new $lodContLangClass();

    wfProfileOut('lodfInitContentLanguage');
}

function lodfRegisterACIcon(& $namespaceMappings) {	
//    global $lodgIP;
//    $namespaceMappings[LOD_NS_LOD]="/extensions/LinkedData/skins/images/LOD_AutoCompletion.gif";
    return true;
}

/**
 * Setup of the meta-data query printers. This feature has to be enabled with
 * the global variable $lodgEnableMetaDataQueryPrinter in LOD_Initialize.php.
 * Do not call this method. It is called from lodfSetupExtension.
 */
function lodfSetupMetaDataQueryPrinter() {
	global $wgHooks, $lodgEnableMetaDataQueryPrinter, $lodgMetaDataPrinters;
	
    if ($lodgEnableMetaDataQueryPrinter) {
		$wgHooks['smwInitDatatypes'][]     = 'LODMetaDataQueryPrinter::onSmwInitDatatypesHooks';
		$wgHooks['ProcessQueryResults'][]  = 'LODMetaDataQueryPrinter::onProcessQueryResults';
    }

	# array(string ID => string className)
	# This array maps from meta data printer IDs to the classes that print the meta
	# data. The ID is the same as the one used in the "metadataformat" parameter
	# in a query. It must be completely in lower case. 
	# The className is the name of the class that formats the meta data and attaches
	# it to the data value.
	$lodgMetaDataPrinters = array(
		'table' => 'LODMDPTable',
		'xslt'  => 'LODMDPXslt',
	
	);
	
}

/**
 * Setup of the rating features for triples.
 * Do not call this method. It is called from lodfSetupExtension.
 * The rating feature needs the meta-data query printer as prerequisite.
 */
function lodfSetupRating() {
	global $wgHooks, $lodgEnableMetaDataQueryPrinter;
	
    if (!$lodgEnableMetaDataQueryPrinter) {
    	// No rating possible without the meta-data query printer
    	return;
    }
	
    $wgHooks['ProcessSPARQLXMLResults'][] = 'LODRatingAccess::onProcessSPARQLXMLResults';
    
	global $lodgScriptPath;
		
	$css = "rating.css";
	$cssFile = $lodgScriptPath . "/skins/$css";
	SMWOutputs::requireHeadItem($css,
			'<link rel="stylesheet" media="screen, projection" type="text/css" href="'.$cssFile.'" />');
	$css = "jquery.fancybox-1.3.1.css";
	$cssFile = $lodgScriptPath . "/skins/$css";
	SMWOutputs::requireHeadItem($css,
			'<link rel="stylesheet" media="screen, projection" type="text/css" href="'.$cssFile.'" />');
	
	$script = "LOD_Rating.js";
	$scriptFile = $lodgScriptPath . "/scripts/$script";
	SMWOutputs::requireHeadItem($script,
			'<script type="text/javascript" src="' . $scriptFile . '"></script>');
	$script = "jquery.fancybox-1.3.1.js";
	$scriptFile = $lodgScriptPath . "/scripts/$script";
	SMWOutputs::requireHeadItem($script,
			'<script type="text/javascript" src="' . $scriptFile . '"></script>');
	
}