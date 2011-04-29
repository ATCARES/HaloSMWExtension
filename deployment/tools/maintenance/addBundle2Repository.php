<?php
/**
 * @file
 * @ingroup DFMaintenance
 *
 * Adds a bundle or a set of bundles to a repository.
 *
 * Usage: php addBundle2Repository.php 
 *                  -r <repository-dir>         The repository root directory 
 *                  -b <bundle file or dir>     The bundle file or a directory containing bundle files
 *                  --url <repository-url>      The download base URL  
 *                  [--latest]                  Latest version?
 *
 * @author: Kai Kühn / ontoprise / 2011
 *
 */

global $rootDir;
$rootDir = dirname(__FILE__);
$rootDir = str_replace("\\", "/", $rootDir);
$rootDir = realpath($rootDir."/../../");

require_once($rootDir."/descriptor/DF_DeployDescriptor.php");
require_once($rootDir."/tools/smwadmin/DF_PackageRepository.php");
require_once($rootDir."/tools/smwadmin/DF_Tools.php");


$latest = false;
$createSymlinks = true;

for( $arg = reset( $argv ); $arg !== false; $arg = next( $argv ) ) {

	//-r => repository directory
	if ($arg == '-r') {
		$repositoryDir = next($argv);
		continue;
	}

	// -b => bundle file or directory containing bundles
	if ($arg == '-b') {
		$bundlePath = next($argv);
		continue;
	}

	// --url => base URL for downloads
	if ($arg == '--url') {
		$repositoryURL = next($argv);
		continue;
	}
	
    if ($arg == '--latest') {
    	$latest = true;
        continue;
    }
}

if (!isset($repositoryDir) || !isset($bundlePath) || !isset($repositoryURL)) {
	echo "\nUsage: php addBundle2Repository.php -r <repository-dir> -b <bundle file or dir> --url <repository-url>\n";
	die(1);
}

// create symlinks for Linux and Windows 7
if (Tools::isWindows($os) && $latest) {
    $createSymlinks = ($os == 'Windows 7');
    if (!$createSymlinks) {
        echo "Be careful: Cannot create symbolic links on Windows <= 7!";
    }
}

// create binary path
Tools::mkpath($repositoryDir."/bin");

// read bundles and extract the deploy descriptors
echo "\nExtract deploy descriptors";
$descriptors = extractDeployDescriptors($bundlePath);
echo "..done.";

// load existing repository
echo "\nLoading repository...";
$repoDoc = loadRepository($repositoryDir."/repository.xml");
echo "..done.";

$nodeList = $repoDoc->getElementsByTagName("extensions");
$extensionsNode = $nodeList->item(0);

