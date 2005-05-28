<?php

// $Id: adminbox.inc,v 1.19 2004/09/13 04:53:39 cryptographite Exp $
// Note that this box is assumed to be attached to a navigator and uses the navigator's
// width parameters .

global $adminbox, $navigator;

// If the border color is not passed in, we do a black/white combo
if ($adminbox["bordercolor"]) {
	$borderIn = $adminbox["bordercolor"];
} else {
	$borderIn = "#FFFFFF";
}
if (!isset($adminbox["commands"])) {
	$adminbox["commands"] = "";
}

$pixelImage = "<img src=\"" . getImagePath('pixel_trans.gif') . "\" width=\"1\" height=\"1\" alt=\"pixel_trans\">";

$width='';
if (isset($navigator["fullWidth"]) && isset($navigator["widthUnits"])) {
        $width=' width="'. $navigator["fullWidth"] . $navigator["widthUnits"] .'" ';
}

?>
<table<?php echo $width; ?> border="0" cellspacing="0" cellpadding="0" class="modnavboxtop">
<?php
if ($adminbox["top"]) {
}
?>
  <tr> 
    <td class="admin" align="left" valign="middle" width="4000" height="18">&nbsp;<?php echo $adminbox["text"] ?></td>
    <td class="admin" align="right" valign="middle" width="3000" height="18">
        <form name="admin_options_form" action="view_album.php" style="margin-bottom: 0px;">
        <?php echo $adminbox["commands"] ?>
        </form>
    </td>
  </tr>
<?php
if (!$adminbox["top"]) {
}
?>
</table>    