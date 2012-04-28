<?php
define('THEME_NAME','iphoto');

// LOCALIZATION
load_theme_textdomain( THEME_NAME,TEMPLATEPATH .'/languages');

// Custom background
add_custom_background();

// Add default posts and comments RSS feed links to head
add_theme_support( 'automatic-feed-links' );

// Add post-formats
add_theme_support( 'post-formats', array( 'video'));

// WP nav menu
if ( function_exists('register_nav_menus') ) {
	register_nav_menus(array('primary' => 'header'));
}

// Widgetized Sidebar.
add_action( 'widgets_init', 'iphoto_widgets_init' );
function iphoto_widgets_init() {
	register_sidebar(array(
		'name' => __('Primary Widget Area','iphoto'),
		'id' => 'primary-widget-area',
		'description' => __('The primary widget area','iphoto'),
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget' => '</div>',
		'before_title' => '<h2>',
		'after_title' => '</h2>'
	));
}

// Add post_meta likes
add_action('publish_post', 'add_likes_fields');
function add_likes_fields($post_ID) {
	add_post_meta($post_ID, 'likes', 0, true);
}

// Delete post_meta likes
add_action('delete_post', 'delete_likes_fields');
function delete_likes_fields($post_ID) {
	global $wpdb;
	if(!wp_is_post_revision($post_ID)) {
		delete_post_meta($post_ID, 'likes');
	}
}

// Update post_meta likes
add_action('save_post', 'update_thumbnail_fields');
function update_thumbnail_fields($post_ID) {
	delete_post_meta($post_ID, 'thumbnail');
	add_thumbnail_fields($post_ID);
}

// Add post_meta thumbnail
add_action('publish_post', 'add_thumbnail_fields');
function add_thumbnail_fields($post_ID) {
    delete_post_meta($post_ID, 'thumbnail');
    $gallery_id=get_post_meta($post_ID, 'gallery', $single = true);

    if($gallery_id){
        $gallerys=nggdb::get_gallery($gallery_id, $order_by = 'sortorder', $order_dir = 'ASC', $exclude = false, $limit = 1, $start=rand(0, 10));
        foreach($gallerys as $gallery){
            $post_img=$gallery['thumbHTML'];
        }
        add_post_meta($post_ID, 'thumbnail', $post_img, true);
    }else{
        global $wpdb;
        if(!wp_is_post_revision($post_ID)) {
            $content_post = get_post($post_ID);
            $content = $content_post->post_content;
            $post_img = '';
            ob_start();
            ob_end_clean();
            $output = preg_match_all('/\<img.+?src="(.+?)".*?\/>/is',$content,$matches ,PREG_SET_ORDER);
            $cnt = count( $matches );
            if($cnt>0){
                $post_img_src = $matches [0][1];
                $args = getimagesize($post_img_src);
                $height = $args[1]*285/$args[0];
                $post_img = '<img src="'.get_bloginfo('template_url').'/timthumb.php?src='.$post_img_src.'&amp;w=285&amp;zc=1" width="285" height="'.$height.'"/>';
            }
            add_post_meta($post_ID, 'thumbnail', $post_img, true);
        }
    }
}

// Delete post_meta thumbnail
add_action('delete_post', 'delete_thumbnail_fields');
function delete_thumbnail_fields($post_ID) {
	global $wpdb;
	if(!wp_is_post_revision($post_ID)) {
		delete_post_meta($post_ID, 'thumbnail');
	}
}

// Write the thumbnail data to Database
function ajax_thumbnail() {
    return;
	if( isset($_GET['action'])&& $_GET['action'] == 'update_thumbnail'){
		$the_query = new WP_Query( 'posts_per_page=-1&ignore_sticky_posts=1' );
		set_time_limit(0);
		error_reporting(0);
		while ( $the_query->have_posts() ) : $the_query->the_post();
			$ID = get_the_ID();
			$content = get_the_content();
			$post_img = '';
			ob_start();
			ob_end_clean();
			$output = preg_match_all('/\<img.+?src="(.+?)".*?\/>/is',$content,$matches ,PREG_SET_ORDER);
			$cnt = count( $matches );
			if($cnt>0){
				$post_img_src = $matches [0][1];
				$args = getimagesize($post_img_src);
				$height = round($args[1]*285/$args[0]);
				$post_img = '<img src="'.get_bloginfo('template_url').'/timthumb.php?src='.$post_img_src.'&amp;w=285&amp;zc=1" width="285" height="'.$height.'"/>';
			}
			add_post_meta($ID, 'thumbnail', $post_img, true);
		endwhile;
		wp_reset_postdata();
	}
 }
