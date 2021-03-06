<?php

global $smwgSimileSite;
$smwgSimileSite = "http://api.simile-widgets.org";

class SMWSimileTimeplotResultPrinter extends SMWResultPrinter {
	protected $mTime = '';
	protected $mEnd = '';
	protected $mValue = '';
	
	protected $m_isAjax = false;
	
	public static function registerResourceModules() {
		global $wgResourceModules, $srfpgScriptPath;
		
		$moduleTemplate = array(
			'localBasePath' => dirname( __FILE__ ),
			'remoteBasePath' => $srfpgScriptPath . '/Simile',
			'group' => 'ext.srf'
		);
		
		$wgResourceModules['ext.srf.timeplot'] = $moduleTemplate + array(
			'scripts' => array( '/scripts/Simile_TimeplotWiki.js' ),
			'dependencies' => array( 'jquery' )
		);
	}
	
    protected function includeJS() {
		global $smwgSimileSite;
		$header_js = '<script src="' . $smwgSimileSite . '/timeplot/1.1/timeplot-api.js"></script>';
		SMWOutputs::requireHeadItem("simile_timeplot", $header_js);

		// MediaWiki 1.17 introduces the Resource Loader.
		$realFunction = array( 'SMWOutputs', 'requireResource' );
		if ( defined( 'MW_SUPPORTS_RESOURCE_MODULES' ) && is_callable( $realFunction ) ) {
			global $wgOut;
			$wgOut->addModules( 'ext.srf.timeplot' );
		}
		else {
			global $srfpgScriptPath;
			SMWOutputs::requireHeadItem("jquery", '<script type="text/javascript" src="'. $srfpgScriptPath . '/Simile/scripts/jquery-1.3.2.min.js"></script>');
			SMWOutputs::requireHeadItem("simile_timeplotwiki", '<script src="'. $srfpgScriptPath . '/Simile/scripts/Simile_TimeplotWiki.js"></script>');
		}
	}
	
	function getParameters() {
        return array(
			array('name' => 'time', 'type' => 'string', 'description' => "field name of start time"),
			array('name' => 'end', 'type' => 'string', 'description' => "field name of end time"),
			array('name' => 'value', 'type' => 'string', 'description' => "field name of timeplot value"),
		);
    }
	
	protected function readParameters( $params, $outputmode ) {
		parent::readParameters( $params, $outputmode );

		if ( array_key_exists( 'time', $params ) ) {
			$this->mTime = trim( str_replace( '_', ' ', $params['time'] ) );
		}
		if ( array_key_exists( 'end', $params ) ) {
			$this->mEnd = trim( $params['end'] );
		}
		if ( array_key_exists( 'value', $params ) ) {
			$this->mValue = trim( $params['value'] );
		}
		global $wgRequest;
		if ( $wgRequest->getVal( 'action' ) == 'ajax' ) {
			$this->m_isAjax = true;
		}
	}
	
	private function getTime($object) {
		if($object instanceof SMWTimeValue) {
			if(version_compare(SMW_VERSION, '1.6', '>=')) {
				$time = $object->getISO8601Date();
			} else {
				$time = $object->getYear()."-".str_pad($object->getMonth(),2,'0',STR_PAD_LEFT)."-".str_pad($object->getDay(),2,'0',STR_PAD_LEFT)." ".$object->getTimeString();
			}
		} else {
			$time = version_compare(SMW_VERSION, '1.5', '>=') ? $object->getWikiValue() : $object->getXSDValue();
		}
		return $time;
	}
	
