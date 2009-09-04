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
					return '[[' + window.parent.gLanguage.getMessage('CATEGORY') + text + '|' + sort + ']]';
				}
				if (emptyVal.exec(text)) return '';
				return '[[' + window.parent.gLanguage.getMessage('CATEGORY')+ text + ']]'
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
                        // If Semantic Toolbar is present, change the Event handlers
                        // when switching between wikitext <-> wysiwyg
                        if (fckSemanticToolbar.GetState()) {
                            ClearEventHandler4AnnotationBox();
                            SetEventHandler4AnnotationBox();
                        }

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
                    
                        // if Semantic Toolbar is present, change the Event handlers when switching
                        // between wikitext <-> wysiwyg, also hide the context popup because the user
                        // must reselect the text that he wants to annotate
                        if (fckSemanticToolbar.GetState()) {
                            HideContextPopup();
                            ClearEventHandler4AnnotationBox();
                            SetEventHandler4AnnotationBox();
                        }
                        // add autocompletion
                        //FCK.EditingArea.Textarea.className += ' wickEnabled:MWFloater0';
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
        this.IsActive = FCK_TRISTATE_OFF;
        this.contextMenu = null;
    },

    Execute: function() {
        //if ( FCK.EditMode != FCK_EDITMODE_WYSIWYG )
          //  return FCK_TRISTATE_DISABLED ;
        if (!this.IsActive)
            this.EnableAnnotationToolbar();
        else
            this.DisableAnnotationToolbar();

    },

    GetState: function() {
        return this.IsActive;
    },

    /**
     * enable the anntoation toolbar (usually when clicking the button in the
     * FCK toolbar)
     *
     * @access private
     */
    EnableAnnotationToolbar: function() {
        this.IsActive = FCK_TRISTATE_ON;
        window.parent.AdvancedAnnotation.create();
        window.parent.stb_control.stbconstructor();
        window.parent.stb_control.createForcedHeader();
        window.parent.obContributor.registerContributor();
        window.parent.relToolBar.callme();
        window.parent.catToolBar.callme();
        window.parent.propToolBar.callme();
        window.parent.smw_help_callme();
        // webservice toolbar, only available if DataImport extension is included
        if (window.parent.wsToolBar)
            window.parent.wsToolBar.callme();
        // rule toolbar, only available if SemanticRuls extension is included
        if (window.parent.ruleToolBar)
            window.parent.ruleToolBar.callme();
        // Annotations toolbar, only if SemanticGardening extension is included
        if (window.parent.smwhgGardeningHints)
            window.parent.smwhgGardeningHints.createContainer();
        //window.parent.smw_links_callme();
        SetEventHandler4AnnotationBox();
    },

    /**
     * disable the anntotation toolbar (usually when clicking the button in the
     * FCK toolbar or switching to wiki text). If the context menu is active
     * remove this as well.
     *
     * @access private
     */
    DisableAnnotationToolbar: function() {
        this.IsActive = FCK_TRISTATE_OFF;
        HideContextPopup();
        window.parent.AdvancedAnnotation.unload();
        ClearEventHandler4AnnotationBox();
    },

    /**
     * When the editor content has been changed, then the annotation toolbar
     * must be updated to reflect changes that may have occured in the edited
     * text. This function is called by the event keyup. Because of function
     * keys that do not change the content, so it's always checked if the edited
     * text has been changed. Only then the toolbar is rebuild.
     *
     * @access private
     */
    EditorareaChanges : function() {
        if (this.editorArea != FCK.GetData()) {
            window.parent.relToolBar.fillList();
            window.parent.catToolBar.fillList();
            this.editorArea = FCK.GetData();
        }
    }
}

// the following functions are defined in the global scope, because I had problems
// to access the correct instance of the class, when is must be done in an event
// handler.

/**
 * reomove the context menu from the DOM tree
 */
HideContextPopup = function() {
    if (fckPopupContextMenu) {
        fckPopupContextMenu.remove();
        fckPopupContextMenu = null;
    }
}

/**
 * fetches the current selected text from the gEditInterface (i.e. the FCK editor
 * area) and creates a context menu for annotating the selected text or modifying
 * the selected annotation.
 *
 * @param Event event
 */
