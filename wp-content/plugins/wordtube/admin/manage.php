<?php
/*
+----------------------------------------------------------------+
+	wordtube-admin
+	by Alex Rabe & Alakhnor
+   required for wordtube
+----------------------------------------------------------------+
*/

class wordTubeManage extends wordTubeAdmin  {

	var $mode = 'main';
	var $wptfile_abspath;
	var $wp_urlpath;
	var $act_vid = false;
	var $act_pid = false;
	var $base_page = '?page=wordTube';
	var $PerPage = 10;
	
	function wordTubeManage() {
		global $wordTube;
		
		// get the options
		$this->options = get_option('wordtube_options');

		// $this->options = wt_get_DefaultOption();
		
		// same as $_SERVER['REQUEST_URI'], but should work under IIS 6.0
		$this->base_page   = admin_url() . 'admin.php' . $this->base_page ;
		
		// Create taxonomy
		register_taxonomy( WORDTUBE_TAXONOMY, 'wordtube', array('update_count_callback' => '_update_media_term_count') );

		// Manage upload dir
		add_filter('upload_dir', array(&$this, 'upload_dir'));

		$wp_upload = wp_upload_dir();
		$this->wptfile_abspath = $wp_upload['path'].'/';
		$this->wp_urlpath = $wp_upload['url'].'/';

		// output Manage screen
		$this->controller();
	}

	/**
	 * Renders an admin section of display code
	 *@author     John Godley (http://urbangiraffe.com)
	 *
	 * @param string $ug_name Name of the admin file (without extension)
	 * @param string $array Array of variable name=>value that is available to the display code (optional)
	 * @return void
	 **/
	
	function render_admin ($ug_name, $ug_vars = array ())
	{
			
		$function_name = array($this, 'show_'.$ug_name);
			
		if ( is_callable($function_name) )
			call_user_func_array($function_name, $ug_vars);
		else
			echo "<p>Rendering of admin function show_$ug_name failed</p>";
	}	
	
	// Return custom upload dir/url
	function upload_dir($uploads) {

		if (!$this->options['usewpupload']) {
		 	$dir = ABSPATH.trim( $this->options['uploadurl'] ).'/';
		 	$url = trailingslashit( get_option('siteurl') ).trim( $this->options['uploadurl']).'/';
        	
			// Make sure we have an uploads dir
			if ( ! wp_mkdir_p( $dir ) ) {
				$message = sprintf(__('Unable to create directory %s. Is its parent directory writable by the server?','wpTube'), $dir);
				$uploads['error'] = $message;
				return $uploads;
			}
			$uploads = array('path' => $dir, 'url' => $url, 'error' => false);
		}
		return $uploads;

	}
	
	function controller() {
		global $wpdb;
		
		$this->mode = isset ($_GET['mode']) ? trim($_GET['mode']) : false;

		$this->act_vid = isset ($_GET['id']) ? (int) $_GET['id'] : 0;
		$this->act_pid = isset ($_GET['pid']) ? (int) $_GET['pid'] : 0;
		
		//TODO:Include nonce !!!			
		
		if (isset($_POST['add_media'])) {
			wt_add_media($this->wptfile_abspath, $this->wp_urlpath);
			$this->mode = 'main';
		}
		
		if (isset($_POST['edit_update']))
			wt_update_media( $this->act_vid );	
		
		if (isset($_POST['cancel']) || isset($_POST['search']))
			$this->mode = 'main';	
		
		if (isset($_POST['show_add'])) 
			$this->mode = 'add';

		if (isset($_POST['add_playlist'])) {
			wt_add_playlist();
			$this->mode = 'playlist';
		}	
			
		if (isset($_POST['update_playlist'])) {
			wt_update_playlist();
			$this->mode = 'playlist';
		}

		if ( $this->mode =='delete') {
			wt_delete_media($this->act_vid, $this->options['deletefile']);;
			$this->mode = 'main';
		}
		
		//Let's show the main screen if no one selected	
		if ( empty($this->mode) ) 
			$this->mode = 'main';
			
		//show license agreement
		$this->license_check();

		// render the admin screen
		$this->render_admin($this->mode);
	}

