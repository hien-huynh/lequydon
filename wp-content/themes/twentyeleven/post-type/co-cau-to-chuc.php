<?php
// Đăng ký Post Type Cơ cấu tổ chức
function register_co_cau_to_chuc() {
    // 1. Đăng ký Post Type
    register_post_type('co_cau', array(
        'labels' => array(
            'name' => 'Cơ cấu tổ chức',
            'singular_name' => 'Đơn vị',
        ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-networking',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'show_in_rest' => true, // Hỗ trợ Gutenberg
        'rewrite' => array('slug' => 'co-cau'),
    ));

    // 2. Đăng ký Taxonomy phân cấp (để tạo cha/con như hình)
    register_taxonomy('nhom_don_vi', 'co_cau', array(
        'hierarchical' => true, // Quan trọng: cho phép tạo cha/con
        'labels' => array(
            'name' => 'Nhóm đơn vị',
            'singular_name' => 'Nhóm',
        ),
        'show_ui' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'nhom-don-vi'),
    ));
}
add_action('init', 'register_co_cau_to_chuc');