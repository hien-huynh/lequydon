<?php
/**
 * Module: List V2
 * Duplicate of the list module but with separate ACF field names.
 */

function list_v2_module_populate_post_type_choices( $field ) {
    if ( empty( $field['name'] ) || 'list_v2_post_type_select' !== $field['name'] ) {
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
add_filter( 'acf/load_field/name=list_v2_post_type_select', 'list_v2_module_populate_post_type_choices' );

function render_list_v2_module( $args = array() ) {
    $sidebar_title = '';
    if ( ! empty( $args['list_v2_sidebar_title'] ) ) {
        $sidebar_title = $args['list_v2_sidebar_title'];
    } elseif ( function_exists( 'get_field' ) ) {
        $sidebar_title = get_field( 'list_v2_sidebar_title' );
    }
    if ( ! $sidebar_title ) {
        $sidebar_title = 'THÔNG TIN - THÔNG BÁO';
    }

    $post_types = array();
    if ( ! empty( $args['list_v2_post_type_select'] ) ) {
        $post_types = (array) $args['list_v2_post_type_select'];
    } elseif ( function_exists( 'get_field' ) ) {
        $pt = get_field( 'list_v2_post_type_select' );
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

    $posts_per_page = 0;
    if ( ! empty( $args['list_v2_posts_per_page'] ) ) {
        $posts_per_page = intval( $args['list_v2_posts_per_page'] );
    } elseif ( function_exists( 'get_field' ) ) {
        $posts_per_page = intval( get_field( 'list_v2_posts_per_page' ) );
    }
    if ( $posts_per_page < 1 ) {
        $posts_per_page = 5;
    }

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
            $post_id = get_the_ID();
            $post_type = get_post_type( $post_id );

            $item = array(
                'title' => get_the_title(),
                'link'  => get_permalink(),
                'post_type' => $post_type,
            );

            if ( 'van_ban' === $post_type ) {
                // ACF fields for van_ban
                $raw_date = function_exists( 'get_field' ) ? get_field( 'van_ban_date', $post_id ) : false;
                if ( $raw_date ) {
                    // expect Y-m-d, display d/m/Y
                    $dt = DateTime::createFromFormat( 'Y-m-d', $raw_date );
                    if ( $dt ) {
                        $item['date'] = $dt->format( 'd/m/Y' );
                    } else {
                        $item['date'] = $raw_date;
                    }
                } else {
                    $item['date'] = get_the_date( 'd/m/Y', $post_id );
                }

                $item['summary'] = function_exists( 'get_field' ) ? get_field( 'van_ban_summary', $post_id ) : '';

                $file = function_exists( 'get_field' ) ? get_field( 'van_ban_file', $post_id ) : false;
                $file_url = '';
                if ( $file ) {
                    if ( is_array( $file ) && ! empty( $file['url'] ) ) {
                        $file_url = $file['url'];
                    } elseif ( is_string( $file ) ) {
                        $file_url = $file;
                    }
                }
                $item['download'] = $file_url;
            }

            $news[] = $item;
        }
        wp_reset_postdata();
    }

    wp_enqueue_style( 'list-v2-css', get_template_directory_uri() . '/modules/list-v2/style.css' );

    ob_start();
    ?>
    <div class="mod-thong-bao-sidebar list-v2 <?php if ( isset( $item['post_type'] ) && 'van_ban' === $item['post_type'] ) : ?> van-ban-list<?php endif; ?>">
        <div class="sidebar__widget">
            <div class="sidebar__title">
                <h3 style="margin:0"><?php echo esc_html( $sidebar_title ); ?></h3>
            </div>
            <?php if ( ! empty( $news ) ) : ?>
            <ul class="sidebar__content">
                <?php foreach ( $news as $item ) : ?>
                    <li class="lithbao">
                        <a href="<?php echo esc_url( $item['link'] ); ?>" class="sidebar__link">
                            <?php echo esc_html( $item['title'] ); ?> <?php if ( ! empty( $item['date'] ) ) : ?> <span> - Ngày:
                                    <?php echo esc_html( $item['date'] ); ?>
                                </span><?php endif; ?>
                                <?php if ( ! empty( $item['summary'] ) ) : ?>
                                    <span><?php echo esc_html( $item['summary'] ); ?></span>
                                <?php endif; ?>
                        </a>
                        <?php if ( isset( $item['post_type'] ) && 'van_ban' === $item['post_type'] ) : ?>
                            <?php if ( ! empty( $item['download'] ) ) : ?>
                                <a class="sidebar__download" href="<?php echo esc_url( $item['download'] ); ?>" target="_blank" rel="noopener noreferrer" download> Tải về</a>
                            <?php endif; ?>
                        <?php endif; ?>
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
add_shortcode( 'list-v2', 'render_list_v2_module' );
