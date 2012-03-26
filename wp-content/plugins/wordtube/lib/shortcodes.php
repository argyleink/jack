<?php

/**
 * @author Alex Rabe
 * @copyright 2008-2010
 * @description Use WordPress Shortcode API for more features
 * @Docs http://codex.wordpress.org/Shortcode_API
 */

class wordTube_shortcodes {
	
	var $count = 1;
	
	// register the new shortcodes
	function wordTube_shortcodes() {
	
		// convert the old shortcode
		add_filter('the_content', array(&$this, 'convert_shortcode'));
		
		// original version must be disabled, no filter function available
		remove_filter('get_the_excerpt', 'wp_trim_excerpt');
		add_filter('get_the_excerpt', array(&$this, 'wp_trim_excerpt'));

		// Action calls for RSS feed
		add_action('rss2_item', array(&$this, 'add_media_to_rss2'));
		add_action('the_content_rss', array(&$this, 'add_media_to_rss2'));
		
		add_shortcode('media', array(&$this, 'show_media') );
		add_shortcode('playlist', array(&$this, 'show_playlist' ) );
		
		// replace excerpt shortcodes with media
		add_filter('the_excerpt', array(&$this, 'excerpt_shortcodes'));
		
	}
	 /**
	   * wordTube_shortcodes::wp_trim_excerpt()
	   * this is the same function as in wp-includes\formatting.php, but we need to add the shortcode converter before
	   * 
	   * @param string $content Content to search for shortcodes
	   * @return string Content with new shortcodes.
	   */
	function wp_trim_excerpt($text) { // Fakes an excerpt if needed
		if ( '' == $text ) {
			$text = get_the_content('');
			// here we ned to add our converter
			$text = $this ->convert_shortcode( $text ); 
			
			$text = strip_shortcodes( $text ); 
			
			$text = apply_filters('the_content', $text);
			$text = str_replace(']]>', ']]&gt;', $text);
			$text = strip_tags($text);
			$excerpt_length = apply_filters ( 'excerpt_length', 55);
            $excerpt_more = apply_filters ( 'excerpt_more', '[...]');
			$words = explode(' ', $text, $excerpt_length + 1);
			if (count($words) > $excerpt_length) {
				array_pop($words);
				array_push ($words, $excerpt_more);
				$text = implode(' ', $words);
			}
		}
		return $text;
	}

	 /**
	   * wordTube_shortcodes::add_media_to_rss2()
	   * add single media file to RSS feed
	   * 
	   * @param string $content Content to search for shortcodes
	   * @return string Content with new shortcodes.
	   */	
	function add_media_to_rss2() {
	
		global $wpdb, $post;
	
		$search = "/\[media id=(\d+)\]/";   //search for 'media' entry
		
		// first convert old shortcodes
		$post->post_content = $this ->convert_shortcode( $post->post_content ); 
		
		preg_match_all($search, $post->post_content, $matches);

		if (is_array($matches[1])) {
			foreach ($matches[1] as $id) {

				$dbresult = $wpdb->get_results("SELECT * FROM $wpdb->wordtube WHERE vid = '$id'");
				if ($dbresult) {
					$file_type = pathinfo(strtolower($dbresult[0]->file));
					$mime_type = "application/unknown";
					if ($file_type["extension"] == "mp3") $mime_type = "audio/mpeg";
					elseif ($file_type["extension"] == "flv") $mime_type = "video/x-flv";
					elseif ($file_type["extension"] == "swf") $mime_type = "application/x-shockwave-flash";
					elseif ($file_type["extension"] == "jpg") $mime_type = "image/jpeg";			
					elseif ($file_type["extension"] == "jpeg") $mime_type = "image/jpeg";
					echo '<enclosure url="'.$dbresult[0]->file.'" length="1" type="'.$mime_type.'"/>'."\n";
				}
			}
		}
	}
	
