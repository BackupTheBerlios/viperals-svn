<?php /* $Id: commentdraw.inc,v 1.23 2004/06/21 23:24:10 cryptographite Exp $ */ ?>
<?php 

	global $commentdraw, $i, $index, $id;

// If the border color is not passed in, we do a black/white combo
if ($commentdraw["bordercolor"])
{
	$borderIn = $commentdraw["bordercolor"];
} else {
	$borderIn = "#FFFFFF";
}

$pixelImage = "<img src=\"" . getImagePath('pixel_trans.gif') . "\" width=\"1\" height=\"1\" alt=\"\">";

?>
<!-- Begin Comment -->
<table class="tablebg" cellpadding="4" cellspacing="1" width="100%">
<tbody>
<tr>
	<td class="row1"><?php echo _("Poster:") ?>
	<b><?php echo $commentdraw["name"] ?></b>
<?php
if ($gallery->user->isAdmin()) {
	echo "@ &nbsp;";
	echo $commentdraw["IPNumber"];
}
?>	<br/><?php echo _("Date:") .' ' .$commentdraw["datePosted"]; ?></td>
</tr>
<tr>
	<td colspan="3"><?php echo wordwrap($commentdraw["comment"], 100, " ", 1) ?></td>
</tr>

<?php
if ($gallery->user->canWriteToAlbum($gallery->album)) {
	echo "\n<tr>";
	echo "\n\t". '<td colspan="3" align="center" class="commentboxfooter">';
        $url = doCommand("delete-comment", array("index" => $index, "comment_index" => $i), "view_photo.php", array("set_albumName" => $gallery->session->albumName, "id" => $id));
	echo '<a href="#" onclick="javascript:' . popup($url,1) . '">['. _("delete comment") . ']</a>';
	echo "\n\t</td>";
	echo "\n</tr>";
}
?>
</tbody>
</table>
<br>
<!-- End Comment -->
