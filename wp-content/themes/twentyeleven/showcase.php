<?php

/**
 * Template Name: Showcase Template
 *
 * Description: A Page Template that showcases Sticky Posts, Asides, and Blog Posts.
 *
 * The showcase template in Twenty Eleven consists of a featured posts section using sticky posts,
 * another recent posts area (with the latest post shown in full and the rest as a list)
 * and a left sidebar holding aside posts.
 *
 * We are creating two queries to fetch the proper posts and a custom widget for the sidebar.
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

// Enqueue showcase script for the slider.
wp_enqueue_script(
	'twentyeleven-showcase',
	get_template_directory_uri() . '/js/showcase.js',
	array('jquery'),
	'20211130',
	array(
		'in_footer' => false, // Because involves header.
		'strategy'  => 'defer',
	)
);

get_header(); ?>

<div id="primary" class="showcase">
	<div id="content" role="main">

		<div class="slidertbao">
			<div id="featured">
				<ul class="ui-tabs-nav sliderborder">
					<li class="ui-tabs-nav-item ui-tabs-selected" id="nav-fragment-1">
						<a href="#fragment-0">
							<img class="imgsmall_slider" src="files/news/20260628160917_image_1.jpg" alt="" />
							<span>Giấy mời họp Cha mẹ học sinh lớp 10 năm học 2026-2027 &nbsp;(28-06-2026)</span></a>
					</li>
					<li class="ui-tabs-nav-item ui-tabs-selected" id="nav-fragment-1">
						<a href="#fragment-1">
							<img class="imgsmall_slider" src="files/news/20260627110303_ke_hoach_thuhosonhaphoc10.jpg" alt="" />
							<span>Kế hoạch thu hồ sơ nhập học vào lớp 10 năm học ... &nbsp;(27-06-2026)</span></a>
					</li>
					<li class="ui-tabs-nav-item ui-tabs-selected" id="nav-fragment-1">
						<a href="#fragment-2">
							<img class="imgsmall_slider" src="files/news/20260626131612_thong_bao_kehoachnhaphoc10.jpg" alt="" />
							<span>Kế hoạch nhập học vào lớp 10 năm học 2026-2027 &nbsp;(26-06-2026)</span></a>
					</li>
					<li class="ui-tabs-nav-item ui-tabs-selected" id="nav-fragment-1">
						<a href="#fragment-3">
							<img class="imgsmall_slider" src="files/news/20260626130131_dstrungtueyen.jpg" alt="" />
							<span>Danh sách thí sinh đủ điểm chuẩn vào lớp 10 THPT - ... &nbsp;(26-06-2026)</span></a>
					</li>
					<li class="ui-tabs-nav-item ui-tabs-selected" id="nav-fragment-1">
						<a href="#fragment-4">
							<img class="imgsmall_slider" src="files/news/20260615160931_sgk.jpg" alt="" />
							<span>Quyết định bộ sách giáo khoa sử dụng thống nhất toàn quốc &nbsp;(12-05-2026)</span></a>
					</li>

				</ul>

				<!-- First Content -->
				<div id="fragment-0" class="ui-tabs-panel" style="">
					<img src="files/news/20260628160917_image_1.jpg" alt="" />
					<div class="info">
						<h2><a href="news/view/giay-moi-hop-cha-me-hoc-sinh-lop-10-nam-hoc-2026-2027.html">Giấy mời họp Cha mẹ học sinh lớp 10 năm học 2026-2027</a></h2>
					</div>
				</div>
				<div id="fragment-1" class="ui-tabs-panel" style="">
					<img src="files/news/20260627110303_ke_hoach_thuhosonhaphoc10.jpg" alt="" />
					<div class="info">
						<h2><a href="news/view/ke-hoach-thu-ho-so-nhap-hoc-vao-lop-10-nam-hoc-2026-2027.html">Kế hoạch thu hồ sơ nhập học vào lớp 10 năm học 2026-2027</a></h2>
					</div>
				</div>
				<div id="fragment-2" class="ui-tabs-panel" style="">
					<img src="files/news/20260626131612_thong_bao_kehoachnhaphoc10.jpg" alt="" />
					<div class="info">
						<h2><a href="news/view/ke-hoach-nhap-hoc-vao-lop-10-nam-hoc-2026-2027.html">Kế hoạch nhập học vào lớp 10 năm học 2026-2027</a></h2>
					</div>
				</div>
				<div id="fragment-3" class="ui-tabs-panel" style="">
					<img src="files/news/20260626130131_dstrungtueyen.jpg" alt="" />
					<div class="info">
						<h2><a href="news/view/danh-sach-thi-sinh-du-diem-chuan-vao-lop-10-thpt-truong-thpt-tran-phu.html">Danh sách thí sinh đủ điểm chuẩn vào lớp 10 THPT - Trường THPT Trần Phú</a></h2>
					</div>
				</div>
				<div id="fragment-4" class="ui-tabs-panel" style="">
					<img src="files/news/20260615160931_sgk.jpg" alt="" />
					<div class="info">
						<h2><a href="news/view/quyet-dinh-bo-sach-giao-khoa-su-dung-thong-nhat-toan-quoc.html">Quyết định bộ sách giáo khoa sử dụng thống nhất toàn quốc</a></h2>
					</div>
				</div>


			</div>






			<div style="float:right; width:250px">



				<div id="primary" class="widget-area">

					<ul class="xoxo">
						<li style="padding:3px" id="linkcat-2" class="widget-container widget_links">
							<h3 class="content-title_new" style="margin:0">THÔNG TIN - THÔNG BÁO</h3>




							<ul class="xoxo blogroll bordertbaoss">
								<li class="lithbao"><a href="news/view/giay-moi-hop-cha-me-hoc-sinh-lop-10-nam-hoc-2026-2027.html" class="textnomalslider">Giấy mời họp Cha mẹ học sinh lớp 10 năm học 2026-2027</a></li>
								<li class="lithbao"><a href="news/view/ke-hoach-thu-ho-so-nhap-hoc-vao-lop-10-nam-hoc-2026-2027.html" class="textnomalslider">Kế hoạch thu hồ sơ nhập học vào lớp 10 năm học 2026-2027</a></li>
								<li class="lithbao"><a href="news/view/thong-bao-v-v-moi-chao-gia-cung-ung-dich-vu-thue-van-chuyen-doan-can-bo-giao-vien-di-cong-tac-tai-ti.html" class="textnomalslider">Thông báo v/v mời chào giá cung ứng dịch vụ thuê vận chuyển đoàn cán bộ giáo viên đi công tác tại tỉnh Ninh Bình</a></li>
								<li class="lithbao"><a href="news/view/ke-hoach-nhap-hoc-vao-lop-10-nam-hoc-2026-2027.html" class="textnomalslider">Kế hoạch nhập học vào lớp 10 năm học 2026-2027</a></li>
								<li class="lithbao"><a href="news/view/danh-sach-thi-sinh-du-diem-chuan-vao-lop-10-thpt-truong-thpt-tran-phu.html" class="textnomalslider">Danh sách thí sinh đủ điểm chuẩn vào lớp 10 THPT - Trường THPT Trần Phú</a></li>
							</ul>
						</li>
					</ul>

				</div>


			</div>


		</div>

	</div><!-- #content -->
</div><!-- #primary -->

<?php get_footer(); ?>