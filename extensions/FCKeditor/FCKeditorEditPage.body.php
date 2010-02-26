<?php

/**
 * @file
 * @ingroup WYSIWYG
 */

/**
 * Extends the EditPage class of Mediawiki to have the WYSIWG editor used#
 * in the edit page.
 *
 * @ingroup WYSIWYG
 */
class FCKeditorEditPage extends EditPage
{
	/**
	 * Should we show a preview when the edit form is first shown?
	 *
	 * @return bool
	 */
	public function previewOnOpen() {
		global $wgRequest, $wgUser;
		if( $wgRequest->getVal( 'preview' ) == 'yes' ) {
			// Explicit override from request
			return true;
		} elseif( $wgRequest->getVal( 'preview' ) == 'no' ) {
			// Explicit override from request
			return false;
		} elseif( $this->section == 'new' ) {
			// Nothing *to* preview for new sections
			return false;
		} elseif( ( $wgRequest->getVal( 'preload' ) !== '' || $this->mTitle->exists() ) && $wgUser->getOption( 'previewonfirst' ) ) {
			// Standard preference behaviour
			return true;
		} elseif( !$this->mTitle->exists() && $this->mTitle->getNamespace() == NS_CATEGORY ) {
			// Categories are special
			return true;
		} else {
			return false;
		}
	}

	function getPreviewText() {
		if (!$this->isCssJsSubpage) {
			wfRunHooks( 'EditPageBeforePreviewText', array( &$this, $this->previewOnOpen() ) );
			$result = parent::getPreviewText();
			wfRunHooks( 'EditPagePreviewTextEnd', array( &$this, $this->previewOnOpen() ) );
		}
		else {
			$result = parent::getPreviewText();
		}
		return $result;
	}

	function getContent( $def_text = '' ) {
		$t = parent::getContent( $def_text );
		if(!$this->isConflict) {
			return $t;
		}
		$options = new FCKeditorParserOptions();
		$options->setTidy(true);
		$parser = new FCKeditorParser();
		$parser->setOutputType(OT_HTML);
		$pa = $parser->parse($t, $this->mTitle, $options);
		return $pa->mText;

	}

	function getWikiContent(){
		return $this->mArticle->getContent();
	}

	/**
	 * This is a hack to fix
	 * http://dev.fckeditor.net/ticket/1174
	 * If RTE is enabled, diff must be performed on WikiText, not on HTML
	 */
	function showDiff() {
		global $wgFCKWikiTextBeforeParse;
		if (isset($wgFCKWikiTextBeforeParse)) {
			$_textbox1 = $this->textbox1;
			$this->textbox1 = $wgFCKWikiTextBeforeParse;
		}
			$result = parent::showDiff();
		if (isset($wgFCKWikiTextBeforeParse)) {
			$this->textbox1 = $_textbox1;
		}
	}

        /**
         * When the FCKeditor is used and the preview button is hit, we must
         * change the form and add the hidden field mode with value wysiwyg so
         * that the FCKeditor is also displayed in the preview mode. Without
         * this we would get the normal text editor from mediawiki.
         *
         * @global OutputPage $wgOut
         * @param function $formCallback
         */
        function showEditForm( $formCallback=null ) {
                global $wgOut;
                parent::showEditForm($formCallback);
                $bodyHtml = $wgOut->getHTML();
                $bodyHtml = str_replace(
                    '</form>',
                    '<input type="hidden" name="mode" value="wysiwyg" /></form>',
                    $bodyHtml);
                $wgOut->clearHTML();
                $wgOut->addHTML($bodyHtml);
        }
}
