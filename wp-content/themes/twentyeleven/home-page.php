<?php
/*
Template Name: Home page
*/
get_header(); // Gọi phần đầu trang (menu, logo)
?>

<div id="primary">
    <div class="sidebar-left-custom">
        <div >
            <?php echo do_shortcode('[list]'); ?>
        </div>
        <div class="custom-sidebar-nav">
            <?php
            wp_nav_menu( array(
                'menu'            => 'sidebar', // Tên menu bạn đặt
                'container'       => 'div',
                'container_class' => 'sidebar-menu-wrapper', // Class để bạn CSS
                'menu_class'      => 'sidebar-list', // Class cho thẻ <ul>
                ) );
                ?>
        </div>
    </div>
    <div id="content" role="main">
        <?php echo do_shortcode('[slider]'); ?>
        <?php echo do_shortcode('[list-v2]'); ?>
    </div>
</div><!-- #primary -->

<?php 
get_footer(); // Gọi phần chân trang 
?>