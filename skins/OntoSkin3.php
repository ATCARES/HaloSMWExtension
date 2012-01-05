<?php
/*
 * Copyright (C) Vulcan Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program.If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**    This skin is based on Monobook from Mediawiki 1.15
 *     changes making this compatible to Mediawiki 1.13 have been marked
 */
if ( !defined( 'MEDIAWIKI' ) )
	die( -1 );

/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @todo document
 * @ingroup Skins
 */
class SkinOntoSkin3 extends SkinTemplate {

	function __construct() {
		global $wgResourceModules, $wgStylePath, $wgStyleDirectory;

		$wgResourceModules['skins.ontoskin3'] = array(
			'styles' => array(
				'ontoskin3/css/skin-main.css' => array( 'media' => 'screen' ),
				'ontoskin3/css/skin-pagecontent.css' => array( 'media' => 'screen' ),
				'ontoskin3/css/skin-printable.css' => array( 'media' => 'print' ),
			),
			'scripts' => array(
				'ontoskin3/javascript/skin.js'
			),
			'remoteBasePath' => $wgStylePath,
			'localBasePath' => $wgStyleDirectory,
			'dependencies' => 'jquery.placeholder'
		);
	}

	/**
	 *
	 *    Using OntoSkin3.
	 *
	 * */
	function initPage( OutputPage $out ) {

		parent::initPage( $out );
		$this->skinname = 'ontoskin3';
		$this->stylename = 'ontoskin3';
		$this->template = 'OntoSkin3Template';
		$this->addResourceModules($out);
	}

	function getSkinName() {
		return 'ontoskin3';
	}

	function setupSkinUserCss( OutputPage $out ) {
		parent::setupSkinUserCss( $out );
		$out->addModuleStyles( 'skins.ontoskin3' );
	}

	function addResourceModules( OutputPage $out ) {
		$out->addModuleScripts( 'skins.ontoskin3' );
		// Add the module for the tree view
		$out->addModules('ext.TreeView.tree');
	}
}

/**
 * @todo document
 * @ingroup Skins
 */
class OntoSkin3Template extends QuickTemplate {

	var $skin;

