<?php
/**
 * Module: List
 * Renders a simple list of posts selected by post type and count.
 * Supports being used as a module include (passing variables) or as an ACF block (using get_field()).
 */

// Populate choices for the ACF 'post_type_select' field (matching slider behavior)
// Populate choices for the ACF 'list_post_type_select' field
function list_module_populate_post_type_choices( $field ) {
    if ( empty( $field['name'] ) || 'list_post_type_select' !== $field['name'] ) {
        return $field;
    }

    $post_types = get_post_types( array( 'public' => true ), 'objects' );
    $choices = array();

    foreach ( $post_types as $post_type ) {
        if ( in_array( $post_type->name, array( 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation' ), true ) ) {
            continue;
        }

        $choices[ $post_type->name ] = $post_type->label;
    }

    $field['choices'] = $choices;

    return $field;
}
add_filter( 'acf/load_field/name=list_post_type_select', 'list_module_populate_post_type_choices' );

/**
 * Render the list module. Returns HTML string.
 * $args may include 'list_post_type_select' (array|string), 'list_posts_per_page' (int), 'list_sidebar_title' (string)
 */
function render_list_module( $args = array() ) {
    // Title
    $sidebar_title = '';
    if ( ! empty( $args['list_sidebar_title'] ) ) {
        $sidebar_title = $args['list_sidebar_title'];
    } elseif ( function_exists( 'get_field' ) ) {
        $sidebar_title = get_field( 'list_sidebar_title' );
    }
    if ( ! $sidebar_title ) {
        $sidebar_title = 'THÔNG TIN - THÔNG BÁO';
    }

    // Determine post types
    $post_types = array();
    if ( ! empty( $args['list_post_type_select'] ) ) {
        $post_types = (array) $args['list_post_type_select'];
    } elseif ( function_exists( 'get_field' ) ) {
        $pt = get_field( 'list_post_type_select' );
        if ( $pt ) {
            $post_types = (array) $pt;
        }
    }
    if ( ! empty( $post_types ) ) {
        $post_types = array_map( 'sanitize_key', $post_types );
        $post_types = array_filter( $post_types );
    }
    if ( empty( $post_types ) ) {
        $post_types = array( 'post' );
    }

    // posts per page
    $posts_per_page = 0;
    if ( ! empty( $args['list_posts_per_page'] ) ) {
        $posts_per_page = intval( $args['list_posts_per_page'] );
    } elseif ( ! empty( $args['posts_per_page'] ) ) {
        $posts_per_page = intval( $args['posts_per_page'] );
    } elseif ( function_exists( 'get_field' ) ) {
        $posts_per_page = intval( get_field( 'list_posts_per_page' ) );
    }
    if ( $posts_per_page < 1 ) {
        $posts_per_page = 5;
    }

    // Query
    $query_args = array(
        'post_type'      => $post_types,
        'posts_per_page' => $posts_per_page,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    $q = new WP_Query( $query_args );
    $news = array();
    if ( $q->have_posts() ) {
        while ( $q->have_posts() ) {
            $q->the_post();
            $news[] = array(
                'title' => get_the_title(),
                'link'  => get_permalink(),
            );
        }
        wp_reset_postdata();
    }

    // enqueue styles when rendering
    wp_enqueue_style( 'list-css', get_template_directory_uri() . '/modules/list/style.css' );

    ob_start();
    ?>
    <div class="mod-thong-bao-sidebar">
        <div class="sidebar__widget">
            <div class="sidebar__title">
                <h3 style="margin:0"><?php echo esc_html( $sidebar_title ); ?></h3>
            </div>
            <?php if ( ! empty( $news ) ) : ?>
            <ul class="sidebar__content">
                <?php foreach ( $news as $item ) : ?>
                    <li class="lithbao">
                        <a href="<?php echo esc_url( $item['link'] ); ?>" class="sidebar__link">
                            <?php echo esc_html( $item['title'] ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php else : ?>
            <p class="sidebar__empty" style="margin:8px 12px;color:#666;">Không có bài viết.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Register shortcode for backward compatibility
add_shortcode( 'list', 'render_list_module' );