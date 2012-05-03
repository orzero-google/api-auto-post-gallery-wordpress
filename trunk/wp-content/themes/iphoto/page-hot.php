<?php get_header(); ?>
<div id="cate" data-animate="<?php echo get_option('iphoto_animate');?>" data-ajax="<?php echo get_option('iphoto_noajax');?>"><?php echo isset($_GET['order']) ? $_GET['order'] : home;?></div>
<div id="container">

    <?php if(have_posts()) :
    if(isset($_GET['order'])){
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $args = array(
            'meta_key' => 'views',
            'orderby'   => 'meta_value_num',
            'paged' => $paged,
            'order' => DESC
        );
    }else{
        $args = array(
            'paged' => $paged
        );
    }
    query_posts($args);
    while (have_posts()) : the_post(); ?>
        <?php get_template_part( 'content', get_post_format() ); ?>
        <?php endwhile; endif; ?>

</div>
<div id="pagenavi">
    <?php pagenavi();?>
</div>
<div class="clear"></div>
<?php get_footer(); ?>