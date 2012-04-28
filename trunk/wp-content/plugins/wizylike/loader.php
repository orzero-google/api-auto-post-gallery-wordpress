<?php
/*
Plugin Name: WizyLike
Description: A plugin that allow users or the puplic to like/unlike posts with a sidebar widget to display most liked posts.
Version: 1.6
Author: WizyLabs
Author URI: http://wizylabs.com
*/

// constants paths
define('WL_PATH', dirname(__FILE__));

// constants URIs
define('WL_URI', get_bloginfo('wpurl') . '/wp-content/plugins/wizylike');
define('WL_CSSURI', WL_URI . '/css');
define('WL_JSURI', WL_URI . '/js');
define('WL_IMGURI', WL_URI . '/images');


// Calls database global
global $wpdb, $wl_tablename;


// Combines default db tables prefix with our newly tabel name
$wl_tablename = $wpdb->prefix . 'wizylike';

// Runs when the plugin is activated
function wizylike_activate() {
	global $wpdb, $wl_tablename;
	
	if (!empty($wpdb->charset))
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		
		// run the SQL statement on the database
		$wpdb->query("CREATE TABLE {$wl_tablename} (
							id BIGINT(20) NOT NULL AUTO_INCREMENT,
							post_id BIGINT(20) NOT NULL,
							ip_address VARCHAR(25) NOT NULL,
							user_id BIGINT(20) NOT NULL,
							like_status VARCHAR(25) NOT NULL DEFAULT 'like',
							PRIMARY KEY (id), 
							UNIQUE (id)
							){$charset_collate};");
		
		$wpdb->query("ALTER TABLE `$wpdb->posts` ADD `like_count` BIGINT( 20 ) NOT NULL DEFAULT  '0'");
	
	update_option('wizylike_capabilities', 'users-only');
	update_option('wizylike_colour', 'red');
	update_option('wizylike_style', 'style_1');
	update_option('wizylike_like_txt', 'Like?');
	update_option('wizylike_unlike_txt', 'Unlike!');
	update_option('wizylike_widget_txt', 'Likes');
	
	
}
register_activation_hook(__FILE__, 'wizylike_activate');


// Runs when the plugin is deactivated
function wizylike_deactivate() {
	global $wpdb, $wl_tablename;
	
	$wpdb->query("ALTER TABLE `$wpdb->posts` DROP `like_count`;");
	$wpdb->query("DROP TABLE IF EXISTS {$wl_tablename};");
}
register_deactivation_hook(__FILE__, 'wizylike_deactivate');



add_action('init', 'wizylike_init');






// wizylike front-end init
function wizylike_init(){
	
	// includes main class
	require_once(WL_PATH . '/class.wizylike.php');
	
	// includes template tags for ease of usage
	require_once(WL_PATH . '/template-tags.php');
	
	wp_enqueue_script('wizylike', WL_JSURI . '/wizylike.js', false, '1.0', false);
}


/* *******************************************************************************************
 * The code below creates the wizylike disable button on any new post page or update post page
 */

$meta_key = 'wizylike';

function create_wizylike_meta_box() {
	if(function_exists('add_meta_box')) {
		add_meta_box( 'wizylike-metabox', 'WizyLike', 'wizylike_meta_box', 'post', 'side', 'high' );
	}
}

function wizylike_meta_box() {
	global $post, $meta_key;
	
	$checked = '';
	$meta_val = get_post_meta($post->ID, $meta_key, true);
	
	if($meta_val === 'disabled'){
		$checked = 'checked';
	}
	
	$form =  '<div class="form-wrap">' . "\n";
	$form .= '<div class="form-field">' . "\n";
	$form .= '<label for="disable_wizylike">' . "\n";
	$form .= '<input type="checkbox" name="disable_wizylike" id="disable_wizylike" style="width: auto;" ' . $checked . '>';
	$form .= ' Disable Wizylike on this post</label>' . "\n" . '</div>' . "\n" . '</div>';
	
	echo $form;
}

function save_wizylike_meta_box($post_id) {
	global $post, $meta_key;
	
	if(!current_user_can( 'edit_post', $post_id))
		return $post_id;
	
	if(isset($_POST['disable_wizylike'])) {
		update_post_meta($post_id, $meta_key, 'disabled');
	} else {
		update_post_meta($post_id, $meta_key, 'enabled');
	}
}

add_action('admin_menu', 'create_wizylike_meta_box');
add_action('save_post', 'save_wizylike_meta_box');

?>