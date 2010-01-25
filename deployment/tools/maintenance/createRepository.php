<?php
/**
 * Reads repository and updates patchlevels.
 * 
 * Usage: php updatePatchlevels.php -o <repository path>
 * 
 * @author: Kai K�hn / ontoprise / 2009
 */
require_once("../../descriptor/DF_DeployDescriptor.php");
require_once("../../tools/smwadmin/DF_Tools.php");

function getLocalPackages($ext_dir) {

    $localPackages = array();
    // add trailing slashes
    if (substr($ext_dir,-1)!='/'){
        $ext_dir .= '/';
    }

    $handle = @opendir($ext_dir);
    if (!$handle) {
        throw new IllegalArgument('Extension directory does not exist: '.$ext_dir);
    }

    while ($entry = readdir($handle) ){
        if ($entry[0] == '.'){
            continue;
        }

        if (is_dir($ext_dir.$entry)) {
            // check if there is a deploy.xml
            if (file_exists($ext_dir.$entry.'/deploy.xml')) {
                $dd = new DeployDescriptor(file_get_contents($ext_dir.$entry.'/deploy.xml'));
                $localPackages[$dd->getID()] = $dd;

            }
        }

    }
     
    return $localPackages;
}

$latest = false;
for( $arg = reset( $argv ); $arg !== false; $arg = next( $argv ) ) {

    //-o => output
    if ($arg == '-o') {
        $outputDir = next($argv);
        continue;
    }
    
     //-r => release num
    if ($arg == '-r') {
        $release = str_replace('.','',next($argv));
        continue;
    }
    
}

if (!isset($outputDir)) {
    echo "\nSet output dir by using -o <directory>\n";
    die();
}
if (!isset($release)) {
    echo "\nSet release by using -r <releasenum>\n";
    die();
}

$outputDir = str_replace("\\", "/", $outputDir);
if (substr($outputDir, -1) != "/") $outputDir .= "/";

$rootDir = dirname(__FILE__);
$rootDir = str_replace("\\", "/", $rootDir);
$rootDir = realpath($rootDir."/../../../extensions/");
print($rootDir);
if (substr($rootDir, -1) != "/") $rootDir .= "/";

echo "\nRead local packages";
$localPackages = getLocalPackages($rootDir);

echo "\nCreate new repository ".$outputDir."repository.xml";


$new_ser = '<?xml version="1.0" encoding="UTF-8"?>'."<root>\n<extensions>\n";
foreach($localPackages as $lp) {
    $id = $lp->getID();
    $installdir = $lp->getInstallationDirectory();
    $new_ser .= "<extension id=\"$id\">";
    
        $url = "http://dailywikibuilds.ontoprise.com:8080/job/smwhalo_".addSeparators($release,"_")."_release/lastSuccessfulBuild/artifact/SMWHaloTrunk/$installdir/deploy/bin/$id-".addSeparators($lp->getVersion(),".").".zip";
        $ver = $lp->getVersion();
        $newPatchlevel = $lp->getPatchlevel();
        if ($newPatchlevel == '') $newPatchlevel = 0;
        
        $new_ser .= "<version ver=\"$ver\" url=\"$url\" patchlevel=\"$newPatchlevel\"/>";
    
    $new_ser .= "</extension>\n";
}
$new_ser .= "\n</extensions>\n</root>";

echo "\nWrite new repository to ".$outputDir."repository.xml";
$handle = fopen($outputDir."repository.xml", "w");
fwrite($handle, $new_ser);
fclose($handle);

function addSeparators($version, $sep = ".") {
    $sep_version = "";
    for($i = 0; $i < strlen($version); $i++) {
        if ($i>0) $sep_version .= $sep;
        $sep_version .= $version[$i];
    }
    return $sep_version;
}