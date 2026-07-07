<?php
/*
Template Name: Home page
*/
get_header(); // Gọi phần đầu trang (menu, logo)
?>

<div id="primary">
    <div class="sidebar-left-custom">
        <div >
            <?php 
            echo do_shortcode('[list]'); 
            ?>
        </div>
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
        // Flexible content: homepage_modules (ACF)
        if ( function_exists( 'have_rows' ) && have_rows( 'homepage_modules' ) ) :
            while ( have_rows( 'homepage_modules' ) ) : the_row();
                $layout = get_row_layout();
                $layout_to_folder = array(
                    'list'    => 'list',
                    'list_v2' => 'list-v2',
                    'list_v3' => 'list-v3',
                    'slider'  => 'slider',
                );
                $module_folder = isset( $layout_to_folder[ $layout ] ) ? $layout_to_folder[ $layout ] : $layout;
                $module_path = get_template_directory() . '/modules/' . $module_folder . '/index.php';
                if ( file_exists( $module_path ) ) {
                    include_once $module_path;
                    switch ( $layout ) {
                        case 'list':
                            $args = array(
                                'list_post_type_select' => get_sub_field( 'list_post_type_select' ),
                                'list_posts_per_page'   => get_sub_field( 'list_posts_per_page' ),
                                'list_link'             => get_sub_field( 'list_link' ),
                                'list_sidebar_title'    => get_sub_field( 'list_sidebar_title' ),
                            );
                            if ( function_exists( 'render_list_module' ) ) echo render_list_module( $args );
                            break;
                        case 'list_v2':
                            $args = array(
                                'list_v2_post_type_select' => get_sub_field( 'list_v2_post_type_select' ),
                                'list_v2_posts_per_page'   => get_sub_field( 'list_v2_posts_per_page' ),
                                'list_v2_link'             => get_sub_field( 'list_v2_link' ),
                                'list_v2_sidebar_title'    => get_sub_field( 'list_v2_sidebar_title' ),
                            );
                            if ( function_exists( 'render_list_v2_module' ) ) echo render_list_v2_module( $args );
                            break;
                        case 'list_v3':
                            $args = array(
                                'list_v3_post_type_select' => get_sub_field( 'list_v3_post_type_select' ),
                                'list_v3_posts_per_page'   => get_sub_field( 'list_v3_posts_per_page' ),
                                'list_v3_link'             => get_sub_field( 'list_v3_link' ),
                                'list_v3_sidebar_title'    => get_sub_field( 'list_v3_sidebar_title' ),
                                'instance_id'              => uniqid( 'list_v3_' ),
                            );
                            if ( function_exists( 'render_list_v3_module' ) ) echo render_list_v3_module( $args );
                            break;
                        case 'slider':
                            $args = array(
                                'slider_post_type_select' => get_sub_field( 'slider_post_type_select' ),
                                'slider_posts_per_page'   => get_sub_field( 'slider_posts_per_page' ),
                            );
                            if ( function_exists( 'render_slider_module' ) ) echo render_slider_module( $args );
                            break;
                        default:
                            // unknown layout: try shortcode fallback
                            $shortcodes = array( 'list' => '[list]', 'list_v2' => '[list-v2]', 'slider' => '[slider]' );
                            if ( isset( $shortcodes[ $layout ] ) ) echo do_shortcode( $shortcodes[ $layout ] );
                    }
                } else {
                    // module file missing: fallback to shortcode
                    $shortcodes = array( 'list' => '[list]', 'list_v2' => '[list-v2]', 'slider' => '[slider]' );
                    if ( isset( $shortcodes[ $layout ] ) ) echo do_shortcode( $shortcodes[ $layout ] );
                }
            endwhile;
        else:
            // fallback to existing shortcodes for backward compatibility
            echo do_shortcode( '[slider]' );
            echo do_shortcode( '[list-v2]' );
        endif;
        ?>
    </div>
</div><!-- #primary -->

<?php 
get_footer(); // Gọi phần chân trang 
?>