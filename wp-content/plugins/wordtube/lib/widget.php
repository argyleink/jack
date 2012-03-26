<?php 

/**
 * wordTube_Widget. Require WordPress 2.8 or higher
 * 
 * @package wordTube
 * @author Alex Rabe
 * @copyright 2010
 * @version 2.0.0
 * @access public
 */
class wordTube_Widget extends WP_Widget {
	
	function wordTube_Widget() {
		$widget_ops = array('classname' => 'widget_wordtube', 'description' => __( 'Show a WordTube video', 'wpTube') );
		$this->WP_Widget('wid-show-wordtube', __('WordTube', 'wpTube'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract( $args );

		global $wordTube;
		 
		extract($args);
	    
		$title   = apply_filters('widget_title', empty( $instance['title'] ) ? __('WordTube', 'wpTube') : $instance['title']);
		$mediaid = $instance['mediaid'];
		$width   = (int) $instance['width'];
		$height  = (int) $instance['height'];
		
		$dbresult = $wordTube->GetVidByID($mediaid);

        echo $before_widget;
        if ( $title)
			echo $before_title . $title . $after_title;
		echo '<div class="wordtube-widget">';
		if ($dbresult)
			if ($width == 0) 
                $width = $dbresult->width;
			if ($height == 0) {
				if ($dbresult->width == 0)
					$height = $dbresult->height;
				else
					$height = $width / $dbresult->width * $dbresult->height;
			}
			echo $wordTube->ReturnMedia($dbresult->vid, $dbresult->file, $dbresult->image, $width, $height, $dbresult->autostart, $dbresult);
		echo '</div>';
		echo $after_widget;
  
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		$instance['title']   = strip_tags($new_instance['title']);
		$instance['mediaid'] = $new_instance['mediaid'];
		$instance['width']   = (int) $new_instance['width'];
		$instance['height']  = (int) $new_instance['height'];

		return $instance;
	}

	function form( $instance ) {
		
		global $wpdb;

		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'WordTube', 'mediaid' => 'last', 'height' => '120', 'width' => '160') );
		$title  = esc_attr( $instance['title'] );
		$vid    = $instance['mediaid'];
        $height = intval( $instance['height'] );
		$width  = intval( $instance['width'] );
        
        $tables = $wpdb->get_results("SELECT * FROM $wpdb->wordtube ORDER BY 'vid' ASC ");
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
		<p>
			<label for="<?php echo $this->get_field_id('mediaid'); ?>"><?php _e('Select Media:', 'wpTube'); ?></label>
				<select size="1" name="<?php echo $this->get_field_name('mediaid'); ?>" id="<?php echo $this->get_field_id('mediaid'); ?>" class="widefat">
                    <option value="last" <?php if ('last' == $instance['mediaid']) echo "selected='selected' "; ?> ><?php _e('Last media', 'wpTube'); ?></option>
                    <option value="random" <?php if ('random' == $instance['mediaid']) echo "selected='selected' "; ?> ><?php _e('Random media', 'wpTube'); ?></option>                    
<?php
				if( is_array($tables) ) {
					foreach($tables as $table) {
					echo '<option value="'.$table->vid.'" ';
					if ($table->vid == $instance['mediaid']) echo "selected='selected' ";
					echo '>'.$table->name.'</option>'."\n\t"; 
					}
				}
?>
				</select>
		</p>
		<p><label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Height :', 'wpTube'); ?></label> <input id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" style="padding: 3px; width: 45px;" value="<?php echo $height; ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('Width :', 'wpTube'); ?></label> <input id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" style="padding: 3px; width: 45px;" value="<?php echo $width; ?>" /></p>
<?php	
	}
}

// register it
add_action('widgets_init', create_function('', 'return register_widget("wordTube_Widget");'));

?>