	/**
	 * Template filter callback for OntoSkin3 skin.
	 * Takes an associative array of data set from a SkinTemplate-based
	 * class, and a wrapper for MediaWiki's localization database, and
	 * outputs a formatted page.
	 *
	 * @access private
	 */
	function execute() {
		global $wgRequest, $wgUser;
		$this->skin = $skin = $this->data['skin'];
		$action = $wgRequest->getText( 'action' );

		//Load skinlib providing additional feature like halomenu quicklinks etc.
		require_once("ontoskin3/includes/OntoSkin3Lib.php");
		//create smwh_Skin Object, which provides functions for menu, quicklings, tabs
		$this->smwh_Skin = new SMWH_Skin( $this, $action );

		global $wgOut;
		$wgOut->addModules( 'skins.ontoskin3' );

		// Suppress warnings to prevent notices about missing indexes in $this->data
		wfSuppressWarnings();
		?>
<!DOCTYPE html>
<html lang="<?php $this->text( 'lang' ) ?>" dir="<?php $this->text( 'dir' ) ?>">
	<head>
		<meta http-equiv="Content-Type" content="<?php $this->text( 'mimetype' ) ?>; charset=<?php $this->text( 'charset' ) ?>" />
		<?php $this->html( 'headlinks' ) ?>
		<title><?php $this->text( 'pagetitle' ) ?></title>
		<?php $this->html( 'csslinks' ) ?>

		<!--[if lt IE 7]><script type="<?php $this->text( 'jsmimetype' ) ?>"
			src="<?php $this->text( 'stylepath' ) ?>/common/IEFixes.js?<?php echo $GLOBALS['wgStyleVersion'] ?>"></script>
		<meta http-equiv="imagetoolbar" content="no" /><![endif]-->

		<?php print Skin::makeGlobalVariablesScript( $this->data ); ?>

		<!-- <script type="<?php $this->text( 'jsmimetype' ) ?>" src="<?php $this->text( 'stylepath' ) ?>/common/wikibits.js?<?php echo $GLOBALS['wgStyleVersion'] ?>"></script>  -->

		<?php $this->html( 'headscripts' ) ?>
		<?php if ( $this->data['jsvarurl'] ) { ?>
			<script type="<?php $this->text( 'jsmimetype' ) ?>" src="<?php $this->text( 'jsvarurl' ) ?>"><!-- site js --></script>
		<?php } ?>
		<?php
			if ( $this->data['pagecss'] ) { ?>
				<style type="text/css"><?php $this->html( 'pagecss' ) ?></style>
		<?php }
			if ( $this->data['usercss'] ) { ?>
				<style type="text/css"><?php $this->html( 'usercss' ) ?></style>
		<?php }
			if ( $this->data['userjs'] ) { ?>
				<script type="<?php $this->text( 'jsmimetype' ) ?>" src="<?php $this->text( 'userjs' ) ?>"></script>
		<?php }
			if ( $this->data['userjsprev'] ) { ?>
				<script type="<?php $this->text( 'jsmimetype' ) ?>"><?php $this->htmgl( 'userjsprev' ) ?></script>
		<?php }
			if ( $this->data['trackbackhtml'] )
				print $this->data['trackbackhtml'];
		?>
	</head>
	<body<?php if ( $this->data['body_ondblclick'] ) { ?> ondblclick="<?php $this->text( 'body_ondblclick' ) ?>"<?php } ?>
	<?php if ( $this->data['body_onload'] ) { ?>
		onload="<?php $this->text( 'body_onload' ) ?>"
	<?php } ?>
		class="mediawiki <?php $this->text( 'dir' ) ?> <?php $this->text( 'pageclass' ) ?> <?php $this->text( 'skinnameclass' ) ?>">
		<!-- globalWrapper -->
		<div id="globalWrapper">
			<?php if ( $wgRequest->getText( 'page' ) != "plain" ) : ?>
			<!-- header -->
			<div id="smwh_head">
				<div class="smwh_center">
					<!-- logo -->
					<div id="smwh_logo">
						<a href="<?php echo htmlspecialchars( $this->data['nav_urls']['mainpage']['href'] ) ?>"
							<?php echo $skin->tooltipAndAccesskey( 'p-logo' ) ?>>
							<img src="<?php $this->text( 'logopath' ) ?>"/>
						</a>
					</div>
					<!-- /logo -->
					<!-- personalbar -->
					<div id="smwh_personal">
						<a id="personal_expand" class="limited" href="javascript:smwh_Skin.resizePage()">Change view</a>
						<?php
							foreach ( $this->data['personal_urls'] as $key => $item ) {
								//echo $key;
								if ( !($key == "login" || $key == "anonlogin" || $key == "logout" || $key == "userpage") ) {
									continue;
								} ?>
								<a id="personal_<?php echo $key ?>"
									href="<?php echo htmlspecialchars( $item['href'] ) ?>"<?php echo $skin->tooltipAndAccesskey( 'pt-' . $key ) ?>
									class="<?php if ( $item['active'] ) { ?>active<?php }
									if ( !empty( $item['class'] ) ) {
										echo htmlspecialchars( $item['class'] );
									} ?>">
									<?php echo htmlspecialchars( $item['text'] ) ?>
								</a>
						<?php } ?>
					</div>
					<!-- /personalbar -->
					<?php echo $this->smwh_Skin->buildPersonalQuickLinks(); ?>
				</div>
			</div>
			<!-- /header -->
			<!-- menu -->
			<div id="smwh_menu">
				<div class="smwh_center">
					<div id="home">
						<a href="<?php echo htmlspecialchars( $this->data['nav_urls']['mainpage']['href'] ) ?>"<?php echo $skin->tooltipAndAccesskey( 'p-logo' ); ?>>
							<img src="<?php $this->text( 'stylepath' ) ?>/<?php $this->text( 'stylename' ) ?>/img/menue_mainpageicon_white.gif" alt="mainpage"/>
						</a>
					</div>
					<?php echo $this->smwh_Skin->buildMenuHtml(); ?>
					<!-- Search -->
					<?php $this->searchBox(); ?>
				</div>
			</div>
			<!-- /menu -->
			<!-- content -->
			<div id="main" class="shadows smwh_center">
				<div id="smwh_breadcrumbs">
					<div id="smwh_last_visited">
						<?php $this->msg( 'smw_last_visited' ); ?>
					</div>
					<div id="breadcrumb"></div>
				</div>
				<div id="mainpage">
					<div id="smwh_tabs">
						<?php echo $this->smwh_Skin->buildTabs(); ?>
					</div>
					<?php echo $this->smwh_Skin->buildCreatedBy(); ?>
					<?php endif; // action != 'plainpage'  ?>
					<div id="column-content">
						<div id="content">
							<!-- div from mw 1.13 removed 1.15 -->
							<div id="bodyContent">
								<h3 id="siteSub"><?php $this->msg( 'tagline' ) ?></h3>
								<div id="contentSub"><?php $this->html( 'subtitle' ) ?></div>
									<?php if ( $this->data['undelete'] ) { ?>
										<div id="contentSub2"><?php $this->html( 'undelete' ) ?></div>
									<?php } ?>
									<?php if ( $this->data['newtalk'] ) { ?>
										<div class="usermessage"><?php $this->html( 'newtalk' ) ?></div>
									<?php } ?>
									<?php if ( $this->data['showjumplinks'] ) { ?>
										<div id="jump-to-nav"><?php $this->msg( 'jumpto' ) ?>
											<a href="#column-one"><?php $this->msg( 'jumptonavigation' ) ?></a>, <a href="#searchInput"><?php $this->msg( 'jumptosearch' ) ?></a>
										</div>
									<?php } ?>
									<?php $this->html( 'bodytext' ) ?>
										<div class="visualClear"></div>
									<?php if ( $this->data['catlinks'] ) { ?>
										<div id="catlinks"><?php $this->html( 'catlinks' ) ?></div>
									<?php } ?>
									<?php if ( $this->data['dataAfterContent'] ) {
										$this->html( 'dataAfterContent' );
									} ?>
								<div class="visualClear"></div>
							</div>
						</div>
					</div>
						<?php if ( $wgRequest->getText( 'page' ) != "plain" ) : ?>
				</div>
				<div class="visualClear"></div>
				<div id="smwh_pstats"> <?php echo $this->smwh_Skin->showPageStats(); ?> </div>
				<?php endif; // page != 'plain'  ?>
				<?php if ( $wgRequest->getText( 'page' ) != "plain" ) : ?>
					<?php echo $this->smwh_Skin->treeview(); ?>
			</div>
			<!-- /content -->
		</div>
		<!-- /globalWrapper -->
		<!-- footer -->
		<div id="footer">
			<div class="smwh_center">
				<?php echo $this->smwh_Skin->buildQuickLinks(); ?>
			</div>
		</div>
		<!-- /footer -->
		<?php endif; // page != 'plain'  ?>
		<div id="ontomenuanchor"></div>
		<?php $this->html( 'bottomscripts' ); /* JS call to runBodyOnloadHook */ ?>
		<?php $this->html( 'reporttime' ) ?>
		<?php if ( $this->data['debug'] ): ?>
			<!-- Debug output:
			<?php $this->text( 'debug' ); ?>
			-->
		<?php endif; ?>
	</body>
</html>
<?php
	wfRestoreWarnings();
}
// end of execute() method

/************************************************************************************************ */

