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
 * $Id: gpl.txt,v 1.6 2004/02/03 05:03:38 beckettmw Exp $
 */
?>
<?php
/* 
 * Protect against very old versions of 4.0 (like 4.0RC1) which  
 * don't implicitly create a new stdClass() when you use a variable 
 * like a class. 
 */ 
if (!isset($gallery)) { 
        $gallery = new stdClass(); 
}
if (!isset($gallery->app)) { 
        $gallery->app = new stdClass(); 
}

/* Version  */
$gallery->app->config_version = '83';

/* Features */
$gallery->app->feature["zip"] = 1;
$gallery->app->feature["rewrite"] = 1;
$gallery->app->feature["mirror"] = 1;

/* Constants */
$gallery->app->galleryTitle = "Gallery";
$gallery->app->skinname = "none";
$gallery->app->uploadMode = "form";
$gallery->app->albumDir = "C:/Program Files/Apache Group/Apache2/htdocs/modules/gallery/albums";
$gallery->app->tmpDir = "C:/WINDOWS/TEMP";
$gallery->app->photoAlbumURL = "http://localhost/modules/gallery";
$gallery->app->albumDirURL = "http://localhost/modules/gallery/albums";
// optional <i>watermarkDir</i> missing
$gallery->app->watermarkSizes = "0";
$gallery->app->movieThumbnail = "C:\Program Files\Apache Group\Apache2\htdocs\modules\Gallery/images/movie.thumb.jpg";
// optional <i>mirrorSites</i> missing
$gallery->app->graphics = "ImageMagick";
// optional <i>pnmDir</i> missing
$gallery->app->pnmtojpeg = "pnmtojpeg";
$gallery->app->ImPath = "C:/Program Files/ImageMagick";
$gallery->app->autorotate = "yes";
$gallery->app->jpegImageQuality = "90";
// optional <i>geeklog_dir</i> missing
$gallery->app->showAlbumTree = "no";
$gallery->app->highlight_size = "200";
$gallery->app->showOwners = "no";
$gallery->app->albumsPerPage = "5";
$gallery->app->showSearchEngine = "yes";
$gallery->app->slowPhotoCount = "no";
$gallery->app->gallery_thumb_frame_style = "simple_book";
// optional <i>zipinfo</i> missing
// optional <i>unzip</i> missing
// optional <i>rar</i> missing
// optional <i>use_exif</i> missing
$gallery->app->cacheExif = "no";
// optional <i>use_jpegtran</i> missing
$gallery->app->default_language = "en_US";
$gallery->app->ML_mode = "3";
$gallery->app->available_lang[] = "en_US";
$gallery->app->show_flags = "no";
$gallery->app->dateString = "%x";
$gallery->app->dateTimeString = "%c";
$gallery->app->locale_alias['en_US'] = "eng";
$gallery->app->locale_alias['af_ZA'] = "af";
$gallery->app->locale_alias['bg_BG'] = "bulgarian";
$gallery->app->locale_alias['ca_ES'] = "ca";
$gallery->app->locale_alias['cs_CZ.iso-8859-2'] = "czech";
$gallery->app->locale_alias['da_DK'] = "da";
$gallery->app->locale_alias['de_DE'] = "german";
$gallery->app->locale_alias['es_ES'] = "es";
$gallery->app->locale_alias['fi_FI'] = "fi";
$gallery->app->locale_alias['fr_FR'] = "fr";
$gallery->app->locale_alias['hu_HU'] = "hu";
$gallery->app->locale_alias['is_IS'] = "icelandic";
$gallery->app->locale_alias['it_IT'] = "it";
$gallery->app->locale_alias['nl_NL'] = "dutch";
$gallery->app->locale_alias['no_NO'] = "no";
$gallery->app->locale_alias['pl_PL'] = "polish";
$gallery->app->locale_alias['pt_BR'] = "portuguese_brazil";
$gallery->app->locale_alias['pt_PT'] = "portuguese";
$gallery->app->locale_alias['ru_RU.cp1251'] = "ru";
$gallery->app->locale_alias['sl_SI'] = "sl";
$gallery->app->locale_alias['sv_SE'] = "swedish";
$gallery->app->locale_alias['tr_TR'] = "turkish";
$gallery->app->locale_alias['uk_UA'] = "uk";
$gallery->app->locale_alias['cs_CZ.cp1250'] = "eng";
$gallery->app->locale_alias['en_GB'] = "eng";
$gallery->app->locale_alias['gl_ES'] = "eng";
$gallery->app->locale_alias['he_IL.utf8'] = "eng";
$gallery->app->locale_alias['ja_JP'] = "eng";
$gallery->app->locale_alias['ko_KR'] = "eng";
$gallery->app->locale_alias['lt_LT'] = "eng";
$gallery->app->locale_alias['ru_RU.koi8r'] = "eng";
$gallery->app->locale_alias['vi_VN'] = "eng";
$gallery->app->locale_alias['zh_CN'] = "eng";
$gallery->app->locale_alias['zh_TW'] = "eng";
$gallery->app->locale_alias['zh_TW.utf8'] = "eng";
$gallery->app->emailOn = "no";
// optional <i>adminEmail</i> missing
// optional <i>senderEmail</i> missing
$gallery->app->emailSubjPrefix = "[Gallery]";
// optional <i>emailGreeting</i> missing
$gallery->app->selfReg = "no";
$gallery->app->selfRegCreate = "no";
$gallery->app->multiple_create = "no";
$gallery->app->adminCommentsEmail = "no";
$gallery->app->adminOtherChangesEmail = "no";
// optional <i>email_notification</i> missing
$gallery->app->useOtherSMTP = "no";
$gallery->app->smtpHost = "localhost";
$gallery->app->smtpFromHost = "localhost";
$gallery->app->smtpPort = "25";
// optional <i>smtpUserName</i> missing
// optional <i>smtpPassword</i> missing
$gallery->app->gallery_slideshow_type = "off";
$gallery->app->gallery_slideshow_length = "20";
$gallery->app->gallery_slideshow_loop = "yes";
$gallery->app->slideshowMode = "high";
$gallery->app->comments_enabled = "yes";
$gallery->app->comments_indication = "photos";
$gallery->app->comments_indication_verbose = "no";
$gallery->app->comments_anonymous = "no";
$gallery->app->comments_display_name = "!!FULLNAME!! (!!USERNAME!!)";
$gallery->app->comments_addType = "popup";
$gallery->app->comments_length = "300";
$gallery->app->comments_overview_for_all = "no";
$gallery->app->timeLimit = "30";
$gallery->app->blockRandomCache = "86400";
$gallery->app->blockRandomAttempts = "2";
$gallery->app->debug = "no";
$gallery->app->devMode = "no";
$gallery->app->useSyslog = "no";
$gallery->app->use_flock = "yes";
$gallery->app->expectedExecStatus = "0";
$gallery->app->sessionVar = "gallery_session";
$gallery->app->rssEnabled = "yes";
$gallery->app->rssMode = "basic";
// optional <i>rssHighlight</i> missing
$gallery->app->rssMaxAlbums = "25";
$gallery->app->rssVisibleOnly = "yes";
$gallery->app->rssDCDate = "no";
$gallery->app->rssBigPhoto = "no";
$gallery->app->rssPhotoTag = "yes";
$gallery->app->userDir = "C:/Program Files/Apache Group/Apache2/htdocs/modules/gallery/albums/.users";
$gallery->app->maximumAlbumDepth = "50";

