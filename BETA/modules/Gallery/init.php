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
 * $Id: init.php,v 1.117 2004/09/27 09:36:58 cryptographite Exp $
 */
?>
<?php

/*// Hack prevention.
$register_globals = @ini_get('register_globals');
if (!empty($register_globals) && !eregi("no|off|false", $register_globals)) {
	foreach (array_keys($_REQUEST) as $key) {
		unset($$key);
	}
}*/

$sensitiveList = array("gallery", "GALLERY_EMBEDDED_INSIDE", "GALLERY_EMBEDDED_INSIDE_TYPE");

foreach ($sensitiveList as $sensitive) 
{
	if (!empty($_REQUEST[$sensitive])) {
		print _("Security violation") ."\n";
		exit;
	}
}

// Optional developer hook - location to add useful
// functions such as code profiling modules
if (file_exists(dirname(__FILE__) . "/lib/devel.php"))
{
	require_once(dirname(__FILE__) . "/lib/devel.php");
}

global $gallery;
require(dirname(__FILE__) . "/Version.php");
require(dirname(__FILE__) . "/util.php");

/* Load bootstrap code */
if (getOS() == OS_WINDOWS) {
	include_once(dirname(__FILE__) . "/platform/fs_win32.php");
} else {
	include_once(dirname(__FILE__) . "/platform/fs_unix.php");
}

if (fs_file_exists(dirname(__FILE__) . "/config.php"))
{
	include_once(dirname(__FILE__) . "/config.php");

	/* Here we set a default execution time limit for the entire Gallery script
	 * the value is defined by the user during setup, so we want it inside the
	 * 'if config.php' block.  If the user increases from the default, this will cover
	 * potential execution issues on slow systems, or installs with gigantic galleries.
	 * By calling set_time_limit() again further in the script (in locations we know can
	 * take a long time) we reset the counter to 0 so that a longer execution can occur.
	 */
	set_time_limit($gallery->app->timeLimit);
}

/*
 * Detect if we're running under SSL and adjust the URL accordingly.
 */
if(isset($gallery->app))
{
	if (isset($_SERVER["HTTPS"] ) && stristr($_SERVER["HTTPS"], "on")) {
		$gallery->app->photoAlbumURL = 
			eregi_replace("^http:", "https:", $gallery->app->photoAlbumURL);
		$gallery->app->albumDirURL = 
			eregi_replace("^http:", "https:", $gallery->app->albumDirURL);
	} else {
		$gallery->app->photoAlbumURL = 
			eregi_replace("^https:", "http:", $gallery->app->photoAlbumURL);
		$gallery->app->albumDirURL = 
			eregi_replace("^https:", "http:", $gallery->app->albumDirURL);
	}

	/*
	 * We have a Coral (http://www.scs.cs.nyu.edu/coral/) request coming in, adjust outbound links
	 */
	if(strstr($_SERVER['HTTP_USER_AGENT'], 'CoralWebPrx')) {
		if (ereg("^(http://[^:]+):(\d+)(.*)$", $gallery->app->photoAlbumURL)) {
			$gallery->app->photoAlbumURL = ereg_replace("^(http://[^:]+):(\d+)(.*)$", "\1.\2\3", $galllery->app->photoAlbumURL);
		}
			
		$gallery->app->photoAlbumURL = ereg_replace("^(http://[^/]+)(.*)$", '\1.nyud.net:8090\2',$gallery->app->photoAlbumURL);
		if (ereg("^(http://[^:]+):(\d+)(.*)$", $gallery->app->albumDirURL)) {
			$gallery->app->albumDirURL = ereg_replace("^(http://[^:]+):(\d+)(.*)$", "\1.\2\3", $galllery->app->albumDirURL);
		}
		$gallery->app->albumDirURL = ereg_replace("^(http://[^/]+)(.*)$", '\1.nyud.net:8090\2',$gallery->app->albumDirURL);
	} 
}

/* Load classes and session information */
require(dirname(__FILE__) . "/classes/Album.php");
require(dirname(__FILE__) . "/classes/Image.php");
require(dirname(__FILE__) . "/classes/AlbumItem.php");
require(dirname(__FILE__) . "/classes/AlbumDB.php");
require(dirname(__FILE__) . "/classes/User.php");
require(dirname(__FILE__) . "/classes/EverybodyUser.php");
require(dirname(__FILE__) . "/classes/NobodyUser.php");
require(dirname(__FILE__) . "/classes/LoggedInUser.php");
require(dirname(__FILE__) . "/classes/UserDB.php");
require(dirname(__FILE__) . "/classes/Comment.php");

if (!isset($GALLERY_NO_SESSIONS))
{
    require(dirname(__FILE__) . "/session.php");
}

$gallerySanity = gallerySanityCheck();

// Languages need to be initialized early or installations without gettext will break.
// initLanguage is called again later in init.php to pick up user settings.
initLanguage();

/* Make sure that Gallery is set up properly */
if ($gallerySanity != NULL)
{
	include_once(dirname(__FILE__) . "/errors/$gallerySanity");
	exit;
}

$gallery_save = array();

include_once(dirname(__FILE__) . "/classes/Database.php");
include_once(dirname(__FILE__) . "/classes/database/mysql/Database.php");
include_once(dirname(__FILE__) . "/classes/nuke5/UserDB.php");
include_once(dirname(__FILE__) . "/classes/nuke5/User.php");

$gallery->database{"nuke"} = new MySQL_Database();
$gallery->database{"user_prefix"} = $GLOBALS['user_prefix'] . '_';
$gallery->database{"prefix"} = $GLOBALS['prefix'] . '_';

	$gallery->database{'fields'} =
		array ('name'  => 'username',
				'uname' => 'username',
				'email' => 'user_email',
				'uid'   => 'user_id');

// Load our user database (and user object)
$gallery->userDB = new Nuke5_UserDB;

if (is_admin()) 
{
	include_once(dirname(__FILE__) . "/classes/nuke5/AdminUser.php");
	
	$gallery->user = new Nuke5_AdminUser();
	$gallery->session->username = $gallery->user->getUsername();
	
} elseif (is_user()) {

	$gallery->session->username = $_CLASS['user']->data['username']; 
	$gallery->user = $gallery->userDB->getUserByUsername($gallery->session->username);
	
} 

/* If there's no specific user, they are the special Everybody user */
if (!isset($gallery->user) || empty($gallery->user))
{

	$gallery->user = $gallery->userDB->getEverybody();
	$gallery->session->username = '';
}

if (!isset($gallery->session->offline)) {
    $gallery->session->offline = FALSE;
}

if ($gallery->userDB->versionOutOfDate()) {
	include_once(dirname(__FILE__) . '/upgrade_users.php');
	exit;
}

/* Load the correct album object */
if (!empty($gallery->session->albumName))
{
	$gallery->album = new Album;
	$ret = $gallery->album->load($gallery->session->albumName);
	if (!$ret) {
		$gallery->session->albumName = "";
	} else {
		if ($gallery->album->versionOutOfDate()) {
			include_once(dirname(__FILE__) . "/upgrade_album.php");
			exit;
		}
	}
}
?>