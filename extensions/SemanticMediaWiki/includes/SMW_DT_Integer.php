<?php
/**
 * Typehandler class for integer.
 */

/**
 * Class for managing integer types. Parses whole number
 * strings and generates appropriate error messages on
 * failure.
 */
class SMWIntegerTypeHandler implements SMWTypeHandler {

	function getID() {
		return 'int';
	}

	function getXSDType() {
		return 'http://www.w3.org/2001/XMLSchema#integer';
	}

	function getUnits() { //no units for string
		return array('STDUNIT'=>false, 'ALLUNITS'=>array());
	}

	function processValue($v,&$datavalue) {
		// strip kilo separators and split off number from the rest:
		// TODO: this regexp is also in FloatTypeHandler, should move to common.
		$arr= preg_split('/([-+]?[\d]+)/', str_replace(wfMsgForContent('smw_kiloseparator'), '', $v), 2, PREG_SPLIT_DELIM_CAPTURE);

		if (($arr[0]=='') and ($arr[1]!='') and ($arr[2]=='')) { //no junk
			if (mb_substr($arr[1],0,1)=='+') {
				$arr[1]=substr($arr[1],1);  //strip leading + (relevant for LIKE search)
			}
			$datavalue->setProcessedValues($v, $arr[1], $arr[1]);
			$datavalue->setPrintoutString(smwfNumberFormat($arr[1],0));
			$datavalue->addQuicksearchLink();
			$datavalue->addServiceLinks($arr[1]);
		} else {
			$datavalue->setError(wfMsgForContent('smw_nointeger',$v));
		}
		return true;
	}

	/**
	 * This method parses the value in the XSD form that was
	 * generated by parsing some user input. It is needed since
	 * the XSD form must be compatible to XML, and thus does not
	 * respect the internationalization settings. E.g. the German
	 * input value "1,234" is translated into XSD "1.234" which,
	 * if reparsed as a user input would be misinterpreted as 1234.
	 *
	 * @public
	 */
	function processXSDValue($value,$unit,&$datavalue) {
		return $this->processValue($value . $unit, $datavalue);
	}

	function isNumeric() {
		return TRUE;
	}
}