add_action('init', 'ajax_thumbnail');

// Ajax upload photo
add_action('wp_ajax_b_ajax_post_action', 'b_ajax_callback');
function b_ajax_callback() {
  global $wpdb;
  if(isset($_POST['type']) && $_POST['type'] == 'upload') {
    $clickedID = $_POST['data']; 
    $filename = $_FILES[$clickedID];
    $filename['name'] = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename['name']); 
    $override['test_form'] = false;
    $override['action'] = 'wp_handle_upload';    
    $uploaded_file = wp_handle_upload($filename,$override);
    $upload_tracking[] = $clickedID;	
    update_option($clickedID, $uploaded_file['url'] );			
    if(!empty($uploaded_file['error'])) {echo 'Upload Error: ' . $uploaded_file['error']; }	
    else { echo $uploaded_file['url']; }
  }	
  die();
}

// Ajax load posts
function ajax_post(){
	if( isset($_GET['action'])&& $_GET['action'] == 'ajax_post'){
		if(isset($_GET['meta'])){
			$args = array(
				'meta_key' => $_GET['meta'],
				'orderby'   => 'meta_value_num',
				'paged' => $_GET['pag'],
				'order' => DESC
			);
		}else if(isset($_GET['cat'])){
			$args = array(
				'category_name' => $_GET['cat'],
				'paged' => $_GET['pag']
			);
		}else if(isset($_GET['pag'])){
			$args = array(
				'paged' => $_GET['pag']
			);
		}
		query_posts($args);
		if(have_posts()){while (have_posts()):the_post();?>
					<?php get_template_part( 'content', get_post_format() ); ?>
			<?php endwhile;}
		die();
	}else{return;}
}
add_action('init', 'ajax_post');

function pagenavi( $p = 2 ) { //Mini Pagenavi v1.0 by Willin Kan. Edit by zwwooooo
if ( is_singular() ) return;
global $wp_query,$paged;
$max_page = $wp_query->max_num_pages;
if ( $max_page == 1 ){
echo '<span id="post-current">1</span> / <span id="post-count">1</span>';
 return;
}
if ( empty( $paged ) ) $paged = 1;
if ( $paged >1 ) echo "<a id='prev' title='Prev' href='",esc_html( get_pagenum_link( $paged -1 ) ),"'>&lt;&lt;</a> ";
echo '<span id="post-current">'.$paged .'</span> / <span id="post-count">'.$max_page .'</span>';
if ( $paged <$max_page  ) echo "<a id='next' title='Next' href='",esc_html( get_pagenum_link( $paged +1) ),"'>&gt;&gt;</a> ";
}

//Theme comments lists
function iphoto_comment($comment,$args,$depth) {
$GLOBALS['comment'] = $comment;
;echo '	<li ';comment_class();;echo ' id="li-comment-';comment_ID() ;echo '" >
		<div id="comment-';comment_ID();;echo '" class="comment-body">
			<div class="commentmeta">';echo get_avatar( $comment->comment_author_email,$size = '48');;echo '</div>
				';if ($comment->comment_approved == '0') : ;echo '				<em>';_e('Your comment is awaiting moderation.') ;echo '</em><br />
				';endif;;echo '			<div class="commentmetadata">&nbsp;-&nbsp;';printf(__('%1$s %2$s'),get_comment_date('Y.n.d'),get_comment_time('G:i'));;echo '</div>
			<div class="reply">';comment_reply_link(array_merge( $args,array('depth'=>$depth,'max_depth'=>$args['max_depth'],'reply_text'=>__('Reply')))) ;echo '</div>
			<div class="vcard">';printf(__('%s'),get_comment_author_link()) ;echo '</div>
			';comment_text() ;echo '		</div>
';
}
add_action('admin_init', 'iphoto_init');
function iphoto_init() {
	if (isset($_GET['page']) && $_GET['page'] == 'functions.php') {
		$dir = get_bloginfo('template_directory');
		wp_enqueue_script('adminjquery', $dir . '/includes/admin.js', false, '1.0.0', false);
		wp_enqueue_style('admincss', $dir . '/includes/admin.css', false, '1.0.0', 'screen');
	}
}

