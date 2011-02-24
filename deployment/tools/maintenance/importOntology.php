<?php
/**
 * Import script for importing an ontology.
 * 
 * DOES NOT detect conflicts!
 *
 * @file
 * @ingroup DFMaintenance
 */

$optionsWithArgs = array( 'report' );

require_once( '../../../maintenance/commandLine.inc' );
require_once('../../io/import/DF_DeployWikiOntologyImporter.php');
require_once('../../io/import/DF_OntologyMerger.php');
require_once('../../tools/smwadmin/DF_Tools.php');
require_once('../../io/DF_Log.php');

$langClass = "DF_Language_$wgLanguageCode";
if (!file_exists("../../languages/$langClass.php")) {
	$langClass = "DF_Language_En";
}
require_once("../../languages/$langClass.php");
$dfgLang = new $langClass();

if( wfReadOnly() ) {
	wfDie( "Wiki is in read-only mode; you'll need to disable it for import to work.\n" );
}

for( $arg = reset( $argv ); $arg !== false; $arg = next( $argv ) ) {
  
    //-o => ontology name
    if ($arg == '-o') {
        $ontologyName = next($argv);
        continue;
    }
    //-f => file name
    if ($arg == '-f') {
        $filePath = next($argv);
        continue;
    }
    $params[] = $arg;
}

if (!isset($filePath)) {
    print "Usage: php importOntology.php -f <filePath> -o <ontology name> ";
    die();
}


if( preg_match( '/\.gz$/', $filePath ) ) {
	$filename = 'compress.zlib://' . $filePath;
}
$file = fopen( $filePath, 'rt' );
return importFromHandle( $file, $ontologyName );


function importFromHandle( $handle, $ontologyName ) {

	$source = new ImportStreamSource( $handle );
	$importer = new DeployWikiOntologyImporter( $source, $ontologyName, "", 1, NULL );

	$importer->setDebug( false );
    $importer->doImport();
	 
	$result = $importer->getResult();

}
