/**
* Skin class - Javascript functionality of Ontoskin3
*
* @author Robert Ulrich
*/
function Smwh_Skin() {

    //Variables
    this.expanded = false; //stores if skin is expanded or not
    this.treeviewhidden = true; //stores if treeview is hidden or not


    /**
     * @brief function showMenu
     *        This functions sets the hovering class so the menu is shown.
     *        It's bound to hover events
     *
     */
    this.showMenu = function(){
        $jq(this).addClass("hovering");
    };

     /**
     * @brief function hideMenu
     *        This functions removes the hovering class so the menu is hidden.
     *        It's bound to mouseout events
     *
     */
    this.hideMenu = function(){
        $jq(this).removeClass("hovering");
    };


     /**
     * @brief function resizePage
     *        This functions resizes the skin between a fixed width and full width.
     *
     */
    this.resizePage = function (){
        if( this.expanded == false){
            //show layout, which uses full browser window size
            $jq("#shadows").css("width", "100%");
            $jq("#personal_expand").removeClass("limited");
            $jq("#personal_expand").addClass("expanded");
            $jq("#smwh_treeviewtoggleleft,#smwh_treeviewtoggleright,#smwh_treeview").addClass("expanded");
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
            $jq("#personal_expand").removeClass("expanded");
            $jq("#smwh_treeviewtoggleleft,#smwh_treeviewtoggleright,#smwh_treeviewtogglecenter,#smwh_treeview").removeClass("expanded");
            $jq("#personal_expand").addClass("limited");
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
        $jq("#smwh_treeviewtoggleleft").removeClass("active");
        $jq("#smwh_treeviewtoggleright").removeClass("active");
        $jq("#smwh_treeview").removeAttr("style");

        //store state in a cookie
        if(GeneralBrowserTools!=undefined){
            GeneralBrowserTools.setCookieObject("smwSkinTree", "none");
        }
    };
    
    this.showTreeViewLeftSide = function(){
        if( this.treeviewhidden == false ){
            this.hideTree();
        } else {
            //Hide tree
            this.hideTree();
            
            $jq("#smwh_treeview").css("width", "500px");
            $jq("#smwh_treeview").removeClass("smwh_treeviewright");
            $jq("#smwh_treeview").addClass("smwh_treeviewleft");
            $jq("#smwh_treeviewtoggleleft").addClass("active");
            this.setRightDistance();
            
            //Set tree as shown
            this.treeviewhidden = false;

            //store state in a cookie
            if(GeneralBrowserTools!=undefined){
                GeneralBrowserTools.setCookieObject("smwSkinTree", "left");
            }
        }
    };

    this.showTreeViewRightSide = function(){
        if( this.treeviewhidden == false ){
            this.hideTree();
        } else {
            this.hideTree()
            //Show tree
            //if page uses full screen width don't show tree on the right
            if(this.expanded == true) return;
            
            $jq("#smwh_treeview").removeClass("smwh_treeviewleft");
            $jq("#smwh_treeview").addClass("smwh_treeviewright");
            $jq("#smwh_treeviewtoggleright").addClass("active");

            //if the calculated width is too small don't show tree
            if( this.setRightWidth() < 200) return;

            //Set tree as shown
            this.treeviewhidden = false;

            //store state in a cookie
            if(GeneralBrowserTools!=undefined){
                GeneralBrowserTools.setCookieObject("smwSkinTree", "right");
            }
        }
    };


    //Calculate distance to the right browser border and apply to treeview if shown on the leftside
    this.setRightDistance = function(){
        var toggleoffset = $jq("#shadow_right").offset().left;
        var windowwidth  = $jq(window).width();
        var rightspace = windowwidth - toggleoffset;
        $jq('.smwh_treeviewleft').css('right', rightspace + 'px');

        if( this.expanded )
        {
            $jq('.smwh_treeviewleft').css('right', null);

        }
        else
        {
            $jq('.smwh_treeviewleft').css('right', rightspace + 'px');
        }
        
        return rightspace;
    }

    //Calculate gap between page and right browser border and apply to treeview if shown on the rightside
    this.setRightWidth = function(){
        var contentoffset = $jq("#shadows").offset().left - 40;
        $jq(".smwh_treeviewright").css("width", contentoffset+"px");
        return contentoffset;
    }

    this.resizeControl = function(){

        //set minimum height, so page always reachs to the bottom of the browser screen
        var windowheight = $jq(window).height();
        $jq("#smwh_HeightShell").css("min-height", windowheight+"px");

        //Adjust css for left and right viewed treeview
        this.setRightDistance();
        //hide tree if shown on the right side and not enough space is given.
        if( this.setRightWidth() < 200 && $jq(".smwh_treeviewright").length > 0 ){
            this.hideTree();
        }
        

        //Check if there is enough space on the left side to show the treeview otherwise remove button
        contentoffset = $jq("#shadows").offset().left - 20;
        if( this.expanded == true || contentoffset < 200 ){
            $jq("#smwh_treeviewtoggleright").css("display","none");
        } else {
            $jq("#smwh_treeviewtoggleright").css("display","block");
        }

    }



    if(typeof GeneralBrowserTools != 'undefined'){
        var state = GeneralBrowserTools.getCookieObject("smwSkinExpanded");
        if (state == true && this.expanded == false){
            this.resizePage();
            
        }
        state = GeneralBrowserTools.getCookieObject("smwSkinTree");
        if (state == "left" && this.treeviewhidden == true){
            this.showTreeViewLeftSide();

        } else if (state == "right" && this.treeviewhidden == true){
            this.showTreeViewRightSide()

        }


    }

    //Constructor
    this.constructor = function(){
        //register Eventhandler for the menubar itself
        $jq("#smwh_menu * .smwh_menulistitem").hover(this.showMenu, this.hideMenu);
        //register Eventhandler for the more tab
        $jq("#more").hover(this.showMenu, this.hideMenu);

        $jq("#smwh_treeviewtoggleright").click(this.showTreeViewRightSide.bind(this));
        $jq("#smwh_treeviewtoggleleft").click(this.showTreeViewLeftSide.bind(this));
        $jq(window).resize(this.resizeControl.bind(this));
    }

    //Execute constructor on object creation
    this.constructor();
    
}

var smwh_Skin;

$jq(document).ready(function(){
    smwh_Skin = new Smwh_Skin();
    smwh_Skin.resizeControl();
}
);

