<?php
/*
+----------------------------------------------------------------+
+	wordtube-install
+	by Alex Rabe & Alakhnor
+   required for wordtube
+----------------------------------------------------------------+
*/
/****************************************************************/
/* Install routine for wordtube
/****************************************************************/
function wordtube_install() {

	global $wpdb;

		// set tablename
		$table_name 		= $wpdb->prefix . 'wordtube'; 		
		$table_playlist		= $wpdb->prefix . 'wordtube_playlist';
		$table_med2play		= $wpdb->prefix . 'wordtube_med2play';

		$wfound = false;
		$pfound = false;
		$mfound = false;
		$found = true;
	
	    foreach ($wpdb->get_results("SHOW TABLES;", ARRAY_N) as $row) {
	        	
			if ($row[0] == $table_name) 	$wfound = true;
			if ($row[0] == $table_playlist) $pfound = true;
			if ($row[0] == $table_med2play) $mfound = true;
		}
	        
    	// add charset & collate like wp core
		$charset_collate = '';
	
		if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') ) {
			if ( ! empty($wpdb->charset) )
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if ( ! empty($wpdb->collate) )
				$charset_collate .= " COLLATE $wpdb->collate";
		}
	        
		if (!$wfound) {
		 
			$sql = "CREATE TABLE ".$table_name." (
				vid MEDIUMINT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	      			name MEDIUMTEXT NULL,
	      			creator MEDIUMTEXT NULL,
					description LONGTEXT NULL,
	      			file MEDIUMTEXT NULL,
	      			image MEDIUMTEXT NULL,
	      			link MEDIUMTEXT NULL,
	      			width SMALLINT(5) NOT NULL,
	      			height SMALLINT(5) NOT NULL,
	      			autostart TINYINT(1) NULL DEFAULT '0',
					disableads TINYINT(1) NULL DEFAULT '0',
					counter MEDIUMINT(10) NULL DEFAULT '0'
	     			) $charset_collate;";
	     
			$res = $wpdb->get_results($sql);
		}
		
		if (!$pfound) {
		 
		 	$sql = "CREATE TABLE ".$table_playlist." (
				pid BIGINT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				playlist_name VARCHAR(200) NOT NULL ,
				playlist_desc LONGTEXT NULL,
				playlist_order VARCHAR(50) NOT NULL DEFAULT 'ASC'
				) $charset_collate;";
	     
			$res = $wpdb->get_results($sql);
		}

		if (!$mfound) {
		 
		 	$sql = "CREATE TABLE ".$table_med2play." (
				rel_id BIGINT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				media_id BIGINT(10) NOT NULL DEFAULT '0',
				playlist_id BIGINT(10) NOT NULL DEFAULT '0',
				porder MEDIUMINT(10) NOT NULL DEFAULT '0'
				) $charset_collate;";
	     
			$res = $wpdb->get_results($sql);
		}

		// update routine
		$result=$wpdb->query('SHOW COLUMNS FROM '.$table_name.' LIKE "creator"');
		if (!$result) {
			$wpdb->query("ALTER TABLE ".$table_name." ADD creator VARCHAR(255) NOT NULL AFTER name");
			$found = false;
		}
		
		$result=$wpdb->query('SHOW COLUMNS FROM '.$table_name.' LIKE "autostart"');
		if (!$result) {
			$wpdb->query("ALTER TABLE ".$table_name." ADD autostart TINYINT(1) NULL DEFAULT '0'");
			$found = false;
		}
		
		$result=$wpdb->query('SHOW COLUMNS FROM '.$table_name.' LIKE "counter"');
		if (!$result) {
			$wpdb->query("ALTER TABLE ".$table_name." ADD counter MEDIUMINT(10) NULL DEFAULT '0'");
			$found = false;
		}
		
		$result=$wpdb->query('SHOW COLUMNS FROM '.$table_name.' LIKE "exclude"');
		if ($result) {
			$wpdb->query("ALTER TABLE ".$table_name." CHANGE exclude autostart TINYINT(1) NULL DEFAULT '0'");
			$found = false;
		}
		
		// update to database v1.40
		$result=$wpdb->query('SHOW COLUMNS FROM '.$table_name.' LIKE "link"');
		if (!$result) {
	
			$wpdb->query("ALTER TABLE ".$table_name." ADD link MEDIUMTEXT NULL AFTER image ");
			$wpdb->query("ALTER TABLE ".$table_name." CHANGE creator creator MEDIUMTEXT NULL ");
			$wpdb->query("ALTER TABLE ".$table_name." CHANGE name name MEDIUMTEXT NULL ");
			$wpdb->query("ALTER TABLE ".$table_name." CHANGE file file MEDIUMTEXT NULL ");
			$wpdb->query("ALTER TABLE ".$table_name." CHANGE image image MEDIUMTEXT NULL ");
			$found = false;
		}

		// update to database v1.55
		$result=$wpdb->query('SHOW COLUMNS FROM '.$table_med2play.' LIKE "porder"');
		if (!$result) {
			$wpdb->query("ALTER TABLE ".$table_med2play." ADD porder MEDIUMINT(10) NOT NULL DEFAULT '0'");
			$found = false;
		}

		// update to database v2.00
		$result=$wpdb->query('SHOW COLUMNS FROM '.$table_name.' LIKE "description"');
		if (!$result) {
			$wpdb->query("ALTER TABLE ".$table_name." ADD description LONGTEXT NULL AFTER creator");
			$wpdb->query("ALTER TABLE ".$table_name." ADD disableads TINYINT(1) NULL DEFAULT '0' AFTER autostart");
		}
		
		$wt_options = wt_get_DefaultOption();
		$wt_options['version'] = WORDTUBE_VERSION;
		
		update_option('wordtube_options', $wt_options);		
		
}

