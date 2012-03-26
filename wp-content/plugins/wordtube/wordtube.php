<?php
/*
Plugin Name: wordTube
Plugin URI: http://alexrabe.de/?page_id=20
Description: This plugin manages the JW FLV MEDIA PLAYER 5.0 and makes it easy for you to put music, videos or flash movies onto your WordPress posts and pages. Various skins for the JW PLAYER are available via www.jeroenwijering.com
Author: Alex Rabe & Alakhnor
Version: 2.4.0
Author URI: http://alexrabe.de/

Copyright 2006-2011 Alex Rabe , Alakhnor

The wordTube button is taken from the Silk set of FamFamFam. See more at 
http://www.famfamfam.com/lab/icons/silk/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

global $wp_version;

// The current version
define('WORDTUBE_VERSION', '2.4.0');

if ( version_compare($wp_version, '2.8', '<') ){
	add_action('admin_notices', create_function('', 'echo \'<div id="message" class="error fade"><p><strong>' . __('Sorry, wordTube works only under WordPress 2.8 or higher',"wpTube") . '</strong></p></div>\';'));
	return;
}

// define URL
define('WORDTUBE_ABSPATH', WP_PLUGIN_DIR . '/' . plugin_basename( dirname(__FILE__) ).'/' );
define('WORDTUBE_URLPATH', WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ).'/' );
define('WORDTUBE_TAXONOMY', 'wt_tag');

include (dirname (__FILE__) . '/lib/functions.php');
include_once (dirname (__FILE__) . '/lib/widget.php');
include_once (dirname (__FILE__) . '/lib/shortcodes.php');
include_once (dirname (__FILE__) . '/lib/wordtube.class.php');
include_once (dirname (__FILE__) . '/lib/swfobject.php');
include_once (dirname (__FILE__) . '/tinymce/tinymce.php');

// Insert the add_wpTube() sink into the plugin hook list for 'admin_menu'
if (is_admin()) {
  	include_once ( WORDTUBE_ABSPATH  . '/admin/dashboard.php' );
	include_once ( WORDTUBE_ABSPATH  . '/admin/admin.php' );
	$wordTubeAdmin = new wordTubeAdmin ();
}

// Start this plugin once all other plugins are fully loaded
add_action( 'plugins_loaded', create_function( '', 'global $wordTube; $wordTube = new wordTubeClass();' ) );

/**
 * wt_install()
 * init wpTable in wp-database if plugin is activated
 * 
 * @return void
 */
function wt_install() {

	require_once(dirname (__FILE__). '/admin/install.php');
	wordtube_install();
} 

// Init options & tables during activation 
register_activation_hook( plugin_basename( dirname(__FILE__) ) . '/wordtube.php', 'wt_install' );

/**
 * wt_main_init() - Loads language and taxonomy file at init
 * 
 * @return void
 */
function wt_main_init () {
	
	load_plugin_textdomain('wpTube', false, dirname( plugin_basename(__FILE__) ) . '/languages');

	register_taxonomy( WORDTUBE_TAXONOMY, 'wordtube', array('update_count_callback' => '_update_media_term_count') );

}

/**
 * wt_add_queryvars() - adding a new query var
 * 
 * @param mixed $query_vars
 * @return
 */
function wt_add_queryvars( $query_vars ){
	
    $query_vars[] = 'xspf';
    $query_vars[] = 'wt-stat';
    $query_vars[] = 'wordtube-js';

	return $query_vars;
}

/**
 * check_request() - Callback and output the content XSPF playlist or statistic
 * 
 * @param mixed $wp
 * @return
 */
function wt_check_request( $wp ) {
    
    if (array_key_exists('xspf', $wp->query_vars) && $wp->query_vars['xspf'] == 'true') {
		// Create XML output
		require_once (dirname (__FILE__) . '/myextractXML.php');
        exit();
    }
    
    if (array_key_exists('wordtube-js', $wp->query_vars) && $wp->query_vars['wordtube-js'] == 'true') {
		// Create XML output
		require_once (dirname (__FILE__) . '/javascript/statistic.js.php');
        exit();
    }

    if (array_key_exists('wt-stat', $wp->query_vars) && $wp->query_vars['wt-stat'] == 'true') {
		// Create XML output
		require_once (dirname (__FILE__) . '/lib/statistic.php');
        exit();
    }
    
}

// Parse the $_GET vars for callbacks
add_filter('query_vars', 'wt_add_queryvars' );
add_action('parse_request',  'wt_check_request', 9 );  

// init some functions
add_action('init', 'wt_main_init');