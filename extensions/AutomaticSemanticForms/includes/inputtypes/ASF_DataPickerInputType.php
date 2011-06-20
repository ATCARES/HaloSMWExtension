<?php

global $wgAjaxExportList;
$wgAjaxExportList[] = 'dapi_refreshData';

global $wgHooks;
$wgHooks['ParserFirstCallInit'][] = 'ASFDataPickerParserFunction::registerFunctions';
$wgHooks['LanguageGetMagic'][] = 'ASFDataPickerParserFunction::languageGetMagic';

function dapi_refreshData($wsParam, $dapiId, $selectedIds, $containerId){
	
	global $smwgDIIP;
	require_once($smwgDIIP.'/specials/WebServices/SMW_WebServiceUsage.php');
	
	global $dapi_instantiations;
	
	$wsCallParameters = array();
	$wsCallParameters[] = "dummy";
	$wsCallParameters[] = $dapi_instantiations[$dapiId]['ws-name'];
	$wsCallParameters[] = $dapi_instantiations[$dapiId]['param'].'='.$wsParam;
	$wsCallParameters[] = "?result.".$dapi_instantiations[$dapiId]['id'];
	$wsCallParameters[] = "?result.".$dapi_instantiations[$dapiId]['label'];
	
	$parser = null;
	$wsresult = SMWWebServiceUsage::processCall($parser, $wsCallParameters, true, false, true);
	
	$resultItems = array();
	if(is_array($wsresult)){
		
		$selectedIds = json_decode($selectedIds, true);
		if(!is_array($selectedIds)){
			$selectedIds = json_decode($selectedIds, true);
		}
		
		for($i = 0; $i < count($wsresult[$dapi_instantiations[$dapiId]['id']]); $i++){
			
			if(in_array($wsresult[$dapi_instantiations[$dapiId]['id']][$i], $selectedIds)){
				$selected = 'true';
			} else {
				$selected = 'false';
			}
			
			$resultItems[] = array('id' => $wsresult[$dapi_instantiations[$dapiId]['id']][$i], 
				'label' 	=> $wsresult[$dapi_instantiations[$dapiId]['label']][$i], 'selected' => $selected); 		
		}
	} else {
		//dd sme error processing
	}
	
	$result = array('results' => $resultItems, 'containerId' => $containerId);
	$result = json_encode($result);

	return '--##starttf##--' . $result . '--##endtf##--';
}

class ASFDataPickerInputType {
	
	public static function getHTML($currentValue, $inputName, $isMandatory, $isDisabled, $otherArgs){
		
		$dataPickerId = '';
		if(array_key_exists('datapicker id', $otherArgs)){
			$dataPickerId = $otherArgs['datapicker id'];
		}
		
		global $dapi_instantiations;
		if(!array_key_exists($dataPickerId, $dapi_instantiations)){
			return self::getErrorMessageHTML($dataPickerId);
		}
		
		$className = $isMandatory ? "mandatoryField" : "createboxInput";
		if(array_key_exists('class', $otherArgs)){
			$className .= " " . $otherArgs['class'];
		}
		
		global $sfgFieldNum;
		$inputFieldId = "input_$sfgFieldNum";
		
		if ( array_key_exists( 'delimiter', $otherArgs)){
			$delimiter = $otherArgs['delimiter'];
		} else {
			//default is comma
			$delimiter = ",";
		}
		
		$json = substr($currentValue, 
			strpos($currentValue, '{{#DataPickerValues:') + strlen('{{#DataPickerValues:'));
		$json = substr($json, 0,  
			strrpos($json, '}}'));
		$json = str_replace(
			array('##dlcb##', '##drcb##', '##pipe##', '##dlsb##', '##drsb##'), array("{{", "}}", "|", "[[", "]]"), $json);
		$json = json_decode($json, true);
		if(!is_array($json)){
			$json = json_decode($json, true);
		}
		
		$values = array();
		if(is_array($json)){
			$values = $json[0];
			
			if(!$dapi_instantiations[$dataPickerId]['remember options']){
				foreach($values as $key => $value){
					if($value['selected'] != 'true'){
						unset($values[$key]);
					}
				}
			}
		}
		
		$wsParam = '';
		if(is_array($json)){
			$wsParam = $json[2];
		}
		
		$size = null;
		if(array_key_exists('size', $otherArgs ) ) {
			$size = $otherArgs['size'];
		}
		
		global $asfHeaders;
		$asfHeaders['datapicker.js'] = true;
		
		$html = '<span class="dapi-form-field-container" id="todo" 
			onmouseover="dapi_showRefreshdControls(event)">';
		
		$html .= self::getRefreshControlsHTML($wsParam);
		
		$html .= self::getChooseValueControlsHTML(
			$values, $inputFieldId, $inputName, $className, $size, $isDisabled, $isMandatory);
		
		$html .= self::getHiddenDataContainerHTML($delimiter, $dataPickerId);
		
		$html .= '</span>';
		
		return $html;
	}
	
