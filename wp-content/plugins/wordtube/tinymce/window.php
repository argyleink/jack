<?php

/*
+----------------------------------------------------------------+
+	wordtube-tinymce V1.60
+	by Alex Rabe
+----------------------------------------------------------------+
*/

// look up for the path
require_once( dirname( dirname(__FILE__) ) .'/wordtube-config.php');

// check for rights
if ( !is_user_logged_in() || !current_user_can('edit_posts') ) 
	wp_die(__("You are not allowed to be here"));

global $wpdb;

// get the options
$options = get_option('wordtube_options');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>wordTube</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/utils/mctabs.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/utils/form_utils.js"></script>
	<script language="javascript" type="text/javascript">
	function init() {
		tinyMCEPopup.resizeToInnerSize();
	}
	
	function insertwpTubeLink() {
		
		var tagtext;
		
		var media = document.getElementById('media_panel');
		var playlist = document.getElementById('playlist_panel');
		
		// who is active ?
		if (media.className.indexOf('current') != -1) {
			var mediaid = document.getElementById('mediatag').value;
			var mediaWidth = document.getElementById('mediaWidth').value;
			var mediaHeight = document.getElementById('mediaHeight').value;
			var new_width = "";
			var new_height = "";
			
			if (mediaWidth != 0 )
				new_width = " width=" + mediaWidth;

			if (mediaHeight != 0 )
				new_height = " height=" + mediaHeight;
			
			if (mediaid != 0 )
				tagtext = "[media id=" + mediaid + new_width + new_height + "]";
			else
				tinyMCEPopup.close();
		}
	
		if (playlist.className.indexOf('current') != -1) {
			var playlistid = document.getElementById('playlist').value;
			var plyWidth = document.getElementById('plyWidth').value;
			var plyHeight = document.getElementById('plyHeight').value;
			var new_width = "";
			var new_height = "";
			
			if (plyWidth != 0 )
				new_width = " width=" + plyWidth;

			if (plyHeight != 0 )
				new_height = " height=" + plyHeight;
			
			tagtext = "[playlist id=" + playlistid + new_width + new_height + "]";
		}
		
		if(window.tinyMCE) {
			window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, tagtext);
			//Peforms a clean up of the current editor HTML. 
			//tinyMCEPopup.editor.execCommand('mceCleanup');
			//Repaints the editor. Sometimes the browser has graphic glitches. 
			tinyMCEPopup.editor.execCommand('mceRepaint');
			tinyMCEPopup.close();
		}
		
		return;
	}
	</script>
	<base target="_self" />
</head>
<body id="link" onload="tinyMCEPopup.executeOnLoad('init();');document.body.style.display='';document.getElementById('mediatag').focus();" style="display: none">
<!-- <form onsubmit="insertLink();return false;" action="#"> -->
	<form name="wordTube" action="#">
	<div class="tabs">
		<ul>
			<li id="media_tab" class="current"><span><a href="javascript:mcTabs.displayTab('media_tab','media_panel');" onmousedown="return false;"><?php _e("Media", 'wpTube'); ?></a></span></li>
			<li id="playlist_tab"><span><a href="javascript:mcTabs.displayTab('playlist_tab','playlist_panel');" onmousedown="return false;"><?php _e("Playlist", 'wpTube'); ?></a></span></li>
		</ul>
	</div>
	
	<div class="panel_wrapper">
		<!-- media panel -->
		<div id="media_panel" class="panel current">
		<br />
		<table border="0" cellpadding="4" cellspacing="0">
         <tr>
            <td nowrap="nowrap"><label for="mediatag"><?php _e("Select media file", 'wpTube'); ?></label></td>
            <td><select id="mediatag" name="mediatag" style="width: 190px">
                <option value="0"><?php _e("No file", 'wpTube'); ?></option>
                <option value="last"><?php _e("Last media", 'wpTube'); ?></option>
                <option value="random"><?php _e("Random media", 'wpTube'); ?></option>
				<?php
					$tables = $wpdb->get_results("SELECT * FROM $wpdb->wordtube ORDER BY vid DESC ");
					if($tables) {
						foreach($tables as $table) {
						echo '<option value="'.$table->vid.'">'.$table->name.'</option>'; 
						}
					}
				?>	
            </select></td>
          </tr>
          <tr>
            <td nowrap="nowrap"><?php _e("Width x Height", 'nggallery'); ?></td>
            <td>
				<input type="text" size="5" id="mediaWidth" name="mediaWidth" value="<?php echo $options['media_width']; ?>" /> x <input type="text" size="5" id="mediaHeight" name="mediaHeight" value="<?php echo $options['media_height']; ?>" /><br /> <?php _e("(0 = default value )", 'wpTube'); ?>
			</td>
          </tr>
        </table>
		</div>
		<!-- media panel -->
		
		<!-- playlist panel -->
		<div id="playlist_panel" class="panel">
		<br />
		<table border="0" cellpadding="4" cellspacing="0">
         <tr>
            <td nowrap="nowrap"><label for="playlist"><?php _e("Select playlist", 'wpTube'); ?></label></td>
            <td><select id="playlist" name="playlist" style="width: 190px">
                <option value="0"><?php _e("All files", 'wpTube'); ?></option>
				<?php
					$tables = $wpdb->get_results("SELECT * FROM $wpdb->wordtube_playlist ORDER BY pid DESC ");
					if($tables) {
						foreach($tables as $table) {
						echo '<option value="'.$table->pid.'">'.$table->playlist_name.'</option>'; 
						}
					}
				?>		
            </select></td>
          </tr>
          <tr>
            <td nowrap="nowrap"><?php _e("Width x Height", 'nggallery'); ?></td>
            <td><input type="text" size="5" id="plyWidth" name="plyWidth" value="<?php echo $options['width']; ?>" /> x <input type="text" size="5" id="plyHeight" name="plyHeight" value="<?php echo $options['height']; ?>" /></td>
          </tr>
        </table>
		</div>
		<!-- playlist panel -->
	</div>

	<div class="mceActionPanel">
		<div style="float: left">
			<input type="button" id="cancel" name="cancel" value="<?php _e("Cancel", 'wpTube'); ?>" onclick="tinyMCEPopup.close();" />
		</div>

		<div style="float: right">
			<input type="submit" id="insert" name="insert" value="<?php _e("Insert", 'wpTube'); ?>" onclick="insertwpTubeLink();" />
		</div>
	</div>
</form>
</body>
</html>
<?php

?>