CheckSelectedAndCallPopup = function(event) {
        // handle here if the popup box for a selected annotation must be shown
        var selection = gEditInterface.getSelectionAsArray();
        if (selection == null)
            alert('The current selection cannot be used for annotating a category or property');
        // something is selected, this will be a new annotation,
        // offer both category and property toolbox
        else if (selection.length == 1 && selection[0] != "") {
            ShowNewToolbar(event, selection[0]);
        }
        // an existing annotation will be edited
        else if (selection.length > 1) {
            if (selection[1] == 102) { // Property
                var show = selection[0];
                var val = selection[0];
                if (selection.length == 4)  // an explizit property value is set, then
                    val = selection[3];     // it's different from the selected (show)
                else                        // no difference between value and show
                    show = '';              // because there is not separate prop value
                ShowRelToolbar(event, selection[2], val, show);
            }
            else if (selection[1] == 14) { // Category
                ShowCatToolbar(event, selection[0]);
            }
        }
}

// global variable for the context menu itself
var fckPopupContextMenu;

/**
 * Create a new context menu for annotating a selection that is not yet annotated.
 * Both property and category container will be shown.
 *
 * @param Event event
 * @param string value selected text
 */
ShowNewToolbar = function(event, value) {
        var [x, y] = CalculateClickPosition(event);
        var wtp = new window.parent.WikiTextParser();
        fckPopupContextMenu = new window.parent.ContextMenuFramework();
        fckPopupContextMenu.setPosition(x, y);
        var relToolBar = new window.parent.RelationToolBar();
        var catToolBar = new window.parent.CategoryToolBar();
        relToolBar.setWikiTextParser(wtp);
        catToolBar.setWikiTextParser(wtp);
        relToolBar.createContextMenu(fckPopupContextMenu, value, '');
        catToolBar.createContextMenu(fckPopupContextMenu, value);
        fckPopupContextMenu.showMenu();

}

/**
 * Create a new context menu for annotating a property.
 * Only the property container will be shown.
 * The selected text is the representation at least. If value and represenation
 * are equal then the selected text is the value as well.
 *
 * @param Event event
 * @param string name of the property
 * @param string value of the property
 * @param string representation of the property
 */
ShowRelToolbar = function(event, name, value, show) {
        var [x, y] = CalculateClickPosition(event);
        var wtp = new window.parent.WikiTextParser();
        fckPopupContextMenu = new window.parent.ContextMenuFramework();
        fckPopupContextMenu.setPosition(x, y);
        var toolBar = new window.parent.RelationToolBar();
        toolBar.setWikiTextParser(wtp);
        toolBar.createContextMenu(fckPopupContextMenu, value, show, name);
        fckPopupContextMenu.showMenu();
}

/**
 * Create a new context menu for annotating a category.
 * Only the category container will be shown.
 * The selected text is the category name.
 *
 * @param Event event
 * @param string name selected text
 */
ShowCatToolbar = function(name) {
        var [x, y] = CalculateClickPosition(event);
        var wtp = new window.parent.WikiTextParser();
        fckPopupContextMenu = new window.parent.ContextMenuFramework();
        fckPopupContextMenu.setPosition(x, y);
        var toolBar = new window.parent.CategoryToolBar();
        toolBar.setWikiTextParser(wtp);
        toolBar.createContextMenu(fckPopupContextMenu, name);
        fckPopupContextMenu.showMenu();
}

/**
 * Calculate correct x and y coordinates of event in browser window
 *
 * @param Event event
 * @return Array(int, int) coordinates x, y
 */
CalculateClickPosition = function(event) {
    var el = window.parent.document.getElementById('editform');

    var x = 0;
    var y = 0;

    if (el.offsetParent) {
        do {
            x += el.offsetLeft;
            y += el.offsetTop;
        } while (el = el.offsetParent);
    }
    x += event.clientX;
    y += event.clientY;

    return [200, 200];  // set this fix for the moment, because the popup might
                        // be overlapped by the Semantic toolbar itself ans because
                        // popup is not yet dragable
}

// needed to access the Plugin class from the FCKeditInterface
var fckSemanticToolbar = new SMW_Annotate();

function SetEventHandler4AnnotationBox() {
    if ( FCK.EditMode == FCK_EDITMODE_WYSIWYG ) {
        window.parent.Event.observe(window.frames[0], 'keyup', fckSemanticToolbar.EditorareaChanges);
        window.parent.Event.observe(window.frames[0], 'mouseup', CheckSelectedAndCallPopup);
        window.parent.Event.observe(window.frames[0], 'mousedown', HideContextPopup);
    } else {
        window.parent.Event.observe(FCK.EditingArea.Textarea, 'keyup', fckSemanticToolbar.EditorareaChanges);
        window.parent.Event.observe(FCK.EditingArea.Textarea, 'mouseup', CheckSelectedAndCallPopup);
        window.parent.Event.observe(FCK.EditingArea.Textarea, 'mousedown', HideContextPopup);
    }
}

