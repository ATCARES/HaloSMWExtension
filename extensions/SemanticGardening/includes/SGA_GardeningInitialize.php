<?php

define('SGA_GARDENING_EXTENSION_VERSION', "1.0");

// register initialize function
global $wgExtensionFunctions, $sgagIP, $IP;
$wgExtensionFunctions[] = 'sgagGardeningSetupExtension';
$sgagIP = $IP."/extensions/SemanticGardening";

$wgExtensionCredits['semanticgardening'][] = array(
        'name' => 'Semantic Gardening extension v'.SGA_GARDENING_EXTENSION_VERSION,
        'author' => 'Kai K�hn',
        'url' => 'http://sourceforge.net/projects/halo-extension/',
        'description' => 'Gardening keeps your wiki clean and consistent and is a basis for '.
            'several other features like term import, webservice import or semantic notifications.',
);

function sgagGardeningSetupExtension() {

	global $wgAutoloadClasses, $wgHooks, $sgagIP;

	$wgHooks['smwhaloBeforeUpdateData'][] = 'sgagBeforeUpdateData';
	$wgHooks['smwhaloAfterUpdateData'][] = 'sgagAfterUpdateData';
	
	$wgHooks['BeforePageDisplay'][]='sgafGAAddHTMLHeader';
	$wgHooks['BeforePageDisplay'][]='sgaFWAddHTMLHeader';
	$wgHooks['ArticleSaveComplete'][] = 'sgafHaloSaveHook'; // gardening update (SMW does the storing)
	$wgHooks['ArticleDelete'][] = 'sgafHaloPreDeleteHook';
	$wgHooks['ArticleSave'][] = 'sgafHaloPreSaveHook';
	$wgAutoloadClasses['SGAGardening'] = $sgagIP . '/includes/SGA_Gardening.php';
	$wgAutoloadClasses['SGAGardeningTableResultPrinter'] = $sgagIP . '/includes/SGA_QP_GardeningTable.php';
	if (property_exists('SMWQueryProcessor','formats')) { // registration up to SMW 1.2.*
		SMWQueryProcessor::$formats['table'] = 'SGAGardeningTableResultPrinter'; // overwrite SMW printer
			
	} else { // registration since SMW 1.3.*
		global $smwgResultFormats;
		$smwgResultFormats['table'] = 'SGAGardeningTableResultPrinter'; // overwrite SMW printer

	}

	global $sgagLocalGardening, $wgJobClasses;
	$wgJobClasses['SMW_LocalGardeningJob'] = 'SMW_LocalGardeningJob';

	global $wgRequest;
	$action = $wgRequest->getVal('action');
	if ($action != 'ajax') {
		sgafGardeningInitMessages();
	}

	if ($action == 'ajax') {
		$method_prefix = sgafGetAjaxMethodPrefix();

		// decide according to ajax method prefix which script(s) to import
		switch($method_prefix) {

			case '_ga_' :
				require_once($sgagIP . '/includes/SGA_GardeningAjaxAccess.php');
				break;
			case '_fw_' :
				require_once($sgagIP . '/includes/findwork/SGA_FindWorkAjaxAccess.php');
				break;
		}
	} else {
		global $wgSpecialPages, $wgSpecialPageGroups;
		$wgAutoloadClasses['SGAGardening'] = $sgagIP . '/includes/SGA_Gardening.php';
		$wgSpecialPages['Gardening'] = array('SGAGardening');
		$wgSpecialPageGroups['Gardening'] = 'smwplus_group';

		$wgSpecialPages['GardeningLog'] = array('SMWSpecialPage','GardeningLog', 'smwfDoSpecialLogPage', $sgagIP . '/includes/SGA_GardeningLogPage.php');
		$wgSpecialPageGroups['GardeningLog'] = 'smwplus_group';

		$wgSpecialPages['FindWork'] = array('SMWSpecialPage','FindWork', 'smwfDoSpecialFindWorkPage', $sgagIP . '/includes/findwork/SGA_FindWork.php');
		$wgSpecialPageGroups['FindWork'] = 'smwplus_group';

	}
	require_once($sgagIP . '/includes/jobs/SGA_LocalGardeningJob.php');
	return true;
}

function sgagBeforeUpdateData(& $data) {
	global $sgagCurrentAnnotationsToUpdate;
	$sgagCurrentAnnotationsToUpdate = SMWSuggestStatistics::getStore()->getRatedAnnotations($data->getSubject());
	return true;
}

function sgagAfterUpdateData(& $data) {
	global $sgagCurrentAnnotationsToUpdate;
	if ($sgagCurrentAnnotationsToUpdate !== NULL) {
		foreach($sgagCurrentAnnotationsToUpdate as $pa) {
			SMWSuggestStatistics::getStore()->rateAnnotation($data->getSubject()->getDBkey(), $pa[0], $pa[1], $pa[2] );
		}
	}
	return true;
}


function sgafGardeningInitMessages() {
	global $sgagMessagesInitialized;
	if (!$sgagMessagesInitialized) {
		wfGAInitUserMessages();
		wfGAInitContentMessages();
		$sgagMessagesInitialized = true;
	}
}

/**
 * Registers ACL messages.
 */
