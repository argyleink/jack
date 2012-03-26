<?php
/*
+----------------------------------------------------------------+
+	wordtube-functions
+	by Alex Rabe & Alakhnor
+   required for wordtube
+----------------------------------------------------------------+
*/
/***********************************************************************************
	function wt_get_options($option)
		returns a wordTube option
		
 	function wt_GetVideo($id, $width=0, $height=0)
		returns a media with specified size
	
 	function wt_GetPlaylist($id, $width=0, $height=0, $exc=false)
		returns a playlist with specified size
	
 	function wt_get_related_media($user_args='', $width=0, $height=0)
		returns post related media with specified size.
	
 	function wt_get_related_media_list($user_args='')
		returns a list of related media. user_arg specified if 
		it is related to a post or a media.
	
 	function get_the_media_tags($id)
		returns the tag list of a media
		
***********************************************************************************/


/**************************************************************************************
	Get wordtube options.
**************************************************************************************/
function wt_get_options($option) {
	global $wordTube;
		return $wordTube->wordtube_options[$option];
}

/**************************************************************************************
	Return video by id with new width and height

	If width & height = 0, returns database video sizes
	If only height = 0, adjust height to width in proportions of database sizes
**************************************************************************************/
function wt_GetVideo($id, $width = 0 , $height = 0) {
	global $wpdb, $wordTube;

	$dbresult = $wordTube->GetVidByID($id);
	if ($dbresult) {
		if ($width == 0) $width = $dbresult->width;
		if ($height == 0) {
			if ($dbresult->width == 0)
				$height = $dbresult->height;
			else
				$height = $width / $dbresult->width * $dbresult->height;
		}
		return $wordTube->ReturnMedia($id, $dbresult->file, $dbresult->image, $width, $height, $dbresult->autostart, $dbresult);
	}
	return;
}
/**************************************************************************************
	Return video by id with new width and height
**************************************************************************************/
function wt_GetPlaylist($id, $width =0 , $height = 0) {
	global $wordTube;

	return $wordTube->ReturnPlaylist($id, $width, $height);
}
/**************************************************************************************
	Return related video

	If width & height = 0, returns database video sizes
	If only height = 0, adjust height to width in proportions of database sizes
	works only for WordPress 2.3 and above
**************************************************************************************/
function wt_get_related_media($user_args='', $width=0, $height=0) {
	global $wordTube;

	$dbresults = wt_get_related_media_list($user_args);
	if ($dbresults) {
		$ret = '';
		foreach ($dbresults as $dbresult) :

			if ($width == 0) $width = $dbresult->width;
			if ($height == 0) {
				if ($dbresult->width == 0)
					$height = $dbresult->height;
				else
					$height = $width / $dbresult->width * $dbresult->height;
			}
			$ret .= $wordTube->ReturnMedia($id, $dbresult->file, $dbresult->image, $width, $height, $dbresult->autostart, $dbresult);

		endforeach;
		return $ret;
	}
	return;
}
/**************************************************************************************
	Returns related video list
		parameters should be passed in a query style (a '&' separated list)

		number		: max number of record to return
		order		: sort order (count-asc|count-desc|name-asc|name-desc|random)
			count	: number of shared tags between post and media
		type		: filter on media type (all|mp3|flv)
		media_id	: if 0, result for current post
		post_id		: if 0, result for current post (only if media_id is '')
		exclude_tags	: list of tags to exclude from count
		min_shared	: minimum common tags between post and media.

	Inspired from Amo's simple tags plugin.
**************************************************************************************/
function wt_get_related_media_list($user_args='') {
	global $wpdb, $wordTube;

	$defaults = array(
		'number' => 5,
		'order' => 'count-desc',
		'type' => 'all',
		'post_id' => '',
		'media_id' => '',
		'exclude_tags' => '',
		'min_shared' => 1,
		'use_cache' => false
		);
	$default['use_cache'] = $wordTube->use_cache;

	$args = wp_parse_args( $user_args, $defaults );
	extract($args);

	// Get media or post data
	if ($media_id != '' && $media_id != 0) {
		$object_id = (int) $media_id;
		$src = 'media';
	} else {
		$object_id = (int) $post_id;
		if ( $post_id == 0 ) {
			global $post;
			$object_id = (int) $post->ID;
		}
		$src = 'post';
	}
		

	// Get cache if exist
	$results = false;		
	if ( $use_cache === true ) { // Use cache
		// Generate key cache
		$key = md5(maybe_serialize('wt-'.$user_args.'-'.$object_id));

		if ( $cache = wp_cache_get( 'related_media', 'wordtube' ) ) {
			if ( isset( $cache[$key] ) ) {
				$results = $cache[$key];
			}
		}
	}

	// If cache not exist, get datas and set cache
	if ( $results === false || $results === null ) {

		// Get get tags                         
		if ($src == 'media') {
			$current_tags = get_the_media_tags( (int) $object_id );
			$exclude_id = " AND (p.vid <> {$object_id}) ";
		} else {
			$current_tags = get_the_tags( (int) $object_id );
			$exclude_id = "";
		}
		if ( $current_tags === false ) {
			return false;
		}
	
		// Number - Limit
		$number = (int) $number;
		if ( $number == 0 ) {
			$number = 5;
		} elseif( $number > 50 ) {
			$number = 50;
		}
		$limit_sql = 'LIMIT 0, '.$number;
		unset($number);
	
	       	// Order tags before output (count-asc/count-desc/date-asc/date-desc/name-asc/name-desc/random)
		$order_by = '';
		$order = strtolower($order);
		switch ( $order ) {
			case 'count-asc':
				$order_by = 'count ASC, p.name DESC';
				break;
			case 'random':
				$order_by = 'RAND()';
				break;
			case 'name-asc':
				$order_by = 'p.name ASC';
				break;
			case 'name-desc':
				$order_by = 'p.name DESC';
				break;
			default: // count-desc
				$order_by = 'count DESC, p.name DESC';
				break;
		}
	
		// Restricts tags
		$tags_to_exclude = array();
		if ( $exclude_tags != '' ) {
			$exclude_tags = (array) explode(',', $exclude_tags);
			foreach ( $exclude_tags as $value ) {
				$tags_to_exclude[] = trim($value);
			}
		}
		unset($exclude_tags);
	
		// Media select
		if ($type == 'flv' || $type == 'mp3')
			$select_media_sql = " AND p.file LIKE '%{$type}%' ";
		else
			$select_media_sql = '';
		unset($type);
	
		// SQL Tags list
		$tag_list = '';
		foreach ( (array) $current_tags as $tag ) {
			if ( !in_array($tag->name, $tags_to_exclude) ) {
				$tag_list .= '"'.(int) $tag->term_id.'", ';
			}
		}
	
		// If empty return blank
		if ( empty($tag_list) ) {
			return '';
		}
	
		// Remove latest ", "
		$tag_list = substr($tag_list, 0, strlen($tag_list) - 2);
	
		// Posts: title, comments_count, date, permalink, post_id, counter
		$results = $wpdb->get_results("
			SELECT p.*, COUNT(tr.object_id) AS count 
			FROM {$wpdb->wordtube} AS p
			INNER JOIN {$wpdb->term_relationships} AS tr ON (p.vid = tr.object_id)
			INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
			WHERE tt.taxonomy = '".WORDTUBE_TAXONOMY."'
			{$exclude_id}
			AND (tt.term_id IN ({$tag_list}))
			{$select_media_sql}
			GROUP BY tr.object_id
			ORDER BY {$order_by}
			{$limit_sql}
			");

		if ( $use_cache === true) { // Use cache
			$cache[$key] = $results;
			wp_cache_set('related_media', $cache, 'wordtube');
		}
	}
	
	return $results;
}
/**************************************************************************************
	Returns the tag list of a media
**************************************************************************************/
function get_the_media_tags($id) {

 	$id = (int) $id;

	if ( ! $id )
		return false;

	$tags = get_object_term_cache($id, WORDTUBE_TAXONOMY);
	if ( false === $tags )
		$tags = wp_get_object_terms($id, WORDTUBE_TAXONOMY);

	$tags = apply_filters( 'get_the_media_tags', $tags );
	if ( empty( $tags ) )
		return false;
	return $tags;
}
/**************************************************************************************
	 _update_media_term_count() - Will update term count based on media

	Private function for the default callback for wt_tag.
	@param array $terms List of Term IDs
**************************************************************************************/
function _update_media_term_count( $terms ) {
	global $wpdb;

	foreach ( $terms as $term ) {
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->wordtube WHERE $wpdb->wordtube.vid = $wpdb->term_relationships.object_id AND term_taxonomy_id = %d", $term ) );
		$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
	}
}

?>