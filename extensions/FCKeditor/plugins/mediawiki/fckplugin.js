/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2007 Frederico Caldeira Knabben
 *
 * == BEGIN LICENSE ==
 *
 * Licensed under the terms of any of the following licenses at your
 * choice:
 *
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 *
 * Main MediaWiki integration plugin.
 *
 * Wikitext syntax reference:
 *	http://meta.wikimedia.org/wiki/Help:Wikitext_examples
 *	http://meta.wikimedia.org/wiki/Help:Advanced_editing
 *
 * MediaWiki Sandbox:
 *	http://meta.wikimedia.org/wiki/Meta:Sandbox
 */

// Rename the "Source" buttom to "Wikitext".
FCKToolbarItems.RegisterItem( 'Source', new FCKToolbarButton( 'Source', 'Wikitext', null, FCK_TOOLBARITEM_ICONTEXT, true, true, 1 ) ) ;

// Register our toolbar buttons.
var tbButton = new FCKToolbarButton( 'MW_Template', 'Template', 'Insert/Edit Template' ) ;
tbButton.IconPath = FCKConfig.PluginsPath + 'mediawiki/images/tb_icon_template.gif' ;
FCKToolbarItems.RegisterItem( 'MW_Template', tbButton ) ;

tbButton = new FCKToolbarButton( 'MW_Ref', 'Reference', 'Insert/Edit Reference' ) ;
tbButton.IconPath = FCKConfig.PluginsPath + 'mediawiki/images/tb_icon_ref.gif' ;
FCKToolbarItems.RegisterItem( 'MW_Ref', tbButton ) ;

tbButton = new FCKToolbarButton( 'MW_Math', 'Formula', 'Insert/Edit Formula' ) ;
tbButton.IconPath = FCKConfig.PluginsPath + 'mediawiki/images/tb_icon_math.gif' ;
FCKToolbarItems.RegisterItem( 'MW_Math', tbButton ) ;

tbButton = new FCKToolbarButton( 'MW_Special', 'Special Tag', 'Insert/Edit Special Tag' ) ;
tbButton.IconPath = FCKConfig.PluginsPath + 'mediawiki/images/tb_icon_special.gif' ;
FCKToolbarItems.RegisterItem( 'MW_Special', tbButton ) ;

var tbButton = new FCKToolbarButton( 'SMW_QueryInterface', 'QueryInterface', 'Query Interface' ) ;
tbButton.IconPath = FCKConfig.PluginsPath + 'mediawiki/images/tb_icon_ask.gif' ;
FCKToolbarItems.RegisterItem( 'SMW_QueryInterface', tbButton );

// Override some dialogs.
FCKCommands.RegisterCommand( 'MW_Template', new FCKDialogCommand( 'MW_Template', 'Template Properties', FCKConfig.PluginsPath + 'mediawiki/dialogs/template.html', 800, 600 ) ) ;
FCKCommands.RegisterCommand( 'MW_Ref', new FCKDialogCommand( 'MW_Ref', 'Reference Properties', FCKConfig.PluginsPath + 'mediawiki/dialogs/ref.html', 400, 250 ) ) ;
FCKCommands.RegisterCommand( 'MW_Math', new FCKDialogCommand( 'MW_Math', 'Formula', FCKConfig.PluginsPath + 'mediawiki/dialogs/math.html', 400, 300 ) ) ;
FCKCommands.RegisterCommand( 'MW_Special', new FCKDialogCommand( 'MW_Special', 'Special Tag Properties', FCKConfig.PluginsPath + 'mediawiki/dialogs/special.html', 400, 330 ) ) ; //YC
FCKCommands.RegisterCommand( 'Link', new FCKDialogCommand( 'Link', FCKLang.DlgLnkWindowTitle, FCKConfig.PluginsPath + 'mediawiki/dialogs/link.html', 400, 250 ) ) ;
FCKCommands.RegisterCommand( 'Image', new FCKDialogCommand( 'Image', FCKLang.DlgImgTitle, FCKConfig.PluginsPath + 'mediawiki/dialogs/image.html', 450, 300 ) ) ;
FCKCommands.RegisterCommand( 'SMW_QueryInterface', new FCKDialogCommand( 'SMW_QueryInterface', 'QueryInterface', FCKConfig.PluginsPath + 'mediawiki/dialogs/queryinterface.php', 800, 600 ) ) ;

