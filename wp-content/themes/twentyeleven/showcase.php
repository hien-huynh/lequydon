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
wp_enqueue_style(
	'twentyeleven-showcase',
	get_template_directory_uri() . '/css/showcase.css',
	array(),
	'20260630'
);
wp_enqueue_script(
	'twentyeleven-showcase',
	get_template_directory_uri() . '/js/showcase.js',
	array('jquery'),
	'20260630',
	array(
		'in_footer' => true,
		'strategy'  => 'defer',
	)
);
$news = [];

for ($i = 1; $i <= 5; $i++) {
	$news[] = [
		'title' => "Giấy mời họp Cha mẹ học sinh lớp 10 năm học 2026-2027  (28-06-2026)26-2027  (28-06-2026) 26-2027  (28-06-2026)26-2027  (28-06-2026)26-2027  (28-06-2026) $i",
		'image' => 'https://placehold.co/650x350',
		'link'  => '#'
	];
}
get_header(); ?>

<div id="primary" class="showcase">
	<div class="mod-thong-bao">

		<div id="slidertbao" class="showcase-slider">
			<div class="showcase-slider__media">
				<?php foreach ($news as $index => $item) : ?>
					<div id="fragment-<?php echo $index; ?>" class="ui-tabs-panel showcase-slider__panel <?php echo $index === 0 ? 'is-active' : ''; ?>" data-panel="<?php echo $index; ?>">
						<a href="<?php echo $item['link']; ?>">	
							<img src="<?php echo $item['image']; ?>" alt="<?php echo esc_attr( $item['title'] ); ?>">
							<div class="info">
										<h2>
										<?php echo $item['title']; ?>
										</h2>
							</div>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
			<ul class="ui-tabs-nav showcase-slider__nav">
				<?php foreach ($news as $index => $item) : ?>
					<li class="showcase-slider__tab <?php echo $index == 0 ? 'ui-tabs-selected is-active' : ''; ?>" data-tab="<?php echo $index; ?>">
						<a href="#fragment-<?php echo $index; ?>">
							<img class="showcase-slider__image" src="<?php echo $item['image']; ?>" alt="">
							<span><?php echo $item['title']; ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div class="mod-thong-bao-sidebar">

			<div class="sidebar__widget">
				<div class="sidebar__title">
					<h3 class="" style="margin:0">
						THÔNG TIN - THÔNG BÁO
					</h3>
				</div>
				<ul class="sidebar__content">

					<?php foreach ($news as $item) : ?>
						<li class="lithbao">
							<a href="<?php echo $item['link']; ?>" class="sidebar__link">
								<?php echo $item['title']; ?>
							</a>
						</li>
					<?php endforeach; ?>

				</ul>

			</div>

		</div>
	</div>
	<div id="content" role="main">
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