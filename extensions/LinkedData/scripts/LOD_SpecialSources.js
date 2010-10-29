/*  Copyright 2010, ontoprise GmbH
*  This file is part of the LinkedData-Extension.
*
*   The LinkedData-Extension is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; either version 3 of the License, or
*   (at your option) any later version.
*
*   The LinkedData-Extension is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * @file
 * @ingroup LinkedDataScripts
 * @author: Kai Kuehn
 */
if (typeof LOD == "undefined") {
// Define the LOD module	
	var LOD = { 
		classes : {}
	};
}
/**
 * This is the class of the LODSpecialSources page.
 */
LOD.classes.SpecialSources = function () {
	var that = {};
	
	that.doImportOrUpdate = function (elem, dataSourceID, update) {
		var url = wgServer + wgScriptPath + "/index.php?action=ajax";
		
		// callback for successful import
		var importOrUpdateSuccess = function (data, textStatus, request) {
			alert("Data source " + (update ? "updated" : "imported")+" "+data);
			jQuery.ajax({ url:  url, 
				  data: "rs=lodGetDataSourceTable&rsargs[]=",
				  success: that.updateTable,
				  error: showErrorMessage
				});
		};
		
		// callback for errornous import
		var showErrorMessage = function (request, textStatus, errorThrown) {
			alert("Error: "+request.statusText);
			jQuery.ajax({ url:  url, 
				  data: "rs=lodGetDataSourceTable&rsargs[]=",
				  success: that.updateTable,
				  error: showErrorMessage
				});
		};
		
		// trigger LOD source import/update
		jQuery.ajax({ url:  url, 
					  data: "rs=lodImportOrUpdate&rsargs[]="
						  	+ encodeURIComponent(dataSourceID)
						  	+ "&rsargs[]="
						  	+ (update ? "true" : "false"),
					  success: importOrUpdateSuccess,
					  error: showErrorMessage
					});
	};
	
	/**
	 * Callback function for displaying the table on the 
	 * special page LODSpecialSources.
	 */
	that.updateTable = function(data, textStatus, request) {
		var $ = jQuery;
		$('lod_source_table').replaceWith(data);
	};
	
	return that;
};

LOD.sources = LOD.classes.SpecialSources();
