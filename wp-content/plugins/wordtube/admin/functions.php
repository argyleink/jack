<?php
/*
+----------------------------------------------------------------+
+	wordtube-admin-functions
+	by Alex Rabe & Alakhnor
+   required for wordtube
+----------------------------------------------------------------+
*/

/******************************************************************
/* get_playlist by ID
******************************************************************/
function get_playlistname_by_ID($pid = 0) {
	global $wpdb;
	
	$pid    = (int) $pid;
	$result = $wpdb->get_var( $wpdb->prepare("SELECT playlist_name FROM $wpdb->wordtube_playlist WHERE pid = %d ", $pid) ); 
	
	return $result; 
}
/******************************************************************
/* return filename of a complete url
******************************************************************/
function wpt_filename($urlpath) {
	$filename = substr(($t=strrchr($urlpath,'/'))!==false?$t:'',1);
	return $filename;
}
/******************************************************************
/* get_playlist output für DBX
******************************************************************/
function get_playlist_for_dbx($mediaid) {
	
	global $wpdb;

	// get playlist ID's 
	$playids = $wpdb->get_col("SELECT pid FROM $wpdb->wordtube_playlist");

	// to be sure
	$mediaid = (int) $mediaid;
	
	// get checked ID's'
	$checked_playlist = $wpdb->get_col( $wpdb->prepare("
		SELECT playlist_id
		FROM $wpdb->wordtube_playlist, $wpdb->wordtube_med2play
		WHERE $wpdb->wordtube_med2play.playlist_id = pid AND $wpdb->wordtube_med2play.media_id = '%d'
		", $mediaid) );
		
	if (count($checked_playlist) == 0) $checked_playlist[] = 0;
		
	$result = array ();
	
	// create an array with playid, checked status and name
	if (is_array($playids)) {
		foreach ($playids as $playid) {
			$result[$playid]['playid'] = $playid;
			$result[$playid]['checked'] = in_array($playid, $checked_playlist);
			$result[$playid]['name'] = get_playlistname_by_ID($playid);
		}
	} 
	
	foreach ($result as $playlist) {
		
		echo '<label for="playlist-'.$playlist['playid']
			.'" class="selectit"><input value="'.$playlist['playid']
			.'" type="checkbox" name="playlist[]" id="playlist-'.$playlist['playid']
			.'"'.($playlist['checked'] ? ' checked="checked"' : "").'/> '. esc_html($playlist['name'])."</label>\n";		

	}
}
/****************************************************************/
/* Add video
/****************************************************************/
function wt_add_media($wptfile_abspath, $wp_urlpath) {
	global $wpdb;
	
	// Get input informations from POST
	$act_name 		= trim($_POST['name']);
	$act_creator 	= trim($_POST['creator']);
	$act_desc	 	= trim($_POST['description']);
	$act_filepath 	= trim($_POST['filepath']);
	$act_image 		= trim($_POST['urlimage']);
	$act_link		= '';
	$act_width 		= 320;
	$act_height 	= 240;
	$act_counter 	= (int) $_POST['act_counter'];
	$act_autostart 	= $_POST['autostart'];
	$disableAds 	= $_POST['disableAds'];
	if (empty($act_autostart)) $act_autostart = 0; // need now for sql_mode, see http://bugs.mysql.com/bug.php?id=18551
	if (empty($disableAds)) $disableAds = 0;
	$act_playlist 	= $_POST['playlist'];
	$act_tags 		= trim($_POST['act_tags']);
	
	if ($act_height < 20 ) $act_height = 20 ;
	if ($act_width == 0 ) $act_width = 320 ;
			
	$upload_path_video = trailingslashit($wptfile_abspath).sanitize_file_name(htmlspecialchars(stripslashes($_FILES['video_file']['name']), ENT_QUOTES));  // set upload path
	$upload_path_image = trailingslashit($wptfile_abspath).sanitize_file_name($_FILES['image_file']['name']);  // set upload path

	if (!empty($act_filepath)) {
		$ytb_pattern = "@youtube.com\/watch\?v=([0-9a-zA-Z_-]*)@i";
		if ( preg_match($ytb_pattern, stripslashes($act_filepath), $match) ) {
			$youtube_data = wt_GetSingleYoutubeVideo($match[1]);
			if ( $youtube_data ) {
				if ($act_name == '') 	$act_name = $youtube_data['title'];
				if ($act_creator == '') $act_creator = $youtube_data['author_name'];
				if ($act_desc == '') 	$act_desc = $youtube_data['description'];
				if ($act_image == '') 	$act_image = $youtube_data['thumbnail_url'];
				if ($act_tags == '') 	$act_tags = $youtube_data['tags'];
				if ($act_link == '') 	$act_link = $act_filepath;
			} else		
		 		wordTubeAdmin::render_error( __('Could not retrieve Youtube video information','wpTube'));
		}
	} else {

		if($_FILES['video_file']['error'] == 0 && $upload_path_video != '') {
	
			move_uploaded_file($_FILES['video_file']['tmp_name'], $upload_path_video); // save temp file
			@chmod ($upload_path_video, 0666) or die ('<div class="updated"><p><strong>'.__('Unable to change permissions for file ', 'wpTube').$upload_path_video.'!</strong></p></div>');
			if (empty($act_name)) $act_name = sanitize_file_name($_FILES['video_file']['name']);
			if (file_exists($upload_path_video)) {
		 	 	$act_filepath = trailingslashit($wp_urlpath).sanitize_file_name($_FILES['video_file']['name']);
				if($_FILES['image_file']['error']== 0) {
				 	move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_path_image); // save temp file
					@chmod ($upload_path_image, 0666) or die ('<div class="updated"><p><strong>'.__('Unable to change permissions for file ', 'wpTube').$upload_path_image.'!</strong></p></div>');
				 	if (file_exists($upload_path_image)) 
						$act_image = trailingslashit($wp_urlpath).sanitize_file_name($_FILES['image_file']['name']);
				}	
			} else 
				wordTubeAdmin::render_error(__('ERROR : File cannot be saved. Check the permission of the wordpress upload folder','wpTube'));
	 	} else
	 		wordTubeAdmin::render_error(__('ERROR : Upload failed. Check the file size','wpTube'));
	}

	$insert_video = $wpdb->query( $wpdb->prepare("INSERT INTO $wpdb->wordtube ( name, creator, description, file, image, width, height, link, autostart, disableAds, counter )
	VALUES ( %s, %s, %s, %s, %s, %d, %d, %s, %d, %d, %d )", $act_name, $act_creator, $act_desc, $act_filepath, $act_image, $act_width, $act_height, $act_link, $act_autostart, $disableAds, $act_counter ));
	if ($insert_video != 0) {
 		$video_aid = $wpdb->insert_id;  // get index_id

        //hook for other plugin to update the fields
        do_action('wordtube_update_media_meta', $video_aid, $_POST);
        
		$tags = explode(',',$act_tags);
		wp_set_object_terms($video_aid, $tags, WORDTUBE_TAXONOMY);
		
		wordTubeAdmin::render_message(__('Media file','wpTube').' '.$video_aid.__(' added successfully','wpTube'));
	}

		
	// Add any link to playlist?
	if ($video_aid && is_array($act_playlist)) {
		$add_list = array_diff($act_playlist, array());

		if ($add_list) {
			foreach ($add_list as $new_list) {
				$wpdb->query( $wpdb->prepare("INSERT INTO $wpdb->wordtube_med2play (media_id, playlist_id) VALUES (%d, %d)", $video_aid, $new_list));
			}
		}
	}
	
	return;
}

/**
 * wt_update_media() - Call from Manage screen update update the media data
 * 
 * @param int $media_id
 * @return void
 */
function wt_update_media( $media_id ) {
	global $wpdb;

	// read the $_POST values
	$act_name 		=	trim($_POST['act_name']);
	$act_creator 	=	trim($_POST['act_creator']);
	$act_desc 		=	trim($_POST['act_desc']);
	$act_filepath 	= 	trim($_POST['act_filepath']);
	$act_image 		=	trim($_POST['act_image']);
	$act_link 		=	trim($_POST['act_link']);
	$act_counter 	=	(int) ($_POST['act_counter']);

	$act_autostart 	= 	isset( $_POST['autostart'] );
	$act_playlist 	= 	isset( $_POST['playlist'] ) ? (array)$_POST['playlist'] : array() ;
	$disableAds 	=	isset( $_POST['disableAds'] );

	// Update tags
	$act_tags 	= trim($_POST['act_tags']);
	$tags = explode(',',$act_tags);
	wp_set_object_terms( $media_id, $tags, WORDTUBE_TAXONOMY);

	if (empty($act_autostart)) 
        $act_autostart = 0; // need now for sql_mode, see http://bugs.mysql.com/bug.php?id=18551
							
	// Read the old playlist status
	$old_playlist = $wpdb->get_col( $wpdb->prepare("SELECT playlist_id FROM $wpdb->wordtube_med2play WHERE media_id = %d", $media_id));
	$old_playlist = ($old_playlist == false) ? array() : array_unique($old_playlist);
   
	// Delete any ?
	$delete_list = array_diff($old_playlist, $act_playlist);

	if ($delete_list) {
		foreach ($delete_list as $del) {
			$wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->wordtube_med2play WHERE playlist_id = $del AND media_id = %d", $media_id ));
		}
	}
				
	// Add any? 
	$add_list = array_diff($act_playlist, $old_playlist);

	if ($add_list) {
		foreach ($add_list as $new_list) {
			$wpdb->query( $wpdb->prepare("INSERT INTO $wpdb->wordtube_med2play (media_id, playlist_id) VALUES (%d, %d)", $media_id, $new_list));
		}
	}
				
	if(!empty($act_filepath)) {
		$result = $wpdb->query( $wpdb->prepare("UPDATE $wpdb->wordtube SET name=%s, creator=%s, description=%s, file=%s , image=%s , link=%s , autostart=%d , counter=%d, disableAds=%d WHERE vid = %d"
        , $act_name, $act_creator, $act_desc, $act_filepath, $act_image, $act_link, $act_autostart, $act_counter, $disableAds, $media_id ));
	}

    //hook for other plugin to update the fields
    do_action('wordtube_update_media_meta', $media_id, $_POST);

	// Finished
	
	wordTubeAdmin::render_message(__('Update Successfully','wpTube'));
	return;

}
/****************************************************************/
/* Delete media
/****************************************************************/
function wt_delete_media($act_vid, $deletefile) {
	global $wpdb;
		
 	// Delete file
	if ($deletefile) {

		$act_videoset = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->wordtube WHERE vid=%d", $act_vid) );

		$act_filename = wpt_filename($act_videoset->file);
		$abs_filename = str_replace(trailingslashit(get_option('siteurl')), ABSPATH, trim($act_videoset->file));
		if (!empty($act_filename)) {
				
			$wpt_checkdel = @unlink($abs_filename);
			if(!$wpt_checkdel) wordTubeAdmin::render_error (__('Error in deleting file','wpTube'));					
		}
			
		$act_filename = wpt_filename($act_videoset->image);
		$abs_filename = str_replace(trailingslashit(get_option('siteurl')), ABSPATH, trim($act_videoset->image));
		if (!empty($act_filename)) {
				
			$wpt_checkdel = @unlink($abs_filename);
			if(!$wpt_checkdel) wordTubeAdmin::render_error( __('Error in deleting file','wpTube') );
		}
	} 

	//TODO: The problem of this routine : if somebody change the path, after he uploaded some files

	$wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->wordtube_med2play WHERE media_id = %d", $act_vid) );
			
	$delete_video = $wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->wordtube WHERE vid = %d", $act_vid) );
	// Delete tag relationships
	wp_delete_object_term_relationships($act_vid, WORDTUBE_TAXONOMY);
			
	if(!$delete_video)
	 	wordTubeAdmin::render_error(  __('Error in deleting media file','wpTube') );

	if(empty($text))
		wordTubeAdmin::render_message( __('Media file','wpTube').' \''.$act_vid.'\' '.__('deleted successfully','wpTube'));

	return;
}
/****************************************************************/
/* Add Playlist
/****************************************************************/
function wt_add_playlist() {
	global $wpdb;

	// Get input informations from POST
	$p_name = trim($_POST['p_name']);
	$p_description = trim($_POST['p_description']);
	$p_playlistorder = $_POST['sortorder'];
	if (empty($p_playlistorder)) $p_playlistorder = "ASC";

	// Add playlist in db
	if(!empty($p_name)) {
		$insert_plist = $wpdb->query( $wpdb->prepare("INSERT INTO $wpdb->wordtube_playlist (playlist_name, playlist_desc, playlist_order) VALUES (%s, %s, %s)", $p_name, $p_description, $p_playlistorder )); 
		if ($insert_plist != 0) {
	 		$pid = $wpdb->insert_id;  // get index_id
			wordTubeAdmin::render_message( __('Playlist','wpTube').' '.$pid.__(' added successfully','wpTube'));
		}
	}
	
	return;
}
/****************************************************************/
/* Update Playlist
/****************************************************************/
function wt_update_playlist() {
	global $wpdb;

	// Get input informations from POST
	$p_id = (int) ($_POST['p_id']);
	$p_name = trim($_POST['p_name']);
	$p_description = trim($_POST['p_description']);
	$p_playlistorder = $_POST['sortorder'];
	$p_pmedia_sortorder = trim($_POST['pmedia_sortorder']);

	// First update the sort order of media playlists
	if(!empty($p_pmedia_sortorder)) {
		foreach(explode(':', $p_pmedia_sortorder) As $order => $vid) {
			$order++;
			$wpdb->query( $wpdb->prepare("UPDATE $wpdb->wordtube_med2play SET porder=%s WHERE playlist_id=%d AND media_id=%d ", $order, $p_id, $vid ));
		}
	}

	// Now store the playlist details
	if(!empty($p_name)) {
		$wpdb->query( $wpdb->prepare("UPDATE $wpdb->wordtube_playlist SET playlist_name = %s, playlist_desc = %s, playlist_order = %s WHERE pid = %d ", $p_name, $p_description, $p_playlistorder, $p_id));
		wordTubeAdmin::render_message( __('Update Successfully','wpTube'));
	}

	return;
}
/****************************************************************/
/* Delete Playlist
/****************************************************************/
function wt_delete_playlist($act_pid) {
	global $wpdb;

	$text = '';

 	$delete_plist = $wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->wordtube_playlist WHERE pid = %d", $act_pid));
	if($delete_plist) {
		wordTubeAdmin::render_message( __('Playlist','wpTube').' \''.$act_pid.'\' '.__('deleted successfully','wpTube'));
	}

	return;
}
/****************************************************************/
/* Return Youtube single video
/****************************************************************/
function wt_GetSingleYoutubeVideo($youtube_media) {
	if ($youtube_media=='') return;
	$url = 'http://gdata.youtube.com/feeds/api/videos/'.$youtube_media;
	$ytb = wt_ParseYoutubeDetails(wt_GetYoutubePage($url));
	return $ytb[0];
}
/****************************************************************/
/* Parse xml from Youtube
/****************************************************************/
function wt_ParseYoutubeDetails( $ytVideoXML ) {

	// Create parser, fill it with xml then delete it
	$yt_xml_parser = xml_parser_create();
	xml_parse_into_struct($yt_xml_parser, $ytVideoXML, $yt_vals);
	xml_parser_free($yt_xml_parser);
	// Init individual entry array and list array
	$yt_video = array();
	$yt_vidlist = array();

	// is_entry tests if an entry is processing
	$is_entry = true;
	// is_author tests if an author tag is processing
	$is_author = false;
	foreach ($yt_vals as $yt_elem) {

		// If no entry is being processed and tag is not start of entry, skip tag
		if (!$is_entry && $yt_elem['tag'] != 'ENTRY') continue;

		// Processed tag
		switch ($yt_elem['tag']) {
			case 'ENTRY' :
				if ($yt_elem['type'] == 'open') {
					$is_entry = true;
                                        $yt_video = array();
				} else {
					$yt_vidlist[] = $yt_video;
					$is_entry = false;
				}
			break;
			case 'ID' :
				$yt_video['id'] = substr($yt_elem['value'],-11);
				$yt_video['link'] = $yt_elem['value'];
			break;
			case 'PUBLISHED' :
				$yt_video['published'] = substr($yt_elem['value'],0,10).' '.substr($yt_elem['value'],11,8);
			break;
			case 'UPDATED' :
				$yt_video['updated'] = substr($yt_elem['value'],0,10).' '.substr($yt_elem['value'],11,8);
			break;
			case 'MEDIA:TITLE' :
				$yt_video['title'] = $yt_elem['value'];
			break;
			case 'MEDIA:KEYWORDS' :
				$yt_video['tags'] = $yt_elem['value'];
			break;
			case 'MEDIA:DESCRIPTION' :
				$yt_video['description'] = $yt_elem['value'];
			break;
			case 'MEDIA:CATEGORY' :
				$yt_video['category'] = $yt_elem['value'];
			break;
			case 'YT:DURATION' :
				$yt_video['duration'] = $yt_elem['attributes'];
			break;
			case 'MEDIA:THUMBNAIL' :
				if ($yt_elem['attributes']['HEIGHT'] == 240) {
					$yt_video['thumbnail'] = $yt_elem['attributes'];
					$yt_video['thumbnail_url'] = $yt_elem['attributes']['URL'];
				}
			break;
			case 'YT:STATISTICS' :
				$yt_video['viewed'] = $yt_elem['attributes']['VIEWCOUNT'];
			break;
			case 'GD:RATING' :
				$yt_video['rating'] = $yt_elem['attributes'];
			break;
			case 'AUTHOR' :
				$is_author = ($yt_elem['type'] == 'open');
			break;
			case 'NAME' :
				if ($is_author) $yt_video['author_name'] = $yt_elem['value'];
			break;
			case 'URI' :
				if ($is_author) $yt_video['author_uri'] = $yt_elem['value'];
			break;
			default :
		}
  	}
  	
  	unset($yt_vals);
  
	return $yt_vidlist;
}
/****************************************************************/
/* Returns content of a remote page
/* Still need to do it without curl
/****************************************************************/
function wt_GetYoutubePage($url) {

	// Try to use curl first
	if (function_exists('curl_init')) {
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		$xml = curl_exec ($ch);
		curl_close ($ch);
	}
	// If not found, try to use file_get_contents (requires php > 4.3.0 and allow_url_fopen)
	else {
		$xml = @file_get_contents($url);
	}

	return $xml;
}

?>
