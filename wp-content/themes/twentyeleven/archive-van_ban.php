<?php
/**
 * Template for displaying Archive pages
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
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
			<header class="entry-header">
				<h1 class="entry-title">
					<?php
					$post_type = get_post_type();
					$post_type_obj = get_post_type_object( $post_type );
					if ( $post_type_obj ) {
						echo esc_html( $post_type_obj->labels->singular_name );
					}
					?>
				</h1>
			</header>
			<?php
			if ( function_exists( 'render_list_v2_module' ) ) {
				echo render_list_v2_module( array(
					'list_v2_post_type_select' => get_post_type(),
					'list_v2_posts_per_page'   => get_option( 'posts_per_page' ),
					'instance_id'              => 'archive-page',
				) );
			} else {
				// Fallback nếu module không có
				while ( have_posts() ) :
					the_post();
					get_template_part( 'content', get_post_format() );
				endwhile;

				echo paginate_links( array(
					'total'        => $wp_query->max_num_pages,
					'current'      => max( 1, get_query_var( 'paged' ) ),
					'prev_text'    => __( '« Trang trước', 'twentyeleven' ),
					'next_text'    => __( 'Trang sau »', 'twentyeleven' ),
					'type'         => 'list',
				) );
			}
			?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
