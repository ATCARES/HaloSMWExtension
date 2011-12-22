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
 * @author Kai Kuehn
 * 
 */
(function($) {
	
	//TODO: replace with language constants
	var content = { 
		level1 : [	
			'Category',
			'Annotation/property',
			'Template' ]
		,
		
		level2 : {
			0 : ['add', 'remove', 'replace'],
			1 : ['add', 'remove', 'replace', 'set value'],
			2 : ['set', 'rename', 'replace']
		},
		
		parameters : { 
			'00' : [ { id : 'category', ac : 'namespace: Category', title : 'Category name' } ],
			'01' : [ { id : 'category', ac : 'namespace: Category', title : 'Category name' } ],
			'02' : [ { id : 'old_category', ac : 'namespace: Category', title : 'Old category name' },
			         { id : 'new_category', ac : 'namespace: Category', title : 'New category name' } ]
		}
	
	};

	var commandBox = {
			
		current_operation : -1, 
		
		createHTML : function() {
			var html = '<h1>Choose commands</h1>';
			html += '<div style="float:left"><select id="sref_operation_type" class="sref_operation_selector" size="5">';
			$(content.level1).each(function(i, e) { 
				html += '<option value="'+e+'">'+e+'</option>';
			});
			html += '</select></div>';
			
			html += '<div style="float:left"><img src="'+wgScriptPath+'/extensions/SemanticRefactoring/skins/images/arrow.png"/></div>';
			html += '<div style="float:left"><select id="sref_operation" class="sref_operation_selector" size="5">';
			html += '</select></div>';
			
			html += '<div style="float:left"><img src="'+wgScriptPath+'/extensions/SemanticRefactoring/skins/images/arrow.png"/></div>'
			html += '<div style="float:left" id="sref_parameters" class="sref_operation_selector">';
			html += '</div>';
			
			return html;
		},
	
		addListeners: function() {
			$('#sref_operation_type').change(function(e) { 
				var i = e.currentTarget.selectedIndex;
				commandBox.current_operation = i;
				var html = "";
				$(content.level2[i]).each(function(i, e) { 
					html += '<option value="'+e+'">'+e+'</option>';
				});
				$('#sref_operation').html(html);
			});
			
			$('#sref_operation').change(function(e) { 
				var i = e.currentTarget.selectedIndex;
				var html = "<table>";
				i = ""+commandBox.current_operation+i;
				$(content.parameters[i]).each(function(i, e) {
					html += "<tr>";
					html += commandBox.createInputField(e);
					html += "</tr>";
				});
				html += "</table>";
				$('#sref_parameters').html(html);
			});
		},
		
		createInputField : function(e) {
	
			var acAttr = "";
			if (e.ac && e.ac != null) {
				acAttr='class="wickEnabled"';
				acAttr+=' constraints="'+e.ac+'"';
			}
			var html = "<td>"+e.title+"</td>"+'<td><input id="'+e.id+'" type="text" value="" '+acAttr+'></input></td>';
			return html;
		}
	};
			
	$('#sref_commandboxes').html(commandBox.createHTML());
	commandBox.addListeners();
	
	$('#sref_start_operation').click(function(e) { 
		var results = $('input[checked="true"]', '#sref_resultbox');
		prefixedTitles = [];
		results.each(function(i,e) { 
			var prefixedTitle = $(e).attr("prefixedTitle");
			prefixedTitles.push(prefixedTitle);
		});
		alert(prefixedTitles.join(","));
	});
	
})(jQuery);	
