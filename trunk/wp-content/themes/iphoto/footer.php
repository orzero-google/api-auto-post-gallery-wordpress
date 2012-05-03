<div class="clear"></div>
</div>
<div id="footer">
	<p><?php if(stripslashes(get_option('iphoto_copyright'))!=''){echo stripslashes(get_option('iphoto_copyright'));}else{echo 'Copyright &copy; '.date("Y").' '.'<a href="'.home_url( '/' ).'" title="'.esc_attr( get_bloginfo( 'name') ).'">'.esc_attr( get_bloginfo( 'name') ).'</a> All rights reserved';}?></p><p>Powered by <a href="http://wordpress.org/" title="Wordpress">WordPress <?php bloginfo('version');?></a>  |  <a href="http://www.lolita.im/sitemap.xml" title="SiteMap">sitemap.xml</a> </p>
</div>
<!--[if IE 6]><script src="<?php bloginfo('template_url');?>/includes/jQuery.autoIMG.min.js"></script><![endif]-->
<script type="text/javascript">
/* <![CDATA[ */
//$("div.post-content > p > a > img").show().
//    attr('data-original', $(self).attr('src')).
<!--    attr('data-original', '--><?php //bloginfo('template_url'); ?><!--/images/logo-b.png').-->
//    lazyload();
$(function() {
    $("div.post-content > p > a > img").lazyload({
        placeholder : "<?php bloginfo('template_url'); ?>/images/logo-b.png",
        event : "sporty"
    });
});
$(window).bind("load", function() {
    var timeout = setTimeout(function() {$("div.post-content > p > a > img").trigger("sporty")}, 200);
});
/* ]]> */
</script>
<?php wp_footer(); ?>
</body>
</html>