<?php
/**
 * Transforms a PHPUnit XML test result to into a Hudson compatible version.
 *
 * Usage:
 *
 *  php Transform.php -i <input file> -o <output file>
 *
 *
 * @author Kai K�hn
 *
 */
$params = array();
for( $arg = reset( $argv ); $arg !== false; $arg = next( $argv ) ) {
	//-i => input file
	if ($arg == '-i') {
		$inputFile = next($argv);
		continue;
	}
	// -o => output file
	if ($arg == '-o') {
		$outputFile = next($argv);
		continue;
	}
	// -p => package name
	if ($arg == '-p') {
		$package = next($argv);
		continue;
	}
	$params[] = $arg;
}


$xp = new XsltProcessor;
$xp->setParameter("", "package", isset($package) ? $package : "GeneralTests");
$xsl = new DOMDocument;
$xsl->load(dirname(__FILE__)."/transform.xslt");
$xp->importStylesheet($xsl);

$xml = new DOMDocument;
echo "\nReading testcases from $inputFile ...";
$xml->load($inputFile);

echo "\nTransforming...";
$output = $xp->transformToXML($xml)
or die('Transformation error!');

$handle = fopen($outputFile,"wb");
echo "\nWriting in output file: ".$outputFile;
fwrite($handle, $output);
fclose($handle);
echo "\ndone.\n";

?>