	function searchBox() {
		global $wgUseTwoButtonsSearchForm, $wgScriptPath;
		?>
		<!-- searchBox -->
		<div id="smwh_search" class="portlet">
			<div id="searchBody" class="pBody" >
				<form action="<?php $this->text( 'wgScript' ) ?>" id="searchform">
					<input type='hidden' name="title" value="<?php $this->text( 'searchtitle' ) ?>"/>
					<div id="createNewArticleCtrl">
						<img src="<?php echo $wgScriptPath . '/extensions/SMWHalo/skins/CreateNewArticle/Addcontent.png' ?>"></img>New page
					</div>
					<input id="searchInput" pasteNS="true" class="wickEnabled" 
						name="search" constraints="all" 
						type="text" <?php echo $this->skin->tooltipAndAccesskey( 'search' ); ?>
						placeholder="<?php $this->msg( 'smw_search_this_wiki' ); ?>" />
					<input type='submit' src='<?php $this->text( 'stylepath' ) ?>/<?php $this->text( 'stylename' ) ?>/img/button_go.png'
						name="go" class="searchButton" id="searchGoButton" 
						value="<?php $this->msg( 'searcharticle' ) ?>"<?php echo $this->skin->tooltipAndAccesskey( 'search-go' ); ?> />
					<!-- @todo: looking glass
						<input type='submit' src='<?php $this->text( 'stylepath' ) ?>/<?php $this->text( 'stylename' ) ?>/img/button_search.png' name="fulltext" class="searchButton" id="mw-searchButton" value="<?php $this->msg( 'searchbutton' ) ?>"<?php echo $this->skin->tooltipAndAccesskey( 'search-fulltext' ); ?> />
					-->
				</form>
			</div>
		</div>
		<!-- /searchBox -->
		<?php
	}