	protected function getResultText($res, $outputmode) {
		$this->includeJS();
		
		$header_items = array();
		foreach ( $res->getPrintRequests() as $pr ) {
			$header_items[] = $pr->getLabel();
		}
		
		global $smwgIQRunningNumber;
		$div = "SimileQuery$smwgIQRunningNumber";
		$result = "<div id=\"$div\" style=\"height: 150px;\"></div>";
		
		$data = "";
		
		$firstrow = true;
		$cols = array();
		while ( ($row = $res->getNext()) !== false ) {
			$act_column = 0;
			if(!$firstrow) {
				$data .= ",";
			} else {
				$firstrow = false;
			}
				
			$data .= "{";
			$html = "";
			$values = "";
			foreach ($row as $field) {
				$firstobj = true;
				$li = "";
				$text = "";
				while ( ($object = $field->getNextObject()) !== false ) {
					$text = $object->getShortHTMLText();
					$text = str_replace("\"", "\\\"", $text);

					if($act_column == 0) {
						$url = str_replace("\"", "\\\"", $object->getTitle()->getFullURL());
						$data .= "title: \"$text\", link:\"$url\"";
					} else {
						if(  strtolower( $header_items[$act_column] ) == strtolower( $this->mTime ) ) {
							$data .= ",start:\"{$this->getTime($object)}\"";
						} else if( strtolower( $header_items[$act_column] ) == strtolower( $this->mEnd ) ) {
							$data .= ",end:\"{$this->getTime($object)}\"";
						}
						if(!$firstobj) {
							$li .= ", ";
						} else {
							$firstobj = false;
							if ( '' != $field->getPrintRequest()->getLabel() ) {
								$li .= "<b>" . $field->getPrintRequest()->getLabel() . "</b> : ";
							}
						}
						if ($object->getTypeID() == '_wpg') { // use shorter "LongText" for wikipage
							$text = $object->getLongText(SMW_OUTPUT_HTML,$this->getLinker($act_column == 0));
						} else if ($object instanceof SMWNumberValue) {
							$text = version_compare(SMW_VERSION, '1.5', '>=') ? $object->getWikiValue() : $object->getXSDValue();
						} else {
							$text = $object->getShortText(SMW_OUTPUT_HTML,$this->getLinker($act_column == 0));
						}
						$li .= str_replace("\"", "\\\"", $text);
					}
				}
				if( strtolower( $header_items[$act_column] ) == strtolower( $this->mValue ) ) {
					$values .= ",". ($text == ''? '0':$text);
					$cols[$act_column] = 1;
				}
				if($li) $html .= "<li>$li</li>";

				$act_column ++;
			}
			$data .= ",values:[".substr($values, 1)."],description:\"$html\"}";
		}
		
		$js = '
<script type="text/javascript">
	simileTimeplot.records.push( {
		div: "' . $div . '",
		count: ' . count($cols) . ',
		data: [' . $data . ']
	} );
</script>';
		// MediaWiki 1.17 introduces the Resource Loader.
		$realFunction = array( 'SMWOutputs', 'requireResource' );
		if ( defined( 'MW_SUPPORTS_RESOURCE_MODULES' ) && is_callable( $realFunction ) ) {
			global $wgOut;
			$wgOut->addScript( $js );
		} else {
			SMWOutputs::requireHeadItem("simile$smwgIQRunningNumber", $js);
		}
		
		// Make label for finding further results
		if ( $this->linkFurtherResults($res) && ( ('ol' != $this->mFormat) || ($this->getSearchLabel(SMW_OUTPUT_WIKI)) ) ) {
			$link = $res->getQueryLink();
			if ($this->getSearchLabel(SMW_OUTPUT_WIKI)) {
				$link->setCaption($this->getSearchLabel(SMW_OUTPUT_WIKI));
			}

			$link->setParameter('simile-timeplot','format');
			if ($this->mTemplate != '') {
				$link->setParameter($this->mTemplate,'template');
				if (array_key_exists('link', $this->m_params)) { // linking may interfere with templates
					$link->setParameter($this->m_params['link'],'link');
				}
			}
			$result .= $link->getText(SMW_OUTPUT_WIKI,$this->mLinker);
		}

		return $result;
	}
}



class SMWSimileRunwayResultPrinter extends SMWResultPrinter {
	protected $mImage = '';
	protected $mSubtitle = '';
	
	protected $m_isAjax = false;
	
	public static function registerResourceModules() {
		global $wgResourceModules, $srfpgScriptPath;
		
		$moduleTemplate = array(
			'localBasePath' => dirname( __FILE__ ),
			'remoteBasePath' => $srfpgScriptPath . '/Simile',
			'group' => 'ext.srf'
		);
		
		$wgResourceModules['ext.srf.runway'] = $moduleTemplate + array(
			'scripts' => array( '/scripts/Simile_RunwayWiki.js' ),
			'dependencies' => array( 'jquery' )
		);
	}
	
    protected function includeJS() {
		global $smwgSimileSite;
		$header_js = '<script src="' . $smwgSimileSite . '/runway/1.0/runway-api.js"></script>';
		SMWOutputs::requireHeadItem("simile_runway", $header_js);
    	
		// MediaWiki 1.17 introduces the Resource Loader.
		$realFunction = array( 'SMWOutputs', 'requireResource' );
		if ( defined( 'MW_SUPPORTS_RESOURCE_MODULES' ) && is_callable( $realFunction ) ) {
			global $wgOut;
			$wgOut->addModules( 'ext.srf.runway' );
		}
		else {
			global $srfpgScriptPath;
			SMWOutputs::requireHeadItem("jquery", '<script type="text/javascript" src="'. $srfpgScriptPath . '/Simile/scripts/jquery-1.3.2.min.js"></script>');
			SMWOutputs::requireHeadItem("simile_runwaywiki", '<script src="'. $srfpgScriptPath . '/Simile/scripts/Simile_RunwayWiki.js"></script>');
		}
	}
	
	function getParameters() {
        return array(
			array('name' => 'image', 'type' => 'string', 'description' => "field name of image(s)"),
			array('name' => 'subtitle', 'type' => 'string', 'description' => "field name of subtitle(s)"),
		);
    }
	
