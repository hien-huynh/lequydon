<?php
// Đăng ký Post Type và Taxonomy
function register_tintuc_custom_post_type() {
    // Đăng ký Post Type
    register_post_type('tin_tuc', array(
        'labels' => array(
            'name'          => 'Tin tức',
            'singular_name' => 'Tin tức',
            'add_new_item'  => 'Thêm tin mới',
            'edit_item'     => 'Chỉnh sửa tin'
        ),
        'public'             => true,
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-megaphone',
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
        'show_in_rest'       => true, // Hỗ trợ Gutenberg
        'rewrite'            => array('slug' => 'tin-tuc', 'with_front' => false),
        'capability_type'    => 'post', // Sử dụng quyền của bài viết bình thường
    ));

    // Đăng ký Taxonomy
    register_taxonomy('chuyen_muc_tin', 'tin_tuc', array(
        'hierarchical'       => true,
        'labels'             => array(
            'name'           => 'Chuyên mục tin',
            'singular_name'  => 'Chuyên mục'
        ),
        'show_ui'            => true,
        'show_in_rest'       => true, // Cực kỳ quan trọng để hiển thị chuyên mục trong Gutenberg
        'rewrite'            => array('slug' => 'chuyen-muc-tin'),
    ));
}
add_action('init', 'register_tintuc_custom_post_type');