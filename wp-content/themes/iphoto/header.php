<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html;charset=UTF-8" /><?php include('includes/seo.php'); ?><link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/style.css" type="text/css" media="screen" /><link rel="shortcut icon" href="<?php bloginfo('url');?>/favicon.ico" type="image/x-icon" /><?php if(get_option('iphoto_lib')!="") : ?><script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script><?php else : ?><script type="text/javascript" src="<?php bloginfo('template_url'); ?>/includes/jquery.min.js"></script><?php endif; ?><script type="text/javascript" src="<?php bloginfo('template_url'); ?>/includes/all.js"></script><?php if (is_home() || is_archive()) { ?><script type="text/javascript" src="<?php bloginfo('template_url'); ?>/includes/jquery.waterfall.min.js"></script><script type="text/javascript" src="<?php bloginfo('template_url'); ?>/includes/index.js"></script><?php } elseif (is_singular()){ ?><script type="text/javascript" src="<?php bloginfo('template_url'); ?>/includes/comments-ajax.js"></script><!--script type="text/javascript" src="<?php bloginfo('template_url'); ?>/includes/phzoom.js"></script--><script type="text/javascript" src="<?php bloginfo('template_url'); ?>/includes/single.js"></script><?php }?><?php wp_head(); ?></head><body <?php body_class(); ?>>	<div id="header">		<div id="header-box">			<div id="logo"><a href="<?php bloginfo('url'); ?>" title="<?php bloginfo('name'); ?>"><img src="<?php bloginfo('template_url'); ?>/images/logo.png" alt="logo" /></a></div>			<?php wp_nav_menu(array( 'theme_location'=>'primary','container_id' => 'nav')); ?>			<?php if (!(current_user_can('level_0'))){ ?>				<div id="login"><a href="#" title="<?php _e('Log in','iphoto'); ?>"><?php _e('Log in','iphoto'); ?></a></div>				<div id="login-form">					<form action="<?php echo get_option('home'); ?>/wp-login.php" method="post">						<div id="actions">							<p><?php _e('Username','iphoto'); ?>							<input id="log" type="text" name="log" value="<?php echo wp_specialchars(stripslashes($user_login), 1) ?>" size="20" />							<span>&nbsp;&nbsp;&nbsp;&nbsp;</span>                            <?php _e('Password','iphoto'); ?>							<input type="password" name="pwd" id="pwd" size="20"  />                            <a href="<?php echo get_option('home'); ?>/wp-login.php?action=lostpassword"><?php _e('Forgot password ?','iphoto'); ?></a>							<input type="submit" name="submit" value="<?php _e('Log in','iphoto'); ?>" class="button" />							<input type="hidden" name="redirect_to" value="<?php bloginfo('url'); ?>/" /></p>                            <div class="clear"></div>						</div>					</form>				</div>			<?php } else { ?>				<div id="logined">					<a href="#" id="info" title="info"><?php global $current_user;get_currentuserinfo();echo get_avatar( $current_user->user_email, 36);echo '<span>';echo $current_user->user_login;echo '</span>';?></a>					<div id="info-content" class="hidden">						<a id="info-post" href="<?php bloginfo('url'); ?>" title="<?php _e('Post','iphoto'); ?>"><?php _e('Post','iphoto'); ?></a><a id="info-quit" href="<?php echo wp_logout_url( get_bloginfo('url') ); ?>" title="<?php _e('Logout','iphoto'); ?>"><?php _e('Logout','iphoto'); ?></a>					</div>				</div>			<?php }?>			<div class="clear"></div>		</div>	</div>	<div id="wrapper">