// MediaWiki Wikitext Data Processor implementation.
FCK.DataProcessor =
{
	_inPre : false,
	_inLSpace : false,	

	/*
	 * Returns a string representing the HTML format of "data". The returned
	 * value will be loaded in the editor.
	 * The HTML must be from <html> to </html>, eventually including
	 * the DOCTYPE.
	 *     @param {String} data The data to be converted in the
	 *            DataProcessor specific format.
	 */
	ConvertToHtml : function( data )
	{
		// Call the original code.
		return FCKDataProcessor.prototype.ConvertToHtml.call( this, data ) ;
	},

	/*
	 * Converts a DOM (sub-)tree to a string in the data format.
	 *     @param {Object} rootNode The node that contains the DOM tree to be
	 *            converted to the data format.
	 *     @param {Boolean} excludeRoot Indicates that the root node must not
	 *            be included in the conversion, only its children.
	 *     @param {Boolean} format Indicates that the data must be formatted
	 *            for human reading. Not all Data Processors may provide it.
	 */
	ConvertToDataFormat : function( rootNode, excludeRoot, ignoreIfEmptyParagraph, format )
	{
		// rootNode is <body>.

		// Normalize the document for text node processing (except IE - #1586).
		if ( !FCKBrowserInfo.IsIE )
			rootNode.normalize() ;

		var stringBuilder = new Array() ;
		this._AppendNode( rootNode, stringBuilder, '' ) ;
		return stringBuilder.join( '' ).RTrim().replace(/^\n*/, "") ;
	},

	/*
	 * Makes any necessary changes to a piece of HTML for insertion in the
	 * editor selection position.
	 *     @param {String} html The HTML to be fixed.
	 */
	FixHtml : function( html )
	{
		return html ;
	},

	// Collection of element definitions:
	//		0 : Prefix
	//		1 : Suffix
	//		2 : Ignore children
	_BasicElements : {
		body	: [ ],
		b		: [ "'''", "'''" ],
		strong	: [ "'''", "'''" ],
		i		: [ "''", "''" ],
		em		: [ "''", "''" ],
		p		: [ '\n', '\n' ],
		h1		: [ '\n= ', ' =\n' ],
		h2		: [ '\n== ', ' ==\n' ],
		h3		: [ '\n=== ', ' ===\n' ],
		h4		: [ '\n==== ', ' ====\n' ],
		h5		: [ '\n===== ', ' =====\n' ],
		h6		: [ '\n====== ', ' ======\n' ],
		br		: [ '<br>', null, true ],
		hr		: [ '\n----\n', null, true ]
	} ,

	// This function is based on FCKXHtml._AppendNode.
	_AppendNode : function( htmlNode, stringBuilder, prefix )
	{
		if ( !htmlNode )
			return ;

		switch ( htmlNode.nodeType )
		{
			// Element Node.
			case 1 :

				// Here we found an element that is not the real element, but a
				// fake one (like the Flash placeholder image), so we must get the real one.
				if ( htmlNode.getAttribute('_fckfakelement') && !htmlNode.getAttribute( '_fck_mw_math' ) )
					return this._AppendNode( FCK.GetRealElement( htmlNode ), stringBuilder ) ;

				// Mozilla insert custom nodes in the DOM.
				if ( FCKBrowserInfo.IsGecko && htmlNode.hasAttribute('_moz_editor_bogus_node') )
					return ;

				// This is for elements that are instrumental to FCKeditor and
				// must be removed from the final HTML.
				if ( htmlNode.getAttribute('_fcktemp') )
					return ;

				// Get the element name.
				var sNodeName = htmlNode.tagName.toLowerCase()  ;

				if ( FCKBrowserInfo.IsIE )
				{
					// IE doens't include the scope name in the nodeName. So, add the namespace.
					if ( htmlNode.scopeName && htmlNode.scopeName != 'HTML' && htmlNode.scopeName != 'FCK' )
						sNodeName = htmlNode.scopeName.toLowerCase() + ':' + sNodeName ;
				}
				else
				{
					if ( sNodeName.StartsWith( 'fck:' ) )
						sNodeName = sNodeName.Remove( 0,4 ) ;
				}

				// Check if the node name is valid, otherwise ignore this tag.
				// If the nodeName starts with a slash, it is a orphan closing tag.
				// On some strange cases, the nodeName is empty, even if the node exists.
				if ( !FCKRegexLib.ElementName.test( sNodeName ) )
					return ;

				if ( sNodeName == 'br' && ( this._inPre || this._inLSpace ) ) 
				{
					stringBuilder.push( "\n" ) ;
					if ( this._inLSpace )
						stringBuilder.push( " " ) ;
					return ;
				}
					
				// Remove the <br> if it is a bogus node.
				if ( sNodeName == 'br' && htmlNode.getAttribute( 'type', 2 ) == '_moz')
					return ;

				// The already processed nodes must be marked to avoid then to be duplicated (bad formatted HTML).
				// So here, the "mark" is checked... if the element is Ok, then mark it.
				if ( htmlNode._fckxhtmljob && htmlNode._fckxhtmljob == FCKXHtml.CurrentJobNum )
					return ;

				var basicElement = this._BasicElements[ sNodeName ] ;
				if ( basicElement )
				{
					var basic0 = basicElement[0];
					var basic1 = basicElement[1];

					if ( ( basicElement[0] == "''" || basicElement[0] == "'''" ) && stringBuilder.length > 2 )
					{
						var pr1 = stringBuilder[stringBuilder.length-1];
						var pr2 = stringBuilder[stringBuilder.length-2];

						if ( pr1 + pr2 == "'''''") {
							if ( basicElement[0] == "''")
							{
								basic0 = '<i>';
								basic1 = '</i>';
							}
							if ( basicElement[0] == "'''")
							{
								basic0 = '<b>';
								basic1 = '</b>';
							}
						}
					}

					if ( basic0 )
						stringBuilder.push( basic0 ) ;

					var len = stringBuilder.length ;
					
					if ( !basicElement[2] )
					{
						this._AppendChildNodes( htmlNode, stringBuilder, prefix ) ;
						// only empty element inside, remove it to avoid quotes
						if ( ( stringBuilder.length == len || (stringBuilder.length == len + 1 && !stringBuilder[len].length) ) 
							&& basicElement[0].charAt(0) == "'")
						{
							stringBuilder.pop();
							stringBuilder.pop();
							return;
						}
					}

					if ( basic1 )
						stringBuilder.push( basic1 ) ;
				}
				else
				{
					switch ( sNodeName )
					{
						case 'ol' :
						case 'ul' :
							var isFirstLevel = !htmlNode.parentNode.nodeName.IEquals( 'ul', 'ol', 'li', 'dl', 'dt', 'dd' ) ;

							this._AppendChildNodes( htmlNode, stringBuilder, prefix ) ;

							if ( isFirstLevel && stringBuilder[ stringBuilder.length - 1 ] != "\n" ) {
								stringBuilder.push( '\n' ) ;
							}

							break ;

						case 'li' :

							if( stringBuilder.length > 1)
							{
								var sLastStr = stringBuilder[ stringBuilder.length - 1 ] ;
								if ( sLastStr != ";" && sLastStr != ":" && sLastStr != "#" && sLastStr != "*")
 									stringBuilder.push( '\n' + prefix ) ;
							}
							
							var parent = htmlNode.parentNode ;
							var listType = "#" ;
							
							while ( parent )
							{
								if ( parent.nodeName.toLowerCase() == 'ul' )
								{
									listType = "*" ;
									break ;
								}
								else if ( parent.nodeName.toLowerCase() == 'ol' )
								{
									listType = "#" ;
									break ;
								}
								else if ( parent.nodeName.toLowerCase() != 'li' )
									break ;

								parent = parent.parentNode ;
							}
							
							stringBuilder.push( listType ) ;
							this._AppendChildNodes( htmlNode, stringBuilder, prefix + listType ) ;
							
							break ;

						case 'a' :

							// Get the actual Link href.
							var href = htmlNode.getAttribute( '_fcksavedurl' ) ;
							var hrefType		= htmlNode.getAttribute( '_fck_mw_type' ) || '' ;
							
							if ( href == null )
								href = htmlNode.getAttribute( 'href' , 2 ) || '' ;

							var isWikiUrl = true ;
							
							if ( hrefType == "media" )
								stringBuilder.push( '[[Media:' ) ;
							else if ( htmlNode.className == "extiw" )
							{
								stringBuilder.push( '[[' ) ;
								var isWikiUrl = true;
							}
							else
							{
								var isWikiUrl = !( href.StartsWith( 'mailto:' ) || /^\w+:\/\//.test( href ) ) ;
								stringBuilder.push( isWikiUrl ? '[[' : '[' ) ;
							}
							stringBuilder.push( href ) ;
							if ( htmlNode.innerHTML != '[n]' && (!isWikiUrl || href != htmlNode.innerHTML || !href.toLowerCase().StartsWith("category:")))
							{
								stringBuilder.push( isWikiUrl? '|' : ' ' ) ;
								this._AppendChildNodes( htmlNode, stringBuilder, prefix ) ;
							}
							stringBuilder.push( isWikiUrl ? ']]' : ']' ) ;

							break ;
							
						case 'dl' :
						
							this._AppendChildNodes( htmlNode, stringBuilder, prefix ) ;
							var isFirstLevel = !htmlNode.parentNode.nodeName.IEquals( 'ul', 'ol', 'li', 'dl', 'dd', 'dt' ) ;
							if ( isFirstLevel && stringBuilder[ stringBuilder.length - 1 ] != "\n" )
								stringBuilder.push( '\n') ;
							
							break ;

						case 'dt' :
						
							if( stringBuilder.length > 1)
							{
								var sLastStr = stringBuilder[ stringBuilder.length - 1 ] ;
								if ( sLastStr != ";" && sLastStr != ":" && sLastStr != "#" && sLastStr != "*" )
 									stringBuilder.push( '\n' + prefix ) ;
							}
							stringBuilder.push( ';' ) ;
							this._AppendChildNodes( htmlNode, stringBuilder, prefix + ";") ;
							
							break ;

						case 'dd' :
						
							if( stringBuilder.length > 1)
							{
								var sLastStr = stringBuilder[ stringBuilder.length - 1 ] ;
								if ( sLastStr != ";" && sLastStr != ":" && sLastStr != "#" && sLastStr != "*" )
 									stringBuilder.push( '\n' + prefix ) ;
							}
							stringBuilder.push( ':' ) ;
							this._AppendChildNodes( htmlNode, stringBuilder, prefix + ":" ) ;
							
							break ;
							
						case 'table' :

							var attribs = this._GetAttributesStr( htmlNode ) ;

							stringBuilder.push( '\n{|' ) ;
							if ( attribs.length > 0 )
								stringBuilder.push( attribs ) ;
							stringBuilder.push( '\n' ) ;

							if ( htmlNode.caption && htmlNode.caption.innerHTML.length > 0 )
							{
								stringBuilder.push( '|+ ' ) ;
								this._AppendChildNodes( htmlNode.caption, stringBuilder, prefix ) ;
								stringBuilder.push( '\n' ) ;
							}

							for ( var r = 0 ; r < htmlNode.rows.length ; r++ )
							{
								attribs = this._GetAttributesStr( htmlNode.rows[r] ) ;

								stringBuilder.push( '|-' ) ;
								if ( attribs.length > 0 )
									stringBuilder.push( attribs ) ;
								stringBuilder.push( '\n' ) ;

								for ( var c = 0 ; c < htmlNode.rows[r].cells.length ; c++ )
								{
									attribs = this._GetAttributesStr( htmlNode.rows[r].cells[c] ) ;

									if ( htmlNode.rows[r].cells[c].tagName.toLowerCase() == "th" )
										stringBuilder.push( '!' ) ; 
									else
										stringBuilder.push( '|' ) ;

									if ( attribs.length > 0 )
										stringBuilder.push( attribs + ' |' ) ;

									stringBuilder.push( ' ' ) ;

									this._IsInsideCell = true ;
									this._AppendChildNodes( htmlNode.rows[r].cells[c], stringBuilder, prefix ) ;
									this._IsInsideCell = false ;

									stringBuilder.push( '\n' ) ;
								}
							}

							stringBuilder.push( '|}\n' ) ;

							break ;

						case 'img' :

							var formula = htmlNode.getAttribute( '_fck_mw_math' ) ;

							if ( formula && formula.length > 0 )
							{
								stringBuilder.push( '<math>' ) ;
								stringBuilder.push( formula ) ;
								stringBuilder.push( '</math>' ) ;
								return ;
							}

							var imgName		= htmlNode.getAttribute( '_fck_mw_filename' ) ;
							var imgCaption	= htmlNode.getAttribute( 'alt' ) || '' ;
							var imgType		= htmlNode.getAttribute( '_fck_mw_type' ) || '' ;
							var imgLocation	= htmlNode.getAttribute( '_fck_mw_location' ) || '' ;
							var imgWidth	= htmlNode.getAttribute( '_fck_mw_width' ) || '' ;
							var imgHeight	= htmlNode.getAttribute( '_fck_mw_height' ) || '' ;

							stringBuilder.push( '[[Image:' )
							stringBuilder.push( imgName )

							if ( imgType.length > 0 )
								stringBuilder.push( '|' + imgType ) ;

							if ( imgLocation.length > 0 )
								stringBuilder.push( '|' + imgLocation ) ;

							if ( imgWidth.length > 0 )
							{
								stringBuilder.push( '|' + imgWidth ) ;

								if ( imgHeight.length > 0 )
									stringBuilder.push( 'x' + imgHeight ) ;

								stringBuilder.push( 'px' ) ;
							}

							if ( imgCaption.length > 0 )
								stringBuilder.push( '|' + imgCaption ) ;

							stringBuilder.push( ']]' )

							break ;

						case 'span' :
							switch ( htmlNode.className )
							{
								case 'fck_mw_ref' :
									var refName = htmlNode.getAttribute( 'name' ) ;

									stringBuilder.push( '<ref' ) ;

									if ( refName && refName.length > 0 )
										stringBuilder.push( ' name="' + refName + '"' ) ;

									if ( htmlNode.innerHTML.length == 0 )
										stringBuilder.push( ' />' ) ;
									else
									{
										stringBuilder.push( '>' ) ;
										stringBuilder.push( htmlNode.innerHTML ) ;
										stringBuilder.push( '</ref>' ) ;
									}
									return ;

								case 'fck_mw_references' :
									stringBuilder.push( '<references />' ) ;
									return ;

								case 'fck_mw_template' :
									stringBuilder.push( FCKTools.HTMLDecode(htmlNode.innerHTML).replace(/fckLR/g,'\r\n') ) ;
									return;
									
								case 'fck_mw_askquery' :
									stringBuilder.push( FCKTools.HTMLDecode(htmlNode.innerHTML).replace(/fckLR/g,'\r\n') ) ;
									return ;
								
								case 'fck_mw_magic' :
									stringBuilder.push( htmlNode.innerHTML ) ;
									return ;

								case 'fck_mw_property' :
								case 'fck_mw_category' :
									stringBuilder.push( this._formatSemanticValues(htmlNode) ) ;
									return ;

								case 'fck_mw_nowiki' :
									sNodeName = 'nowiki' ;
									break ;

								case 'fck_mw_includeonly' :
									sNodeName = 'includeonly' ;
									break ;

								case 'fck_mw_noinclude' :
									sNodeName = 'noinclude' ;
									break ;

								case 'fck_mw_gallery' :
									sNodeName = 'gallery' ;
									break ;
									
								case 'fck_mw_onlyinclude' :
									sNodeName = 'onlyinclude' ;
									break ;
								case 'fck_mw_special' :
								    tagName = htmlNode.getAttribute( '_fck_mw_tagname' );
								    tagType = htmlNode.getAttribute( '_fck_mw_tagtype' );
								    switch (tagType) {
								        case 't' :
								            stringBuilder.push( '<' + tagName + '>' + FCKTools.HTMLDecode(htmlNode.innerHTML).replace(/fckLR/g,'\r\n') + '</' + tagName + '>');
								            break;
								        case 'c' :
								            stringBuilder.push( '__' + tagName + '__' );
								            break;
								        case 'v' :
								        case 'w' :
								            stringBuilder.push( '{{' + tagName + '}}' );
								            break;
								        case 'p' :
								            stringBuilder.push( '{{' + tagName );
								            if (htmlNode.innerHTML.length > 0)
								                stringBuilder.push( ':' + FCKTools.HTMLDecode(htmlNode.innerHTML).replace(/fckLR/g,'\r\n') );
								            stringBuilder.push( '}}');
								            break;
								    }
								    return;
							}

							// Change the node name and fell in the "default" case.
							if ( htmlNode.getAttribute( '_fck_mw_customtag' ) )
								sNodeName = htmlNode.getAttribute( '_fck_mw_tagname' ) ;

						case 'pre' :
							var attribs = this._GetAttributesStr( htmlNode ) ;
							
							if ( htmlNode.className == "_fck_mw_lspace")
							{
								stringBuilder.push( "\n " ) ;
								this._inLSpace = true ;
								this._AppendChildNodes( htmlNode, stringBuilder, prefix ) ;
								this._inLSpace = false ;
								if ( !stringBuilder[stringBuilder.length-1].EndsWith("\n") )
									stringBuilder.push( "\n" ) ;
							}
							else
							{
								stringBuilder.push( '<' ) ;
								stringBuilder.push( sNodeName ) ;

								if ( attribs.length > 0 )
									stringBuilder.push( attribs ) ;

								stringBuilder.push( '>' ) ;
								this._inPre = true ;
								this._AppendChildNodes( htmlNode, stringBuilder, prefix ) ;
								this._inPre = false ;

								stringBuilder.push( '<\/' ) ;
								stringBuilder.push( sNodeName ) ;
								stringBuilder.push( '>' ) ;
							}
						
							break ;
						default :
							var attribs = this._GetAttributesStr( htmlNode ) ;

							stringBuilder.push( '<' ) ;
							stringBuilder.push( sNodeName ) ;

							if ( attribs.length > 0 )
								stringBuilder.push( attribs ) ;

							stringBuilder.push( '>' ) ;
							this._AppendChildNodes( htmlNode, stringBuilder, prefix ) ;
							stringBuilder.push( '<\/' ) ;
							stringBuilder.push( sNodeName ) ;
							stringBuilder.push( '>' ) ;
							break ;
					}
				}

				htmlNode._fckxhtmljob = FCKXHtml.CurrentJobNum ;
				return ;

			// Text Node.
			case 3 :

				var parentIsSpecialTag = htmlNode.parentNode.getAttribute( '_fck_mw_customtag' ) ; 
				var textValue = htmlNode.nodeValue;
	
				if ( !parentIsSpecialTag ) 
				{
					if ( FCKBrowserInfo.IsIE && this._inLSpace ) {
						textValue = textValue.replace(/\r/, "\r ") ;
					}
					
					if (!this._inLSpace && !this._inPre && !FCKBrowserInfo.IsOpera) {
						textValue = textValue.replace( /[\n\t]/g, ' ' ) ; 
					}
	
					textValue = FCKTools.HTMLEncode( textValue ) ;
					textValue = textValue.replace( /\u00A0/g, '&nbsp;' ) ;

					if ( ( !htmlNode.previousSibling ||
					( stringBuilder.length > 0 && stringBuilder[ stringBuilder.length - 1 ].EndsWith( '\n' ) ) ) && !this._inLSpace && !this._inPre )
					{
						textValue = textValue.LTrim() ;
					}

					if ( !htmlNode.nextSibling && !this._inLSpace && !this._inPre && (!htmlNode.parentNode || !htmlNode.parentNode.nextSibling))
						textValue = textValue.RTrim() ;

					if (!this._inLSpace && !this._inPre)
						textValue = textValue.replace( / {2,}/g, ' ' ) ;

					if ( this._inLSpace && textValue.length == 1 && textValue.charCodeAt(0) == 13 )
						textValue = textValue + " " ;
					if ( this._IsInsideCell )
						textValue = textValue.replace( /\|/g, '&#124;' ) ;
				}
				else 
				{
					textValue = FCKTools.HTMLDecode(textValue).replace(/fckLR/g,'\r\n');
				}
				stringBuilder.push( textValue ) ;
				return ;

			// Comment
			case 8 :
				// IE catches the <!DOTYPE ... > as a comment, but it has no
				// innerHTML, so we can catch it, and ignore it.
				if ( FCKBrowserInfo.IsIE && !htmlNode.innerHTML )
					return ;

				stringBuilder.push( "<!--"  ) ;

				try	{ stringBuilder.push( htmlNode.nodeValue ) ; }
				catch (e) { /* Do nothing... probably this is a wrong format comment. */ }

				stringBuilder.push( "-->" ) ;
				return ;
		}
	},

	_AppendChildNodes : function( htmlNode, stringBuilder, listPrefix )
	{
		var child = htmlNode.firstChild ;

		while ( child )
		{
			this._AppendNode( child, stringBuilder, listPrefix ) ;
			child = child.nextSibling ;
		}
	},

	_GetAttributesStr : function( htmlNode )
	{
		var attStr = '' ;
		var aAttributes = htmlNode.attributes ;

		for ( var n = 0 ; n < aAttributes.length ; n++ )
		{
			var oAttribute = aAttributes[n] ;

			if ( oAttribute.specified )
			{
				var sAttName = oAttribute.nodeName.toLowerCase() ;
				var sAttValue ;

				// Ignore any attribute starting with "_fck".
				if ( sAttName.StartsWith( '_fck' ) )
					continue ;
				// There is a bug in Mozilla that returns '_moz_xxx' attributes as specified.
				else if ( sAttName.indexOf( '_moz' ) == 0 )
					continue ;
				// For "class", nodeValue must be used.
				else if ( sAttName == 'class' )
				{
					// Get the class, removing any fckXXX we can have there.
					sAttValue = oAttribute.nodeValue.replace( /(^|\s*)fck\S+/, '' ).Trim() ;

					if ( sAttValue.length == 0 )
						continue ;
				}
				else if ( sAttName == 'style' && FCKBrowserInfo.IsIE ) {
					sAttValue = htmlNode.style.cssText.toLowerCase() ;
				}
				// XHTML doens't support attribute minimization like "CHECKED". It must be trasformed to cheched="checked".
				else if ( oAttribute.nodeValue === true )
					sAttValue = sAttName ;
				else
					sAttValue = htmlNode.getAttribute( sAttName, 2 ) ;	// We must use getAttribute to get it exactly as it is defined.

				// leave templates
				if ( sAttName.StartsWith( '{{' ) && sAttName.EndsWith( '}}' ) ) {
					attStr += ' ' + sAttName ;
				}
				else {
					attStr += ' ' + sAttName + '="' + String(sAttValue).replace( '"', '&quot;' ) + '"' ;
				}
			}
		}
		return attStr ;
	},
	
	// Property and Category values must be of a certain format. Otherwise this will break
	// the semantic annotation when switching between wikitext and WYSIWYG view
	_formatSemanticValues : function (htmlNode) {
		var text = htmlNode.innerHTML;

		// remove any &nbsp;
		text = text.replace('&nbsp;', ' ');
		// remove any possible linebreaks
		text = text.replace('<br>', ' ');
		// ltrim
		text = text.replace(/^\s+/, '');
		// rtrim
		text = text.replace(/\s+$/, '');
		// no value set, then add an space to fix problems with [[prop:val| ]]
		if (text.length == 0)
			text = " ";
		// regex to check for empty value
		var emptyVal = /^\s+$/;

		switch (htmlNode.className) {
			case 'fck_mw_property' :
				var name = htmlNode.getAttribute('property');
				if (name.indexOf('::') != -1) {
					if ( emptyVal.exec( name.substring(name.indexOf('::') + 2) ) ) return '';
					return '[[' + name + '|' + text + ']]' ;
				}
				else {
					if (emptyVal.exec(text)) return '';
					return '[[' + name + '::' + text + ']]' ;
				}
			case 'fck_mw_category' :
				var sort = htmlNode.getAttribute('sort');
				if (sort) {
					if (emptyVal.exec(sort)) return '';
					return '[[Category:' + text + '|' + sort + ']]';
				}
				if (emptyVal.exec(text)) return '';
				return '[[Category:' + text + ']]'
		}
	}
	
} ;

// Here we change the SwitchEditMode function to make the Ajax call when
// switching from Wikitext.
(function()
{
	var original = FCK.SwitchEditMode ;

	FCK.SwitchEditMode = function()
	{
		var args = arguments ;

		var loadHTMLFromAjax = function( result )
		{
			FCK.EditingArea.Textarea.value = result.responseText ;
			original.apply( FCK, args ) ;
		}

		if ( FCK.EditMode == FCK_EDITMODE_SOURCE )
		{
			// Hide the textarea to avoid seeing the code change.
			FCK.EditingArea.Textarea.style.visibility = 'hidden' ;

			var loading = document.createElement( 'span' ) ;
			loading.innerHTML = '&nbsp;Loading Wikitext. Please wait...&nbsp;' ;
			loading.style.position = 'absolute' ;
			loading.style.left = '5px' ;
//			loading.style.backgroundColor = '#ff0000' ;
			FCK.EditingArea.Textarea.parentNode.appendChild( loading, FCK.EditingArea.Textarea ) ;

			// Use Ajax to transform the Wikitext to HTML.
			window.parent.sajax_request_type = 'POST' ;
			window.parent.sajax_do_call( 'wfSajaxWikiToHTML', [FCK.EditingArea.Textarea.value], loadHTMLFromAjax ) ;
		}
		else
			original.apply( FCK, args ) ;
	}
})() ;

// MediaWiki document processor.
FCKDocumentProcessor.AppendNew().ProcessDocument = function( document )
{
	// Templates and magic words.
	var aSpans = document.getElementsByTagName( 'SPAN' ) ;

	var eSpan ;
	var i = aSpans.length - 1 ;
	while ( i >= 0 && ( eSpan = aSpans[i--] ) )
	{
		var className = null ;
		switch ( eSpan.className )
		{
			case 'fck_mw_ref' :
				className = 'FCK__MWRef' ;
			case 'fck_mw_references' :
				if ( className == null )
					className = 'FCK__MWReferences' ;
			case 'fck_mw_template' :
				if ( className == null ) //YC
					className = 'FCK__MWTemplate' ; //YC
			case 'fck_mw_askquery' :
				if ( className == null )
					className = 'FCK__SMWask' ;
			case 'fck_mw_magic' :
				if ( className == null )
					className = 'FCK__MWMagicWord' ;
			case 'fck_mw_special' : //YC
				if ( className == null )
					className = 'FCK__MWSpecial' ;
			case 'fck_mw_nowiki' :
				if ( className == null )
					className = 'FCK__MWNowiki' ;
			case 'fck_mw_includeonly' :
				if ( className == null )
					className = 'FCK__MWIncludeonly' ;
			case 'fck_mw_gallery' :
				if ( className == null )
					className = 'FCK__MWGallery' ;
			case 'fck_mw_noinclude' :
				if ( className == null )
					className = 'FCK__MWNoinclude' ;
			case 'fck_mw_onlyinclude' :
				if ( className == null )
					className = 'FCK__MWOnlyinclude' ;
				// Property and Category elements remains as span, don't replace the span with an img
				if (className != null) {
					var oImg = FCKDocumentProcessor_CreateFakeImage( className, eSpan.cloneNode(true) ) ;
					oImg.setAttribute( '_' + eSpan.className, 'true', 0 ) ;

					eSpan.parentNode.insertBefore( oImg, eSpan ) ;
					eSpan.parentNode.removeChild( eSpan ) ;
				}
			break ;
		}
	}
	
	// InterWiki / InterLanguage links
	var aHrefs = document.getElementsByTagName( 'A' ) ;
	var a ;
	var i = aHrefs.length - 1 ;
	while ( i >= 0 && ( a = aHrefs[i--] ) )
	{
		if (a.className == 'extiw')
		{
			 a.href = ":" + a.title ;
			 a.setAttribute( '_fcksavedurl', ":" + a.title ) ;
		}
	}
}

// Context menu for templates.
FCK.ContextMenu.RegisterListener({
	AddItems : function( contextMenu, tag, tagName )
	{
		if ( tagName == 'IMG' )
		{
			if ( tag.getAttribute( '_fck_mw_template' ) )
			{
				contextMenu.AddSeparator() ;
				contextMenu.AddItem( 'MW_Template', 'Template Properties' ) ;
			}
			if ( tag.getAttribute( '_fck_mw_askquery' ) )
			{
				contextMenu.AddSeparator() ;
				contextMenu.AddItem( 'SMW_QueryInterface', 'Query Source' ) ;
			}
			if ( tag.getAttribute( '_fck_mw_magic' ) )
			{
				contextMenu.AddSeparator() ;
				contextMenu.AddItem( 'MW_MagicWord', 'Modify Magic Word' ) ;
			}
			if ( tag.getAttribute( '_fck_mw_ref' ) )
			{
				contextMenu.AddSeparator() ;
				contextMenu.AddItem( 'MW_Ref', 'Reference Properties' ) ;
			}
			if ( tag.getAttribute( '_fck_mw_math' ) )
			{
				contextMenu.AddSeparator() ;
				contextMenu.AddItem( 'MW_Math', 'Edit Formula' ) ;
			}
			if ( tag.getAttribute( '_fck_mw_special' ) || tag.getAttribute( '_fck_mw_nowiki' ) || tag.getAttribute( '_fck_mw_includeonly' ) || tag.getAttribute( '_fck_mw_noinclude' ) || tag.getAttribute( '_fck_mw_onlyinclude' ) || tag.getAttribute( '_fck_mw_gallery' )) //YC
			{
				contextMenu.AddSeparator() ;
				contextMenu.AddItem( 'MW_Special', 'Special Tag Properties' ) ;
			}
		}
	}
}) ;


var SMW_Annotate = window.parent.Class.create();
SMW_Annotate.prototype = {

    initialize: function() {
        this.editorArea = FCK.GetData();
        this.IsActive = 0;
        this.contextMenu = null;
        this.eventManager = new window.parent.EventManager();
    },

    Execute: function() {

        if (this.IsActive == this.GetState())
           this.IsActive = 1 - this.GetState()
        if (this.IsActive) {
            window.parent.AdvancedAnnotation.create();
            window.parent.stb_control.stbconstructor();
            window.parent.stb_control.createForcedHeader();
            window.parent.obContributor.registerContributor();
            window.parent.relToolBar.callme();
            window.parent.catToolBar.callme();
            window.parent.smw_help_callme();
            window.parent.smw_links_callme();
            SetEventHandler4AnnotationBox();
        } else {
            window.parent.AdvancedAnnotation.unload();
            //FCK.EditorDocument.body.onchange = "";
            this.eventManager.deregisterAllEvents();
        }

    },

    GetState: function() {
        if ( FCK.EditMode != FCK_EDITMODE_WYSIWYG )
            return FCK_TRISTATE_DISABLED ;
        return this.IsActive ? FCK_TRISTATE_ON : FCK_TRISTATE_OFF ;
    },
    
    EditorareaChanges : function() {
        if (this.editorArea != FCK.GetData()) {
            window.parent.relToolBar.fillList();
            window.parent.catToolBar.fillList();
            this.editorArea = FCK.GetData();
        }
    },

    CheckSelectedAndCallPopup : function() {
        // handle here if the popup box for a selected annotation must be shown
        var selection = gEditInterface.getSelectionAsArray();
        if (selection == null)
            alert('The current selection cannot be used for annotating a category or property');
        // something is selected, this will be a new annotation,
        // offer both category and property toolbox
        else if (selection.length == 1 && selection[0] != "")
            this.ShowNewToolbar(selection[0]);
        // an existing annotation will be edited
        else if (selection.length > 1) {
            if (selection[1] == 102) { // Property
                var val = (selection.length == 4) ? selection[3] : selection[0];
                this.ShowRelToolbar(val, selection[0], selection[2]);
            }
            else { // Category
                this.ShowCatToolbar(selection[0]);
            }
        }
        else {
            alert('nothing to do')
        }
    },

    ShowNewToolbar: function(value) {
        var wtp = new window.parent.WikiTextParser();
        this.contextMenu = new window.parent.ContextMenuFramework();
        this.contextMenu.setPosition(100, 100);
        var relToolBar = new window.parent.RelationToolBar();
        var catToolBar = new window.parent.CategoryToolBar();
        relToolBar.setWikiTextParser(wtp);
        catToolBar.setWikiTextParser(wtp);
        relToolBar.createContextMenu(this.contextMenu, value, value);
        catToolBar.createContextMenu(this.contextMenu, value);
        this.contextMenu.showMenu();

    },
    
    ShowRelToolbar: function(name, value, show) {
        var wtp = new window.parent.WikiTextParser();
        this.contextMenu = new window.parent.ContextMenuFramework();
        this.contextMenu.setPosition(100, 100);
        var toolBar = new window.parent.RelationToolBar();
        toolBar.setWikiTextParser(wtp);
        toolBar.createContextMenu(this.contextMenu, name, value, show);
        this.contextMenu.showMenu();
    },

    ShowCatToolbar: function(name) {
        var wtp = new window.parent.WikiTextParser();
        this.contextMenu = new window.parent.ContextMenuFramework();
        this.contextMenu.setPosition(100, 100);
        var toolBar = new window.parent.CategoryToolBar();
        toolBar.setWikiTextParser(wtp);
        toolBar.createContextMenu(this.contextMenu, name);
        this.contextMenu.showMenu();
    }
};


// needed to access the Plugin class from the FCKeditInterface
var gAnnotationPlugin = new SMW_Annotate();

function SetEventHandler4AnnotationBox() {
    window.parent.Event.observe(window.frames[0], 'keyup', gAnnotationPlugin.EditorareaChanges);
    window.parent.Event.observe(window.frames[0], 'mouseup', gAnnotationPlugin.CheckSelectedAndCallPopup);
}



var FCKeditInterface = window.parent.Class.create();
FCKeditInterface.prototype = {

    initialize: function() {
        this.newText = '';
        this.selection = Array();
        this.start = -1;
        this.end = -1;
        this.selectedElement = null;
    },

   /**
    * gets the selected string. This is the  simple string of a selected
    * text in the editor arrea.
    * 
    * @access public
    * @return string selected text or null
    * */
    getSelectedText: function() {
        if (this.selection.length == 0) this.getSelectionAsArray();
        return (this.selection.length > 0) ? this.selection[0] : null;
    },

    /**
     * returns the text of the edit window. This is wiki text.
     * If this.newText is set, then something on the text has changed but the
     * editarea is not yet updated with the new value. Therefore return this
     * instead of fetching the text (with still the old value) from the editor
     * area
     *
     * @access public
     * @return string wikitext of the editors textarea
     */
    getValue: function() {
        return (this.newText) ? this.newText : FCK.GetData();
    },

    setValue: function(text) {
        if (text) {
            function ajaxResponseSetHtmlText(request) {
                if (request.status == 200) {
                    // success => store wikitext as FCK HTML
                    FCK.SetData(request.responseText);
                }
                gEditInterface.newText = '';
                // custom event handlers are lost when using FCK.SetData
                SetEventHandler4AnnotationBox();
                //gAnnotationPlugin.SetEventHandler();
            };
            this.newText = text;
	    window.parent.sajax_do_call('wfSajaxWikiToHTML', [text],
	                                ajaxResponseSetHtmlText);
        }
    },

    getSelectedElement: function() {
        return this.selectedElement;
    },

    /**
     * gets the selected text of the current selection from the FCK
     * and fill up the member variable selection. This is an array of
     * maximum 4 elements which are:
     * 0 => selected text
     * 1 => namespace (14 = category, 102 = property) not existend otherwise
     * 2 => name of property or not set
     * 3 => actual value of property if sel. text is representation only not
     *      existend otherwise
     * If the selection is valid at least this.selection[0] must be set. The
     * selection is then returned to the caller
     *
     * @access public
     * @return Array(mixed) selection
     */
    getSelectionAsArray: function() {
        // selected element node
        this.selectedElement = FCKSelection.GetSelectedElement();
        // selection text only without html mark up
        var fckSelection = FCKSelection.GetSelection();
        // parent element of the selected text (mostly a <p>)
        var parent = FCKSelection.GetParentElement();
        // selection with html markup of the imediate parent element, if required
        var html = this.getSelectionHtml();
        // (partly) selected text within these elements can be annotated.
        var goodNodes = ['P', 'B', 'I', 'U', 'S'];

        // selection is the same as the innerHTML -> no html was selected
        if (fckSelection == html) {
            // if the parent node is <a> or a <span> (property, category) then
            // we automatically select *all* of the inner html and the annotation
            // works for the complete node content (this is a must for these nodes)
            if (parent.nodeName == 'A') {
                this.selection[0] = parent.innerHTML;
                this.selectedElement = parent;
                return this.selection;
            }
            // check category and property that might be in the <span> tag,
            // ignore all other spans that might exist as well
            if (parent.nodeName == 'SPAN') {
                var sclass = parent.getAttribute('class');
                switch (sclass) {
                    case 'fck_mw_property' :
                        this.selectedElement = parent;
                        this.selection[0] = parent.innerHTML;
                        this.selection[1] = 102;
                        var val = parent.getAttribute('property');
                        // differenciation between displayed representation and
                        // actual value of the property
                        if (val.indexOf('::') != -1) {
                            this.selection[2] = val.substring(0, val.indexOf('::'));
                            this.selection[3] = val.substring(val.indexOf('::') +2);
                        } else
                            this.selection[2] = val;
                        return this.selection;
                    case 'fck_mw_category' :
                        this.selectedElement = parent;
                        this.selection[0] = parent.innerHTML;
                        this.selection[1] = 14;
                        return this.selection;
                }
                return;
            }
            // just any text was selected, use this one for the selection
            // if it was encloded between the "good nodes"
            for (var i = 0; i < goodNodes.length; i++) {
                if (parent.nodeName == goodNodes[i]) {
                    this.selectedElement = parent;
                    this.selection[0] = fckSelection;
                    return this.selection;
                }
            }
            // selection is invalid
            return;
        }
        // the selection is exactly one tag that encloses the selected text
        var ok = html.match(/^<[^>]*?>[^<>]*<\/[^>]*?>$/g);
        if (ok && ok.length == 1) {
            var tag = html.replace(/^<(\w+) .*/, '$1').toUpperCase();
            var cont = html.replace(/^<[^>]*?>([^<>]*)<\/[^>]*?>$/, '$1');
            // anchors are the same as formating nodes, we use the selected
            // node content as the value.
            goodNodes.push('A');
            for (var i = 0; i < goodNodes.length; i++) {
                if (tag == goodNodes[i]) {
                    this.MatchSelectedNodeInDomtree(parent, tag, cont);
                    this.selection[0] = cont;
                    return this.selection;
                }
            }
            // there are several span tags, we need to find categories and properties
            if (tag == 'SPAN') {
                if (html.indexOf('class="fck_mw_property"') != -1) {
                    this.MatchSelectedNodeInDomtree(parent, tag, cont);
                    this.selection[0] = cont;
                    this.selection[1] = 102;
                    var val = html.replace(/.*property="(.*?)".*/, '$1');
                    if (val.indexOf('::') != -1) {
                        this.selection[2] = val.substring(0, val.indexOf('::'));
                        this.selection[3] = val.substring(val.indexOf('::') +2);
                    } else {
                        this.selection[2] = val;
                    }
                    return this.selection;
                }
                if (html.indexOf('class="fck_mw_category"') != -1) {
                    this.MatchSelectedNodeInDomtree(parent, tag, cont);
                    this.selection[0] = cont;
                    this.selection[1] = 14;
                    return this.selection;
                } // below here passing all closing brakets means that the selection
            }     // was invalid
        }
    },

   /**
    * from the parent node go over the child nodes and
    * select the appropriate child based on the string match that was
    * done before
    *
    * @access private
    * @param  DOMNode parent
    * @param  string node name
    * @param  string node value
    */
   MatchSelectedNodeInDomtree: function (parent, nodeName, nodeValue) {
        for(var i = 0; i < parent.childNodes.length; i++) {
            if (parent.childNodes[i].nodeType == 1 &&
                parent.childNodes[i].nodeName.toUpperCase() == nodeName &&
                parent.childNodes[i].innerHTML.replace(/^\s*/, '').replace(/\s*$/, '') == nodeValue) {
                this.selectedElement = parent.childNodes[i];
                return;
            }
        }
   },

   /**
    * Checks the current selection and returns the html content of the
    * selection.
    *
    * @access private
    * @param  void
    * @return string text
    */
    getSelectionHtml: function() {

        var selection = (FCK.EditorWindow.getSelection ? FCK.EditorWindow.getSelection() : FCK.EditorDocument.selection);
        if(selection.createRange) {
            var range = selection.createRange();
            var html = range.htmlText;
        }
        else {
            var range = selection.getRangeAt(selection.rangeCount - 1).cloneRange();
            var clonedSelection = range.cloneContents();
            var div = document.createElement('div');
            div.appendChild(clonedSelection);
            var html = div.innerHTML;
            // check if selection contains a html tag of span or a
            // i.e. link, property, category, because in these cases
            // we must select all of the inner content.
            if (html.indexOf('<span') == 0 || html.indexOf('<a') == 0) {
                // even though in the original the tag look like <span property...>This is my property rep</span>
                // the selected html might contain <span property...>property rep</span> only. To select all make
                // a text match of the content of the ancestor tag content.
                var parentContent = selection.getRangeAt(selection.rangeCount -1).commonAncestorContainer.innerHTML;
                // build a pattern of <span property...>property rep</span>
                var pattern = html.replace(/([().|?*{}\/])/g, '\\$1');
                pattern = pattern.replace('>', '>.*?');
                pattern = pattern.replace('<\\/', '.*?<\\/');
                pattern = '(.*)(' + pattern + ')(.*)';
                // the pattern is now: (.*)(<span property\.\.\.>.*?property rep.*?<\/span>)(.*)
                var rex = new RegExp(pattern)
                if (rex instanceof RegExp)
                    html = parentContent.replace(rex, '$2');
            }
        }
        return html.replace(/^\s*/, '').replace(/\s*$/, ''); // trim the selected text
    },

    setSelectionRange: function(start, end) {
        var html = FCK.GetData().substring(start, end - start);
        if (html.indexOf('class="fck_mw_property"') != -1) {
            this.selection[0] = html.replace(/<[^>]*>([^<]*)<[^>]*>/, '$1');
            this.selection[1] = 102;
            var val = html.replace(/.*property="(.*?)".*/, '$1');
            if (val.indexOf('::') != -1) {
                this.selection[2] = val.substring(0, val.indexOf('::'));
                this.selection[3] = val.substring(val.indexOf('::') +2);
            } else {
                this.selection[2] = val;
            }
        }
        else if (html.indexOf('class="fck_mw_category"') != -1) {
            this.selection[0] = html.replace(/<[^>]*>([^<]*)<[^>]*>/, '$1');
            this.selection[1] = 14;
        }
    },

    getTextBeforeCursor: function() {
        
    },
    
    selectCompleteAnnotation: function() {}



};


var gEditInterface = new FCKeditInterface();
window.parent.gEditInterface = gEditInterface;


var tbButton = new FCKToolbarButton( 'SMW_Annotate', 'Annotate', 'Semantic Annotations' ) ;
tbButton.IconPath = FCKConfig.PluginsPath + 'mediawiki/images/tb_icon_add.png' ;
FCKToolbarItems.RegisterItem( 'SMW_Annotate', tbButton );

FCKCommands.RegisterCommand( 'SMW_Annotate', gAnnotationPlugin ) ;