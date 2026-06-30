<?php
// Đăng ký style
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
} );


add_action( 'generate_after_header', function() {
    // Lấy URL ảnh từ ACF Options Page
    $url_banner_image = get_field('banner_image', 'option');

    // Kiểm tra xem có ảnh hay không mới hiển thị
    if ( $url_banner_image ) {
        echo '<div class="site-banner" style="text-align: center;">';
        echo '<img style="width: 100%; height: auto; max-width: 1200px;" src="' . esc_url($url_banner_image) . '" alt="Banner Trường">';
        echo '</div>';
    }
});
if( function_exists('acf_add_options_page') ) {
    acf_add_options_page(array(
        'page_title'    => 'Global Settings',
        'menu_title'    => 'Global Settings',
        'menu_slug'     => 'global-settings',
        'capability'    => 'edit_posts',
        'redirect'      => false
    ));
}

function create_news_post_type()
{
    register_post_type(
        'news',
        array(
            'labels' => array(
                'name' => __('News'),
                'singular_name' => __('News'),
                'add_new' => __('Add New News'),
                'add_new_item' => __('Add New News'),
                'all_items' => __('All News'),
                'menu_name' => __('News'),
                'parent_item_colon' => __("Parent News:"),
                'view_item' => __("View News"),
                'edit_item' => __("Edit News"),
                'update_item' => __("Update News"),
                'search_items' => __("Search News"),
            ),
            'menu_icon' => 'dashicons-media-document',
            'public' => true,
            'has_archive' => false,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'),
            'show_in_rest' => true, // Enable Gutenberg editor
            'publicly_queryable' => true,
            'show_ui' => true,
            'rewrite' => array('slug' => '/about/news'),
        )
    );
}

add_action('init', 'create_news_post_type');