<?php
function slider_module_populate_post_type_choices($field) {
    if (empty($field['name']) || 'slider_post_type_select' !== $field['name']) {
        return $field;
    }

    $post_types = get_post_types(array('public' => true), 'objects');
    $choices = array();

    foreach ($post_types as $post_type) {
        if (in_array($post_type->name, array('attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation'), true)) {
            continue;
        }

        $choices[$post_type->name] = $post_type->label;
    }

    $field['choices'] = $choices;

    return $field;
}
add_filter('acf/load_field/name=slider_post_type_select', 'slider_module_populate_post_type_choices');

// Function xử lý hiển thị
function render_slider_module($atts = array()) {
    // Output module CSS and JS inline because module renders after wp_head
    static $slider_assets_printed = false;
    $style_url  = get_template_directory_uri() . '/modules/slider/style.css';
    $script_url = get_template_directory_uri() . '/modules/slider/script.js';
    ob_start();
    if ( ! $slider_assets_printed ) {
        echo '<link rel="stylesheet" href="' . esc_url( $style_url ) . '" type="text/css" media="all" />';
        echo '<script src="' . esc_url( $script_url ) . '"></script>';
        $slider_assets_printed = true;
    }

    $selected_post_types = array();
    if ( ! empty( $atts['slider_post_type_select'] ) ) {
        $selected_post_types = (array) $atts['slider_post_type_select'];
    } elseif ( function_exists( 'get_field' ) ) {
        $selected_post_types = get_field( 'slider_post_type_select' );
    }

    $posts_per_page = 0;
    if ( ! empty( $atts['slider_posts_per_page'] ) ) {
        $posts_per_page = intval( $atts['slider_posts_per_page'] );
    } elseif ( function_exists( 'get_field' ) ) {
        $posts_per_page = absint( get_field( 'slider_posts_per_page' ) );
    }

    if ( $posts_per_page < 1 ) {
        $posts_per_page = 5;
    }

    if ( empty( $selected_post_types ) ) {
        $selected_post_types = array( 'post' );
    }

    if ( ! is_array( $selected_post_types ) ) {
        $selected_post_types = array( $selected_post_types );
    }

    $selected_post_types = array_filter( array_map( 'sanitize_key', $selected_post_types ) );

    if ( empty( $selected_post_types ) ) {
        $selected_post_types = array( 'post' );
    }

    $args = array(
        'post_type'      => $selected_post_types,
        'posts_per_page' => $posts_per_page,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    $query = new WP_Query( $args );
    $news = array();

    $default_image = '';
    if (function_exists('get_field')) {
        $acf_default = get_field('post_image_default', 'option');
        if (is_array($acf_default) && ! empty($acf_default['url'])) {
            $default_image = $acf_default['url'];
        } elseif (is_string($acf_default) && $acf_default) {
            $default_image = $acf_default;
        }
    }

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $news[] = array(
                'title' => get_the_title(),
                'link'  => get_permalink(),
                'image' => get_the_post_thumbnail_url( get_the_ID(), 'full' ) ? get_the_post_thumbnail_url( get_the_ID(), 'full' ) : esc_url( $default_image ),
            );
        }
        wp_reset_postdata();
    }

    $slider_uid = 'slider-' . uniqid();
    ob_start();
    if ( ! empty( $news ) ) : ?>
        <div class="mod-thong-bao" data-module="slider" data-slider-id="<?php echo esc_attr( $slider_uid ); ?>">
            <div id="<?php echo esc_attr( $slider_uid ); ?>" class="showcase-slider" data-slider-id="<?php echo esc_attr( $slider_uid ); ?>">
                <div class="showcase-slider__media">
                    <?php foreach ( $news as $index => $item ) : ?>
                        <div id="<?php echo esc_attr( $slider_uid ) . '-fragment-' . esc_attr( $index ); ?>" class="ui-tabs-panel showcase-slider__panel <?php echo $index === 0 ? 'is-active' : ''; ?>" data-panel="<?php echo esc_attr( $index ); ?>">
                            <a href="<?php echo esc_url( $item['link'] ); ?>">
                                <img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>">
                                <div class="info">
                                    <h2><?php echo esc_html( $item['title'] ); ?></h2>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <ul class="ui-tabs-nav showcase-slider__nav">
                    <?php foreach ( $news as $index => $item ) : ?>
                        <li class="showcase-slider__tab <?php echo $index == 0 ? 'ui-tabs-selected is-active' : ''; ?>" data-tab="<?php echo esc_attr( $index ); ?>">
                            <a href="#<?php echo esc_attr( $slider_uid ) . '-fragment-' . esc_attr( $index ); ?>">
                                <div class="showcase-slider__image">
                                    <img class="showcase-slider__image-image" src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>">
                                </div>
                                <div style="width: 193px; overflow: hidden;"><span><?php echo esc_html( $item['title'] ); ?></span></div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif;

    return ob_get_clean();
}
// Đăng ký shortcode
add_shortcode('slider', 'render_slider_module');