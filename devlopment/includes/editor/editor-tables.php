<?php
if (!defined('VIPERAL')) {
    header('location: /');
    die();
}

echo $this->location

?>
<script type="text/javascript">
HTMLArea.loadPlugin("CharacterMap");
	HTMLArea.loadPlugin("TableOperations");
	HTMLArea.loadPlugin("SmilesPlus");
	HTMLArea.loadPlugin("CharacterMap");
	HTMLArea.loadPlugin("ContextMenu");
	HTMLArea.loadPlugin("ImageManager");

	HTMLArea.viperalreplaceAll = function(config) {
	var tas = document.getElementsByTagName("textarea");
	var loop = 0;
	for (var i = tas.length; i > 0;) {
	editor = new HTMLArea(tas[--i], config);
		/*Make this a array */
		if (loop == 0)
		{
			editor.registerPlugin(CharacterMap);
			editor.registerPlugin(TableOperations);
			editor.registerPlugin(SmilesPlus);
		}
		editor.registerPlugin(ContextMenu);
		loop= loop + 1;
		editor.generate();
	}
};

function initEditor() {
  var config = new HTMLArea.Config(); 
     config.toolbar = [
[ "fontname", "space",
  "formatblock", "space",
  "bold", "italic", "underline", "separator",
  "strikethrough", "subscript", "superscript", "separator",
  "copy", "cut", "paste", "space", "undo", "redo" ],
		
[ "justifyleft", "justifycenter", "justifyright", "justifyfull", "separator", "outdent", "indent", "separator",
  "forecolor", "hilitecolor", "textindicator", "separator",
  "orderedlist", "unorderedlist", "outdent", "indent", "separator",
  "inserthorizontalrule", "createlink", "insertimage", "inserttable", "htmlmode", "separator",
  "popupeditor","print", "separator"]
]

<?php 
if ($this->editorids) 
{
echo $this->editorids; 
} else {
?>
HTMLArea.viperalreplaceAll(config);

<?php } ?>

return false;
}

function addEvent(obj, evType, fn)
{
if (obj.addEventListener) { obj.addEventListener(evType, fn, true); return true; }
else if (obj.attachEvent) { var r = obj.attachEvent("on"+evType, fn); return r; }
else { return false; }
} 
addEvent(window, 'load', HTMLArea.init); 

HTMLArea.onload = initEditor;

</script>