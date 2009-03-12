<?php
 
if ( !defined( 'MEDIAWIKI' ) ) {
    die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}
 
$wgExtensionFunctions[] = 'wfSetupDebugTemplate';
 
$wgExtensionCredits['parserhook'][] = array(
        'name' => 'Debug Template',
        'url' => 'http://www.mediawiki.org/wiki/Extension:DebugTemplate',
        'author' => 'Thomas Schweitzer',
        'description' => 'Show the wikitext that is generated by templates and parser functions.'
);
 
$wgHooks['LanguageGetMagic'][]       = 'wfDebugTemplateLanguageGetMagic';
 
class ExtDebugTemplate {
 
    function debugTemplate( &$parser, $wikiText ) {
        return '<pre>'.$wikiText.'</pre>';
    }
 }
 
function wfSetupDebugTemplate() {
    global $wgParser, $wgMessageCache, $wgExtDebugTemplate, $wgMessageCache, $wgHooks;
 
    $wgExtDebugTemplate = new ExtDebugTemplate;
 
    $wgParser->setFunctionHook( 'debugTemplate', array( &$wgExtDebugTemplate, 'debugTemplate' ) );
}
 
function wfDebugTemplateLanguageGetMagic( &$magicWords, $langCode ) {
        require_once( dirname( __FILE__ ) . '/DebugTemplate.i18n.php' );
        foreach( efDebugTemplateWords( $langCode ) as $word => $trans )
                $magicWords[$word] = $trans;
        return true;
}
 
?>