<?php

/*
 * Any URL that you want to use can either be accessed directly
 * in the case of a standalone Gallery, or indirectly if we're
 * mbedded in another app such as Nuke.  makeGalleryUrl() will
 * always create the appropriate URL for you.
 *
 * Usage:  makeGalleryUrl(target, args [optional])
 *
 * target is a file with a relative path to the gallery base
 *        (eg, "album_permissions.php")
 *
 * args   are extra key/value pairs used to send data
 *        (eg, array("index" => 1, "set_albumName" => "foo"))
 */

function makeGalleryUrl($target, $args=array(), $full_link = false) {

	global $gallery, $GALLERY_EMBEDDED_INSIDE;

	/*
	 * include *must* be last so that the JavaScript code in
	 * view_album.php can append a filename to the resulting URL.
	 */
	if( isset($GALLERY_EMBEDDED_INSIDE))
	{
		if ($target)
		{
			$args["include"] = $target;
		}
		
		$url = '';
		
		if ($args) 
		{
			foreach ($args as $key => $value)
			{
				$url .= "&";  // should replace with &amp; for validatation
	
				if (!is_array($value))
				{
					if (!$value)
					{
						continue;
					}
				
					$url .= $key.'='.$value;
				
				} else {
				
					$j = true;
					
					foreach ($value as $subkey => $subvalue) 
					{
						if ($j)
						{
							$url .= "&";  // should replace with &amp; for validatation
							$j = false;
						}
						
							$url .= $key .'[' . $subkey . ']=' . $subvalue;
						}
					}
				}
		}
		
		if ($url{0} == '/')
		{
			$url = substr($url, 1);
		}
		
		return getlink(htmlspecialchars($url), true, $full_link);
	}
	
	$prefix = isset($gallery->app->photoAlbumURL) ? $gallery->app->photoAlbumURL . "/" : "";
	$target = $prefix . $target;
	$url = $target;
	if ($args) {
		$i = 0;
		foreach ($args as $key => $value) {
			if ($i++) {
				$url .= "&";  // should replace with &amp; for validatation
			} else {
				$url .= "?";
			}

			if (! is_array($value)) {
				$url .= "$key=$value";
			} else {
				$j = 0;
				foreach ($value as $subkey => $subvalue) {
					if ($j++) {
						$url .= "&";  // should replace with &amp; for validatation
					}
					$url .= $key .'[' . $subkey . ']=' . $subvalue;
				}
			}
		}
	}
	return htmlspecialchars($url);

}

function makeGalleryHeaderUrl($target, $args=array())
{
		global $gallery;
	global $GALLERY_EMBEDDED_INSIDE;
	global $GALLERY_EMBEDDED_INSIDE_TYPE;
	global $GALLERY_MODULENAME;

	/* Needed for phpBB2 */
	global $userdata;
	global $board_config;
        
	/* Needed for Mambo */
	global $MOS_GALLERY_PARAMS;

	/* Needed for CPGNuke */
	global $mainindex;

	if( isset($GALLERY_EMBEDDED_INSIDE))
	{

				//$args["op"] = "modload";
				$args["name"] = "$GALLERY_MODULENAME";
				$args["file"] = "index";
			    $args["include"] = $target;
				$target = '/'.$mainindex;

	} else {
		$prefix = isset($gallery->app->photoAlbumURL) ? $gallery->app->photoAlbumURL . "/" : "";
		$target = $prefix . $target;
	}
       
	$url = $target;
	if ($args) {
		$i = 0;
		foreach ($args as $key => $value) {
			if ($i++) {
				$url .= "&";  // should replace with &amp; for validatation
			} else {
				$url .= "?";
			}

			if (! is_array($value)) {
				$url .= "$key=$value";
			} else {
				$j = 0;
				foreach ($value as $subkey => $subvalue) {
					if ($j++) {
						$url .= "&";  // should replace with &amp; for validatation
					}
					$url .= $key .'[' . $subkey . ']=' . $subvalue;
				}
			}
		}
	}
	return $url;
	
	//$url = makeGalleryUrl($target, $args);
	//return unhtmlentities($url);
}

/*
 * makeAlbumUrl is a wrapper around makeGalleryUrl.  You tell it what
 * album (and optional photo id) and it does the rest.  You can also
 * specify additional key/value pairs in the optional third argument.
 */

function makeAlbumUrl($albumName="", $photoId="", $args=array()) {
	global $gallery;

	if ($albumName) {
		$args["set_albumName"] = urlencode ($albumName);
		if ($photoId) {
			$target = "view_photo.php";
			$args["id"] = urlencode ($photoId);
		} else {
			$target = "view_album.php";
		}
	} else {
		$target = "albums.php";
	}
	
	return makeGalleryUrl($target, $args);
}

function makeAlbumHeaderUrl($albumName="", $photoId="", $args=array()) {
	$url = makeAlbumUrl($albumName, $photoId, $args);
	return unhtmlentities($url);
}

function addUrlArg($url, $arg) {
	if (strchr($url, "?")) {
		return "$url&$arg"; // should replace with &amp; for validatation
	} else {
		return "$url?$arg";
	}
}

?>