function ClearEventHandler4AnnotationBox() {
    if ( FCK.EditMode == FCK_EDITMODE_WYSIWYG ) {
        window.parent.Event.stopObserving(window.frames[0], 'keyup', fckSemanticToolbar.EditorareaChanges);
        window.parent.Event.stopObserving(window.frames[0], 'mouseup', CheckSelectedAndCallPopup);
        window.parent.Event.stopObserving(window.frames[0], 'mousedown', HideContextPopup);
    } else {
        window.parent.Event.stopObserving(FCK.EditingArea.Textarea, 'keyup', fckSemanticToolbar.EditorareaChanges);
        window.parent.Event.stopObserving(FCK.EditingArea.Textarea, 'mouseup', CheckSelectedAndCallPopup);
        window.parent.Event.stopObserving(FCK.EditingArea.Textarea, 'mousedown', HideContextPopup);
    }
}


/**
 * Class which has the same functionality as SMWEditInterface except for the fact
 * that this must work for the FCK Editor.
 * This class provides access to the edited text, returns a selection, changes
 * the content etc.
 * Basically this will be used in the Annotation toolbar.
 */
var FCKeditInterface = window.parent.Class.create();
FCKeditInterface.prototype = {

    initialize: function() {
        // if set, contains the new text that will be inserted in the editor area
        this.newText = '';
        // the selection in the editor window may be null if selection is invalid.
        // When set, at least element 0 is set. All other are optional depending
        // on the selected element.
        // 0 => the selected text
        // 1 => namespace number (14 => Category, 102 => Property)
        // 2 => name of the property/category
        // 3 => value of the property
        // 4 => representation of the property
        this.selection = Array();
        // the HTML element that contains the selected text
        this.selectedElement = null;
        // start and end for selection when in wikitext mode
        this.start = -1;
        this.end = -1;
    },

   /**
    * gets the selected string. This is the  simple string of a selected
    * text in the editor arrea.
    * 
    * @access public
    * @return string selected text or null
    * */
    getSelectedText: function() {
        this.getSelectionAsArray();
        return (this.selection.length > 0) ? this.selection[0] : null;
    },

    /**
     * Get the current selection of the FCKeditor and replace it with the
     * annotated value. This works on a category or property annotation only.
     * All other input is ignored and nothing will be replaced.
     *
     * @access public
     * @param  string text wikitext
     */
    setSelectedText: function(text) {
        // check if start and end are set, then simply replace the selection
        // in the textarea
        if (this.start != -1 && this.end != -1) {
            var txtarea = FCK.EditingArea.Textarea.value;
            var newtext = txtarea.substr(0, this.start) + text + txtarea.substr(this.end);
            this.clearSelection();
            HideContextPopup();
            this.setValue(newtext);
            return;
        }

        // WYSIWYG mode: continue using the functions of the FCK editor
        // Get the ancestor node. If we have a selection of some property
        // or category text there is a ancestor SPAN node. If this is not the
        // case, oSpan will be null. Then we create a new element. This will be
        // inserted at cursor position.
        var oSpan = FCK.Selection.MoveToAncestorNode( 'SPAN' ) ;
        if (oSpan)
            FCK.Selection.SelectNode( oSpan )
        else
            oSpan = FCK.EditorDocument.createElement( 'SPAN' ) ;

        // check the param text, if it's valid wiki text for a property or
        // category information.
        // check property first
        var regex = new RegExp('^\\[\\[(.*?)::(.*?)(\\|(.*?))?\\]\\]$')
        var match = regex.exec(text);
        if (match) {
            oSpan.className = 'fck_mw_property' ;
            if (match[4]) {
                oSpan.setAttribute( 'property',  match[1] + '::' + match[2] );
                oSpan.innerHTML = match[4];
            } else {
                oSpan.setAttribute( 'property',  match[1] );
                oSpan.innerHTML = match[2];
            }
        // no match for property, check category next
        } else {
            regex = new RegExp('^\\[\\[' + window.parent.gLanguage.getMessage('CATEGORY') + '(.*?)(\\|(.*?))?\\]\\]$');
            match = regex.exec(text);

            if (match) {
                oSpan.className = 'fck_mw_category' ;
                oSpan.innerHTML = match[1];
            }
            // no category neighter, something else (probably garbage) was in
            // the wikitext, then quit and do not modify the edited wiki page
            else return;
        }
        if ( oSpan ) {
            if ( FCK.EditMode == FCK_EDITMODE_WYSIWYG )
                FCK.InsertElement(oSpan);
            else
                FCK.InsertElement(text);
        }
        HideContextPopup();
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

    /**
     * set the text in the editor area completely new. The text in the function
     * argument is wiki text. Therefore an Ajax call must be done, to transform
     * this text into proper html what is needed by the FCKeditor. To parse the
     * wiki text, the parser of the FCK extension is used (the same when the
     * editor is started and when switching between wikitext and html).
     * 
     * Atfer parsing the text can be set in the editor area. This is done with
     * FCK.SetData(). When doing this all Event listeners are lost. Therefore
     * these must be added again. Also the variable this.newText which contains
     * the new text (runtime issues), can be flushed again.
     * In this case the global variable gEditInterface.newText is used to get
     * the correct instance of the class.
     *
     * @access public
     * @param  string text with wikitext
     */
    setValue: function(text) {
        if (text) {
            if (FCK.EditMode == FCK_EDITMODE_WYSIWYG) {
                function ajaxResponseSetHtmlText(request) {
                    if (request.status == 200) {
                        // success => store wikitext as FCK HTML
                        FCK.SetData(request.responseText);
                    }
                    gEditInterface.newText = '';
                    // custom event handlers are lost when using FCK.SetData
                    SetEventHandler4AnnotationBox();
                };
                this.newText = text;
                window.parent.sajax_do_call('wfSajaxWikiToHTML', [text],
                                            ajaxResponseSetHtmlText);
            }
            else {
                FCK.SetData(text);
                SetEventHandler4AnnotationBox();
            }
        }
    },

    /**
     * returns the element were the selection is in.
     *
     * @access private
     * @return HtmlNode
     */
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
        // flush the selection array
        this.selection = [];

        // if we are in wikitext mode, return the selection of the textarea
        if (FCK.EditMode != FCK_EDITMODE_WYSIWYG) {
            this.getSelectionWikitext();
            return this.selection;
        }

        // selection text only without any html mark up etc.
        var fckSel = FCKSelection.GetSelection();
        var srange = fckSel.getRangeAt(fckSel.rangeCount - 1).cloneRange();
        var selTextCont = srange.cloneContents().textContent;
        // nothing was really selected, this always happens when a single or
        // double click is done. The mousup event is fired even though the user
        // might have positioned the cursor somewhere only.
        if (selTextCont == '') {
            this.selection[0] = '';
            return this.selection;
        }

        // selected element node
        this.selectedElement = FCKSelection.GetSelectedElement();
        // parent element of the selected text (mostly a <p>)
        var parent = FCKSelection.GetParentElement();
        // selection with html markup of the imediate parent element, if required
        var html = this.getSelectionHtml();
        // (partly) selected text within these elements can be annotated.
        var goodNodes = ['P', 'B', 'I', 'U', 'S'];

        // selection is the same as the innerHTML -> no html was selected
        if (selTextCont == html) {
            // if the parent node is <a> or a <span> (property, category) then
            // we automatically select *all* of the inner html and the annotation
            // works for the complete node content (this is a must for these nodes)
            if (parent.nodeName.toUpperCase() == 'A') {
                this.selection[0] = parent.innerHTML;
                this.selectedElement = parent;
                return this.selection;
            }
            // check category and property that might be in the <span> tag,
            // ignore all other spans that might exist as well
            if (parent.nodeName.toUpperCase() == 'SPAN') {
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
                if (parent.nodeName.toUpperCase() == goodNodes[i]) {
                    this.selectedElement = parent;
                    this.selection[0] = selTextCont;
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
    * @param  DOMNode parent html element of the node (defined by name and value)
    * @param  string nodeName tag name
    * @param  string nodeValue content text of
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
    * i.e. select: "perty value show" and the returned string would be:
    * <span class="fck_mw_property property="myName">property value shown</span>
    *
    * @access private
    * @return string html selection including surounding html tags
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
            var lowerTags = html.toLowerCase();
            if (lowerTags.indexOf('<span') == 0 || lowerTags.indexOf('<a') == 0) {
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

    /**
     * If in wikitext mode, this function gets the seleced text and must also
     * select the complete annotation if the selection is inside of [[ ]]. Also
     * selections inside templates etc. must be ignored.
     * Evaluated parameter will be stored in variable this.selection.
     *
     * @access private
     * @see    getSelectionAsArray() for details on the selection
     */
    getSelectionWikitext: function() {
        // selected text by the user in the textarea
        var selection = this.getSelectionFromTextarea();
        if (selection.length == 0) return "";  // nothing selected

        // complete text from the editing area
        var txt = FCK.EditingArea.Textarea.value;

        var p; // position marker for later

        var currChar; // current character that is observed

        // start look at the left side of the selection for any special character
        var currPos = this.start;
        while (currPos > 0) {
            // go back one position in text area string.
            currPos--;
            currChar = txt.substr(currPos, 1);
            // "[" found, move the selection start there if there are two of them
            if (currChar == '[') {
                if (selection[0] == '[') {  // one [ is in the selection and other
                    this.start = currPos;        // is at position, stop here
                    break;
                }
                if (currPos > 0 && txt[currPos -1] == '[') {// previous pos also
                    this.start = currPos - 1;                    // also contains a [
                    break;
                }
            }
            // "]" found, stop looking further if there are two of them
            if (currChar == ']' && currPos > 0 && txt[currPos -1] == ']')
                break;
            // ">" found, check it's the end of a tag, and if so, which type of tag
            if (currChar == '>') {
                // look for the corresponding <
                p = txt.substr(0, currPos).lastIndexOf('<');
                if (p == -1) continue; // no < is there, just go ahead
                var tag = txt.substr(p, currPos - p + 1);
                if (tag.match(/^<[^<>]*>$/)) { // we really found a tag
                    if (tag[1] == '/')  // we are at the end of a closing tag
                        break;          // stop looking any further back
                    else if (tag.match(/\s*>$/)) // it's a <tag />, stop here as well
                        break;
                }
            }
            // maybe we are inside a tag and found it's begining. Check that
            if (currChar == '<') {
                // look for the corresponding >
                p = selection.indexOf('>');
                if (p == -1) continue;
                var tag = txt.substr(currPos, this.start + p + 1 - currPos);
                if (tag.match(/^<[^<>]*>$/)) // we really found a tag
                    return this.clearSelection();
            }
            // we are inside a template or parser function or whatever
            if (currChar == '{' && currPos > 0 && txt[currPos - 1] == '{')
                return;
            // end of a template or parser function found, stop here
            if (currChar == '}' && currPos > 0 && txt[currPos - 1] == '}')
                break;
        }

        // adjust selection if we moved the start position
        selection = txt.substr(this.start, this.end - this.start);
        
        // look for any special character at the right side of the selection
        currPos = this.end - 1;
        while (currPos < txt.length - 2) {
            // move the possition one step forward in the string
            currPos++;
            currChar = txt.substr(currPos, 1);
            // if we find an open braket, move the selection start there
            if (currChar == ']') {
                if (selection.substr(selection.length - 1, 1) == ']') {
                    this.end = currPos + 1;
                    break;
                }
                if (currPos < txt.length - 1 && txt.substr(currPos + 1, 1) == ']') {
                    this.end = currPos + 2;
                    break;
                }
            }
            // "[" found, stop looking further if there are two of them
            if (currChar == '[' && currPos < txt.length - 1 && txt[currPos + 1] == '[')
                break;
            // we are inside a template or parser function or whatever
            if (currChar == '}' && currPos < txt.length - 1 && txt[currPos + 1] == '}')
                return;
            // we are facing the begining of a template or parser function, stop here
            if (currChar == '{' && currPos < txt.length - 1 && txt[currPos + 1] == '{')
                break;

            // "<" found, check it's the start of a tag, and if so, which type of tag
            if (currChar == '<') {
                // look for the corresponding >
                p = txt.substr(currPos).indexOf('>');
                if (p == -1) continue; // no > is there, just go ahead
                var tag = txt.substr(currPos, p + 1);
                if (tag.match(/^<[^<>]*>$/)) { // we really found a tag
                    if (tag[1] == '/')  // we are at the end of a closing tag
                        return this.clearSelection(); // stop looking any further and quit
                    else if (tag.match(/\s*>$/)) // it's a <tag />, stop here as well
                        break;
                }
            }
            // maybe we are inside a tag and found it's end. Check that
            if (currChar == '>') {
                // look for the corresponding <
                p = selection.lastIndexOf('<');
                if (p == -1) continue;
                var tag = txt.substr(this.start + p, currPos - this.start - p + 1);
                if (tag.match(/^<[^<>]*>$/)) // we really found a tag
                    return this.clearSelection();
            }
        }
        // adjust selection if we moved the end position
        selection = txt.substr(this.start, this.end - this.start);

        // trim the selection
        selection = selection.replace(/^\s*/, '').replace(/\s+$/, '');
        this.selection[0] = selection;

        // now investigate the selected text and fill up the this.selection array

        // check for a property
        var regex = new RegExp('^\\[\\[(.*?)::(.*?)(\\|(.*?))?\\]\\]$');
        var match = regex.exec(selection);
        if (match) {
            this.selection[0] = match[2];
            this.selection[3] = match[2];
            this.selection[1] = 102;
            this.selection[2] = match[1];
            if (match[4])
                this.selection[0] = match[4];
            return;
        }
        // check for a category
        regex = new RegExp('^\\[\\[' + window.parent.gLanguage.getMessage('CATEGORY') + '(.*?)(\\|(.*?))?\\]\\]$');
        var match = regex.exec(selection);
        if (match) {
            this.selection[1] = 14;
            this.selection[0] = match[1];
            return;
        }
        // link
        regex = new RegExp('^\\[\\[:?(.*?)(\\|(.*?))?\\]\\]$');
        var match = regex.exec(selection);
        if (match) {
            this.selection[0] = match[1];
            return;
        }
        // check if there are no <tags> in the selection
        if (selection.match(/.*?(<\/?[\d\w:_-]+(\s+[\d\w:_-]+="[^<>"]*")*\s*(\/\s*)?>)+.*?/))
            return this.clearSelection();
        // if there are still [[ ]] inside the selection then more that a 
        // link was selected making this selection invalid.
        if (selection.indexOf('[[') != -1 || selection.indexOf(']]') != -1 )
            return this.clearSelection();

        // finished, assuming the selection is good without any further modifying.
        return;
    },

    /**
     * Retrieve the selected text from the textarea when in wikitext mode.
     * If nothing is selected an empty string will be returned. Return value is
     * the selected text within the text area. If selection is not empty, then
     * this.start and this.end are not -1.
     *
     * @access private
     * @return string selection
     */
    getSelectionFromTextarea: function() {

        var myArea = FCK.EditingArea.Textarea;
        var selection = '';

        if ( FCKBrowserInfo.IsIE ) {
            if (window.getselection)
                selection = window.getselection(); 
            else if (document.getselection)
                selection = document.getselection(); 
            else if (document.selection)
                selection = document.selection.createRange().text; 
            this.start = myArea.value.indexOf(selection);
            if (this.start != 0)
                this.end = this.start + selection.length;
            else this.start = -1;
        } 
        else { // all other browsers
            if (myArea.selectionStart != undefined) {
                this.start = myArea.selectionStart;
                this.end = myArea.selectionEnd;
                selection = myArea.value.substr(this.start, this.end - this.start);
            }
        }
        return selection;
    },

    /**
     * Make a previously selected text invalid, remove all markers in the
     * variables
     *
     * @access private
     */
    clearSelection: function() {
        this.start = -1;
        this.end = -1;
        this.selection = Array();
    },
    
    // not needed but exists for compatiblity reasons
    setSelectionRange: function(start, end) {},
    // not needed but exists for compatiblity reasons
    getTextBeforeCursor: function() {},
    // not needed but exists for compatiblity reasons. The handling of selecting
    // the complete annotation is done in the getSelectionAsArray()
    selectCompleteAnnotation: function() {}

};


var gEditInterface = new FCKeditInterface();
    window.parent.gEditInterface = gEditInterface;

var tbButton = new FCKToolbarButton( 'SMW_Annotate', 'Semantic Toolbar', 'Semantic Toolbar', null, true) ;
tbButton.IconPath = FCKConfig.PluginsPath + 'mediawiki/images/tb_icon_add.png' ;
FCKToolbarItems.RegisterItem( 'SMW_Annotate', tbButton );

FCKCommands.RegisterCommand( 'SMW_Annotate', fckSemanticToolbar ) ;