<?php
// ************************************
// ** Admin Section for wordTube
// ** by Alex Rabe
// ************************************

class wordTubeAdmin extends wordTubeClass {

	// constructor
	function wordTubeAdmin() {

		add_action( 'admin_menu', array (&$this, 'add_menu') );
		// Do Media Button later
		// add_action( 'media_buttons', array(&$this, 'add_media_buttons'), 20 );
		
		// get the options
		$this->options = get_option('wordtube_options');
		
		// check if we need to upgrade
		if ( $this->options['version'] < WORDTUBE_VERSION  ) {
			// Execute installation
			$this->install();
		}
	
	}

	/**
	 * Display a standard error message (using CSS ID 'message' and classes 'fade' and 'error)
	 *
	 * @param string $message Message to display
	 * @return void
	 **/
	
	function render_error ($message)
	{
		?>
		<div class="wrap"><h2>&nbsp;</h2>
		<div class="error" id="error">
		 <p><strong><?php echo $message ?></strong></p>
		</div></div>
		<?php
	}
	
	/**
	 * Display a standard notice (using CSS ID 'message' and class 'updated').
	 * Note that the notice can be made to automatically disappear, and can be removed
	 * by clicking on it.
	 *
	 * @param string $message Message to display
	 * @param int $timeout Number of seconds to automatically remove the message (optional)
	 * @return void
	 **/
	
	function render_message ($message, $timeout = 0)
	{
		?>
		<div class="wrap"><h2>&nbsp;</h2>
		<div class="fade updated" id="message" onclick="this.parentNode.removeChild (this)">
		 <p><strong><?php echo $message ?></strong></p>
		</div></div>
		<?php	
	}	
	
	function add_admin_js() {
		
		switch ($_GET['page']) {
			case "wordTube" :
				wp_enqueue_script('postbox');
                wp_enqueue_script('jquery');
				wp_enqueue_script('swfobject', WORDTUBE_URLPATH.'javascript/swfobject.js', false, '2.1');
                echo '<link rel="stylesheet" href="'.WORDTUBE_URLPATH.'admin/css/jquery-ui.css" type="text/css" media="print, projection, screen" />'."\n";
                echo '<link rel="stylesheet" href="'.WORDTUBE_URLPATH.'admin/css/ui.theme.css" type="text/css" media="print, projection, screen" />'."\n";
			break;
			case "wordtube-options" :
				wp_enqueue_script('jquery-ui-tabs');
				echo '<link rel="stylesheet" href="'.WORDTUBE_URLPATH.'admin/css/jquery.ui.tabs.css" type="text/css" media="print, projection, screen" />'."\n";
			break;		
		}
	}	

	// integrate the menu	
	function add_menu()  {
		$mediacenter = add_media_page  ( __('wordTube','wpTube'), __('wordTube','wpTube'), 'edit_posts' , 'wordTube', array (&$this, 'show_menu') );
	    $options 	 = add_options_page( __('wordTube','wpTube'), __('wordTube','wpTube'), 'manage_options', 'wordtube-options', array (&$this, 'show_menu') );
		
		add_action( "admin_print_scripts-$mediacenter", array (&$this, 'add_admin_js') );
		add_action( "admin_print_scripts-$options", array (&$this, 'add_admin_js') );
		
	}

	// load the script for the defined page  and load only this code	
	function  show_menu() {
		switch ($_GET["page"]){
			case "wordTube" :
				include_once (dirname (__FILE__). '/functions.php');	// admin functions
				include_once (dirname (__FILE__). '/manage.php');		// admin functions
				$MediaCenter = new wordTubeManage ();
				break;
			case "wordtube-options" :
				include_once (dirname (__FILE__). '/settings.php');		// settings
				wordtube_admin_options();
				break;
		}
	}

	// Return custom upload dir/url
	function add_media_buttons() {

		$media_upload_iframe_src = WORDTUBE_URLPATH.'admin/media.php';
		$media_title = __('Insert wordTube media', 'wpTube' );
		$image = WORDTUBE_URLPATH.'images/wordtube.gif';
		$out = '<a href="'.$media_upload_iframe_src.'?TB_iframe=true&amp;height=150&amp;width=300" class="thickbox" title="'.$media_title.'"><img src="'.$image.'" alt="'.$media_title.'" /></a>';
		printf($out);

	}
	
	// Install  & upgarde routine for wordtube
	function install() {

		require_once (dirname (__FILE__). '/install.php');
		wordtube_install();
    }

    /**
     * Search for the flash file and return the relative path
     * 
     * @param string $filename
     * @return string $path on sucess, false if failed
     */
    function search_file( $filename ) {
    	global $wpdb;
    
    	$upload = wp_upload_dir();

    	// look first at the old place and move it to wp-content/uploads
    	if ( is_readable( trailingslashit( WORDTUBE_ABSPATH ) . $filename ) ) {
    		@rename( trailingslashit( WORDTUBE_ABSPATH ) . $filename, trailingslashit($upload['basedir']) . $filename);
    		@rename( trailingslashit( WORDTUBE_ABSPATH ) . 'yt.swf', trailingslashit($upload['basedir']) . 'yt.swf');
    	}

    	// this should be the best place 	
    	if ( is_readable( trailingslashit($upload['basedir']) . $filename ) )
    		return trailingslashit ( get_option( 'upload_path' ) ) . $filename;
    
    	// Find the path to the file via the media library
    	if ( $ID = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE guid LIKE '%$filename%'" ) ) {
            if ( $path = get_post_meta( $ID, '_wp_attached_file', true ) )
                return trailingslashit ( get_option( 'upload_path' ) ) . $path;
    	}
        
    	// maybe it's located in wp-content
    	if ( is_readable( trailingslashit(WP_CONTENT_DIR) . $filename ) )
            return str_replace(ABSPATH, '', trailingslashit(WP_CONTENT_DIR) . $filename);	   
    
    	// or in the plugin folder
    	if ( is_readable( trailingslashit(WP_PLUGIN_DIR) . $filename ) )
            return str_replace(ABSPATH, '', trailingslashit(WP_PLUGIN_DIR) . $filename);	   
            
    	// this is deprecated and will be ereased during a automatic upgrade
    	if ( is_readable( trailingslashit ( WORDTUBE_ABSPATH ) . $filename ) )
            return str_replace(ABSPATH, '', trailingslashit( WORDTUBE_ABSPATH ) . $filename);	   
                		
    	return false;
    }
	
} // end of admin class
?>