	protected function readParameters( $params, $outputmode ) {
		parent::readParameters( $params, $outputmode );

		if ( array_key_exists( 'image', $params ) ) {
			$this->mImage = trim( str_replace( '_', ' ', $params['image'] ) );
		}
		if ( array_key_exists( 'subtitle', $params ) ) {
			$this->mSubtitle = trim( $params['subtitle'] );
		}
		global $wgRequest;
		if ( $wgRequest->getVal( 'action' ) == 'ajax' ) {
			$this->m_isAjax = true;
		}
	}

	protected function getResultText($res, $outputmode) {
		$this->includeJS();

		$header_items = array();
		foreach ( $res->getPrintRequests() as $pr ) {
			$header_items[] = $pr->getLabel();
		}
		
		global $smwgIQRunningNumber;
		$div = "SimileQuery$smwgIQRunningNumber";
		$result = "<div id=\"$div\" style=\"height: 400px;\"></div>";
		$slide = "SimileQuerySlide$smwgIQRunningNumber";
		$result .= "<ul id=\"$slide\"></ul>";

		$data = "";
		$items = 0;
		
		$firstrow = true;

		// Print all result rows:
		while ( ($row = $res->getNext()) !== false ) {
			$act_column = 0;
			if(!$firstrow) {
				$data .= ",";
			} else {
				$firstrow = false;
			}
			$data .= "{";
			$html = "";

			foreach ($row as $field) {
				$firstobj = true;
				$li = "";
				while ( ($object = $field->getNextObject()) !== false ) {
					$text = $object->getShortHTMLText();
					$text = str_replace("\"", "\\\"", $text);
					if($act_column == 0) {
						$data .= "title: \"$text\"";
					} else if( strtolower( $header_items[$act_column] ) == strtolower( $this->mImage ) ) {
						if($object instanceof SMWWikiPageValue) {
							$image = new Image($object->getTitle());
							if ($image->exists()) {
								$data .= ", image: \"".$image->getURL/*getFullUrl*/()."\"";
								$items ++;
							}
						} else {
						}
					} else if( strtolower( $header_items[$act_column] ) == strtolower( $this->mSubtitle ) ) {
						$data .= ", subtitle: \"$text\"";
					}
					if(!$firstobj) {
						$li .= ", ";
					} else {
						$firstobj = false;
						if ( '' != $field->getPrintRequest()->getLabel() ) {
							$li .= "<b>" . $field->getPrintRequest()->getLabel() . "</b> : ";
						}
					}
					if ($object->getTypeID() == '_wpg') { // use shorter "LongText" for wikipage
						$text = $object->getLongText(SMW_OUTPUT_HTML,$this->getLinker($act_column == 0));
					} else {
						$text = $object->getShortText(SMW_OUTPUT_HTML,$this->getLinker($act_column == 0));
					}
					$li .= str_replace("\"", "\\\"", $text);
				}
				if($li) $html .= "<li>$li</li>";

				$act_column ++;
			}
			$data .= ", html:\"$html\"}";
		}

		$js = '
<script type="text/javascript">
	simileRunway.records[' . $smwgIQRunningNumber . '] = {
		div: "' . $div . '",
		onSelect: function(index, id) {
			document.getElementById("' . $slide . '").innerHTML = simileRunway.records[' . $smwgIQRunningNumber .'].data[index].html;
		},
		items: ' . $items . ',
		data: [' . $data . ']
	};
</script>';
		// MediaWiki 1.17 introduces the Resource Loader.
		$realFunction = array( 'SMWOutputs', 'requireResource' );
		if ( defined( 'MW_SUPPORTS_RESOURCE_MODULES' ) && is_callable( $realFunction ) ) {
			global $wgOut;
			$wgOut->addScript( $js );
		} else {
			SMWOutputs::requireHeadItem("simile$smwgIQRunningNumber", $js);
		}
		
		// Make label for finding further results
		if ( $this->linkFurtherResults($res) && ( ('ol' != $this->mFormat) || ($this->getSearchLabel(SMW_OUTPUT_WIKI)) ) ) {
			$link = $res->getQueryLink();
			if ($this->getSearchLabel(SMW_OUTPUT_WIKI)) {
				$link->setCaption($this->getSearchLabel(SMW_OUTPUT_WIKI));
			}

			$link->setParameter('runway', 'format');
			if ($this->mTemplate != '') {
				$link->setParameter($this->mTemplate,'template');
				if (array_key_exists('link', $this->m_params)) { // linking may interfere with templates
					$link->setParameter($this->m_params['link'],'link');
				}
			}
			$result .= $link->getText(SMW_OUTPUT_WIKI,$this->mLinker);
		}

		return $result;
	}
}