	 /**
	   * wordTube_shortcodes::convert_shortcode()
	   * convert old shortcodes to the new WordPress core style
	   * [MEDIA=1]  ->> [media id=1]
	   * 
	   * @param string $content Content to search for shortcodes
	   * @return string Content with new shortcodes.
	   */
	function convert_shortcode($content) {
	
		if ( stristr( $content, '[media' )) {
			$search = "@(?:<p>)*\s*\[MEDIA\s*=\s*(\w+|^\+)\]\s*(?:</p>)*@i"; 
			if (preg_match_all($search, $content, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$replace = "[media id={$match[1]}]";
					$content = str_replace ($match[0], $replace, $content);
				}
			}
		}

		if ( stristr( $content, '[media' )) {
			$search = "@(?:<p>)*\s*\[VIDEO\s*=\s*(\w+|^\+)\]\s*(?:</p>)*@i"; 
			if (preg_match_all($search, $content, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$replace = "[media id={$match[1]}]";
					$content = str_replace ($match[0], $replace, $content);
				}
			}
		}		
		
		if ( stristr( $content, '[myplaylist' )) {
			$search = "@(?:<p>)*\s*\[MYPLAYLIST\s*=\s*(\w+|^\+)\]\s*(?:</p>)*@i"; 
			if (preg_match_all($search, $content, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$replace = "[playlist id={$match[1]}]";
					$content = str_replace ($match[0], $replace, $content);
				}
			}
		}
		
		return $content;
	}
	
	function show_media( $atts ) {
	
		global $wordTube;
	
		extract(shortcode_atts(array(
			'id' 		=> false,
			'width' 	=> '',
			'height' 	=> '',
			'plugins'	=> ''
		), $atts ));
		
		// if the page is a single page we look for the autostart option
		$auto = (is_single() && $wordTube->options['startsingle']);
		
		// Get now the video data
		$media = $wordTube->GetVidByID( $id );
		
		if ( empty($width) )
			$width = $media->width;

		if ( empty($height) )
			$height = $media->height;
			
		if ($media) {
			$autostart = ($this->count == 1 && $auto) ? true : $media->autostart;
			$out = $wordTube->ReturnMedia( $media->vid, $media->file, $media->image, $width, $height, $autostart, $media , $plugins );
			$this->count++;
		} else 
			$out = __('[MEDIA not found]','wpTube');
			
		return $out;
	}

	function show_playlist( $atts ) {
	
		global $wordTube, $wpdb;

		extract(shortcode_atts(array(
			'id' 		=> 0,
			'width' 	=> 0,
			'height' 	=> 0,
			'plugins'	=> ''
		), $atts ));
		
		$dbresult = false;
		
		if ( !in_array( $id, $wordTube->PLTags) && is_numeric($id) )
			$dbresult = $wpdb->get_row('SELECT * FROM '.$wpdb->wordtube_playlist.' WHERE pid = '.$id);
		
		// check for tags	
		if ( ($dbresult) || in_array( $id, $wordTube->PLTags) )
			$out = $wordTube->ReturnPlaylist( $id , $width, $height, $plugins);
		else 
			$out = __('[PLAYLIST not found]','wpTube');
		
		return $out;
	}	

	function excerpt_shortcodes($text) {
		
		global $wordTube, $wpdb;
		$text = $this->convert_shortcode($text);
		
		// search for videos
		$search = "/\[media id=(\d+)(?: width=)?(\d+)?(?: height=)?(\d+)?\]/";
		if (preg_match_all($search, $text, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$media = $wordTube->GetVidByID($match[1]);
				$width = $match[2]; 
				$height = $match[3]; 
								
				if (empty($width))
					$width = $media->width;
								
				if (empty($height))
					$height = $media->height;
						
				if ($media)
					$out = $wordTube->ReturnMedia($media->vid, $media->file, $media->image, $width, $height, $media->autostart, $media);
				else
					$out = __('[MEDIA not found]','wpTube');
				
				$text = str_replace($match[0], $out, $text);
			}
		}
		
		// search for playlists
		$dbresult = false;
		$search = "/\[playlist id=(\d+)\]/";			
		if (preg_match_all($search, $text, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$id = $match[1];
				
				if (!in_array($id, $wordTube->PLTags) && is_numeric($id))
					$dbresult = $wpdb->get_row('SELECT * FROM '.$wpdb->wordtube_playlist.' WHERE pid = '.$id);
	
				if (($dbresult) || in_array($id, $wordTube->PLTags))
					$out = $wordTube->ReturnPlaylist($id, $width, $height);
				else
					$out = __('[PLAYLIST not found]','wpTube');

				$text = str_replace($match[0], $out, $text);
			}
		}

		return $text;

	}

}

// let's use it
$wordTubeShortcodes = new wordTube_Shortcodes;	

?>