foreach($descriptors as $tuple) {
	list($dd, $zipFilepath) = $tuple;
	
	// 1. create extensions substructure
	$id = $dd->getID();
	$version = $dd->getVersion();
	$id = strtolower($id);
	echo "\nCreate extension entry for $id";
	Tools::mkpath($repositoryDir."/extensions/$id");
	Tools::unzipFile($zipFilepath, "/deploy.xml", $repositoryDir."/extensions/$id");
	rename($repositoryDir."/extensions/$id/deploy.xml", $repositoryDir."/extensions/$id/deploy-$version.xml");
	if ($createSymlinks && $latest) {
		// remove symbolic link if existing
		if (file_exists($repositoryDir."/$id/deploy.xml")) {
			unlink($repositoryDir."/$id/deploy.xml");
		}
		// create symbolic link
		if (Tools::isWindows()) {
			$target = str_replace("/", "\\", "$repositoryDir/extensions/$id/deploy-$version.xml");
			$link = str_replace("/", "\\", "$repositoryDir/extensions/$id/deploy.xml");
			exec("mklink \"$link\" \"$target\"", $out, $res);
		} else{
			exec("ln -s $repositoryDir/extensions/$id/deploy-$version.xml $repositoryDir/extensions/$id/deploy.xml", $out, $res);
		}
		if ($res == 0) print "\n\tCreated link: ".$repositoryDir."/extensions/".$id.'/deploy.xml';
	}
    echo "\n..done.";
    
	// 2. Add to repository.xml
	$newExt = $repoDoc->createElement("extension");
	$idAttr = $repoDoc->createAttribute("id");
	$idAttr->value = $dd->getID();
	$newExt->appendChild($idAttr);
	
	$newVer = $repoDoc->createElement("version");
    $newExt->appendChild($newVer);
    
	$urlAttr = $repoDoc->createAttribute("url");
	$urlAttr->value = $repositoryURL."/bin/".$dd->getID()."-".Tools::addSeparators($dd->getVersion(), $dd->getPatchlevel()).".zip";
	$newVer->appendChild($urlAttr);

	$versionAttr = $repoDoc->createAttribute("ver");
	$versionAttr->value = $dd->getVersion();
	$newVer->appendChild($versionAttr);


	$patchlevelAttr = $repoDoc->createAttribute("patchlevel");
	$patchlevelAttr->value = $dd->getPatchlevel();
	$newVer->appendChild($patchlevelAttr);

	$maintainerAttr = $repoDoc->createAttribute("maintainer");
	$maintainerAttr->value = $dd->getMaintainer();
	$newVer->appendChild($maintainerAttr);

	$descriptionAttr = $repoDoc->createAttribute("description");
	$descriptionAttr->value = $dd->getDescription();
	$newVer->appendChild($descriptionAttr);

	$helpurlAttr = $repoDoc->createAttribute("helpurl");
	$helpurlAttr->value = $dd->getHelpURL();
	$newVer->appendChild($helpurlAttr);

	echo "\nAdd to repository: ".$dd->getID();
	$extensionsNode->appendChild($newExt);
    echo "..done.";
    	
	// 3. copy binary package
	$targetPackageFile = $repositoryDir."/bin/$id-".Tools::addSeparators($dd->getVersion(), $dd->getPatchlevel()).".zip";
	echo "\nCopy package $id to $targetPackageFile";
    copy($zipFilepath, $targetPackageFile);	
    echo "..done.";
}

// save repository.xml
echo "\nSave repository";
saveRepository($repositoryDir."/repository.xml", $repoDoc);
echo "..done.";
echo "\ns";




/**
 * Save a repository.
 *
 * @param $filePath
 * @param $doc
 */
function saveRepository($filePath, $doc) {
	$xml = $doc->saveXML();
	$handle = fopen($filePath, "w");
	fwrite($handle, $xml);
	fclose($handle);
}

/**
 * Load a repository
 *
 * @param $filePath
 */
function loadRepository($filePath) {
	$xml = file_get_contents($filePath);
	return DOMDocument::loadXML($xml);
}

/**
 * Extracts the deploy descriptor(s) from a bundle or a set of bundles.
 *
 * @param $bundlePath (file or directory)
 * 
 * @return array of (DeployDescriptor, Bundle file path)
 */
function extractDeployDescriptors($bundlePath) {
	$tmpFolder = Tools::isWindows() ? 'c:\temp\mw_deploy_tool' : '/tmp/mw_deploy_tool';
	if (is_dir($bundlePath)) {
		$result = array();
		$dirHandle=opendir($bundlePath);
		while(false !== ($file=readdir($dirHandle))) {
			if($file!="." && $file!="..") {
				$fileExtension = Tools::getFileExtension($file);
				if (strtolower($fileExtension) != 'zip') continue;
				$__file=$bundlePath."/".$file;
				$dd = Tools::unzipDeployDescriptor($__file, $tmpFolder);
				$result[] = array($dd, $__file);
			}
		}
		return $result;
	} else {
		$dd = Tools::unzipDeployDescriptor($bundlePath, $tmpFolder);
		return array(array($dd, $bundlePath));
	}
}



