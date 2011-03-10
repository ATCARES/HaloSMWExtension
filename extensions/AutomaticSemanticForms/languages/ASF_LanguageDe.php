<?php 


global $asfIP;
include_once($asfIP . '/languages/ASF_Language.php');

class ASFLanguageDe extends ASFLanguage {
	
	protected $asfUserMessages = array(
		'asf_free_text' => "Freitext Eingabefeld:",
		'asf_dummy_article_edit_comment' => "Erzeugt von der Automatic Semantic Forms Extension",
		'asf_dummy_article_content' => "'''Dieser Artikel wird von der Automatic Semantic Forms Extension ben&ouml;t. Bitte l&ouml;schen, editieren oder verschieben Sie ihn daher nicht.'''",
		'asf_category_section_label' => "Eingabe von $1 Daten:",
		'asf_duplicate_property_placeholder' => "Bitte einen Wert im Eingabefeld oben eingeben.",
		'asf_unresolved_annotations' => "Bearbeiten von weiteren Annotationen:",
	
		'asf_tt_intro' => "Klicken Sie, um $1 zu öffnen.",
		'asf_tt_type' => "Der <b>Type</b> dieses Properties is $1.",
		'asf_tt_autocomplete' => "Dieses Eingabefeld <b>autovervollst&auml;ndigt</b> auf $1.",
		'asf_tt_delimiter' => "Mehrere Werte sind in diesem Eingabefeld m&ouml;glich. \"$1\" wird als <b>Trennzeichen</b> verwendet.",
	
		'asfspecial' => "Automatic Semantic Forms",
	
	'asf_autogenerated_msg' => "\n\n\n'''Dieses Formular wurde automatisch generiert.'''"
	);

}


