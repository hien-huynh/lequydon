<?php
/**
 * Module: List V3
 * New list module with card-style layout and pagination.
 */

function list_v3_module_populate_post_type_choices( $field ) {
    if ( empty( $field['name'] ) || 'list_v3_post_type_select' !== $field['name'] ) {
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
add_filter( 'acf/load_field/name=list_v3_post_type_select', 'list_v3_module_populate_post_type_choices' );

function render_list_v3_module( $args = array() ) {
    $sidebar_title = '';
    if ( ! empty( $args['list_v3_sidebar_title'] ) ) {
        $sidebar_title = $args['list_v3_sidebar_title'];
    } elseif ( function_exists( 'get_field' ) ) {
        $sidebar_title = get_field( 'list_v3_sidebar_title' );
    }

    $sidebar_link = '';
    if ( ! empty( $args['list_v3_link'] ) ) {
        $sidebar_link = trim( $args['list_v3_link'] );
    } elseif ( function_exists( 'get_field' ) ) {
        $sidebar_link = trim( get_field( 'list_v3_link' ) );
    }

    $post_types = array();
    if ( ! empty( $args['list_v3_post_type_select'] ) ) {
        $post_types = (array) $args['list_v3_post_type_select'];
    } elseif ( function_exists( 'get_sub_field' ) ) {
        $pt = get_sub_field( 'list_v3_post_type_select' );
        if ( $pt ) {
            $post_types = (array) $pt;
        }
    }

    if ( is_string( $post_types ) ) {
        $post_types = array_filter( array_map( 'trim', explode( ',', $post_types ) ) );
    }

    if ( ! empty( $post_types ) ) {
        $post_types = array_map( 'sanitize_key', $post_types );
        $post_types = array_filter( $post_types );
        $post_types = array_filter( $post_types, 'post_type_exists' );
    }

    if ( empty( $post_types ) ) {
        $post_types = array( 'post' );
    }

    $posts_per_page = 0;
    if ( ! empty( $args['list_v3_posts_per_page'] ) ) {
        $posts_per_page = intval( $args['list_v3_posts_per_page'] );
    } elseif ( function_exists( 'get_field' ) ) {
        $posts_per_page = intval( get_field( 'list_v3_posts_per_page' ) );
    }
    if ( $posts_per_page < 1 ) {
        $posts_per_page = 6;
    }

    $instance_id = ! empty( $args['instance_id'] ) ? sanitize_key( $args['instance_id'] ) : 'default';
    $page_var = 'list_v3_page_' . $instance_id;
    $current_page = 1;
    if ( isset( $_GET[ $page_var ] ) ) {
        $current_page = absint( wp_unslash( $_GET[ $page_var ] ) );
    }
    if ( $current_page < 1 ) {
        $current_page = 1;
    }

    $query_args = array(
        'post_type'      => $post_types,
        'posts_per_page' => $posts_per_page,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'paged'          => $current_page,
    );
    $q = new WP_Query( $query_args );
    $news = array();
    $default_image = '';
    if ( function_exists( 'get_field' ) ) {
        $acf_default = get_field( 'post_image_default', 'option' );
        if ( is_array( $acf_default ) && ! empty( $acf_default['url'] ) ) {
            $default_image = $acf_default['url'];
        } elseif ( is_string( $acf_default ) && $acf_default ) {
            $default_image = $acf_default;
        }
    }
    if ( $q->have_posts() ) {
        while ( $q->have_posts() ) {
            $q->the_post();
            $thumb = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
            $image_url = $thumb ? $thumb : ( $default_image ? $default_image : '' );

            $description = '';
            if ( function_exists( 'get_field' ) ) {
                $raw_desc = get_field( 'description', get_the_ID() );
                if ( $raw_desc ) {
                    $description = $raw_desc;
                }
            }
            if ( ! $description ) {
                $description = get_the_excerpt();
            }

            $news[] = array(
                'title'       => get_the_title(),
                'link'        => get_permalink(),
                'description' => $description,
                'image'       => $image_url,
            );
        }
        wp_reset_postdata();
    }

    $style_url = get_template_directory_uri() . '/modules/list-v3/style.css';
    static $list_v3_style_printed = false;
    ob_start();
    if ( ! $list_v3_style_printed ) {
        echo '<link rel="stylesheet" href="' . esc_url( $style_url ) . '" type="text/css" media="all" />';
        $list_v3_style_printed = true;
    }

    $page_base = remove_query_arg( $page_var, ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
    $page_base = esc_url_raw( add_query_arg( $page_var, '%#%', $page_base ) );
    $pagination = '';
    if ( $q->max_num_pages > 1 ) {
        $pagination = paginate_links( array(
            'base'      => $page_base,
            'format'    => '',
            'current'   => $current_page,
            'total'     => $q->max_num_pages,
            'prev_text' => '‹',
            'next_text' => '›',
            'type'      => 'list',
        ) );
    }
    ?>
    <div>
        <?php if ( $sidebar_title ) : ?>
            <div class="sidebar__title" bis_skin_checked="1">
                <?php if ( $sidebar_link ) : ?>
                    <h3><a href="<?php echo esc_url( $sidebar_link ); ?>"><?php echo esc_html( $sidebar_title ); ?></a></h3>
                <?php else : ?>
                    <h3><?php echo esc_html( $sidebar_title ); ?></h3>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <ul class="ui-tabs-nav showcase-slider__nav showcase-slider__nav-v2">
            <?php foreach ( $news as $index => $item ) :
                $tab_index = absint( $index );
                $img_src = ! empty( $item['image'] ) ? esc_url( $item['image'] ) : '';
                $title   = esc_html( $item['title'] );
                $link    = esc_url( $item['link'] );
                $excerpt = '';
                if ( ! empty( $item['description'] ) ) {
                    $excerpt = wp_kses_post( wp_trim_words( $item['description'], 32, '...' ) );
                }
            ?>
            <li class="showcase-slider__tab" data-tab="<?php echo $tab_index; ?>">
                <a href="<?php echo $link; ?>">
                    <div class="showcase-slider__image">
                        <?php if ( $img_src ) : ?>
                            <img class="showcase-slider__image-image" src="<?php echo $img_src; ?>" alt="<?php echo $title; ?>">
                        <?php else : ?>
                            <div class="showcase-slider__image--placeholder"><span>Ảnh</span></div>
                        <?php endif; ?>
                    </div>
                    <div class="showcase-slider__content">
                        <h3><?php echo $title; ?></h3>
                        <?php if ( $excerpt ) : ?><?php echo $excerpt; ?><?php endif; ?>
                    </div>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <?php if ( $pagination ) : ?>
            <div class="list-v3__pagination">
                <?php echo $pagination; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'list-v3', 'render_list_v3_module' );