function wfGAInitUserMessages() {

	global $wgMessageCache, $wgLang, $sgagIP;

	$usLangClass = 'SGA_Language' . str_replace( '-', '_', ucfirst( $wgLang->getCode() ) );

	if (file_exists($sgagIP.'/languages/'. $usLangClass . '.php')) {
		include_once($sgagIP.'/languages/'. $usLangClass . '.php' );
	}
	// fallback if language not supported
	if ( !class_exists($usLangClass)) {
		include_once('extensions/SemanticGardening/languages/SGA_LanguageEn.php' );
		$aclgHaloLang = new SGA_LanguageEn();
	} else {
		$aclgHaloLang = new $usLangClass();
	}
	$wgMessageCache->addMessages($aclgHaloLang->userMessages, $wgLang->getCode());


}

function wfGAInitContentMessages() {
	global $wgMessageCache, $wgLanguageCode, $sgagIP;
	$usLangClass = 'SGA_Language' . str_replace( '-', '_', ucfirst( $wgLanguageCode) );
	if (file_exists($sgagIP.'/languages/'. $usLangClass . '.php')) {
		include_once($sgagIP.'/languages/'. $usLangClass . '.php' );
	}
	// fallback if language not supported
	if ( !class_exists($usLangClass)) {
		include_once($sgagIP.'/languages/SGA_LanguageEn.php' );
		$aclgHaloLang = new SGA_LanguageEn();
	} else {
		$aclgHaloLang = new $usLangClass();
	}

	$wgMessageCache->addMessages($aclgHaloLang->contentMessages, $wgLanguageCode);

}


/**
 * Called *before* an article is saved. Used for LocalGardening
 *
 * @param Article $article
 * @param User $user
 * @param string $text
 * @param string $summary
 * @param bool $minor
 * @param bool $watch
 * @param unknown_type $sectionanchor
 * @param int $flags
 */
function sgafHaloPreSaveHook(&$article, &$user, &$text, &$summary, $minor, $watch, $sectionanchor, &$flags) {
	// -- LocalGardening --

	global $sgagLocalGardening;
	if (isset($sgagLocalGardening) && $sgagLocalGardening == true && (($flags & EDIT_FORCE_BOT) === 0)) {
		$gard_jobs[] = new SMW_LocalGardeningJob($article->getTitle(), "save");
		Job :: batchInsert($gard_jobs);
	}
	return true;
	// --------------------
}


/**
 * Called *before* an article gets deleted.
 *
 * @param Article $article
 * @param User $user
 * @param string $reason
 * @return unknown
 */
function sgafHaloPreDeleteHook(&$article, &$user, &$reason) {
	// -- LocalGardening --
	global $sgagLocalGardening;
	if (isset($sgagLocalGardening) && $sgagLocalGardening == true) {
		$gard_jobs[] = new SMW_LocalGardeningJob($article->getTitle(), "remove");
		Job :: batchInsert($gard_jobs);
	}
	return true;
}
/**
 *  This method will be called after an article is saved
 *  and stores the semantic properties in the database. One
 *  could consider creating an object for deferred saving
 *  as used in other places of MediaWiki.
 *  This hook extends SMW's smwfSaveHook insofar that it
 *  updates dependent properties or individuals when a type
 *  or property gets changed.
 */
function sgafHaloSaveHook(&$article, &$user, &$text) {
	global $sgagIP;
	include_once($sgagIP . '/includes/SGA_GardeningIssues.php');

	$title=$article->getTitle();
	SGAGardeningIssuesAccess::getGardeningIssuesAccess()->setGardeningIssueToModified($title);

	return true; // always return true, in order not to stop MW's hook processing!
}

// Gardening scripts callback
// includes necessary script and css files.
function sgafGAAddHTMLHeader(&$out) {
	global $wgTitle;
	if ($wgTitle->getNamespace() != NS_SPECIAL) return true;

	global $wgScriptPath;

	$out->addLink(array(
                    'rel'   => 'stylesheet',
                    'type'  => 'text/css',
                    'media' => 'screen, projection',
                    'href'  => $wgScriptPath . '/extensions/SemanticGardening/skins/gardening.css'
                    ));
                    $out->addLink(array(
                    'rel'   => 'stylesheet',
                    'type'  => 'text/css',
                    'media' => 'screen, projection',
                    'href'  => $wgScriptPath . '/extensions/SemanticGardening/skins/gardeningLog.css'
                    ));
                     
                    $out->addScript('<script type="text/javascript" src="'.$wgScriptPath .  '/extensions/SemanticGardening/scripts/gardening.js"></script>');

                     
                    return true;
}

// FindWork page callback
// includes necessary script and css files.
function sgaFWAddHTMLHeader(& $out) {
	global $wgTitle;
	if ($wgTitle->getNamespace() != NS_SPECIAL) return true;

	global $wgScriptPath;
	$out->addLink(array(
                    'rel'   => 'stylesheet',
                    'type'  => 'text/css',
                    'media' => 'screen, projection',
                    'href'  => $wgScriptPath . '/extensions/SemanticGardening/skins/findwork.css'
                    ));
                     
                    $out->addScript('<script type="text/javascript" src="'.$wgScriptPath .  '/extensions/SemanticGardening/scripts/findwork.js"></script>');
                    return true;
}

function sgafGetAjaxMethodPrefix() {
	$func_name = isset( $_POST["rs"] ) ? $_POST["rs"] : (isset( $_GET["rs"] ) ? $_GET["rs"] : NULL);
	if ($func_name == NULL) return NULL;
	return substr($func_name, 4, 4); // return _xx_ of smwf_xx_methodname, may return FALSE
}
?>