<?php get_header(); ?>		<div id="single-content">			<div id="post-home">				<?php if(have_posts()) : while (have_posts()) : the_post(); ?>				<div id="post-header">					<?php if (function_exists('get_avatar')) { echo get_avatar( get_the_author_email(), '48' ); }?>					<div id="post-title">						<h1><?php the_title_attribute(); ?></h1>						<p>by <?php the_author_link(); ?> &#160;&#124;&#160;<?php the_time('M d, Y'); ?>&#160;&#124;&#160;in <?php the_category(', '); ?>&#160;&#124;&#160;<?php if(function_exists('the_views')) {$views = the_views(0);preg_match('/\d+/', $views, $match);echo '<span>'._e( 'Views',iphoto).' '.$match[0].'</span>';} ?>&#160;&#124;&#160;<?php _e( 'Comments',iphoto).' ';?><?php comments_popup_link('0', '1', '%'); ?></p>			 					</div>					<div class="clear"></div>				</div><!-- Begin: Black Label Ads, Generated: 2012-05-05 6:17:05  --><script type="text/javascript">    var AdBrite_Title_Color = '0000FF';    var AdBrite_Text_Color = '000000';    var AdBrite_Background_Color = 'FFFFFF';    var AdBrite_Border_Color = 'CCCCCC';    var AdBrite_URL_Color = '008000';    try{var AdBrite_Iframe=window.top!=window.self?2:1;var AdBrite_Referrer=document.referrer==''?document.location:document.referrer;AdBrite_Referrer=encodeURIComponent(AdBrite_Referrer);}catch(e){var AdBrite_Iframe='';var AdBrite_Referrer='';}</script><span style="white-space:nowrap;"><script type="text/javascript">document.write(String.fromCharCode(60,83,67,82,73,80,84));document.write(' src="http://ads.adbrite.com/mb/text_group.php?sid=2141343&zs=3436385f3630&ifr='+AdBrite_Iframe+'&ref='+AdBrite_Referrer+'" type="text/javascript">');document.write(String.fromCharCode(60,47,83,67,82,73,80,84,62));</script><a target="_top" href="http://www.adbrite.com/mb/commerce/purchase_form.php?opid=2141343&afsid=55544"><img src="http://files.adbrite.com/mb/images/adbrite-your-ad-here-banner.gif" style="background-color:#CCCCCC;border:none;padding:0;margin:0;" alt="Your Ad Here" width="11" height="60" border="0" /></a></span><!-- End: Black Label Ads -->				<div class="post-content">					<?php the_content(''); ?>				</div>				<?php endwhile; endif; ?>				<div id="comments">					<?php comments_template('', true); ?>				</div>			</div>		</div><?php get_sidebar(); ?><?php get_footer(); ?>