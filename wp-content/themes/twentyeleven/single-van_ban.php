<?php
/**
 * Template for displaying all single posts
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

get_header(); ?>

		<div id="primary">
			<div class="sidebar-left-custom">
				<div class="custom-sidebar-nav">
					<?php
					wp_nav_menu( array(
						'menu'            => 'sidebar',
						'container'       => 'div',
						'container_class' => 'sidebar-menu-wrapper',
						'menu_class'      => 'sidebar-list',
						) );
						?>
				</div>
			</div>
			<div id="content" role="main">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<?php get_template_part( 'content-single', get_post_format() ); ?>
                        <?php
                        $file = get_field('van_ban_file');

                        if ( $file ) :
                        ?>
                            <div class="document-download">
                                <a class="btn-download"
                                href="<?php echo esc_url( $file['url'] ); ?>"
                                target="_blank"
                                download>
                                    <i class="material-icons">download</i>
 Tải xuống văn bản
                                </a>
                            </div>
                        <?php endif; ?>
					<?php
					// Render flexible homepage modules attached to this post (if any)
					if ( function_exists( 'render_homepage_modules_from_acf' ) ) {
						render_homepage_modules_from_acf();
					}
					?>

					<?php comments_template( '', true ); ?>

				<?php endwhile; // End of the loop. ?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_footer(); ?>
