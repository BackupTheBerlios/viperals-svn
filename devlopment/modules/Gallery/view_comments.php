<?php
/*
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2004 Bharat Mediratta
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * This page Created by Joseph D. Scheve ( chevy@tnatech.com ) for the
 * very pimp application that is Gallery.
 *
 * $Id: view_comments.php,v 1.40 2004/10/02 22:10:03 jenst Exp $
 */
?>
<?php

require_once(dirname(__FILE__) . '/init.php');

// Hack check
if (!$gallery->user->canViewComments($gallery->album)
	&& (! isset($gallery->app->comments_overview_for_all) || $gallery->app->comments_overview_for_all != "yes")) {
	header("Location: " . makeAlbumHeaderUrl());
	return;
}

if (!$gallery->album->isLoaded()) {
	header("Location: " . makeAlbumHeaderUrl());
	return;
}

$albumName = $gallery->session->albumName;

if (empty($gallery->session->viewedAlbum[$albumName]) && 
	!$gallery->session->offline) {
	$gallery->session->viewedAlbum[$albumName] = 1;
	$gallery->album->incrementClicks();
} 



$bordercolor = $gallery->album->fields["bordercolor"];

#-- breadcrumb text ---
$upArrowURL = '<img src="' . getImagePath('nav_home.gif') . '" width="13" height="11" '.
		'alt="' . _("navigate UP") .'" title="' . _("navigate UP") .'" border="0">';

if ($gallery->album->fields['returnto'] != 'no') {
	$breadcrumb["text"][]= _("Gallery") .": <a class=\"bread\" href=\"" . makeGalleryUrl("albums.php") . "\">" .
		$gallery->app->galleryTitle . "&nbsp;" . $upArrowURL . "</a>";
	foreach ($gallery->album->getParentAlbums() as $name => $title) {
		$breadcrumb["text"][] = _("Album") .": <a class=\"bread\" href=\"" . makeAlbumUrl($name) . "\">" .
			$title. "&nbsp;" . $upArrowURL . "</a>";
	}
}

$breadcrumb["bordercolor"] = $bordercolor;
$breadcrumb["top"] = true;
$breadcrumb["bottom"] = true;

if (!$GALLERY_EMBEDDED_INSIDE) {
	doctype();
?>
<html> 
<head>
  <title><?php echo $gallery->app->galleryTitle ?> :: <?php echo $gallery->album->fields["title"] ?></title>
  <?php echo getStyleSheetLink() ?>
  <style type="text/css">
<?php
// the link colors have to be done here to override the style sheet 
if ($gallery->album->fields["linkcolor"]) {
?>
    A:link, A:visited, A:active
      { color: <?php echo $gallery->album->fields[linkcolor] ?>; }
    A:hover
      { color: #ff6600; }
<?php
}
if ($gallery->album->fields["bgcolor"]) {
	echo "BODY { background-color:".$gallery->album->fields[bgcolor]."; }";
}
if ($gallery->album->fields["background"]) {
	echo "BODY { background-image:url(".$gallery->album->fields[background]."); } ";
}
if ($gallery->album->fields["textcolor"]) {
	echo "BODY, TD {color:".$gallery->album->fields[textcolor]."; }";
	echo ".head {color:".$gallery->album->fields[textcolor]."; }";
	echo ".headbox {background-color:".$gallery->album->fields[bgcolor]."; }";
}
?>
  </style>
</head>

<body dir="<?php echo $gallery->direction ?>">
<?php } 

// User wants to delete comments
list($index, $comment_index) = getRequestVar(array('index', 'comment_index'));
if (!empty($comment_index)) {
	// First we reverse the index array, as we want to delete backwards
	foreach(array_reverse($comment_index, true) as $com_index => $trash) {
		if (isDebugging()) {
			echo "\n<br>". sprintf(_("Deleting comment %d of picture with index: %d"), $com_index, $index);
		}
		$gallery->album->deleteComment($index, $com_index);
		$comment=$gallery->album->getComment($index, $com_index);
		$gallery->album->save(array(i18n("Comment \"%s\" by %s deleted from %s"),
			$comment->getCommentText(),
			$comment->getName(),
			makeAlbumURL($gallery->album->fields["name"],
			$gallery->album->getPhotoId($index))));
	}
	$gallery->album->save();
}

includeHtmlWrap("album.header");
$adminText = "<span class=\"admin\">". _("Comments for this Album") ."</span>";
$adminCommands = "<span class=\"admin\">";
$adminCommands .= "<a class=\"admin\" href=\"" . makeAlbumUrl($gallery->session->albumName) . "\">[". _("return to album") ."]</a>";
$adminCommands .= "</span>";
$adminbox["text"] = $adminText;
$adminbox["commands"] = $adminCommands;
$adminbox["bordercolor"] = $bordercolor;
$adminbox["top"] = true;
includeLayout('navtablebegin.inc');
includeLayout('adminbox.inc');
includeLayout('navtablemiddle.inc');
includeLayout('breadcrumb.inc');
includeLayout('navtableend.inc');
?><br><?php

if (!$gallery->album->fields["perms"]['canAddComments']) {
    ?></span><br><b>
	<span class="error"><?php echo _("Sorry.  This album does not allow comments.") ?></span>
	<span class="popup"><br><br></b><?php
} else {
    $numPhotos = $gallery->album->numPhotos(1);
    $commentbox["bordercolor"] = $bordercolor;
    $i = 1;
    while($i <= $numPhotos)
    {
	set_time_limit($gallery->app->timeLimit);
        $id = $gallery->album->getPhotoId($i);
        $index = $gallery->album->getPhotoIndex($id);
        if ($gallery->album->isAlbum($i)) {
		$myAlbumName = $gallery->album->getAlbumName($i);
		$myAlbum = new Album();
		$myAlbum->load($myAlbumName);
		if (((!$gallery->album->isHidden($i) && $gallery->user->canReadAlbum($myAlbum)) || $gallery->user->isAdmin() || 
			$gallery->user->isOwnerOfAlbum($gallery->album) || $gallery->user->isOwnerOfAlbum($myAlbum)))
		{
			$embeddedAlbum = 1;
			$myHighlightTag = $myAlbum->getHighlightTag();
			includeLayout('commentboxtop.inc');
			includeLayout('commentboxbottom.inc');
	        }
	}
        elseif (!$gallery->album->isHidden($i) || $gallery->user->isAdmin() ||  
		$gallery->user->isOwnerOfAlbum($gallery->album) || $gallery->album->isItemOwner($i))
        {
            $comments = $gallery->album->numComments($i);
            if($comments > 0)
            {
		includeLayout('commentboxtop.inc');

                for($j = 1; $j <= $comments; $j++)
                {
                    $comment = $gallery->album->getComment($index, $j);
		    includeLayout('commentbox.inc');
                }
		includeLayout('commentboxbottom.inc');
            }
        }
        $embeddedAlbum = 0;
        $i = getNextPhoto($i);
    }
}
$breadcrumb["top"] = true;
$breadcrumb["bottom"] = true;
includeLayout('navtablebegin.inc');
includeLayout('breadcrumb.inc');
includeLayout('navtableend.inc');

includeLayout('ml_pulldown.inc');
includeHtmlWrap("album.footer");
?>

<?php if (!$GALLERY_EMBEDDED_INSIDE) { ?>

</body>
</html>
<?php } ?>