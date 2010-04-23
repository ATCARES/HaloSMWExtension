// Resize button for full screen mode of the FCK

var tbButton = new FCKToolbarButton( 'Fullscreen', 'Fullscreen', 'Fullscreen', null, true) ;
tbButton.IconPath = FCKConfig.PluginsPath + 'fullscreen/tb_icon_fullscreen.gif' ;
FCKToolbarItems.RegisterItem( 'Fullscreen', tbButton );

var FullscreenCommand = window.parent.Class.create();
FullscreenCommand.prototype = {
    initialize: function() {
        this.fullscreen = 0;
        this.fckiframe = window.parent.document.getElementsByTagName('iframe')[0];
        if (FCKBrowserInfo.IsIE) {
            var styles = this.fckiframe.getAttribute('style');
            this.origStyle = new Object();
            for (var key in styles)
                if (styles[key]) this.origStyle[key] = styles[key];
        }
        else
            this.origStyle = this.fckiframe.getAttribute('style');
    },

    GetState: function() {
        return this.fullscreen;
    },

    Execute: function() {
	    if (this.fullscreen == 1) {
	        if (FCKBrowserInfo.IsIE) {
	            var element = this.fckiframe;
	            for (var key in this.origStyle) {
	                if (this.origStyle[key] != '') {
	                    this.fckiframe.style[key] = this.origStyle[key];
	                }
	            }
	        }
	        else
                this.fckiframe.setAttribute('style', this.origStyle);
           	// hide menubar of ontokin 3
			var osMenuBar = window.parent.document.getElementById("smwh_menu");
			if (osMenuBar) {
				osMenuBar.style.display = "";
			}
			var osMenuTabs = window.parent.document.getElementById("tabsright");
            if (osMenuTabs) {
                osMenuTabs.style.display = "";
            }    
        }
        else {
            this.fckiframe.style.left = '0px';
            this.fckiframe.style.top = '0px';
            this.fckiframe.style.height = '100%'
            this.fckiframe.style.width = '100%'
            this.fckiframe.style.position = 'fixed';
            // needed to suppress the message of the ac
            this.fckiframe.style.zIndex = 2;
			var osMenuBar = window.parent.document.getElementById("smwh_menu");
			if (osMenuBar) {
				osMenuBar.style.display = "none";
			}
            var osMenuTabs = window.parent.document.getElementById("tabsright");
            if (osMenuTabs) {
                osMenuTabs.style.display = "none";
            }
        }
        this.fullscreen = 1 - this.fullscreen;
    }
}

fckFullscreen = new FullscreenCommand();

FCKCommands.RegisterCommand( 'Fullscreen', fckFullscreen) ;


