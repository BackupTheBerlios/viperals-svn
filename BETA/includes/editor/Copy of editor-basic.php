<?php echo $this->location ?>

<script type="text/javascript">
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
HTMLArea.replaceAll(config);

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