	/*	 * ********************************************************************************************** */

	function toolbox() {
		?>
		<!-- toolbox -->
		<div class="portlet" id="p-tb">
			<h5><?php $this->msg( 'toolbox' ) ?></h5>
			<div class="pBody">
				<ul>
					<?php if ( $this->data['notspecialpage'] ) { ?>
						<li id="t-whatlinkshere">
							<a href="<?php
								echo htmlspecialchars( $this->data['nav_urls']['whatlinkshere']['href'] )
							?>"<?php echo $this->skin->tooltipAndAccesskey( 't-whatlinkshere' ) ?>><?php $this->msg( 'whatlinkshere' ) ?>
							</a>
						</li>
						<?php if ( $this->data['nav_urls']['recentchangeslinked'] ) { ?>
						<li id="t-recentchangeslinked">
							<a href="<?php
								echo htmlspecialchars( $this->data['nav_urls']['recentchangeslinked']['href'] )
							?>"<?php echo $this->skin->tooltipAndAccesskey( 't-recentchangeslinked' ) ?>><?php $this->msg( 'recentchangeslinked' ) ?></a></li>
						<?php }
					}
					if ( isset( $this->data['nav_urls']['trackbacklink'] ) ) {
					?>
						<li id="t-trackbacklink"><a href="<?php
							echo htmlspecialchars( $this->data['nav_urls']['trackbacklink']['href'] )
							?>"<?php echo $this->skin->tooltipAndAccesskey( 't-trackbacklink' ) ?>><?php $this->msg( 'trackbacklink' ) ?></a></li>
					<?php }
					if ( $this->data['feeds'] ) { ?>
						<li id="feedlinks">
							<?php foreach ( $this->data['feeds'] as $key => $feed ) {?>
								<a id="<?php echo Sanitizer::escapeId( "feed-$key" ) ?>" href="<?php echo htmlspecialchars( $feed['href'] ) ?>" rel="alternate" type="application/<?php echo $key ?>+xml" class="feedlink"<?php echo $this->skin->tooltipAndAccesskey( 'feed-' . $key ) ?>><?php echo htmlspecialchars( $feed['text'] ) ?></a>&nbsp;
							<?php }
						?></li>
					<?php }
					foreach ( array('contributions', 'log', 'blockip', 'emailuser', 'upload', 'specialpages') as $special ) {
						if ( $this->data['nav_urls'][$special] ) {
							?><li id="t-<?php echo $special ?>">
								<a href="<?php echo htmlspecialchars( $this->data['nav_urls'][$special]['href'] )?>"
									<?php echo $this->skin->tooltipAndAccesskey( 't-' . $special ) ?>><?php $this->msg( $special ) ?>
								</a>
							</li>
						<?php }
					}
					if ( !empty( $this->data['nav_urls']['print']['href'] ) ) { ?>
						<li id="t-print"><a href="<?php echo htmlspecialchars( $this->data['nav_urls']['print']['href'] )
							?>" rel="alternate"<?php echo $this->skin->tooltipAndAccesskey( 't-print' ) ?>><?php $this->msg( 'printableversion' ) ?></a></li><?php
					}
					if ( !empty( $this->data['nav_urls']['permalink']['href'] ) ) { ?>
						<li id="t-permalink"><a href="<?php echo htmlspecialchars( $this->data['nav_urls']['permalink']['href'] )
							?>"<?php echo $this->skin->tooltipAndAccesskey( 't-permalink' ) ?>><?php $this->msg( 'permalink' ) ?></a></li><?php } elseif ( $this->data['nav_urls']['permalink']['href'] === '' ) { ?>
						<li id="t-ispermalink"<?php echo $this->skin->tooltip( 't-ispermalink' ) ?>><?php $this->msg( 'permalink' ) ?></li><?php
						}
					wfRunHooks( 'MonoBookTemplateToolboxEnd', array(&$this) );
					wfRunHooks( 'SkinTemplateToolboxEnd', array(&$this) );
					?>
				</ul>
			</div>
		</div>
		<!-- /toolbox -->
		<?php
	}

