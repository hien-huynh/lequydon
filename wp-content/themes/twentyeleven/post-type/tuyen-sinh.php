<?php
// Đăng ký Post Type Tuyển sinh và Taxonomy đi kèm
function register_tuyen_sinh_custom_post_type() {
    register_post_type('tuyen_sinh', array(
        'labels' => array(
            'name'          => 'Tuyển sinh',
            'singular_name' => 'Tuyển sinh',
            'add_new_item'  => 'Thêm tuyển sinh mới',
            'edit_item'     => 'Chỉnh sửa tuyển sinh'
        ),
        'public'             => true,
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-welcome-learn-more',
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
        'show_in_rest'       => true,
        'rewrite'            => array('slug' => 'tuyen-sinh', 'with_front' => false),
        'capability_type'    => 'post',
    ));

    register_taxonomy('chuyen_muc_tuyen_sinh', 'tuyen_sinh', array(
        'hierarchical'       => true,
        'labels'             => array(
            'name'           => 'Chuyên mục tuyển sinh',
            'singular_name'  => 'Chuyên mục tuyển sinh'
        ),
        'show_ui'            => true,
        'show_in_rest'       => true,
        'rewrite'            => array('slug' => 'chuyen-muc-tuyen-sinh'),
    ));
}
add_action('init', 'register_tuyen_sinh_custom_post_type');
