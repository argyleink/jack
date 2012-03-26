<?php

/**
 * Content of Dashboard-Widget
 */
function wt_dashboard_statistics() {
	global $wpdb;
	$tables = $wpdb->get_results("SELECT * FROM ".$wpdb->wordtube." ORDER BY vid DESC"." LIMIT 0, 5");

	echo '<p><strong>' . __('Newest','wpTube') . '</strong><br />';
	if($tables) {
		foreach($tables as $table) {
			echo $table->vid.'. '."<strong><a title='" . __('Edit this media','wpTube') . "' href='admin.php?page=wordTube&amp;mode=edit&amp;id=$table->vid'>" . stripslashes($table->name) . "</a>".': '. $table->counter .'</strong> <br/>';
		}
	}
	echo '</p>';
	
	$tables = $wpdb->get_results("SELECT * FROM ".$wpdb->wordtube." ORDER BY counter DESC"." LIMIT 0, 5");
	
	echo '<p><strong>' . __('Most popular','wpTube') . '</strong><br />';
	if($tables) {
		foreach($tables as $table) {
			echo $table->vid.'. '."<strong><a title='" . __('Edit this media','wpTube') . "' href='admin.php?page=wordTube&amp;mode=edit&amp;id=$table->vid'>" . stripslashes($table->name) . "</a>".': '. $table->counter .'</strong> <br/>';
		}
	}
	echo '</p>';
	echo '<a class="button rbutton" href="upload.php?page=wordTube&mode=add">'. __('Insert new media file','wpTube') .' &raquo;</a>';
}
 
/**
 * add Dashboard Widget via function wp_add_dashboard_widget()
 */
function wt_dashboard_statistics_setup() {
	wp_add_dashboard_widget( 'wt_dashboard_statistics', __( 'WordTube Statistics','wpTube' ), 'wt_dashboard_statistics' );
}
 
/**
 * use hook, to integrate new widget
 */
add_action('wp_dashboard_setup', 'wt_dashboard_statistics_setup');
?>
