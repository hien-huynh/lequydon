<?php

/**
 * Template Name: Showcase Template
 *
 * Description: A Page Template that showcases Sticky Posts, Asides, and Blog Posts.
 *
 * The showcase template in Twenty Eleven consists of a featured posts section using sticky posts,
 * another recent posts area (with the latest post shown in full and the rest as a list)
 * and a left sidebar holding aside posts.
 *
 * We are creating two queries to fetch the proper posts and a custom widget for the sidebar.
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

// Enqueue showcase styles and script for the slider.
// wp_enqueue_style(
// 	'twentyeleven-showcase',
// 	get_template_directory_uri() . '/css/showcase.css',
// 	array(),
// 	'20260630'
// );
// wp_enqueue_script(
// 	'twentyeleven-showcase',
// 	get_template_directory_uri() . '/js/showcase.js',
// 	array('jquery'),
// 	'20260630',
// 	array(
// 		'in_footer' => true,
// 		'strategy'  => 'defer',
// 	)
// );
get_header(); ?>

<div id="primary" class="">

	<?php echo do_shortcode('[slider]'); ?>
	<div id="content" class="entry-content" role="main">
		<?php
		while (have_posts()) :
			the_post();
		?>

			<?php
			if ('' !== get_the_content()) {
				get_template_part('content', 'intro');
			}
			?>

		<?php endwhile; ?>

	</div><!-- #content -->
</div><!-- #primary -->
<?php get_footer(); ?>