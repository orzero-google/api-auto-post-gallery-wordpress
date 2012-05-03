<?php get_header(); ?>

<?php
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$args = array(
    'meta_key' => 'views',//插件是wp-postviews, 其他插件需要和meta_key挂钩
    'orderby'   => 'meta_value_num',
    'paged' => $paged,
    'order' => DESC
);
query_posts($args);
if(have_posts()) : while (have_posts()) : the_post(); ?>
<?php get_template_part( 'content', get_post_format() ); ?>
<?php endwhile; endif; ?>


<?php get_footer(); ?>