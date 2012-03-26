<?php  
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

	function wordtube_admin_options()  {
	
	global $wpdb;	

	// get the options
	$wt_options = get_option('wordtube_options');

	// same as $_SERVER['REQUEST_URI'], but should work under IIS 6.0
	$filepath    = admin_url() . 'admin.php?page=' . $_GET['page'];

    $border  = '';
    
	if ( isset($_POST['updateoption']) ) {	
		check_admin_referer('wt_settings');
		// get the hidden option fields, taken from WP core
		if ( $_POST['page_options'] )	
			$options = explode(',', stripslashes($_POST['page_options']));
			
		if ($options) {
			foreach ($options as $option) {
				$option = trim($option);
				$value = isset($_POST[$option]) ? trim($_POST[$option]) : false;
				$wt_options[$option] = $value;
			}
		}
		
		// Save options
		update_option('wordtube_options', $wt_options);
	 	wordTubeAdmin::render_message(__('Update Successfully','wpTube'));
	}
	
	if ( isset($_POST['resetdefault']) ) {
		check_admin_referer('wt_settings');

		require_once (dirname (__FILE__). '/install.php');
		
		delete_option( "wordtube_options" );
				
		$wt_options = wt_get_DefaultOption();
		$wt_options['version'] = WORDTUBE_VERSION;
		
		update_option('wordtube_options', $wt_options);
    		
		wordTubeAdmin::render_message(__('Reset all settings to default parameter', 'wpTube'));
	}

	if ( isset($_POST['uninstall']) ) {
		check_admin_referer('wt_settings');

		require_once (dirname (__FILE__) . '/install.php');
		
		delete_option( "wordtube_options" );
				
		$wpdb->query("DROP TABLE $wpdb->wordtube");
		$wpdb->query("DROP TABLE $wpdb->wordtube_playlist");
		$wpdb->query("DROP TABLE $wpdb->wordtube_med2play");
		
		wordTubeAdmin::render_message(__('Tables and settings deleted, deactivate the plugin now', 'wpTube'));
	}

    if ( !is_readable( ABSPATH . $wt_options['path'] ) || empty($wt_options['path']) ) {
        if ( $path = wordTubeAdmin::search_file( 'player.swf' ) ) {
            $wt_options['path'] = $path;
            update_option('wordtube_options', $wt_options);
        } else {
            $border = 'style="border-color:red; border-width:2px; border-style:solid; padding:5px;"';
            wordTubeAdmin::render_error( '<strong>' . __('Could not found player.swf, please verify the path or upload the file.', 'wpTube') . '</strong>' );            
        }

    }
    
	?>
	<script type="text/javascript">
		jQuery(function() {
			jQuery('#slider').tabs({ fxFade: true, fxSpeed: 'fast' });
		});
		function setcolor(fileid,color) {
			jQuery(fileid).css("background", color );
		};
	</script>
	
	<div id="slider" class="wrap">
	
		<ul id="tabs">
			<li><a href="#generaloptions"><?php _e('General Options', 'wpTube') ;?></a></li>
			<li><a href="#player"><?php _e('Media Player', 'wpTube') ;?></a></li>
			<li><a href="#playlist"><?php _e('Playlist', 'wpTube') ;?></a></li>
			<li><a href="#layout"><?php _e('Layout', 'wpTube') ;?></a></li>
			<li><a href="#longtail"><?php _e('LongTail Adsolution', 'wpTube') ;?></a></li>
			<li><a href="#setup"><?php _e('Setup', 'wpTube') ;?></a></li>
		</ul>

		<!-- General Options -->

		<div id="generaloptions">
			<h2><?php _e('General Options','wpTube'); ?></h2>
			<form name="generaloptions" method="post">
			<?php wp_nonce_field('wt_settings') ?>
			<input type="hidden" name="page_options" value="path,usewpupload,uploadurl,deletefile,xhtmlvalid,activaterss,rssmessage" />
				<table class="form-table">
                    <tr valign="top" <?php echo $border; ?>>
                        <th scope="row"><?php _e('Path to JW Media Player', 'wpTube'); ?></th>
    					<td>
                            <input type="text" size="60" name="path" value="<?php echo $wt_options['path']; ?>" />
                            <span class="description"><?php _e('Upload the flash player to your blog. Default is', 'wpTube'); ?> <code>wp-content/uploads/player.swf</code></span> 
                        </td>
    				</tr>
					<tr>
						<th valign="top"><?php _e('Upload folder','wpTube') ?>:</th>
						<td>
							<label><input name="usewpupload" type="radio" value="1" <?php checked( true, $wt_options['usewpupload']); ?> /> <?php _e('Standard upload folder : ','wpTube') ?></label><code><?php echo get_option('upload_path'); ?></code><br />
							<label><input name="usewpupload" type="radio" value="0" <?php checked( false, $wt_options['usewpupload']); ?> /> <?php _e('Store uploads in this folder : ','wpTube') ?></label>
							<input type="text" size="50" maxlength="200" name="uploadurl" value="<?php echo $wt_options['uploadurl'] ?>" />
						</td>
					</tr>
					<tr valign="top">
						<th><?php _e('Delete file with post','wpTube') ?></th>
						<td><input type="checkbox" name="deletefile" value="1" <?php checked('1', $wt_options['deletefile']); ?> />
						<span class="description"><?php _e('Should the media file be deleted, when pressing delete ? ','wpTube') ?></span></td>
					</tr>
					<tr>
						<th><?php _e('Try XHTML validation (with CDATA)','wpTube') ?>:</th>
						<td><input name="xhtmlvalid" type="checkbox" value="1" <?php checked('1', $wt_options['xhtmlvalid']); ?> />
						<span class="description"><?php _e('Insert CDATA and a comment code. Important : Recheck your webpage with all browser types.','wpTube') ?></span></td>
					</tr>
					<tr valign="top">
						<th><?php _e('Activate RSS Feed message','wpTube') ?></th>
						<td><input name="activaterss" type="checkbox" value="1" <?php checked('1', $wt_options['activaterss']); ?> />
							<input type="text" name="rssmessage" value="<?php echo $wt_options['rssmessage'] ?>" size="50" maxlength="200" />
						</td>
					</tr>
				</table>
			<div class="submit"><input class="button-primary" type="submit" name="updateoption" value="<?php _e('Update') ;?>"/></div>
			</form>	
		</div>	
		
		<!-- Media Player settings -->
		
		<div id="player">
			<h2><?php _e('Media Player','wpTube'); ?></h2>
			<form name="playersettings" method="POST" action="<?php echo $filepath.'#player'; ?>" >
			<?php wp_nonce_field('wt_settings') ?>
			<input type="hidden" name="page_options" value="repeat,stretching,quality,smoothing,showfsbutton,volume,bufferlength,media_width,media_height,startsingle,plugins,custom_vars" />
				<p> <?php _e('These settings are valid for all your flash video. The settings are used in the JW FLV Media Player Version 5.1 or higher', 'wpTube') ?> <br />
					<?php _e('See more information on the web page', 'wpTube') ?> <a href="http://www.longtailvideo.com/players/jw-flv-player/" target="_blank">JW FLV Media Player from Jeroen Wijering</a></p>
				<table class="form-table">
					<tr>
						<th><?php _e('Repeat','wpTube') ?></th>
						<td>
						<select size="1" name="repeat">
							<option value="none" <?php selected("none" , $wt_options['repeat']); ?> ><?php _e('none', 'wpTube') ;?></option>
							<option value="list" <?php selected("list" , $wt_options['repeat']); ?> ><?php _e('list', 'wpTube') ;?></option>
							<option value="always" <?php selected("always" , $wt_options['repeat']); ?> ><?php _e('always', 'wpTube') ;?></option>
                            <option value="single" <?php selected("single" , $wt_options['repeat']); ?> ><?php _e('single', 'wpTube') ;?></option>
						</select>
						<span class="description"><?php _e('Set to "list" to play the entire playlist once, to "always" to continously play the song/video/playlist and to "single" to continue repeating the selected file in a playlist.','wpTube') ?></span></td>
					</tr>
					<tr>
						<th><?php _e('Resize images','wpTube') ?></th>
						<td>
						<select size="1" name="stretching">
							<option value="exactfit" <?php selected("exactfit" , $wt_options['stretching']); ?> ><?php _e('exact fit', 'wpTube') ;?></option>
							<option value="fill" <?php selected("fill" , $wt_options['stretching']); ?> ><?php _e('fill', 'wpTube') ;?></option>
							<option value="uniform" <?php selected("uniform" , $wt_options['stretching']); ?> ><?php _e('uniform', 'wpTube') ;?></option>
							<option value="none" <?php selected("none" , $wt_options['stretching']); ?> ><?php _e('none', 'wpTube') ;?></option>
						</select>
						<span class="description"><?php _e('Defines how to resize images in the display. Can be none (no stretching), exactfit (disproportionate), uniform (stretch with black borders) or fill (uniform, but completely fill the display).','wpTube') ?></span></td>
					</tr>
					<tr>
						<th><?php _e('High quality video','wpTube') ?></th>
						<td><input name="quality" type="checkbox" value="1" <?php checked(true , $wt_options['quality']); ?> />
						<span class="description"><?php _e('Enables high-quality playback. This sets the smoothing of videos on/off, the deblocking of videos on/off and the dimensions of the camera small/large.','wpTube') ?></span></td>
					</tr>
					<tr>
						<th><?php _e('Enable Smoothing ','wpTube') ?></th>
						<td><input name="smoothing" type="checkbox" value="1" <?php checked(true , $wt_options['smoothing']); ?> />
						<span class="description"><?php _e('This sets the smoothing of videos, so you won\'t see blocks when a video is upscaled. Set this to false to get performance improvements with old computers / big files','wpTube') ?></span></td>
					</tr>
					<tr>
						<th><?php _e('Enable Fullscreen','wpTube') ?></th>
						<td><input name="showfsbutton" type="checkbox" value="1" <?php checked(true , $wt_options['showfsbutton']); ?> />
						<span class="description"><?php _e('Show the fullscreen button.','wpTube') ?></span></td>
					</tr>
					<tr>					
						<th><?php _e('Volume','wpTube') ?></th>
						<td><input type="text" size="3" maxlength="3" name="volume" value="<?php echo $wt_options['volume'] ?>" />
						<span class="description"><?php _e('Startup volume of the Flash player (default 80).','wpTube') ?></span></td>
					</tr>
					<tr>					
						<th><?php _e('Buffer length','wpTube') ?></th>
						<td><input type="text" size="3" maxlength="3" name="bufferlength" value="<?php echo $wt_options['bufferlength'] ?>" />
						<span class="description"><?php _e('Number of seconds a media file should be buffered ahead before the player starts it. Set this smaller for fast connections or short videos. Set this bigger for slow connections (default 5).','wpTube') ?></span></td>
					</tr>
					<tr>
						<th><?php _e('Default size (W x H)','wpTube') ?></th>
						<td><input type="text" size="3" maxlength="4" name="media_width" value="<?php echo $wt_options['media_width'] ?>" /> x
						<input type="text" size="3" maxlength="4" name="media_height" value="<?php echo $wt_options['media_height'] ?>" />
						<span class="description"><?php _e('Define width and height of the media player screen.','wpTube') ?></span></td>
					</tr>	
					<tr>
						<th><?php _e('Autostart first single media','wpTube') ?></th>
						<td><input name="startsingle" type="checkbox" value="1" <?php checked(true , $wt_options['startsingle']); ?> />
						<span class="description"><?php _e('If checked, first media in a single post will automatically start.','wpTube') ?></span></td>
					</tr>
					<tr>
						<th><?php _e('Plugins','wpTube') ?></th>
						<td><input type="text" size="70" name="plugins" value="<?php echo $wt_options['plugins']; ?>" /><br />
							<span class="description"><?php _e('This is a comma-separated list of swf plugins to load (e.g. yousearch,viral). Each plugin has a unique ID and resides at plugins.longtailvideo.com', 'wpTube') ?></span>
						</td>
					</tr>	
					<tr>
						<th><?php _e('Custom variables','wpTube') ?></th>
						<td><textarea name="custom_vars" cols="80" rows="5"><?php echo $wt_options['custom_vars']; ?></textarea><br />
							<span class="description"><?php _e('This is a comma-separated list of plugin variables or custom parameters (e.g. variable1=this, variable2=that).', 'wpTube') ?></span>
						</td>
					</tr>
				</table>
			<div class="submit"><input class="button-primary" type="submit" name="updateoption" value="<?php _e('Update') ;?>"/></div>
			</form>	
		</div>
		
		<!--Playlist Settings -->
		
		<div id="playlist">
			<h2><?php _e('Playlist Settings','wpTube'); ?></h2>
			<form name="playlistsettings" method="POST" action="<?php echo $filepath.'#playlist'; ?>" >
			<?php wp_nonce_field('wt_settings') ?>
			<input type="hidden" name="page_options" value="autostart,shuffle,width,height,playlistsize,playlist" />
				<p><?php _e('You can show all videos/media files in a playlist. Show the media player with the tag', 'wpTube') ?> <strong> [playlist id=XXX]</strong></p>
					<table class="form-table">
					<tr>
						<th><?php _e('Autostart','wpTube') ?></th>
						<td><input name="autostart" type="checkbox" value="1" <?php checked(true , $wt_options['autostart']); ?> />
						<span class="description"><?php _e('Automatically start playing the media files.','wpTube') ?></span></td>
					</tr>
					<tr>
						<th><?php _e('Shuffle mode','wpTube') ?></th>
						<td><input name="shuffle" type="checkbox" value="1" <?php checked(true , $wt_options['shuffle']); ?> />
						<span class="description"><?php _e('Activate the shuffle mode in the playlist','wpTube') ?></span></td>
					</tr>
					<tr>
						<th><?php _e('Default size (W x H)','wpTube') ?></th>
						<td><input type="text" size="3" maxlength="4" name="width" value="<?php echo $wt_options['width'] ?>" /> x
						<input type="text" size="3" maxlength="4" name="height" value="<?php echo $wt_options['height'] ?>" />
						<span class="description"><?php _e('Define width and height of the Media Player when a playlist is shown','wpTube') ?></span></td>
					</tr>
					<tr>					
						<th><?php _e('Playlist size','wpTube') ?></th>
						<td><input type="text" size="3" maxlength="3" name="playlistsize" value="<?php echo $wt_options['playlistsize'] ?>" />						
						<span class="description"><?php _e('Size of the playlist. When below or above, this refers to the height, when right, this refers to the width of the playlist','wpTube') ?></span></td>
					</tr>
					<tr>
						<th><?php _e('Playlist position','wpTube') ?></th>
						<td>
						<select size="1" name="playlist">
							<option value="bottom" <?php selected("bottom" , $wt_options['playlist']); ?> ><?php _e('bottom', 'wpTube') ;?></option>
							<option value="over" <?php selected("over" , $wt_options['playlist']); ?> ><?php _e('over', 'wpTube') ;?></option>
							<option value="right" <?php selected("right" , $wt_options['playlist']); ?> ><?php _e('right', 'wpTube') ;?></option>
							<option value="none" <?php selected("none" , $wt_options['playlist']); ?> ><?php _e('none', 'wpTube') ;?></option>
						</select>
						<span class="description"><?php _e('Position of the playlist. Can be set to bottom, over, right or none.','wpTube') ?></span></td>
					</tr>
					</table>

			<div class="submit"><input type="submit" class="button-primary" name="updateoption" value="<?php _e('Update') ;?>"/></div>
			</form>	
		</div>

		<!--Layout -->
		
		<div id="layout">
			<h2><?php _e('Layout  / Skin','wpTube'); ?></h2>
			<form name="layout" method="POST" action="<?php echo $filepath.'#layout'; ?>" >
			<?php wp_nonce_field('wt_settings') ?>
			<input type="hidden" name="page_options" value="controlbar,skinurl,usewatermark,watermarkurl,backcolor,frontcolor,lightcolor,screencolor" />
				<p><?php _e('Here you can change the colors and skin of your player and playlist', 'wpTube') ?> </p>
					<table class="form-table">
						<tr>
							<th><?php _e('Controls position','wpTube') ?></th>
							<td>
							<select size="1" name="controlbar">
								<option value="bottom" <?php selected("bottom" , $wt_options['controlbar']); ?> ><?php _e('bottom', 'wpTube') ;?></option>
								<option value="over" <?php selected("over" , $wt_options['controlbar']); ?> ><?php _e('over', 'wpTube') ;?></option>
								<option value="none" <?php selected("none" , $wt_options['controlbar']); ?> ><?php _e('none', 'wpTube') ;?></option>
							</select>
							<span class="description"><?php _e('Position of the controlbar. Can be set to bottom, over and none.','wpTube') ?></span></td>
						</tr>
						<tr>
							<th><?php _e('Skin file','wpTube') ?></th>
							<td><input type="text" size="60" maxlength="200" name="skinurl" value="<?php echo $wt_options['skinurl'] ?>" />
							<span class="description"><?php _e('URL of a SWF skin file with the player graphics','wpTube') ?></span></td>
						</tr>
						<tr>
							<th><?php _e('Show custom logo','wpTube') ?></th>
							<td><input name="usewatermark" type="checkbox" value="1" <?php checked(true , $wt_options['usewatermark']); ?> />
							<input type="text" size="60" maxlength="200" name="watermarkurl" value="<?php echo $wt_options['watermarkurl'] ?>" />
							<span class="description"><?php _e('URL to your watermark (PNG, JPG)','wpTube') ?> <strong><?php _e('(Licensed players only)','wpTube') ?></strong></span></td>
						</tr>						
						<tr>
							<th><?php _e('Background Color','wpTube') ?>:</th>
							<td><input type="text" size="6" maxlength="6" id="backcolor" name="backcolor" onchange="setcolor('#previewBack', this.value)" value="<?php echo $wt_options['backcolor'] ?>" />
							<input type="text" size="1" readonly="readonly" id="previewBack" style="background-color: #<?php echo $wt_options['backcolor'] ?>" />
							<span class="description"><?php _e('Background color of the controlbar and playlist','wpTube') ?></span></td>
						</tr>
						<tr>					
							<th><?php _e('Texts / Buttons Color','wpTube') ?>:</th>
							<td><input type="text" size="6" maxlength="6" id="frontcolor" name="frontcolor" onchange="setcolor('#previewFront', this.value)" value="<?php echo $wt_options['frontcolor'] ?>" />
							<input type="text" size="1" readonly="readonly" id="previewFront" style="background-color: #<?php echo $wt_options['frontcolor'] ?>" />
							<span class="description"><?php _e('Color of all icons and texts in the controlbar and playlist','wpTube') ?></span></td>
						</tr>
						<tr>					
							<th><?php _e('Rollover / Active Color','wpTube') ?>:</th>
							<td><input type="text" size="6" maxlength="6" id="lightcolor" name="lightcolor" onchange="setcolor('#previewLight', this.value)" value="<?php echo $wt_options['lightcolor'] ?>" />
							<input type="text" size="1" readonly="readonly" id="previewLight" style="background-color: #<?php echo $wt_options['lightcolor'] ?>" />
							<span class="description"><?php _e('Color of an icon or text when you rollover it with the mouse','wpTube') ?></span></td>
						</tr>
						<tr>					
							<th><?php _e('Screen Color','wpTube') ?>:</th>
							<td><input type="text" size="6" maxlength="6" id="screencolor" name="screencolor" onchange="setcolor('#previewScreen', this.value)" value="<?php echo $wt_options['screencolor'] ?>" />
							<input type="text" size="1" readonly="readonly" id="previewScreen" style="background-color: #<?php echo $wt_options['screencolor'] ?>" />
							<span class="description"><?php _e('Background color of the display','wpTube') ?></span></td>
						</tr>
					</table>

			<div class="submit"><input type="submit" class="button-primary" name="updateoption" value="<?php _e('Update') ;?>"/></div>
			
			</form>	
		</div>
		
		<!-- Longtail settings -->
		
		<div id="longtail">
			<h2><?php _e('LongTail Adsolution','wpTube'); ?></h2>
			<form name="longtail" method="POST" action="<?php echo $filepath.'#longtail'; ?>" >
			<?php wp_nonce_field('wt_settings') ?>
			<input type="hidden" name="page_options" value="activateAds,LTapiScript,LTchannelID" />
				<p><?php _e('With LongTail Adsolution you can embed any ad tag into your media player, allowing them to run pre-, overlay mid- and post-roll advertisements.', 'wpTube') ?><br />
					<?php _e('See more information on the web page', 'wpTube') ?> <a href="http://www.longtailvideo.com/adsolution.asp" target="_blank">LongTail Adsolution</a></p>
				<table class="form-table">
					<tr>
						<th valign="top"><?php _e('Activate Ads','wpTube') ?>:</th>
						<td><input name="activateAds" type="checkbox" value="1" <?php checked('1', $wt_options['activateAds']); ?> /></td>
					</tr>
					<tr>
						<th valign="top"><?php _e('Channel Code','wpTube') ?>:</th>
						<td><input type="text" name="LTchannelID" value="<?php echo $wt_options['LTchannelID'] ?>" size="30" />
						<span class="description">&nbsp;<?php _e('Look for the channel code at your', 'wpTube') ?> <a href="http://dashboard.longtailvideo.com/ChannelSetup.aspx" target="_blank"><?php _e('LongTail dashboard', 'wpTube') ?></a></span> 
						</td>
					</tr>
				</table>
			<div class="submit"><input type="submit" class="button-primary" name="updateoption" value="<?php _e('Update') ;?>"/></div>
			</form>	
		</div>
		
		<!-- Setup -->
		
		<div id="setup">
		<form name="setup" method="POST" action="<?php echo $filepath.'#setup'; ?>" >
		<?php wp_nonce_field('wt_settings') ?>
			<h2><?php _e('Setup','wpTube'); ?></h2>
			<p><?php _e('You can reset all options/settings to the default installation.', 'wpTube') ;?></p>
			<div align="center"><input type="submit" class="button-secondary" name="resetdefault" value="<?php _e('Reset settings', 'wpTube') ;?>" onclick="javascript:check=confirm('<?php _e('Reset all options to default settings ?\n\nChoose [Cancel] to Stop, [OK] to proceed.\n','wpTube'); ?>');if(check==false) return false;" /></div>
			<div>
				<p><?php _e('You don\'t like wordTube ?', 'wpTube') ;?></p>
				<p><?php _e('No problem, before you deactivate this plugin press the Uninstall Button, because deactivating wordTube does not remove any data that may have been created. ', 'wpTube') ;?>
			</div>
			<p><font color="red"><strong><?php _e('WARNING:', 'wpTube') ;?></strong><br />
			<?php _e('Once uninstalled, this cannot be undone. You should use a Database Backup plugin of WordPress to backup all the tables first.', 'wpTube') ;?></font></p>
			<div align="center">
				<input type="submit" name="uninstall" class="button-secondary delete" value="<?php _e('Uninstall plugin', 'wpTube') ?>" onclick="javascript:check=confirm('<?php _e('You are about to Uninstall this plugin from WordPress.\nThis action is not reversible.\n\nChoose [Cancel] to Stop, [OK] to Uninstall.\n','wpTube'); ?>');if(check==false) return false;"/>
			</div>
		</form>
		</div>
	</div>

	<?php
}

?>
