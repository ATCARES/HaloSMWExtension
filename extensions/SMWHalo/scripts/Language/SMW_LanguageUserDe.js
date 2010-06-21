/*  Copyright 2007, ontoprise GmbH
*  This file is part of the halo-Extension.
*
*   The halo-Extension is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; either version 3 of the License, or
*   (at your option) any later version.
*
*   The halo-Extension is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
*  @file
* 
*  @ingroup SMWHaloLanguage
*/
var wgUserLanguageStrings = {
	'MUST_NOT_BE_EMPTY'       : '(e)Dieses Eingabefeld darf nicht leer sein.',
	'VALUE_IMPROVES_QUALITY'  : '(i)Ein Wert in diesem Eingabefeld verbessert die Qualität der Wissensbasis.',
	'SELECTION_MUST_NOT_BE_EMPTY' : '(e)Die Auswahl darf nicht leer sein!',
	'INVALID_FORMAT_OF_VALUE' : '(e)Der Wert hat ein ungültiges Format.',
	'INVALID_VALUES'          : 'Ungültige Werte.',
	'NAME'                    : 'Name:',
	'ENTER_NAME'              : 'Bitte Name eingeben.',
	'ADD'                     : 'Hinzufügen',
	'CANCEL'                  : 'Abbrechen',
	'CREATE'                  : 'Erzeugen',
	'EDIT'                    : 'Editieren',
	'ANNOTATE'                : 'Annotieren',
	'SUB_SUPER'               : 'Sub/Super',
	'MHAS_PART'               : 'Hat Teil',
	'INVALID_NAME'            : 'Ungültiger Name.',
	'CHANGE'                  : 'ändern',
	'DELETE'                  : 'Löschen',
	'INPUT_BOX_EMPTY'         : 'Fehler! Das Eingabefeld ist leer.',
	'ERR_QUERY_EXISTS_ARTICLE' : 'Fehler bei der Existenzabfrage des Artikels <$-page>.',
	'CREATE_PROP_FOR_CAT'     : 'Dieses Attribut wurde für die Kategorie <$cat> erzeugt. Bitte geben Sie sinnvollen Inhalt ein.',
	'NOT_A_CATEGORY'          : 'Der aktuelle Artikel ist keine Kategorie.',
	'CREATE_CATEGORY'         : 'Diese Kategorie wurde erzeugt aber nicht editiert. Bitte geben Sie sinnvollen Inhalt ein.',
	'CREATE_SUPER_CATEGORY'   : 'Diese Kategorie wurde als Superkategorie erzeugt aber nicht editiert. Bitte geben Sie sinnvollen Inhalt ein.',
	'CREATE_SUB_CATEGORY'     : 'Diese Kategorie wurde als Subkategorie erzeugt aber nicht editiert. Bitte geben Sie sinnvollen Inhalt ein.',
	'NOT_A_PROPERTY'          : 'Der aktuelle Artikel ist kein Attribut.',
	'CREATE_PROPERTY'         : 'Dieses Attribut wurde erzeugt aber nicht editiert. Bitte geben Sie sinnvollen Inhalt ein.',
	'CREATE_SUB_PROPERTY'     : 'Dieser Artikel wurde als Sub-Attribut erzeugt aber nicht editiert. Bitte geben Sie sinnvollen Inhalt ein.',
	'CREATE_SUPER_PROPERTY'   : 'Dieser Artikel wurde als Super-Attribut erzeugt aber nicht editiert. Bitte geben Sie sinnvollen Inhalt ein.',
	'ERROR_CREATING_ARTICLE'  : "Fehler beim Erzeugen des Artikels.",
	'ERROR_EDITING_ARTICLE'   : "Fehler beim Editieren des Artikels.",
	'UNMATCHED_BRACKETS'      : 'Warnung! Dieser Artikel ist syntaktisch nicht korrekt ("]]" fehlen)',
	'MAX_CARD_MUST_NOT_BE_0'  : "(e)Max. Kardinalität darf nicht 0 oder kleiner sein.",
	'SPECIFY_CARDINALITY'     : "(e)Bitte geben Sie eine Kardinalität ein!",
	'MIN_CARD_INVALID'        : "(e)Min. Kardinalität darf nicht kleiner als die max. Kardinalität sein!",
	'ASSUME_CARDINALITY_0'    : "(i) Die min. Kardinalität wird als 0 angenommen.",
	'ASSUME_CARDINALITY_INF'  : "(i) Max. Kardinalität wird als ∞ angenommen.",

	// Namespaces
	'NS_SPECIAL' 			  : 'Special',

	// Relation toolbar
	'ANNOTATE_PROPERTY'       : 'Annotatieren Sie ein Attribut.',
	'PAGE'                    : 'Seite:',
	'ANNO_PAGE_VALUE'         : 'Annotierte Seite/Wert',
	'SHOW'                    : 'Zeige:',
	'DEFINE_SUB_SUPER_PROPERTY' : 'Definieren Sie ein Sub-/Super-Attribut.',
	'CREATE_NEW_PROPERTY'     : 'Erzeugen Sie ein neues Attribut.',
	'ENTER_DOMAIN'            : 'Geben Sie einen Domain ein..',
	'ENTER_RANGE'             : 'Geben Sie eine Range ein.',
	'ENTER_TYPE'              : 'Wählen Sie einen Typ.',
	'PROP_HAS_PART'           : 'Attribut:has part', // name of the has-part property
	'HAS_PART'                : 'has part',
	'PROP_HBSU'               : 'Attribut:has basic structural unit', // name of the property
	'HBSU'                    : 'has basic structural unit',
	'DEFINE_PART_OF'          : 'Definieren Sie eine Teil-von Relation.',
	'OBJECT'                  : 'Objekt:',
	'RENAME_ALL_IN_ARTICLE'   : 'Alle im Artikel umbenennen.',
	'CHANGE_PROPERTY'         : 'Ändern Sie ein Attribut.',
	'PROPERTIES'              : 'Attribute',
	'NO_OBJECT_FOR_POR'       : 'Kein Objekt für die Teil-von Relation gegeben.',
	'RETRIEVE_SCHEMA_DATA'    : 'Die Schema-Daten konnten nicht ermittelt werden!',

	// Property characteristics toolbar
	'PROPERTY_DOES_NOT_EXIST' : '(w)Dieses Attribut existiert nicht.',
	'PROPERTY_ALREADY_EXISTS' : '(w)Dieses Attribut existiert bereits.',
	'PROPERTY_NAME_TOO_LONG'  : '(e)Der Name des Attributs ist zu lang oder enthält ungültige Zeichen.',
	'PROPERTY_VALUE_TOO_LONG' : '(w)Dieser Wert ist sehr lang. Er kann nur in Attributs mit dem Typ "Typ:Text" gespeichert werden.',
	'PROPERTY_ACCESS_DENIED'  : '(e)Sie sind nicht berechtigt, dieses Attribut zu annotieren.',
	'PROPERTY_ACCESS_DENIED_TT'  : 'Sie sind nicht berechtigt, dieses Attribut zu annotieren.',
	'CANT_SAVE_FORBIDDEN_PROPERTIES': 'Der Artikel enthält schreibgeschützte Attribute und kann nicht gespeichert werden.',
	'CREATE_SUPER_PROPERTY'   : 'Erzeuge "$-title" und mache "$t" Super-Attribut von "$-title"',
	'CREATE_SUB_PROPERTY'     : 'Erzeuge "$-title" und mache "$t" Sub-Attribut von "$-title"',
	'MAKE_SUPER_PROPERTY'     : 'Mache "$t" Super-Attribut von "$-title"',
	'MAKE_SUB_PROPERTY'       : 'Mache "$t" Sub-Attribut von "$-title"',
	'ADD_TYPE'                : 'Typ hinzufügen',
	'ADD_RANGE'               : 'Range hinzufügen',
	'DOMAIN'                  : 'Domain:',
	'RANGE'                   : 'Range:',
	'INVERSE_OF'              : 'Inverse von:',
	'MIN_CARD'                : 'Min. Kardinalität:',
	'MAX_CARD'                : 'Max. Kardinalität:',
	'TRANSITIVE'              : 'Transitiv',
	'SYMMETRIC'               : 'Symmetrisch',
	'RETRIEVING_DATATYPES'    : 'Ermittele Datentypen...',
	'NARY_ADD_TYPES'		  : '(e) Bitte fügen Sie Typen oder Ranges hinzu.',
	
	'PROPERTY_PROPERTIES'     : "Attribut Characteristik",
	
	'PAGE_TYPE'               : "page",		// name of the page data type
	'NARY_TYPE'               : "n-ary",       // name of the n-ary data type
	'SPECIFY_PROPERTY'		  : "Spezifizieren Sie dieses Attribut.",
	'PC_DUPLICATE'			  : "Mindestens ein Attribut wird mehrfach spezifiziert. Entfernen Sie bitte die Duplikate.",
	'PC_HAS_TYPE'			  : "Hat Datentyp", 
	'PC_HAS_FIELDS'			  : "Hat Komponenten",
	'PC_MAX_CARD'			  : "hat max Kardinalität",
	'PC_MIN_CARD'			  : "hat min Kardinalität",
	'PC_INVERSE_OF'			  : "ist invers zu", 
	'PC_INVERSE'			  : "inverse", 
	'PC_TRANSITIVE'			  : "transitive", 
	'PC_SYMMETRICAL'		  : "symmetrische", 
	'PC_AND'			 	  : "und ", 
	'PC_UNSUPPORTED'		  : "Dieses Wiki unterstützt keine $1 Attribute.",

	// Category toolbar
	'ANNOTATE_CATEGORY'       : 'Annotieren Sie eine Kategorie.',
	'CATEGORY_DOES_NOT_EXIST' : '(w)Diese Kategorie existiert nicht.',
	'CATEGORY_ALREADY_EXISTS' : '(w)Diese Kategorie existiert bereits.',
	'CATEGORY_NAME_TOO_LONG'  : '(e)Der Name dieser Kategorie ist zu lang oder enthält ungültige Zeichen.',
	'CREATE_SUPER_CATEGORY'   : 'Erzeuge "$-title" und mache "$t" Super-Kategorie von "$-title"',
	'CREATE_SUB_CATEGORY'     : 'Erzeuge "$-title" und mache "$t" Sub-Kategorie von "$-title"',
	'MAKE_SUPER_CATEGORY'     : 'Mache "$t" Super-Kategorie von "$-title"',
	'MAKE_SUB_CATEGORY'       : 'Mache "$t" Super-Kategorie von "$-title"',
	'DEFINE_SUB_SUPER_CAT'    : 'Definieren Sie eine Sub- oder Super-Kategorie.',
	'CREATE_SUB'              : 'Erzeuge Sub',
	'CREATE_SUPER'            : 'Erzeuge Super',
	'CREATE_NEW_CATEGORY'     : 'Erzeugen Sie eine neue Kategorie',
	'CHANGE_ANNO_OF_CAT'      : 'Ändern Sie die Annotation einer Kategorie',
	'CATEGORIES'              : 'Kategorien',
	'ADD_AND_CREATE_CAT'      : 'Hinzufügen und erzeugen',
	'CATEGORY_ALREADY_ANNOTATED': '(w)Diese Kategorie ist bereits annotiert.',

	// Annotation hints
	'ANNOTATION_HINTS'        : 'Annotationshinweise',
	'ANNOTATION_ERRORS'       : 'Annotationsfehler',
	'AH_NO_HINTS'			  : '(i)Keine Hinweise für diesen Artikel.',
	'AH_SAVE_COMMENT'		  : 'Annotationen wurden im Advanced Annotation Mode hinzugefügt.',
	'AAM_SAVE_ANNOTATIONS' 	  : 'Möchten Sie die Annotationen der aktuellen Sitzung speichern?',
	'CAN_NOT_ANNOTATE_SELECTION' : 'Sie können die Auswahl nicht annotieren. Sie enthält bereits Annotationen oder Abschnitte oder endet in einem Link.',
	'AAM_DELETE_ANNOTATIONS'  : 'Möchten Sie diese Annotation wirklich löschen?',
	
	// Save annotations
	'SA_SAVE_ANNOTATION_HINTS': "Vergessen Sie nicht, Ihre Arbeit zu speichern!",
	'SA_SAVE_ANNOTATIONS'	  : 'Speichere Annotationen',
	'SA_SAVE_ANNOTATIONS_AND_EXIT' : 'Speichern & verlassen',
	'SA_ANNOTATIONS_SAVED'	  : '(i) Die Annotationen wurden gespeichert.',
	'SA_SAVING_ANNOTATIONS_FAILED' : '(e) Ein Fehler trat beim Speichern der Annotationen auf.',
	'SA_SAVING_ANNOTATIONS'   : '(i) Speichere Annotationen...',

	// Autocompletion
	'AUTOCOMPLETION_HINT'     : 'Drücken Sie Ctrl+Alt+Space um die Auto-completion zu benutzen. (Ctrl+Space im IE)',
	'WW_AUTOCOMPLETION_HINT'  : '- wird nur im Wikitext Modus unterstützt.',
	'AC_CLICK_TO_DRAG'        : 'Auto-Completion - Hier zum Verschieben klicken',
    'AC_MORE_RESULTS_AVAILABLE' : 'Zuviele Treffer gefunden...',
    'AC_MORE_RESULTS_TOOLTIP' : 'Zuviele Treffer. Bitte erweitern Sie die Suchanfrage.',

	// Combined search
	'ADD_COMB_SEARCH_RES'     : 'Zusätzliche Ergebnisse der Combined-Search.',
	'COMBINED_SEARCH'         : 'Combined-Search',

	'INVALID_GARDENING_ACCESS' : 'Sie dürfen Gardening Bots nicht abbrechen. Das dürfen nur Sysops und Gardener.',
	'GARDENING_LOG_COLLAPSE_ALL' : 'Alles einklappen',
	'GARDENING_LOG_EXPAND_ALL'   : 'Alles ausklappen',
	'BOT_WAS_STARTED'			: 'Der Bot wurde gestartet.',
	
	// Ontology browser
	'OB_ID'					  : 'OntologyBrowser',
	'ONTOLOGY_BROWSER'        : 'Ontology Browser',
	
	'KS_NOT_SUPPORTED'        : 'Konqueror wird momentan nicht unterstützt.',
	'SHOW_INSTANCES'          : 'Zeige Instanzen',
	'HIDE_INSTANCES'          : 'Verstecke Instanzen',
	'ENTER_MORE_LETTERS'      : "Bitte geben Sie mindestens zwei Buchstaben ein. Sonst erhalten Sie wahrscheinlich zu viele Ergebnisse.",
	'MARK_A_WORD'             : 'Selektieren Sie etwas...',
	'OPEN_IN_OB'              : 'Im Ontology Browser öffnen',
	'OPEN_IN_OB_NEW_TAB'      : '... neuer Tab',
	'OB_CREATE'	  			  : 'Erzeugen',
	'OB_RENAME'	  			  : 'Umbenennen',
	'OB_DELETE'	  			  : 'Löschen',
	'OB_PREVIEW' 			  : 'Preview',
	'OB_TITLE_EXISTS'		  : 'Seite existiert bereits',
	'OB_ENTER_TITLE'		  : 'Seitennamen eingeben',
	'OB_SELECT_CATEGORY'	  : 'Erst Kategorie auswählen',
	'OB_SELECT_PROPERTY'	  : 'Erst Property auswählen',
	'OB_SELECT_INSTANCE'	  : 'Erst Instanz auswählen',
	'OB_WRONG_MAXCARD'		  : 'Falsche Max-Kardinalität',
	'OB_WRONG_MINCARD'		  : 'Falsche Min-Kardinalität',
	'OB_CONFIRM_INSTANCE_DELETION' : 'Wollen Sie den Artikel wirklich löschen?',
	'SMW_OB_OPEN' 			  : '(öffne)',
	'SMW_OB_EDIT' 		  	  : '(editiere)',
	'SMW_OB_ADDSOME'		  : '(Füge hinzu)',
	'OB_CONTAINS_FURTHER_PROBLEMS' : 'Contains further problems',
	'SMW_OB_MODIFIED'		  : 'Artikel wurde gespeichert. Das Problem wurde möglicherweise bereits behoben.',

	// Find work
	'FW_SEND_ANNOTATIONS'	  : 'Danke für das Bewerten der Annotationen, ',
	'FW_MY_FRIEND'	  		  : 'mein Freund!',
	
	// Query Interface
	'QUERY_INTERFACE'         : 'Query Interface',
	'QI_MAIN_QUERY_NAME'	  : 'Hauptquery',
	'QI_ARTICLE_TITLE'        : 'Artikel',
	'QI_EMPTY_QUERY'       	  : 'Ihr Query ist leer.',
	'QI_INSTANCE'       	  : 'Instanz:',
	'QI_PROPERTYNAME'         : 'Attributname:',
    'QI_PROPERTYVALUE'        : 'Attributwert:',
	'QI_SHOW_PROPERTY'        : 'In Ergebnissen zeigen',
	'QI_PROPERTY_MUST_BE_SET' : 'Wert muss gesetzt sein',
	'QI_USE_SUBQUERY'         : 'Subquery einfügen',
	'QI_PAGE'				  : 'Page', // has to be the same as the Type:Page in your language
	'QI_OR'        			  : 'oder',
	'QI_ENTER_CATEGORY'       : 'Bitte geben Sie eine Kategorie ein',
	'QI_ENTER_INSTANCE'       : 'Bitte geben Sie eine Instanz ein',
	'QI_ENTER_PROPERTY_NAME'  : 'Bitte geben Sie einen Attributnamen ein',
	'QI_CLIPBOARD_SUCCESS'    : 'Der Query wurde in Ihre Zwischenablage kopiert',
	'QI_CLIPBOARD_FAIL'    	  : 'Ihr Browser erlaubt keinen Zugriff auf die Zwischenablage\nDer Query konnte nicht in Ihre Zwischenablage kopiert werden.\n Bitte verwenden Sie die Funktion "Kompletten Query anzeigen" und kopieren Sie den Query manuell.',
	'QI_SUBQUERY'    	  	  : "Subquery",
	'QI_CATEGORIES'    	  	  : " Kategorien:",
	'QI_INSTANCES'    	  	  : " Instanzen:",
	'QI_QUERY_EXISTS'		  : "Ein Query mit diesem Namen existiert bereits. Bitte wählen sie einen neuen Namen.",
	'QI_QUERY_SAVED'		  : "Ihr Query wurde erfolgreich gespeichert",
	'QI_SAVE_ERROR'		  	  : "Ein unbekannter Fehler ist aufgetreten. Ihr Query konnte nicht gespeichert werden.",
	'QI_EMPTY_TEMPLATE'		  : "Um das Ausgabeformat 'template' benutzen zu können, müssen Sie einen Templatenamen angeben.",
	'QI_SPECIAL_QP_PARAMS'    : 'Spezielle Parameter für',
    'QI_START_CREATING_QUERY' : 'Klicke auf<ul><li>Kategorie hinzuf&uuml;gen</li><li>Attribut hinzuf&uuml;gen</li><li>Instanz hinzuf&uuml;gen</li></ul>um eine neue Query zu bauen.',
    'QI_BC_ADD_CATEGORY'      : 'Kategorie hinzuf&uuml;gen',
    'QI_BC_ADD_PROPERTY'      : 'Attribut hinzuf&uuml;gen',
    'QI_BC_ADD_INSTANCE'      : 'Instanz hinzuf&uuml;gen',
    'QI_BC_ADD_OTHER_CATEGORY': 'weitere Kategorie hinzuf&uuml;gen (ODER)',
    'QI_BC_ADD_OTHER_INSTANCE': 'weitere Instanz hinzuf&uuml;gen (ODER)',
    'QI_DC_ADD_OTHER_RESTRICT': 'weitere Werteinschr&auml;nkung hinzuf&uuml;gen (ODER)',
    'QI_CAT_ADDED_SUCCESSFUL' : 'Kategorie erfolgreich zum Query hinzugef&uuml;gt',
    'QI_PROP_ADDED_SUCCESSFUL': 'Attribut erfolgreich zum Query hinzugef&uuml;gt',
    'QI_INST_ADDED_SUCCESSFUL': 'Instanz erfolgreich zum Query hinzugef&uuml;gt',
    'QI_ADD_PROPERTY_CHAIN'   : 'neues Attribut zur Attributkette hinzuf&uuml;gen',
    'QI_PROP_VALUES_RESTRICT' : 'Einschr&auml;nken der Attributwerte',
    'QI_SPECIFIC_VALUE'       : 'bestimmter Wert',
    'QI_NONE'                 : 'keine',
    'QI_PROPERTY_TYPE'        : 'Typ',
    'QI_PROPERTY_RANGE'       : 'Range',
    'QI_RECORD'               : 'Record',
    'QI_COLUMN_LABEL'         : 'Spalten&uuml;berschrift',
    'QI_SHOWUNIT'             : 'Einheit',
    'QI_EQUAL'                : 'gleich',
    'QI_LT'                   : 'kleiner',
    'QI_GT'                   : 'gr&ouml;&szlig;er',
    'QI_NOT'                  : 'nicht',
    'QI_LIKE'                 : '&auml;hnlich',
	
	// Wiki text parser
	'WTP_TEXT_NOT_FOUND'		  : "Konnte '$1' nicht im Wikitext finden.",
	'WTP_NOT_IN_NOWIKI'			  : "'$1' ist Teil eines &lt;nowiki&gt;-Abschnitts.\nEr kann nicht annotiert werden.",
	'WTP_NOT_IN_TEMPLATE'		  : "'$1' ist Teil einer Vorlage.\nEr kann nicht annotiert werden.",
	'WTP_NOT_IN_ANNOTATION'		  : "'$1' ist Teil einer Annotation.\nEr kann nicht annotiert werden.",
	'WTP_NOT_IN_QUERY'            : "'$1' ist Teil einer Query.\nEr kann nicht annotiert werden.",
        'WTP_NOT_IN_TAG'                  : "'$1' ist innerhalb eines Tags $2.\nEr kann nicht annotiert werden.",
	'WTP_NOT_IN_PREFORMATTED'	  : "'$1' ist Teil eines vorformatierten Textes.\nEr kann nicht annotiert werden.",
	'WTP_SELECTION_OVER_FORMATS'  : "Die Auswahl erstreckt sich über verschiedene Formate:\n$1",
	
	// ACL extension
	'smw_acl_*' : '*',
	'smw_acl_read' : 'lesen',
	'smw_acl_edit' : 'editieren',
	'smw_acl_create' : 'erzeugen',
	'smw_acl_move' : 'umbenennen',
	'smw_acl_permit' : 'erlauben',
	'smw_acl_deny' : 'verbieten',
	'smw_acl_create_denied' : 'Sie sind nicht berechtigt, den Artikel "$1" zu erzeugen.',
	'smw_acl_edit_denied'   : 'Sie sind nicht berechtigt, den Artikel "$1" zu bearbeiten.',
	'smw_acl_delete_denied' : 'Sie sind nicht berechtigt, den Artikel "$1" zu löschen.',

	
	
	// Treeview
    'smw_stv_browse' : 'browsen',
    
	// former content
	'PROPERTY_NS_WOC'         : 'Attribut', // Property namespace without colon
	'RELATION_NS_WOC'         : 'Relation', // Relation namespace without colon
	'CATEGORY_NS_WOC'         : 'Kategorie', // Category namespace without colon
	
	'CATEGORY'                : "Kategorie:",
	'PROPERTY'                : "Attribut:",
	'TEMPLATE'                : "Vorlage:",
	'TYPE'                    : 'Typ:',

	
	
	'smw_wwsu_addwscall'			:	'Web Service Aufruf hinzufügen',
	'smw_wwsu_headline'			:	'Web Service',
	'Help'			:	'Hilfe',
	
	// Derived facts
	'DF_REQUEST_FAILED' : 'Fehler! Die abgeleiteten Fakten konnten nicht ermittelt werden.',

	// Semantic Toolbar General
	'STB_LINKS'		: 'Links zu anderen Seiten',
	'STB_TOOLS'		: 'Tools', 
	'STB_FACTS'		: 'Fakten zu diesem Artikel',
	'STB_ANNOTATION_HELP' 	: 'Annotationen & Hilfe' 
	
};