/* Defaults */
$gallery->app->default["cols"] = "3";
$gallery->app->default["rows"] = "3";
$gallery->app->default["bordercolor"] = "black";
$gallery->app->default["border"] = "1";
$gallery->app->default["font"] = "arial";
$gallery->app->default["thumb_size"] = "150";
$gallery->app->default["resize_size"] = "640";
$gallery->app->default["resize_file_size"] = "0";
$gallery->app->default["max_size"] = "off";
$gallery->app->default["max_file_size"] = "0";
$gallery->app->default["useOriginalFileNames"] = "yes";
$gallery->app->default["add_to_beginning"] = "yes";
$gallery->app->default["fit_to_window"] = "yes";
$gallery->app->default["use_fullOnly"] = "no";
$gallery->app->default["print_photos"] = "";
$gallery->app->default["mPUSHAccount"] = "gallery";
$gallery->app->default["returnto"] = "yes";
$gallery->app->default["defaultPerms"] = "everybody";
$gallery->app->default["display_clicks"] = "yes";
$gallery->app->default["extra_fields"] = "Description";
$gallery->app->default["showDimensions"] = "no";
$gallery->app->default["item_owner_modify"] = "yes";
$gallery->app->default["item_owner_delete"] = "yes";
$gallery->app->default["item_owner_display"] = "no";
$gallery->app->default["voter_class"] = "Nobody";
$gallery->app->default["poll_type"] = "critique";
$gallery->app->default["poll_scale"] = "3";
$gallery->app->default["poll_hint"] = "Vote for this image";
$gallery->app->default["poll_show_results"] = "no";
$gallery->app->default["poll_num_results"] = "3";
$gallery->app->default["poll_orientation"] = "vertical";
$gallery->app->default["poll_nv_pairs"][0]["name"] = "Excellent";
$gallery->app->default["poll_nv_pairs"][0]["value"] = "5";
$gallery->app->default["poll_nv_pairs"][1]["name"] = "Very Good";
$gallery->app->default["poll_nv_pairs"][1]["value"] = "4";
$gallery->app->default["poll_nv_pairs"][2]["name"] = "Good";
$gallery->app->default["poll_nv_pairs"][2]["value"] = "3";
$gallery->app->default["poll_nv_pairs"][3]["name"] = "Average";
$gallery->app->default["poll_nv_pairs"][3]["value"] = "2";
$gallery->app->default["poll_nv_pairs"][4]["name"] = "Poor";
$gallery->app->default["poll_nv_pairs"][4]["value"] = "1";
$gallery->app->default["poll_nv_pairs"][5]["name"] = "";
$gallery->app->default["poll_nv_pairs"][5]["value"] = "";
$gallery->app->default["poll_nv_pairs"][6]["name"] = "";
$gallery->app->default["poll_nv_pairs"][6]["value"] = "";
$gallery->app->default["poll_nv_pairs"][7]["name"] = "";
$gallery->app->default["poll_nv_pairs"][7]["value"] = "";
$gallery->app->default["poll_nv_pairs"][8]["name"] = "";
$gallery->app->default["poll_nv_pairs"][8]["value"] = "";
$gallery->app->default["slideshow_type"] = "ordered";
$gallery->app->default["slideshow_recursive"] = "no";
$gallery->app->default["slideshow_loop"] = "yes";
$gallery->app->default["slideshow_length"] = "0";
$gallery->app->default["album_frame"] = "simple_book";
$gallery->app->default["thumb_frame"] = "solid";
$gallery->app->default["image_frame"] = "solid";
?>