	function show_main() {
		global $wpdb;
			
		// init variables
		$pledit = true;
		$where = '';
		$join = '';
        $class = '';			

		// check for page navigation
		$page     = ( isset($_REQUEST['apage']))    ? (int) $_REQUEST['apage'] : 1;
		$sort     = ( isset($_REQUEST['sort']))     ? $_REQUEST['sort'] : 'DESC';
		$search   = ( isset($_REQUEST['search']))   ? $_REQUEST['search'] : '';
		$filter   = ( isset($_REQUEST['filter']))   ? $_REQUEST['filter'] : 'any';
		$plfilter = ( isset($_REQUEST['plfilter'])) ? $_REQUEST['plfilter'] :'0';
		
		if ($filter == 'mp3' || $filter == 'flv' || $filter == 'swf') 
			$where = " (file LIKE '%.".$filter."') ";
		elseif ($filter == 'img')
			$where = " ((file LIKE '%.png') OR (file LIKE '%.jpg')) ";

		if ($search != '') {
			if ($where != '') $where .= " AND ";
            $search = like_escape($search);
			$where .= " ((name LIKE '%$search%') OR (creator LIKE '%$search%')) ";
		}
		
		if ($plfilter != '0' && $plfilter != 'no') {
			$join = " LEFT JOIN $wpdb->wordtube_med2play ON (vid = media_id) ";
			if ($where != '') $where .= " AND ";
			$where .= " (playlist_id = '".$plfilter."') ";
			$pledit = true;
		} elseif ($plfilter == 'no') {
			$join = " LEFT JOIN $wpdb->wordtube_med2play ON (vid = media_id) ";
			if ($where != '') $where .= " AND ";
			$where .= " (media_id IS NULL) ";
			$pledit = false;
		} else
			$pledit = false;
		
		if ($where != '') $where = " WHERE ".$where;
		
		$total = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->wordtube.$join.$where );

		$total_pages = ceil( $total / $this->PerPage );
		if ($total_pages == 0) $total_pages = 1;
		
		if ($page > $total_pages) $page = $total_pages;
		$start = $offset = ( $page - 1 ) * $this->PerPage;

		if ($pledit) 
			$orderby = " ORDER BY porder ".$sort.", vid ".$sort;
		else
			$orderby = " ORDER BY vid ".$sort;
			
		// Generates retrieve request.
		$tables = $wpdb->get_results("SELECT * FROM ".$wpdb->wordtube.$join.$where.$orderby." LIMIT $start, 10");
		
		// selected playlist
		$show_playlist = ( isset($_POST['show_playlist']) ? (int) $_POST['show_playlist'] : 0 );
	
	?>
	<!-- Manage Video-->
	<div class="wrap">
		<form name="filterType" method="post" id="posts-filter">
			<?php screen_icon(); ?>
			<h2><?php _e('Manage Media files','wpTube'); ?></h2>
			<ul class="subsubsub">
				<li>&nbsp;</li>
			</ul>
			<p class="search-box">
				<input type="text" class="search-input" name="search" value="<?php echo $search; ?>" size="10" />
				<input type="submit" class="button-primary" value="<?php _e('Search Media','wpTube'); ?>" />
				<input type="hidden" name="cancel" value="2"/>
			</p>
			<div class="tablenav">
				<?php $this->navigation($this->PerPage, $page, $total, $search, $sort, $filter, $plfilter); ?>
				<div class="alignleft actions">
					<?php $this->sort_filter($sort); ?>
					<?php $this->type_filter($filter); ?>
					<?php $this->playlist_filter($plfilter); ?>
					<input class="button-secondary" id="post-query-submit" type="submit" name="startfilter"  value="<?php _e('Filter','wpTube'); ?> &raquo;" class="button" />
				</div>
			</div>
			<!-- Table -->
			<table class="widefat" cellspacing="0">
				<thead>
					<tr>
						<th id="id" class="manage-column column-id" scope="col"><?php _e('ID','wpTube'); ?></th>
						<th id="title" class="manage-column column-title" scope="col"><?php _e('Title','wpTube'); ?></th>
						<th id="author" class="manage-column column-author" scope="col"><?php _e('Creator','wpTube'); ?></th>
						<th id="path" class="manage-column column-path"  scope="col"><?php _e('Path','wpTube'); ?></th>
						<th id="counter" class="manage-column column-counter"  scope="col"><?php _e('Views','wpTube'); ?></th>
						<?php if ($pledit) { ?>
							<th id="order" class="manage-column column-order"  scope="col"><?php _e('Order','wpTube'); ?></th>
						<?php } ?>
					</tr>
				</thead>
				<tbody id="the-list" class="list:post">
				<?php
				if($tables) {
					$i = 0;
					foreach($tables as $table) {
						$class = ( $class == 'class="alternate"' ) ? '' : 'class="alternate"';
						echo "<tr $class>\n";
						echo "<th scope=\"row\">$table->vid</th>\n";
						echo "<td class='post-title column-title''><strong><a title='" . __('Edit this media','wpTube') . "' href='$this->base_page&amp;mode=edit&amp;id=$table->vid'>" . stripslashes($table->name) . "</a></strong>\n";
						echo "<span class='edit'>
								<a title='" . __('Edit this media','wpTube') . "' href='$this->base_page&amp;mode=edit&amp;id=$table->vid'>" . __('Edit') . "</a>
							  </span> | ";
						echo "<span class='delete'>
								<a title='" . __('Delete this media','wpTube') . "' href='$this->base_page&amp;mode=delete&amp;id=$table->vid' onclick=\"javascript:check=confirm( '".__("Delete this file ?",'wpTube')."');if(check==false) return false;\">" . __('Delete') . "</a>
							  </span>";
						echo "</td>\n";
						echo "<td>".stripslashes($table->creator)."</td>\n";
						echo "<td>".htmlspecialchars(stripslashes($table->file), ENT_QUOTES)."</td>\n";
						echo "<td>$table->counter</td>\n";
						if ($pledit)
							echo "<td><div class='wtedit' id='p_".$plfilter.'_'.$table->vid."'>".$table->porder."</div></td>\n";
						echo '</tr>';
						$i++;
					}
				} else {
					echo '<tr><td colspan="7" align="center"><b>'.__('No entries found','wpTube').'</b></td></tr>';
				}
				?>
				</tbody>
			</table>
		<div class="tablenav">
			<?php $this->navigation($this->PerPage, $page, $total, $search, $sort, $filter, $plfilter); ?>
			<div class="alignleft actions">
				<input class="button-secondary" type="submit" value="<?php _e('Insert new media file','wpTube') ?> &raquo;" name="show_add"/>
			</div>
			<br class="clear"/>			
		</div>
		</form>
	</div>

