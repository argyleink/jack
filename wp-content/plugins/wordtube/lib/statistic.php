<?php
if (!defined ('ABSPATH')) die ('No direct access allowed');

global $wpdb;

if ( isset($_POST['file']) )  {
	//update the counter for this file +1
	$filename = urldecode( $_POST['file'] );
	$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->wordtube} SET counter = counter + 1 WHERE file = '%s'", $filename ) );
	die('1');
}
die('0');
?>