<?php echo $this->location ?>
<script type="text/javascript">
	HTMLArea.loadPlugin("CharacterMap");
	HTMLArea.loadPlugin("SearchAndReplace");
	HTMLArea.loadPlugin("ContextMenu");
	HTMLArea.loadPlugin("ImageManager");

	HTMLArea.viperalreplaceAll = function(config) {
	var tas = document.getElementsByTagName("textarea");
	var loop = 0;
	for (var i = tas.length; i > 0;) {
	editor = new HTMLArea(tas[--i], config);
		if (loop == 0)
		{
			editor.registerPlugin(CharacterMap);
			editor.registerPlugin(SearchAndReplace);
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
  "fontsize", "space",
  "bold", "smiles", "italic", "underline", "separator",
  "strikethrough", "subscript", "superscript", "separator",
  "copy", "cut", "paste", "space", "undo", "redo", "killword"],
		
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
HTMLArea.onload = initEditor;

if (HTMLArea.is_ie) {
	window.attachEvent("onload", HTMLArea.init);
} else {
	window.addEventListener("load", HTMLArea.init, true);
}

</script>