	private static function getRefreshControlsHTML($wsParam){
		$html = '<span class="dapi-refresh-controls" style="display: none">';
		
		$html .= '<input value="'.$wsParam.'"/>';
		
		//todo: use lanfuage file
		$attributes = array('type' => 'button'
			,'onclick' => 'dapi_doRefresh(event)'
			,'value' => 'Ok');
		$html .= Xml::tags('input', $attributes, '');
		
		$html .= '<br/>';
		
		$html .= '</span>'; 
		
		return $html;
	}
	
	
	private static function getChooseValueControlsHTML(
			$currentValues, $inputFieldId, $inputName, $className, $size, $isDisabled, $isMandatory){
		
		$html = '<span class="dapi-choose-value-controls">';
		
		$optionsText = "";
		foreach($currentValues as $value){
			$attributes = array('value' => $value['value']);
			if($value['selected'] == 'true'){
				$attributes['selected'] = 'selected';
			}
			$optionsText .= Xml::element('option', $attributes, $value['label']);
		}
		
		global $sfgTabIndex;
		$attributes = array(
			'id' => $inputFieldId,
			'tabindex' => $sfgTabIndex,
			'name' => $inputName . '[]',
			'class' => $className,
			'multiple' => 'multiple',
			'width' => '50%',
			'style' => 'max-width: 80%'
		);
		if(!is_null($size)){
			$attributes['size'] = $size;
		}
		if ($isDisabled ) {
			$attributes['disabled'] = 'disabled';
		}
		$html .= Xml::tags( 'select', $attributes, $optionsText );
		$html .= "\t" . Xml::hidden( $inputName . '[is_list]', 1 ) . "\n";
		if ( $isMandatory ) {
			$html = Xml::tags( 'span', array( 'class' => 'inputSpan mandatoryFieldSpan' ), $text );
		}
		
		//todo: use lanfuage file
		$attributes = array('type' => 'button'
			,'onclick' => 'dapi_showRefreshdControls(event)'
			,'value' => 'Refresh');
		//$html .= Xml::tags('input', $attributes, '');		
		
		$html .= '</span>';
		
		return $html;
	}
	
	
	
	private static function getHiddenDataContainerHTML($delimiter, $dataPickerId){
		$html = '<span class="dapi-hidden-data" style="display: none">';	

		$html .= '<span class="dapi-delimiter">'.$delimiter.'</span>';
		
		$html .= '<span class="dapi-dpid">'.$dataPickerId.'</span>';
		
		$html .= '</span>';
		
		return $html;		
	}
	
	
	
	private static function getErrorMessageHTML($dataPickerId){
		$html = '<span class="dapi-error">';	

		//todo; use language file
		$html .= 'A datapicker with the id '.$dataPickerId.' has not been defined.';
		
		$html .= '</span>';
		
		return $html;		
	}

}


class ASFDataPickerParserFunction {
	
	static function registerFunctions( &$parser ) {
		$parser->setFunctionHook( 'DataPickerValues', 
			array( 'ASFDataPickerParserFunction', 'renderDataPickerValues' ));
			
		return true;
	}
	
	
	static function languageGetMagic( &$magicWords, $langCode = "en" ) {
		$magicWords['DataPickerValues']	= array ( 0, 'DataPickerValues' );
		
		return true;
	}
	
	
	static function renderDataPickerValues( &$parser) {
		$json = func_get_args();
		$json = trim($json[1]);
		$json = str_replace(
			array('##dlcb##', '##drcb##', '##pipe##', '##dlsb##', '##drsb##'), array("{{", "}}", "|", "[[", "]]"), $json);
		$json = json_decode($json, true);
		$json = json_decode($json, true);
		
		$values = $json[0];
		$delimiter = $json[1];
		
		$output = array();
		foreach($values as $value){
			if($value['selected'] == 'true'){
				$output[] = $value['value'];
			}
		}
		
		$output = implode($delimiter, $output);
		
		return $output;
	}
	
	
}