// get the default options after reset or installation
function wt_get_DefaultOption() {

	$options = get_option('wordtube_options');

	if ($options['deletefile']=='') 		$options['deletefile'] = 0;
	if ($options['usewpupload']=='')		$options['usewpupload'] = 1;
	if ($options['uploadurl']=='') 			$options['uploadurl'] = get_option('upload_path');
	if ($options['autostart']=='') 			$options['autostart'] = 0;
	if ($options['repeat']=='')				$options['repeat'] = 'none';
	if ($options['overstretch']=='')		$options['overstretch'] = 'true';
	if ($options['showfsbutton']=='') 		$options['showfsbutton'] = 0;
	if ($options['smoothing']=='') 		    $options['smoothing'] = 1;
	if ($options['volume']=='')				$options['volume'] = 80;
	if ($options['bufferlength']=='') 		$options['bufferlength'] = 5;
	// new since 1.10
	if ($options['thumbnail']=='') 			$options['thumbnail'] = true;
	if ($options['width']=='') 				$options['width'] = 400;
	if ($options['height']=='') 			$options['height'] = 500;
	if ($options['playlistsize']=='') 		$options['playlistsize'] = 180;
	if ($options['shuffle']=='') 			$options['shuffle'] = false;
	// new since 1.30
	if ($options['usewatermark']=='') 		$options['usewatermark'] = false;
	if ($options['watermarkurl']=='') 		$options['watermarkurl'] = '';
	if ($options['xhtmlvalid']=='') 		$options['xhtmlvalid'] = false;
	if ($options['activaterss']=='')		$options['activaterss'] = false;
	if ($options['rssmessage']=='') 		$options['rssmessage'] = __('See post to watch Flash video','wpTube');
	// new since 2.00
	if ($options['agree_license']=='') 		$options['agree_license'] = false;
	if ($options['stretching']=='') 		$options['stretching'] = 'uniform';
	if ($options['quality']=='') 			$options['quality'] = false;
	if ($options['controlbar']=='') 		$options['controlbar'] = 'bottom';
	if ($options['skinurl']=='') 			$options['skinurl'] = '';
	if ($options['playlist']=='') 			$options['playlist'] = 'bottom';
	if ($options['activateAds']=='') 		$options['activateAds'] = false;
	if ($options['LTchannelID']=='') 		$options['LTchannelID'] = '';
	if ($options['media_width']=='') 		$options['media_width'] = 320;
	if ($options['media_height']=='') 		$options['media_height'] = 240;
	if ($options['backcolor']=='') 			$options['backcolor'] = 'FFFFFF';
	if ($options['frontcolor']=='') 		$options['frontcolor'] = '000000';
	if ($options['lightcolor']=='') 		$options['lightcolor'] = '000000';
	if ($options['screencolor']=='') 		$options['screencolor'] = '000000';
	// new since 2.2.0
	if ($options['plugins']=='') 			$options['plugins'] = '';
	if ($options['custom_vars']=='') 		$options['custom_vars'] = '';
    // new since 2.3.0 
    if ($options['startsingle']=='') 		$options['startsingle'] = false;
    if ($options['path']=='')               $options['path'] = 'wp-content/uploads/player.swf';
    
    // check for the player files
    if ( $path = wordTubeAdmin::search_file( 'player.swf' ) )
        $options['path'] = $path;        
	
	return $options;
}

?>