function Smwh_Skin() {

    //this.addMenuFunctions = function
    
        this.expanded = false;
        this.treeviewhidden = true;
  
    this.showMenu = function(){
        $jq(this).addClass("hovering");
    };

    this.hideMenu = function(){
        $jq(this).removeClass("hovering");
    };

    this.expandPage = function (){
        if( this.expanded == false){
            //use full browser window size
            $jq("#shadows").css("width", "100%");
            this.expanded = true;

            //Hide treeview (necessary if shown on the left side)
            this.hideTree();
             
            //store state in a cookie
            if(GeneralBrowserTools!=undefined){
                GeneralBrowserTools.setCookieObject("smwSkinExpanded", this.expanded);
            }
        } else {
            //show layout, which is optimized for 1024x768
            $jq("#shadows").css("width", "960px");
            this.expanded = false;

            //store state in a cookie
            if(GeneralBrowserTools!=undefined){
                GeneralBrowserTools.setCookieObject("smwSkinExpanded", this.expanded);
            }

        }
        //Call resize controll, so button for left treeview is shown or hidden
        this.resizeControl();
    };

    this.hideTree = function(){
        this.treeviewhidden = true;
        $jq("#smwh_treeview").removeClass("smwh_treeviewright");
        $jq("#smwh_treeview").removeClass("smwh_treeviewleft");
        $jq("#smwh_treeview").removeAttr("style");
    };
    
    this.showTreeViewLeftSide = function(){
        if( this.treeviewhidden == false ){
            this.hideTree();
        } else {
            //Hide tree
            this.hideTree();

            //Calculate css style left
            var toggleoffset = $jq("#shadow_right").offset().left;
            var windowwidth  = $jq(window).width()
            var rightspace = windowwidth - toggleoffset;
            $jq('#smwh_treeview').css('right', rightspace + 'px');
            $jq("#smwh_treeview").css("width", "500px");
            $jq("#smwh_treeview").addClass("smwh_treeviewleft");
            
            
            //Set tree as shown
            this.treeviewhidden = false;
        }
    };

    this.showTreeViewRightSide = function(){
        if( this.treeviewhidden == false ){
            this.hideTree();
        } else {
            //Show tree
            //if page uses full screen width don't show tree on the right
            if(this.expanded == true) return;
            
            //get width from the left side to the page
            
            var contentoffset = $jq("#shadows").offset().left - 5;

            //if the calculated width is too small don't show tree
            if( contentoffset < 200) return;
            
            this.treeviewhidden = false;
            $jq("#smwh_treeview").css("width", contentoffset+"px");
            $jq("#smwh_treeview").removeClass("smwh_treeviewleft");
            $jq("#smwh_treeview").addClass("smwh_treeviewright");
        }
    };

    this.resizeControl = function(){
        //set minimum height, so page always reachs to the bottom of the browser screen
        var windowheight = $jq(window).height()
        $jq("#smwh_HeightShell").css("min-height", windowheight+"px");

        //Calculate css style right and apply to treeview if shown on the leftside
        var toggleoffset = $jq("#shadow_right").offset().left;
        var windowwidth  = $jq(window).width()
        var rightspace = windowwidth - toggleoffset;
        $jq('.smwh_treeviewleft').css('right', rightspace + 'px');

        //Calculate css style right and apply to treeview if shown on the leftside
        var contentoffset = $jq("#shadows").offset().left - 5;
        //if the calculated width is too small don't show tree
        if( contentoffset < 200) this.hideTree();
        $jq(".smwh_treeviewright").css("width", contentoffset+"px");

        //Check if there is enough space on the left side to show the treeview otherwise remove button
        var contentoffset = $jq("#shadows").offset().left - 5;
        if( this.expanded == true || contentoffset < 200 ){
            $jq("#smwh_treeviewtoggleright").css("display","none");
        } else {
            $jq("#smwh_treeviewtoggleright").css("display","block");
        }

    }
    
    if(typeof GeneralBrowserTools != 'undefined'){
        var state = GeneralBrowserTools.getCookieObject("smwSkinExpanded");
        if (state == true){
            this.expanded = true;
            $jq("#shadows").css("width", "100%");
        }

    }
        
    $jq(".smwh_menulistitem").hover(this.showMenu, this.hideMenu);
    $jq("#smwh_treeviewtoggleright").click(this.showTreeViewRightSide.bind(this));
    $jq("#smwh_treeviewtoggleleft").click(this.showTreeViewLeftSide.bind(this));
    $jq(window).resize(this.resizeControl.bind(this));
}

var smwh_Skin;

$jq(document).ready(function(){
    smwh_Skin = new Smwh_Skin();
    smwh_Skin.resizeControl();
}
);

