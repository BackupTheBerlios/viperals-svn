/**
 * The SearchAndReplace plugin. javascript.
 * @author $Author: Maciej Wilczyñski
 * @script: search-and-replace.js 2004-09-08 23:04
 * @version 1.0 beta2
 * @package SearchAndReplace
 */

/**
 * To Enable the plug-in add the following line before HTMLArea is initialised.
 *
 * HTMLArea.loadPlugin("SearchAndReplace");
 *
*/

function SearchAndReplace(editor) {
        this.editor = editor;
	        
        var cfg = editor.config;
	var toolbar = cfg.toolbar;
	var self = this;
	var i18n = SearchAndReplace.I18N;
        
	cfg.registerButton({
                id       : "searchandreplace",
                tooltip  : i18n["SearchAndReplaceTooltip"],
                image    : editor.imgURL("ed_replace.gif", "SearchAndReplace"),
                textMode : true,
                action   : function(editor) {
                                self.buttonPress(editor);
                           }
            })

	var a, i, j, found = false;
	for (i = 0; !found && i < toolbar.length; ++i) {
		a = toolbar[i];
		for (j = 0; j < a.length; ++j) {
			if (a[j] == "inserthorizontalrule") {
				found = true;
				break;
			}
		}
	}
	if (found)
	    a.splice(j, 0, "searchandreplace");
        else{                
            toolbar[1].splice(0, 0, "separator");
	    toolbar[1].splice(0, 0, "searchandreplace");
        }
};

SearchAndReplace._pluginInfo = {
	name          : "SearchAndReplace",
	version       : "1.0 beta2",
	developer     : "Maciej Wilczyñski",
	developer_url : "http://www.rumia.net/htmlarea/",
	sponsor       : "Ma³e Trójmiasto Kaszubskie",
	sponsor_url   : "http://www.mtk.pl/",
	license       : "htmlArea"
};

SearchAndReplace.prototype.buttonPress = function(editor) {
	
	var selectedtxt = "";
	
	//in source mode mozilla show errors, try diffrent method
	if (editor._editMode == "wysiwyg") selectedtxt = editor.getSelectedHTML();
	else
	 if (HTMLArea.is_ie) {
		selectedtxt = document.selection.createRange().text;
	} else {
		selectedtxt = getMozSelection(editor._textArea);
	}
	
	outparam = {
		f_search : selectedtxt
	};
	
	//Call Search And Replace popup window
    	editor._popupDialog( "plugin://SearchAndReplace/searchandreplace", function( entity ) 
    	{
        	if ( !entity ) 
        	{  
            		//user must have pressed Cancel
            		return false;
        	}
        	var text = editor.getHTML();
        	var search = entity[0];
  		var replace = entity[1];
  		var delim = entity[2];
  		var regularx = entity[3];
  		var closesar = entity[4];
  		var ile = 0;
        	
        	if (search.length < 1) {
        		alert ("Enter a search word! \n search for: " + entity[0]);
        	} else {
        		if (regularx) {
        			var regX = new RegExp (search, delim) ;
        			var text = text.replace ( regX,
    				function (str, n) {
        				// Increment our counter variable.
         				ile++ ;
		        		//return replace ;
		        		return str.replace( regX, replace) ;
        				}
				)
        			
        		} else {
        			while (text.indexOf(search)>-1) {
	        			pos= text.indexOf(search);
    					text = "" + (text.substring(0, pos) + replace + text.substring((pos + search.length), text.length));
    					ile++;
  				}

			}
	  		editor.setHTML(text);
  			editor.forceRedraw(); 

  			if (ile > 0) {
  				alert (ile+ " items replaced; \n");
  			} else {
  				alert ("Search string Not Found! \n");
  			}
  			//if (closesar) SearchAndReplace(editor);
               }
    	}, outparam);
    	
    	//Functions
    	function getMozSelection(txtarea) {
		var selLength = txtarea.textLength;
		var selStart = txtarea.selectionStart;
		var selEnd = txtarea.selectionEnd;
		if (selEnd==1 || selEnd==2) selEnd=selLength;
		return (txtarea.value).substring(selStart, selEnd);
	}
}