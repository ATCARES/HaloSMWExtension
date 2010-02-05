var AjaxInternalLinks = {baseUrl:'', data:[]};
Ext.onReady(function(){
	var links = document.getElementsByTagName('a');
	var idx=0;
	for(var i=0;i<links.length;++i) {
		if(links[i].href==null || (idx=links[i].href.indexOf(AjaxInternalLinks.baseUrl + '/index.php/'))<0) continue;
		var page = links[i].href.substring((AjaxInternalLinks.baseUrl + '/index.php/').length + idx);
		if((idx=page.indexOf('#'))>0) continue;
		if(links[i].id == '') links[i].id = id;
		links[i].title='';
		AjaxInternalLinks.data.push({id:id,page:page});
	}
	for(var i=0;i<AjaxInternalLinks.data.length;++i) {
	    new Ext.ToolTip({
	        target: AjaxInternalLinks.data[i].id,
	        width: 200,
	        autoLoad: {
	        	url: AjaxInternalLinks.baseUrl + '/index.php?action=ajax&rs=smwf_up_Access&rsargs[]=ajaxSparql&rsargs[]=' + i + ',[[' +AjaxInternalLinks.data[i].page + ']] | ?Abstract | format=list | headers=hide',
	        	callback: function(el, success, response, options){
	        		var html = el.dom.innerHTML.replace(/\<[^\>]*\>/mg, '');
	        		if(html.replace(/(&nbsp;)/mg, '').trim() == '') {
	        			html = 'There is no abstract in this article ...';
	        		} 
		        	el.dom.innerHTML = html;
	        	}
	        },
	        
	        dismissDelay: 0
	        // dismissDelay: 15000 // auto hide after 15 seconds
	    });
    }
});
/*
AjaxInternalLinks.register = function (id, page) {
	AjaxInternalLinks.data.push({id:id,page:page});
};
*/