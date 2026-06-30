<?php
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