//Theme option
add_action('admin_menu','iphoto_page');
function iphoto_page (){
if ( count($_POST) >0 &&isset($_POST['iphoto_settings']) ){
$options = array ('keywords','description','analytics','lib','views','noajax','animate','phzoom','copyright');
foreach ( $options as $opt ){
delete_option ( 'iphoto_'.$opt,$_POST[$opt] );
add_option ( 'iphoto_'.$opt,$_POST[$opt] );
}
}
add_theme_page('iPhoto '.__('Theme Options',THEME_NAME),__('Theme Options',THEME_NAME),'edit_themes',basename(__FILE__),'iphoto_settings');
}
function iphoto_settings(){?>
<div class="wrap">
<div>
<h2><?php _e( 'iPhoto Theme Options<span>Version: ',THEME_NAME);?><?php $theme_data=get_theme_data(TEMPLATEPATH . '/style.css'); echo $theme_data['Version'];?></span></h2>
</div>
<div class="clear"></div>
<form method="post" action="">
	<div id="theme-Option">
		<div id="theme-menu">
			<span class="m1"><?php _e( 'jQuery Effect',THEME_NAME);?></span>
			<span class="m2"><?php _e( 'Relative Plugins',THEME_NAME);?></span>
			<span class="m3"><?php _e( 'Website Information',THEME_NAME);?></span>
			<span class="m4"><?php _e( 'Analytics Code',THEME_NAME);?></span>
			<span class="m5"><?php _e( 'Footer Copyright',THEME_NAME);?></span>
			<span class="m6"><?php _e( 'iPhoto Theme Declare',THEME_NAME);?></span>
			<div class="clear"></div>
		</div>
		<div id="theme-content">
			<ul>
				<li>
				<tr><td>
					<em><?php _e( 'iPhoto use jquery 1.4.4 which contained in this theme, you can also use the Google one instead.',THEME_NAME);?></em><br/>
					<label><input name="lib" type="checkbox" id="lib" value="1" <?php if (get_option('iphoto_lib')!='') echo 'checked="checked"' ;?>/><?php _e( 'Load the jQuery Library supported by Google',THEME_NAME);?></label><br/><br/>
				</td></tr>
				<tr><td>
					<em><?php _e( 'Index page infinite loading posts.',THEME_NAME);?></em><br/>
					<label><input name="noajax" type="checkbox" id="noajax" value="1" <?php if (get_option('iphoto_noajax')!='') echo 'checked="checked"' ;?>/><?php _e( 'Deactivate the Infinite loading posts',THEME_NAME);?></label><br/><br/>
				</td></tr>
				<tr><td>
					<em><?php _e( '<strong>Animation of relayout elements</strong>',THEME_NAME);?></em><br />
					<input name="animate" type="checkbox" id="animate" value="1" <?php if (get_option('iphoto_animate')!='') echo 'checked="checked"';?>/><?php _e( 'Deactivate animation effect on index page',THEME_NAME);?>
				</td></tr>
				</li>
				<li>
				<tr><td>
					<em><?php _e( 'WP-PostViews, Enables you to display how many times a post/page had been viewed.',THEME_NAME);?></em><br/>
					<label><input name="views" type="checkbox" id="views" value="1" <?php if (get_option('iphoto_views')!='') echo 'checked="checked"' ?>/><?php _e( 'Activate WP-PostViews',THEME_NAME);?></label><br/><br/>
				</td></tr>
				</li>
				<li>
							<tr><td>
				<?php _e( '<em>Keywords, separate by English commas. like MuFeng, Computer, Software</em>',THEME_NAME);?><br/>
				<textarea name="keywords" id="keywords" rows="1" cols="70" style="font-size:11px;width:100%;"><?php echo get_option('iphoto_keywords');?></textarea><br/>	
			</td></tr>
			<tr><td>
				<?php _e( '<em>Description, explain what\'s this site about. like MuFeng, Breathing the wind</em>',THEME_NAME);?><br/>
				<textarea name="description" id="description" rows="3" cols="70" style="font-size:11px;width:100%;"><?php echo get_option('iphoto_description');?></textarea>		
			</td></tr>
				</li>
				<li>
							<tr><td>
				<?php _e( 'You can get your Google Analytics code <a target="_blank" href="https://www.google.com/analytics/settings/check_status_profile_handler">here</a>.',THEME_NAME);?></label><br>
				<textarea name="analytics" id="analytics" rows="5" cols="70" style="font-size:11px;width:100%;"><?php echo stripslashes(get_option('iphoto_analytics'));?></textarea>
			</td></tr>
				</li>
				<li>
							<tr><td>
				<textarea name="copyright" id="copyright" rows="5" cols="70" style="font-size:11px;width:100%;"><?php if(stripslashes(get_option('iphoto_copyright'))!=''){echo stripslashes(get_option('iphoto_copyright'));}else{echo 'Copyright &copy; '.date('Y').' '.'<a href="'.home_url( '/').'" title="'.esc_attr( get_bloginfo( 'name') ).'">'.esc_attr( get_bloginfo( 'name') ).'</a> All rights reserved'; };?></textarea>
				<br/><em><?php _e( '<b>Preview</b>',THEME_NAME);?><span> : </span><span><?php if(stripslashes(get_option('iphoto_copyright'))!=''){echo stripslashes(get_option('iphoto_copyright'));}else{echo 'Copyright &copy; '.date('Y').' '.'<a href="'.home_url( '/').'" title="'.esc_attr( get_bloginfo( 'name') ).'">'.esc_attr( get_bloginfo( 'name') ).'</a> All rights reserved'; };?></span></em>
			</td></tr>
				</li>
				<li>
							<tr><td>
			<p><?php _e('iPhoto is created, developed and maintained by <a href="http://mufeng.me/">MuFeng</a>.  If you like iPhoto,  please donate.  It will help in developing new features and versions.',THEME_NAME);?><?php _e('Alipay',THEME_NAME);?>:</strong> <a href="http://www.alipay.com" target="_blank" title="Alipay">afcold@gmail.com</a></p>
			<h3 style="color:#333" id="introduce"><?php _e( 'Introduction',THEME_NAME);?></h3>
			<p style="text-indent: 2em;margin:10px 0;"><?php _e( 'iPhoto is evolved from one theme of Tumblr and turned it into a photo theme which can be used at wordpress.',THEME_NAME);?></p>
			<h3 style="color:#333"><?php _e( 'Published Address',THEME_NAME);?></h3>
			<p  id="release" style="text-indent: 2em;margin:10px 0;"><a href="http://mufeng.me/wordpress-theme-iphoto.html" target="_blank">http://mufeng.me/wordpress-theme-iphoto.html</a></p>
			<h3 style="color:#333"><?php _e( 'Preview Address',THEME_NAME);?></h3>
			<p  id="preview" style="text-indent: 2em;margin:10px 0;"><a href="http://mufeng.me/photo/" target="_blank">http://mufeng.me/photo/</a></p>
			<h3 style="color:#333" id="bug"><?php _e( 'Report Bugs',THEME_NAME);?></h3>
			<p style="text-indent: 2em;margin:10px 0;"><?php _e( 'Weibo <a href="http://weibo.com/meapo" target="_blank">@mufeng.me</a> or leave a message at <a href="http://mufeng.me" target="_blank">http://mufeng.me</a>¡£',THEME_NAME);?></p>
			</td></tr>
				</li>
			</ul>
		</div>
	</div>
	<p class="submit">
		<input type="submit" name="Submit" class="button-primary" value="<?php _e( 'Save Options',THEME_NAME);?>" />
		<input type="hidden" name="iphoto_settings" value="save" style="display:none;" />
	</p>
</form>
</div>
<?php
}
?>
