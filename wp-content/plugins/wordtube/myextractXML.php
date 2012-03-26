<?php
/*
+----------------------------------------------------------------+
+	wordtube-XSPF XML output for playlist
+	by Alex Rabe reviewed by Alakhnor
+	required for wordTube
+----------------------------------------------------------------+
*/

// look up for the path
if ( !defined('ABSPATH') ) 
    require_once( dirname(__FILE__) . '/wordtube-config.php');

global $wpdb;

// get the path url from querystring
$playlist_id = $_GET['id'];

$title = 'WordTube Playlist';

$themediafiles = array();
$limit = '';

if (substr($playlist_id,0,4) == 'last') {
	$l= (int) substr($playlist_id,4);
	if ($l <= 0) $l = 10;
	$limit = ' LIMIT 0,'.$l;
	$playlist_id = '0';
}

// Otherwise gets most viewed
if ($playlist_id == 'most') {
	$themediafiles = $wpdb->get_results("SELECT * FROM $wpdb->wordtube WHERE counter >0 ORDER BY counter DESC LIMIT 10");
// Otherwise gets mp3
} elseif ($playlist_id == 'music') {
	$themediafiles = $wpdb->get_results("SELECT * FROM $wpdb->wordtube WHERE file LIKE '%.mp3%' ORDER BY vid DESC");
// Otherwise gets flv
} elseif ($playlist_id == 'video') {
	$themediafiles = $wpdb->get_results("SELECT * FROM $wpdb->wordtube WHERE file LIKE '%.flv%' ORDER BY vid DESC");
// Shows all files when 0
} elseif ($playlist_id == '0') {
	$themediafiles = $wpdb->get_results( $wpdb->prepare ("SELECT * FROM $wpdb->wordtube ORDER BY vid DESC {$limit}") );
// Otherwise gets playlist
} else {
	// Remove all evil code
	$playlist_id = intval($_GET['id']);
 	$playlist = $wpdb->get_row("SELECT * FROM $wpdb->wordtube_playlist WHERE pid = '$playlist_id'");
 	if ($playlist) {
		$select  = " SELECT * FROM {$wpdb->wordtube} w";
		$select .= " INNER JOIN {$wpdb->wordtube_med2play} m";
		$select .= " WHERE (m.playlist_id = '$playlist_id'" ;
		$select .= " AND m.media_id = w.vid) GROUP BY w.vid ";
		$select .= " ORDER BY m.porder ".$playlist->playlist_order." ,w.vid ".$playlist->playlist_order;
		$themediafiles = $wpdb->get_results( $wpdb->prepare( $select ) );
	 	$title = $playlist->playlist_name;
	}
}

// Create XML / XSPF output
header("content-type:text/xml;charset=utf-8");
	
echo "\n"."<playlist version='1' xmlns='http://xspf.org/ns/0/'>";
echo "\n\t".'<title>' . esc_attr($title) . '</title>';
echo "\n\t".'<trackList>';
	
if (is_array ($themediafiles)){

	foreach ($themediafiles as $media) {
		
                $creator = esc_attr(stripslashes($media->creator));
                if ($creator == '') 
					$creator = 'Unknown';
                if ($media->image == '') 
					$image = get_option('siteurl') . '/wp-content/plugins/' . dirname( plugin_basename(__FILE__) ) . '/images/wordtube.jpg';
				else 
					$image = $media->image;
  				$file = pathinfo($media->file);

		echo "\n\t\t".'<track>';
		echo "\n\t\t\t".'<title>' . esc_attr( stripslashes($media->name) ) . '</title>';
		echo "\n\t\t\t".'<creator>' . esc_attr($creator) . '</creator>';
		echo "\n\t\t\t".'<location>' . esc_attr($media->file) . '</location>';
		echo "\n\t\t\t".'<image>' . esc_attr($image) . '</image>';
		echo "\n\t\t\t".'<annotation>' . esc_attr( stripslashes($media->description) ) .  '</annotation>';
		echo "\n\t\t\t".'<id>' . $media->vid . '</id>';
		echo "\n\t\t\t".'<counter>' . $media->counter . '</counter>';
		echo "\n\t\t\t".'<info>' . esc_attr($media->link) . '</info>';
		echo "\n\t\t".'</track>';
	}
}
	 
echo "\n\t".'</trackList>';
echo "\n"."</playlist>\n";	

?>