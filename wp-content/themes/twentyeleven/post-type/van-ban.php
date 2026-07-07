<?php
// Đăng ký Post Type Văn bản
function register_van_ban_post_type() {
    // Post Type
    register_post_type('van_ban', array(
        'labels' => array(
            'name'          => 'Văn bản',
            'singular_name' => 'Văn bản',
            'add_new_item'  => 'Thêm văn bản mới',
            'edit_item'     => 'Chỉnh sửa văn bản',
            'all_items'     => 'Tất cả văn bản',
        ),
        'public'             => true,
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-media-document',
        'supports'           => array('title', 'thumbnail', 'editor', 'custom-fields', 'revisions'),
        'show_in_rest'       => true,
        'rewrite'            => array('slug' => 'van-ban', 'with_front' => false),
        'capability_type'    => 'post',
    ));

    // Taxonomy: Danh mục văn bản
    register_taxonomy('danh_muc_van_ban', 'van_ban', array(
        'hierarchical' => true,
        'labels' => array(
            'name' => 'Danh mục văn bản',
            'singular_name' => 'Danh mục văn bản',
        ),
        'show_ui' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'danh-muc-van-ban'),
    ));
}
add_action('init', 'register_van_ban_post_type');