	<!-- Manage Playlist-->
	<div class="wrap">
		<h2><?php _e('Playlist Preview', 'wpTube') ?> (<a href="<?php echo $this->base_page; ?>&mode=playlist"><?php _e('Edit','wpTube') ?></a>)</h2>
		<p><?php _e('You can show all videos/media files in a playlist. Show this playlist with the tag', 'wpTube') ?> <strong> [playlist id=<?php echo $show_playlist ?>]</strong></p>
		<form name="selectlist" method="post">
			<input type="hidden" name="apage" value="<?php echo $page; ?>" />
			<input type="hidden" name="search" value="<?php echo $search; ?>" />
			<input type="hidden" name="sort" value="<?php echo $sort; ?>" />
			<input type="hidden" name="filter" value="<?php echo $filter; ?>" />
			<input type="hidden" name="plfilter" value="<?php echo $plfilter; ?>" />
			<legend><?php _e('Select Playlist :', 'wpTube'); ?></legend>
			<select name="show_playlist" id="show_playlist">
				<!-- <option value="most" <?php //if ('most' == $show_playlist) echo "selected='selected' "; ?>><?php //_e('Most viewed', 'wpTube') ?></option> -->
				<option value="0" <?php if ('0' == $show_playlist) echo "selected='selected' "; ?>><?php _e('All files', 'wpTube') ?></option>
				<option value="music" <?php if ('music' == $show_playlist) echo "selected='selected' "; ?>><?php _e('All mp3', 'wpTube') ?></option>
				<option value="video" <?php if ('video' == $show_playlist) echo "selected='selected' "; ?>><?php _e('All videos', 'wpTube') ?></option>
				<?php
				$playlists = $wpdb->get_results("SELECT * FROM $wpdb->wordtube_playlist ");
				if($playlists) {
					foreach($playlists as $playlist) {
						echo '<option value="'.$playlist->pid.'" ';
						if ($playlist->pid == $show_playlist) echo "selected='selected' ";
						echo '>'.$playlist->playlist_name.'</option>'."\n\t"; 
					}
				}
				?>
			</select>
			<input type="submit" class="button-secondary" value="<?php _e('OK','wpTube'); ?>"  />
		</form>
		<div style="text-align: center;">
			<?php echo wt_GetPlaylist($show_playlist, $this->options['width'], $this->options['height']); ?>
		</div>
	</div>
	<?php
	}
	
	function show_edit() {
	
		global $wpdb;

		$media = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->wordtube WHERE vid = %d", $this->act_vid) );
		$act_name = esc_attr(stripslashes($media->name));
		$act_creator = esc_attr(stripslashes($media->creator));
		$act_desc = esc_html(stripslashes($media->description));
		$act_filepath = stripslashes($media->file);
		$act_image = stripslashes($media->image);
		$act_link = stripslashes($media->link);
		// Retrieve tags to display
		$act_tags = implode(',',wp_get_object_terms($this->act_vid, WORDTUBE_TAXONOMY, 'fields=names'));
		$act_width = stripslashes($media->width);
		$act_height = stripslashes($media->height);
		$act_counter = $media->counter;
		$autostart = ($media->autostart) ?  'checked="checked"' : '';
		$ads_channel = isset($media->channel) ? $media->channel : '';
		$disableAds = ($media->disableads) ? 'checked="checked"' : '';
	
		?>
		<!-- Edit Video -->
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e('Edit media file', 'wpTube') ?></h2>
			<form name="table_options" method="post" id="video_options">
			<div id="poststuff" class="has-right-sidebar">
				<div class="inner-sidebar">
					<div id="submitdiv" class="postbox">
						<h3 class="hndle"><span><?php _e('Settings','wpTube') ?></span></h3>
						<div class="inside">
							<div id="submitpost" class="submitbox">
								<div class="misc-pub-section">
									<p><?php _e('Here you can edit the selected file. See global settings for the Flash Player under', 'wpTube') ?> <a href="options-general.php?page=wordtube-options"><?php _e('Options->wordTube', 'wpTube')?></a><br /><br />
									<?php _e('If you want to show this media file in your page, enter the tag :', 'wpTube') ?><br /><strong>[media id=<?php echo $this->act_vid; ?>]</strong></p>
								</div>
								<div class="misc-pub-section">
									<h4><?php _e('Select Playlist','wpTube') ?></h4>
									<p id="jaxcat"></p>
									<div id="playlistchecklist"><?php get_playlist_for_dbx($this->act_vid); ?></div>
								</div>
								<div class="misc-pub-section">
									<input class="form-input-tip" type="text" size="5" maxlength="5" name="act_counter" value="<?php echo $act_counter ?>" />
									<?php _e('Edit view counter','wpTube') ?>
								</div>
								<div id="sticky-checkbox" class="misc-pub-section">
									<label class="selectit"><input name="autostart" type="checkbox" value="1"  <?php echo $autostart ?> /> <?php _e('Start file automatic ','wpTube') ?></label>
									<br class="clear"/>
									<label class="selectit"><input name="disableAds" type="checkbox" value="1"  <?php echo $disableAds ?> /> <?php _e('Disable Ads for this Media','wpTube') ?></label>
								</div>
								<div id="major-publishing-actions">
									<input type="submit" class="button-primary" name="edit_update" value="<?php _e('Update'); ?>" class="button button-highlighted" />
									<input type="submit" class="button-secondary" name="cancel" value="<?php _e('Cancel'); ?>" class="button" />
								</div>
							</div>
						</div>
					</div>
					<div class="postbox">
						<h3 class="hndle"><span><?php _e('Preview','wpTube') ?></span></h3>
						<div class="inside">
						<?php echo wt_GetVideo($this->act_vid, 265, $act_height); ?>
						</div>
					</div>
				</div>
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
                        <div class="stuffbox">
                        <h3><?php _e('Media ID','wpTube') ?> <?php echo $this->act_vid; ?></h3>
                        <div class="inside">
						<table class="form-table">
							<tr valign="top">
								<th scope="row"><?php _e('Media title','wpTube') ?></th>
								<td><input type="text" size="50"  name="act_name" value="<?php echo $act_name ?>" /></td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e('Creator / Author','wpTube') ?></th>
								<td><input type="text" size="50"  name="act_creator" value="<?php echo $act_creator ?>" /></td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e('Description','wpTube') ?></th>
								<td><textarea name="act_desc" id="act_desc" rows="5" cols="50" style="width: 97%;"><?php echo $act_desc ?></textarea></td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e('Media URL','wpTube') ?></th>
								<td><input type="text" size="80"  name="act_filepath" value="<?php echo $act_filepath ?>" />
								<br /><?php _e('Here you need to enter the absolute URL to the file (MP3,FLV,SWF,JPG,PNG or GIF)','wpTube') ?>
								<br /><?php echo _e('It also accept Youtube links. Example: http://youtube.com/watch?v=O_MP_6ldeB4','wpTube') ?>
								<br /><?php echo _e('RTMP streams must be look like : rtmp://streaming-server/path/?id=filename','wpTube') ?></td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e('Thumbnail URL','wpTube') ?></th>
								<td><input type="text" size="80"  name="act_image" value="<?php echo $act_image ?>" />
								<br /><?php _e('Enter the URL to show a preview of the media file','wpTube') ?></td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e('Link URL','wpTube') ?></th>
								<td><input type="text" size="80" name="act_link" value="<?php echo $act_link ?>" />
								<br /><?php _e('Enter the URL to the page/file, if you click on the player','wpTube') ?></td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e('Tags','wpTube') ?></th>
								<td><input type="text" size="80" name="act_tags" value="<?php echo $act_tags ?>" />
								<br /><?php _e('Enter media tags','wpTube') ?></td>
							</tr>
                            <?php do_action('wordtube_edit_media_meta', $media); ?>	
						</table>
                        </div>
                        </div>                        
					</div>
					<p>
						<input type="submit" class="button-primary" name="edit_update" value="<?php _e('Update'); ?>" class="button button-highlighted" />
						<input type="submit" class="button-secondary" name="cancel" value="<?php _e('Cancel'); ?>" class="button" />
					</p>
				</div>
			</div><!--END Poststuff -->

			</form>

		</div><!--END wrap -->
		<?php
	}
	
	function show_add() {
		?>
		<!-- Add A Video -->
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e('Add a new media file','wpTube'); ?></h2>
			<form name="table_options" enctype="multipart/form-data" method="post" id="video_options">
			<div id="poststuff" class="has-right-sidebar">
				<div class="inner-sidebar">
					<div id="submitdiv" class="postbox">
						<h3 class="hndle"><span><?php _e('Settings','wpTube') ?></span></h3>
						<div class="inside">
							<div id="submitpost" class="submitbox">
								<div class="misc-pub-section">
									<p>
										<?php _e('Here you can edit the selected file. See global settings for the Flash Player under', 'wpTube') ?> <a href="options-general.php?page=wordtube-options"><?php _e('Options->wordTube', 'wpTube')?></a>
									</p>
								</div>
								<div class="misc-pub-section">
									<h4><?php _e('Select Playlist','wpTube') ?></h4>
									<p id="jaxcat"></p>
									<div id="playlistchecklist"><?php get_playlist_for_dbx($this->act_vid); ?></div>
								</div>
								<div class="misc-pub-section">
									<input class="form-input-tip" type="text" size="5" maxlength="5" name="act_counter" value="0" />
									<?php _e('Edit view counter','wpTube') ?>
								</div>
								<div id="sticky-checkbox" class="misc-pub-section">
									<label class="selectit"><input name="autostart" type="checkbox" value="1"  /> <?php _e('Start file automatic ','wpTube') ?></label>
									<br class="clear"/>
									<label class="selectit"><input name="disableAds" type="checkbox" value="1"  /> <?php _e('Disable Ads for this Media','wpTube') ?></label>
								</div>	
							</div>
						</div>
					</div>
				</div>
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
						<div class="stuffbox">
							<h3 class="hndle"><span><?php _e('Enter Title / Name','wpTube'); ?></span></h3>
							<div class="inside" style="margin:15px;">
								<table class="form-table">
								<tr>
									<th scope="row"><?php _e('Title / Name','wpTube') ?></th>
									<td><input type="text" size="50" maxlength="200" name="name" /></td>
								</tr>
								<tr>
									<th scope="row"><?php _e('Creator / Author','wpTube') ?></th>
									<td><input type="text" size="50" maxlength="200" name="creator" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e('Description','wpTube') ?></th>
									<td><textarea name="description" id="description" rows="5" cols="50" style="width: 97%;"></textarea></td>
								</tr>
                                <?php do_action('wordtube_add_media_meta'); ?>
								</table>
							</div>
						</div>
												
						<div class="stuffbox">
							<h3 class="hndle"><span><?php _e('Upload a file','wpTube'); ?></span></h3>
							<div class="inside" style="margin:15px;">
								<table class="form-table">
								<tr>
									<th scope="row"><?php _e('Select media file','wpTube') ?></th>
									<td><input type="file" size="50" name="video_file" />
									
									<br /><?php _e('Note : The upload limit on your server is ','wpTube') . "<strong>" . min(ini_get('upload_max_filesize'), ini_get('post_max_size')) . "Byte</strong>\n"; ?>
									<br /><?php _e('The Flash Media Player handle : MP3,FLV,SWF,JPG,PNG or GIF','wpTube') ?>
								</tr>
								<tr>
									<th scope="row"><?php _e('Select thumbnail','wpTube') ?></th>
									<td><input type="file" size="50" name="image_file" />
									<br /><?php _e('Upload a image to show a preview of the media file (optional)','wpTube') ?></td>
								</tr>
								</table>
							</div>
						</div>
						
						<div class="stuffbox">
							<h3 class="hndle"><span><?php _e('or enter URL to file','wpTube'); ?></span></h3>
							<div class="inside" style="margin:15px;">
								<table class="form-table">
								<tr>
									<th scope="row"><?php _e('URL to media file','wpTube') ?></th>
									<td><input type="text" size="50" name="filepath" />
									<br /><?php _e('Here you need to enter the absolute URL to the media file','wpTube') ?>
									<br /><?php _e('It accept also a Youtube link: http://youtube.com/watch?v=O_MP_6ldeB4','wpTube') ?></td>
								</tr>
								<tr>
									<th scope="row"><?php _e('URL to thumbnail file','wpTube') ?></th>
									<td><input type="text" size="50" name="urlimage" />
									<br /><?php _e('Enter the URL to show a preview of the media file (optional)','wpTube') ?></td>
								</tr>					
								</table>
							</div>
						</div>
						
					</div>
					<p><input type="submit" name="add_media" class="button-primary" value="<?php _e('Add media file','wpTube'); ?>" class="button" /></p>
				</div>
			</div><!--END Poststuff -->

			</form>

		</div><!--END wrap -->
	<?php
	}
	
	function show_plydel() {
		$message = wt_delete_playlist($this->act_pid);	 
		$this->render_message($message);			
		$this->mode = 'playlist'; 
		// show playlist
		$this->render_admin($this->mode);		
	}

	function show_plyedit() {
		// use the same output as playlist
		$this->render_admin('playlist');		
	}
	
	// Edit or update playlst
	function show_playlist() {
	
		global $wpdb;

		// get the tables		
		$tables = $wpdb->get_results("SELECT * FROM $wpdb->wordtube_playlist ");
		if ($this->mode == 'plyedit')	
			$update = $wpdb->get_row("SELECT * FROM $wpdb->wordtube_playlist WHERE pid = {$this->act_pid} ");
			$pmedia = $wpdb->get_results("SELECT * FROM $wpdb->wordtube, $wpdb->wordtube_med2play WHERE vid = media_id AND playlist_id = {$this->act_pid} ORDER BY porder");
		?>

		<!-- Edit Playlist -->
		<?php if ($this->mode == 'plyedit') { ?>
		<style type="text/css">
		#sortableitems {
			list-style-type: none;
			margin: 0;
			padding: 0;
			width: 100%;
		}
		#sortableitems li {
			margin: 0 3px 3px 3px;
			padding: 0 0.4em 0.7em 1.5em;
			font-size: 0.9em;
		}
		#sortableitems li span {
			position: absolute;
			margin-left: -1.3em;
		}
		</style>
		<script type="text/javascript">
			jQuery(function() {
			jQuery("#sortableitems").sortable();
			jQuery("#sortableitems").disableSelection();
			});

			function get_sortorder( objname ) {
				var result = jQuery('#' + objname).sortable('toArray');
				return result.join(':');
			}

			function save_sortorder( js_objname, storage_txtobj ) {
				var sort_list = get_sortorder(js_objname);
				storage_txtobj.value = sort_list;
			}
		</script>
		<?php } ?>

		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e('Manage Playlist','wpTube'); ?></h2>
			<br class="clear"/>
			<form id="editplist" action="<?php echo $this->base_page; ?>" method="post">
				<table class="widefat" cellspacing="0">
					<thead>
						<tr>
							<th scope="col"><?php _e('ID','wpTube'); ?></th>
							<th scope="col"><?php _e('Name','wpTube'); ?></th>
							<th scope="col"><?php _e('Description','wpTube'); ?></th>
							<th scope="col" colspan="2"><?php _e('Action'); ?></th>
						</tr>
					</thead>
					<?php
					if($tables) {
						$i = 0;
						foreach($tables as $table) {
							if($i%2 == 0) {
								echo "<tr class='alternate'>\n";
							}  else {
								echo "<tr>\n";
							}
							echo "<th scope=\"row\">$table->pid</th>\n";
							echo "<td>".stripslashes($table->playlist_name)."</td>\n";
							echo "<td>".stripslashes($table->playlist_desc)."</td>\n";
							echo "<td><a href=\"$this->base_page&amp;mode=plyedit&amp;pid=$table->pid#addplist\" class=\"edit\">".__('Edit')."</a></td>\n";
							if ($table->pid == 1)
								echo "<td>&nbsp;</td>\n";
							else
								echo "<td><a href=\"$this->base_page&amp;mode=plydel&amp;pid=$table->pid\" class=\"delete\" onclick=\"javascript:check=confirm( '".__("Delete this file ?",'wpTube')."');if(check==false) return false;\">".__('Delete')."</a></td>\n";
							echo '</tr>';
							$i++;
						}
					} else {
						echo '<tr><td colspan="7" align="center"><b>'.__('No entries found','wpTube').'</b></td></tr>';
					}
					?>
				</table>
			</form>
		</div>

		<div class="wrap">
			<div id="poststuff" class="metabox-holder">
				<div id="playlist_edit" class="stuffbox">
					<h3><?php
					if ($this->mode == 'playlist') echo _e('Add Playlist','wpTube');
					if ($this->mode == 'plyedit') echo _e('Update Playlist','wpTube');
					?></h3>
					<div class="inside">
						<form id="addplist" name="addplist" action="<?php echo $this->base_page; ?>" method="post">
							<input type="hidden" value="<?php echo $this->act_pid ?>" name="p_id" />
							<input type="hidden" name="sortorder" value="ASC" />
							<input type="hidden" name="pmedia_sortorder" value="" />

							<p><?php _e('Name:','wpTube'); ?><br/><input type="text" value="<?php if ( isset($update) ) echo $update->playlist_name ?>" name="p_name"/></p>
							<p><?php _e('Description: (optional)','wpTube'); ?><br/><textarea name="p_description" rows="3" cols="50" style="width: 97%;"><?php if ( isset($update) ) echo stripslashes($update->playlist_desc); ?></textarea></p>

							<h3><?php echo _e('Playlist media sort order (Just drag into your desired order)','wpTube'); ?></h3>
							<table class="widefat" cellspacing="0">
								<thead>
									<tr>
										<th width='30%' scope="col"><?php _e('Title','wpTube'); ?></th>
										<th width='20%' scope="col"><?php _e('Creator','wpTube'); ?></th>
										<th width='50%' scope="col"><?php _e('Description','wpTube'); ?></th>
									</tr>
								</thead>
							</table>
							<ul id="sortableitems">
								<?php
								if($this->mode == 'plyedit' && $pmedia) {
									foreach($pmedia as $mitem) {
										echo '<li class="ui-state-default" id="'.stripslashes($mitem->vid).'">';
										echo '<span class="ui-icon ui-icon-arrowthick-2-n-s"></span>';
										echo '<table cellspacing="0" width="100%">';
										echo "<td width='30%' valign='top'>".stripslashes($mitem->name)."</td>\n";
										echo "<td width='20%' valign='top'>".stripslashes($mitem->creator)."</td>\n";
										echo "<td width='50%' valign='top'>".stripslashes($mitem->description)."</td>\n";
										echo '</tr>';
										echo '</table>';
										echo "</li>\n";
									}
								} else {
									echo '<table cellspacing="0" width="100%">';
									echo '<tr><td colspan="3"><br><b>'.__('No media items in this playlist','wpTube').'</b></td></tr>';
									echo '</table>';
								}
								?>
							</ul>
							<div class="submit">
								<?php
									if ($this->mode == 'playlist') echo '<input type="submit" name="add_playlist" value="' . __('Add Playlist','wpTube') . '" class="button-primary" />';
									if ($this->mode == 'plyedit') echo '<input type="submit" name="update_playlist" onclick="save_sortorder(\'sortableitems\', this.form.pmedia_sortorder);" value="' . __('Update Playlist','wpTube') . '" class="button-primary" />';
								?>
								<input type="submit" name="cancel" value="<?php _e('Cancel','wpTube'); ?>" class="button-secondary" />
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php 
	}

	// Display sort form filter
	function sort_filter($sort) {
		?>
		<select name="sort">
			<option value="ASC" <?php if ($sort == 'ASC') echo 'selected="selected"'; ?>><?php _e('Sort Ascending', 'wpTube'); ?></option>
			<option value="DESC" <?php if ($sort == 'DESC') echo 'selected="selected"'; ?>><?php _e('Sort Descending', 'wpTube'); ?></option>
		</select>
		<?php
	}

	// Display type form filter
	function type_filter($filter) {
		?>
		<select name="filter">
			<option value="any" <?php if ($filter == 'any') echo 'selected="selected"'; ?>><?php _e('Any file type', 'wpTube'); ?></option>
			<option value="mp3" <?php if ($filter == 'mp3') echo 'selected="selected"'; ?>><?php _e('mp3 only', 'wpTube'); ?></option>
			<option value="flv" <?php if ($filter == 'flv') echo 'selected="selected"'; ?>><?php _e('flv only', 'wpTube'); ?></option>
			<option value="swf" <?php if ($filter == 'swf') echo 'selected="selected"'; ?>><?php _e('swf only', 'wpTube'); ?></option>
			<option value="img" <?php if ($filter == 'img') echo 'selected="selected"'; ?>><?php _e('images only', 'wpTube'); ?></option>
		</select>
		<?php
	}

	// Display playlist form filter
	function playlist_filter($plfilter) {
		global $wpdb;
		?>
		<select name="plfilter">
			<option value="0" <?php if ($plfilter == '0') echo 'selected="selected"'; ?>><?php _e('All playlists', 'wpTube'); ?></option>
			<option value="no" <?php if ($plfilter == 'no') echo 'selected="selected"'; ?>><?php _e('No playlist', 'wpTube'); ?></option>
			<?php $dbresults = $wpdb->get_results(" SELECT * FROM $wpdb->wordtube_playlist ");
			if ($dbresults) {
				foreach ($dbresults as $dbresult) :
					echo '<option value="'.$dbresult->pid.'"';
					if ($plfilter == $dbresult->pid) echo 'selected="selected"';
					echo '>'.$dbresult->playlist_name.'</option>';
				endforeach;
			}
			?>
		</select>
		<?php
	}
	
	// add a navigation
	function navigation($PerPage, $page, $total, $search, $sort, $filter, $plfilter) {
	
		$sdiv2 = "<div class='tablenav-pages'>";
		$ediv2 = "</div>\n";
	
		if ( $total > $PerPage ) {
			$total_pages = ceil( $total / $PerPage );
			if ($page > $total_pages) $page = $total_pages;
			$r = '';
			if ( 1 < $page ) {
				$args['apage'] = ( 1 == $page - 1 ) ? FALSE : $page - 1;
				if ($search != '') $args['search'] = $search; 
				if ($sort != '') $args['sort'] = $sort; 
				if ($filter != '') $args['filter'] = $filter; 
				if ($plfilter != '') $args['plfilter'] = $plfilter; 
				$r .=  '<a class="prev page-numbers" href="'. add_query_arg( $args, $this->base_page  ) . '">&laquo; '. __('Previous Page', 'wpTube') .'</a>' . "\n";
			}
	
			if ( ( $total_pages = ceil( $total / $PerPage ) ) > 1 ) {
				for ( $page_num = 1; $page_num <= $total_pages; $page_num++ ) :
					if ( $page == $page_num ) {
						$r .=  '<span class="page-numbers current">'.$page_num.'</span>'."\n";
					} else {
						$p = false;
						if ( $page_num < 3 || ( $page_num >= $page - 3 && $page_num <= $page + 3 ) || $page_num > $total_pages - 3 ) {
							$args['apage'] = ( 1 == $page_num ) ? FALSE : $page_num;
							if ($search != '') $args['search'] = $search; 
							if ($sort != '') $args['sort'] = $sort;
							if ($filter != '') $args['filter'] = $filter; 
							if ($plfilter != '') $args['plfilter'] = $plfilter;
							$r .= '<a class="page-numbers" href="' . add_query_arg($args, $this->base_page ) . '">' . ( $page_num ) . "</a>\n";
							$in = true;
						} elseif ( $in == true ) {
							$r .= '<span class="dots">...</span>'."\n";
							$in = false;
						}
					}
				endfor;
			}
	                        
			if ( ( $page ) * $PerPage < $total || -1 == $total ) {
				$args['apage'] = $page + 1;
				if ($search != '') $args['search'] = $search; 
				if ($sort != '') $args['sort'] = $sort;
				if ($filter != '') $args['filter'] = $filter; 
				if ($plfilter != '') $args['plfilter'] = $plfilter;
				$r .=  '<a class="next page-numbers" href="' . add_query_arg($args, $this->base_page ) . '">'. __('Next Page', 'wpTube') .' &raquo;</a>' . "\n";
			}
			$r = $sdiv2.$r.$ediv2;
		} else
			$r = '';
			
		echo $r;
	}
	
	// Agree to the Creative Commons license or not.
	function license_check() {
				
		if (isset($_POST['agree_license'])) {
			$this->options['agree_license'] = true;
			update_option('wordtube_options', $this->options);
		}
	
		if ($this->options['agree_license'] == false) {
			?>
				<div class="fade updated" id="message">
					<p><?php _e("The JW Players are licensed under <a href=\"http://creativecommons.org/licenses/by-nc-sa/3.0/\">Creative Commons</a> license. It allows you to use, modify and redistribute the script for noncommercial purposes. For all other use, buy a <a href=\"/?page=license\">commercial license</a>.", "wpTube") ?></p>
					   <?php _e("You must buy a commercial license if:", "wpTube"); ?>
					   <ul>
							<li><?php _e("Your site has any ads (AdSense, display banners, etc.)", "wpTube"); ?></li>
							<li><?php _e("You want to remove the players' attribution (eliminate the right-click link)", "wpTube"); ?></li>
							<li><?php _e("You are a corporation (governmental or nonprofit use is free)" , "wpTube"); ?></li>
						</ul>
					<div class="submit">
						<form name="license_note" method="post" id="license_note">
							<input type="submit" name="agree_license" value="<?php _e('I agree to the license', "wpTube") ?> &raquo;" class="button" />
						</form>
					</div>
					<br class="clear"/>
				</div>
			<?php	
		}
	}
}
?>
