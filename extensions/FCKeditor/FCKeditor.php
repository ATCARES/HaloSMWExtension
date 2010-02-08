<?php

# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI')) {
	echo <<<HEREDOC
To install the WYSIWYG extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/FCKeditor/FCKeditor.php" );
HEREDOC;
	exit( 1 );
}

# quit when comming from the command line,
# special case, to make Halo webtests run (here we don't have a browser)
if (array_key_exists('SERVER_NAME', $_SERVER) && $_SERVER['SERVER_NAME'] != NULL &&
    (strpos($_SERVER['PHP_SELF'], 'run-test.php') === false) )
	return;

/*
This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
*/

require_once $IP . "/includes/GlobalFunctions.php";
require_once $IP . "/includes/parser/ParserOptions.php";
require_once $IP . "/includes/EditPage.php";
require_once $IP . "/includes/parser/Parser.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "FCKeditorParser.body.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "FCKeditorSajax.body.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "FCKeditorParserOptions.body.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "FCKeditorSkin.body.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "FCKeditorEditPage.body.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "FCKeditor.body.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "fckeditor" . DIRECTORY_SEPARATOR . "fckeditor.php";

if (empty ($wgFCKEditorExtDir)) {
    $wgFCKEditorExtDir = "extensions/FCKeditor" ;
}
if (empty ($wgFCKEditorDir)) {
    $wgFCKEditorDir = "extensions/FCKeditor/fckeditor" ;
}
if (empty ($wgFCKEditorToolbarSet)) {
    $wgFCKEditorToolbarSet = "Wiki" ;
}
if (empty ($wgFCKEditorHeight)) {
    $wgFCKEditorHeight = "0" ; // "0" for automatic ("300" minimum).
}

/**
 * Enable use of AJAX features.
 */
$wgUseAjax = true;
$wgAjaxExportList[] = 'wfSajaxSearchImageFCKeditor';
$wgAjaxExportList[] = 'wfSajaxSearchArticleFCKeditor';
$wgAjaxExportList[] = 'wfSajaxWikiToHTML';
$wgAjaxExportList[] = 'wfSajaxGetImageUrl';
$wgAjaxExportList[] = 'wfSajaxGetMathUrl';
$wgAjaxExportList[] = 'wfSajaxSearchTemplateFCKeditor';
$wgAjaxExportList[] = 'wfSajaxSearchSpecialTagFCKeditor';
$wgAjaxExportList[] = 'wfSajaxTemplateListFCKeditor';
$wgAjaxExportList[] = 'wfSajaxFormForTemplateFCKeditor';

$wgExtensionCredits['other'][] = array(
"name" => "WYSIWYG extension",
"author" => "[http://ckeditor.com FCKeditor] (inspired by the code written by Mafs [http://www.mediawiki.org/wiki/Extension:FCKeditor_%28by_Mafs%29]) extended by [http://www.ontoprise.de Ontoprise]",
"version" => '{{$VERSION}}, FCK 2.6.4 Build 21629',
"url" => "http://smwforum.ontoprise.com/smwforum/index.php/Help%3AWYSIWYG_Extension",
"description" => "FCKeditor for Semantic MediaWiki"
);

$fckeditor = new FCKeditor("fake");
$wgFCKEditorIsCompatible = $fckeditor->IsCompatible();

$oFCKeditorExtension = new FCKeditor_MediaWiki();
$oFCKeditorExtension->registerHooks();

// load Special pages for Template picker (plugin mediawiki)
// if Semantic Forms have been installed
// *Note* This only works if the FCKeditor.php is included in the LocalSettings.php
// directly. The SMWHalo extension has a SMW_WYSIWYG.php that's included, which
// includes this file only if the editor is really required (i.e. on action =edit
// or action=formedit). The files below are used in the template picker of the
// FCKeditor but run in an iframe without the FCKeditor instance itself. Therefore
// these are not included here.
if (defined('SF_VERSION')) {
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "specials" . DIRECTORY_SEPARATOR . "SF_AddDataEmbedded.php";
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "specials" . DIRECTORY_SEPARATOR . "SF_EditDataEmbedded.php";
}