	/*	 * ********************************************************************************************** */

	function languageBox() {
		if ( $this->data['language_urls'] ) { ?>
			<!-- langBox -->
			<div id="p-lang" class="portlet">
				<h5><?php $this->msg( 'otherlanguages' ) ?></h5>
				<div class="pBody">
					<ul>
					<?php foreach ( $this->data['language_urls'] as $langlink ) { ?>
						<li class="<?php echo htmlspecialchars( $langlink['class'] ) ?>"><?php ?><a href="<?php echo htmlspecialchars( $langlink['href'] ) ?>"><?php echo $langlink['text'] ?></a></li>
					<?php } ?>
					</ul>
				</div>
			</div>
			<!-- /langBox -->
			<?php
		}
	}

	/*	 * ********************************************************************************************** */

	function customBox( $bar, $cont ) { ?>
		<!-- customBox -->
		<div class='generated-sidebar portlet' id='<?php echo Sanitizer::escapeId( "p-$bar" ) ?>'<?php echo $this->skin->tooltip( 'p-' . $bar ) ?>>
			<h5><?php
				$out = wfMsg( $bar );
				if ( wfEmptyMsg( $bar, $out ) )
					echo $bar; else
				echo $out;
			?></h5>
			<div class='pBody'>
			<?php if ( is_array( $cont ) ) { ?>
				<ul>
				<?php foreach ( $cont as $key => $val ) { ?>
					<li id="<?php echo Sanitizer::escapeId( $val['id'] ) ?>"<?php if ( $val['active'] ) { ?> class="active" <?php } ?>><a href="<?php echo htmlspecialchars( $val['href'] ) ?>"<?php echo $this->skin->tooltipAndAccesskey( $val['id'] ) ?>><?php echo htmlspecialchars( $val['text'] ) ?></a></li>
				<?php } ?>
				</ul>
				<?php
			} else {
				# allow raw HTML block to be defined by extensions
				print $cont;
			}
				?>
			</div>
		</div>
		<!-- /customBox -->
		<?php
	}

}
// end of class