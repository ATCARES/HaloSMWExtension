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

/**
 * @file
 * @ingroup Refactoring
 *
 * @defgroup Refactoring
 * @ingroup Refactoring
 *
 * @author Kai Kühn
 *
 * Created on 16.02.2011
 *
 */
if ( !defined( 'MEDIAWIKI' ) ) die;

global $sgagIP;
require_once("$sgagIP/includes/SGA_GardeningBot.php");
require_once("$sgagIP/includes/SGA_ParameterObjects.php");

require_once( $srefgIP . '/includes/SRF_Bot.php');
require_once($srefgIP . '/includes/SRF_RefactoringOperation.php');
require_once($srefgIP . '/includes/SRF_Tools.php');
require_once($srefgIP . '/includes/operations/SRF_ChangeValue.php');
require_once($srefgIP . '/includes/operations/SRF_DeleteCategory.php');
require_once($srefgIP . '/includes/operations/SRF_DeleteProperty.php');
require_once($srefgIP . '/includes/operations/SRF_RenameCategory.php');
require_once($srefgIP . '/includes/operations/SRF_RenameInstance.php');
require_once($srefgIP . '/includes/operations/SRF_RenameProperty.php');


/**
 * Exports object logic from TSC.
 *
 * @author kuehn
 *
 */
class SRFRefactoringBot extends GardeningBot {

	function __construct() {
		parent::GardeningBot("smw_refactoringbot");
	}

	public function getHelpText() {
		return wfMsg('smw_gard_exportobl_docu');
	}

	public function getLabel() {
		return wfMsg($this->id);
	}

	public function isVisible() {
		return false;
	}

	/**
	 * Returns an array
	 */
	public function createParameters() {
		return array();
	}

	public function run($paramArray, $isAsync, $delay) {
			
		// do not allow to start synchronously.
		if (!$isAsync) {
			return "RefactoringBot should not be executed synchronously!";
		}

		if (!array_key_exists('SRF_OPERATION', $paramArray)) {
			return "Refactoring operation not specified.";
		}

		$operation = $paramArray['SRF_OPERATION'];

		switch($operation) {
			case 'renameProperty':
				if (!array_key_exists('oldProperty', $paramArray)) {
					return "Old property missing";
				}
				$oldProperty = $paramArray['oldProperty'];

				if (!array_key_exists('newProperty', $paramArray)) {
					return "New property missing";
				}
				$newProperty = $paramArray['newProperty'];

				if (!array_key_exists('rename_annotations', $paramArray) || $paramArray['rename_annotations'] == false) {
					return "Nothing done.";
				}

				$op = new SRFRenamePropertyOperation($oldProperty, $newProperty);
				$num = $op->getNumberOfAffectedPages();
				break;
			case 'renameCategory' :
				if (!array_key_exists('oldCategory', $paramArray)) {
					return "Old category missing";
				}
				$oldCategory = $paramArray['oldCategory'];

				if (!array_key_exists('newCategory', $paramArray)) {
					return "New property missing";
				}
				$newCategory = $paramArray['newCategory'];
				
				if (!array_key_exists('rename_annotations', $paramArray) || $paramArray['rename_annotations'] == false) {
					return "Nothing done.";
				}
				
				$op = new SRFRenameCategoryOperation($oldCategory, $newCategory);
				$num = $op->getNumberOfAffectedPages();
				break;
		}
		$op->setBot($this);
		$this->setNumberOfTasks(1);
		$this->addSubTask($num);

		$logMessages=array();
		$op->refactor(false, $logMessages);

		return implode("\n*", $logMessages);
	}
}

