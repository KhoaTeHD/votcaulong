<?php
/**
 * Template Name: Contact
 **/
get_header();
?>
<div class="container my-3">
    <div class="post-content bg-white p-3">
        <div class="row">
            <div class="contact-title m-3">Liên hệ chúng tôi</div>
        </div> 
        <div class="row">
            <div class="col-12 col-md-4 contact-items">
                <!-- Icon Điện Thoại -->
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 2h4l2 5-3 2c1 3 4 6 7 7l2-3 5 2v4c0 1-1 2-2 2h-2C7 21 3 12 3 5V3c0-1 1-2 2-2z" />
                </svg>
                <p class="contact-form1">Điện thoại</p>
                <p class="contact-form2">0776856666</p>
            </div>
            <div class="col-12 col-md-4 contact-items">
                <!-- Icon Địa Chỉ -->
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C8 2 5 5 5 9c0 4.5 7 11 7 11s7-6.5 7-11c0-4-3-7-7-7zm0 5a2 2 0 110 4 2 2 0 010-4z" />
                </svg>
                <p class="contact-form1">Địa chỉ</p>
                <p class="contact-form2">Số 527 đường Điện Biên Phủ, phường 3, Quận 3, Tp HCM</p>
            </div>
            <div class="col-12 col-md-4 contact-items">
                <!-- Icon Email -->
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 4c0-1 1-2 2-2h16c1 0 2 1 2 2v16c0 1-1 2-2 2H4c-1 0-2-1-2-2V4zm2 0v2l8 5 8-5V4H4zm0 4v12h16V8l-8 5-8-5z" />
                </svg>
                <p class="contact-form1">Email liên hệ</p>
                <p class="contact-form2">cskh@votcaulongshop.vn</p>
            </div>
        </div>
        <div class="contact-form row m-1">
            <div class="col-12 col-lg-6 local-img-contact order-2 order-lg-1">
                <img class="img-contact" src="<?php echo get_template_directory_uri(); ?>/assets/images/contact-img.png" alt="Logo">
            </div>
            <div class="col-12 col-lg-6 order-1 order-lg-1">
            <?php echo do_shortcode('[contact-form-7 id="bef81f6" title="Lien-he"]'); ?>
            </div>
        </div>
    </div>
	
</div>
<?php
get_footer();

