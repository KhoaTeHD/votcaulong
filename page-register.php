<?php
/**
 * Template Name: Register & Login
 **/
get_header();
$account_page = get_field('user_account','options');
if (is_user_logged_in()) {
	?>
    <script>
        window.location.href = "<?php echo $account_page  ?>";
    </script>
	<?php
}
$login_content = get_field('login_content');
?>
<div class="container">
	<div class="post-content my-5 ">
        <div class="row register_login_form-wrapper login-form shadow-lg ">
            <div class="col-md-6 left-col">
                <div class="left-content">
                    <div class="overlay"></div>
                    <div class="register-text">
                        <?php if ($login_content) {
                            echo $login_content;
                        }  ?>
                    </div>
                    <h3 class="login-text text-center" style="display:none;">
                        Đã đăng ký ?
                    </h3>
                    <button type="button" class="btn btn-light" id="formToggle-btn" data-register="Đăng ký ngay" data-login="Đăng nhập tại đây">Đăng ký ngay</button>
                </div>
            </div>
            <div class="col-md-6 right-col">
                <div class="right-content position-relative h-100 right-content position-relative h-100 d-flex flex-column justify-content-between">
                    <div class="form-body position-relative w-100">
                        <div id="login-frm-wrapper" class="frm-effect">
                            <h5 class="form-title"><?php _e('Login',LANG_ZONE)  ?></h5>
                            <form id="frm-login" action="" class="">
                                <input class="form-control my-4" type="text" name="email_phone_number" placeholder="<?php _e('Enter phone number/Email',LANG_ZONE)  ?>" aria-label="">
                                <div class="input-group2 custom-input-group">
                                    <input class="form-control password-input" type="password" name="password" placeholder="<?php _e('Password',LANG_ZONE)  ?>" aria-label="">
                                    <span class="input-group-text togglePassword"><i class="bi bi-eye-slash" role="button"></i></span>
                                </div>
                                <p class="text-end my-3"><a href="#"><?php _e('Forgot password ?',LANG_ZONE)  ?></a></p>
                                <button class="btn btn-primary text-uppercase w-100" type="submit"><span class="spinner-border spinner-border-sm" style="display: none;" aria-hidden="true"></span> <?php _e('Login',LANG_ZONE)  ?></button>
                            </form>
                            <p class="text-center my-3"><?php _e("Don't have an account ?",LANG_ZONE)  ?> <a href="#" class="formToggle-btn"><?php _e('Register',LANG_ZONE)  ?></a></p>
                        </div>
                        <div id="register-frm-wrapper" class="frm-effect" style="display: none;">
                            <h5 class="form-title"><?php _e('Register',LANG_ZONE)  ?></h5>
                            <form id="frm-register" action="" class="">
                                <input class="form-control my-4" type="text" name="email_phone_number" placeholder="<?php _e('Enter phone number/Email',LANG_ZONE)  ?>" aria-label="">
                                <div class="input-group2 custom-input-group my-4">
                                    <input class="form-control password-input" type="password" name="password" placeholder="<?php _e('Password',LANG_ZONE)  ?>" aria-label="">
                                    <span class="input-group-text togglePassword"><i class="bi bi-eye-slash" role="button"></i></span>
                                </div>
                                <div class="input-group2 custom-input-group my-4">
                                    <input class="form-control password-input" type="password" name="repeat-password" placeholder="<?php _e('Repeat Password',LANG_ZONE)  ?>" aria-label="">
                                    <span class="input-group-text togglePassword"><i class="bi bi-eye-slash" role="button"></i></span>
                                </div>
                                <button class="btn btn-primary text-uppercase w-100" type="submit">  <span class="spinner-border spinner-border-sm" style="display: none;" aria-hidden="true"></span>
	                                <?php _e('Continue',LANG_ZONE)  ?></button>
                            </form>
                            <p class="text-center my-3"><?php _e("Already have an account!",LANG_ZONE)  ?> <a href="#" class="formToggle-btn"><?php _e('Login',LANG_ZONE)  ?></a></p>
                        </div>
                    </div>
                    <div class="form-footer" style="display: none;">

                        <p class="text-center my-3 text-uppercase"><?php _e('OR', LANG_ZONE)  ?></p>
                        <div class="login-with-social">
                            <a href="#" role="button" class="icon-link">
                                <img src="<?php echo IMG_URL  ?>icons/icn-zalo.png"> <?php _e('Login with Zalo',LANG_ZONE)  ?>
                            </a>
                            <a href="#" role="button" class="icon-link">
                                <img src="<?php echo IMG_URL  ?>icons/icn-facebook.png"> <?php _e('Login with Facebook',LANG_ZONE)  ?>
                            </a>
                            <a href="#" role="button" class="icon-link">
                                <img src="<?php echo IMG_URL  ?>icons/icn-google.png"> <?php _e('Login with Google',LANG_ZONE)  ?>
                            </a>
                            <a href="#" role="button" class="icon-link">
                                <img src="<?php echo IMG_URL  ?>icons/icn-qrcode.png"> <?php _e('Login with QRCode',LANG_ZONE)  ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
<div class="toast-container position-fixed top-50 start-50 translate-middle p-3">
    <div id="form-noti" class="toast " role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header text-bg-danger">
            <strong class="me-auto"><?php _e('Error!',LANG_ZONE)  ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">

        </div>
    </div>
</div>

<style>
    .breadcrumbs {
        display: none;
    }
    .register_login_form-wrapper {
        height: 540px;
        border-radius: 35px;
        background-color: #fff;
    }
    .register_login_form-wrapper a {
        text-decoration: none;
        color: var(--bs-linear-color2);
    }
    .register_login_form-wrapper .left-col {
        color: #fff;

        border-top-left-radius: 35px;
        border-bottom-left-radius: 35px;
        position: relative;
        overflow: hidden;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        /*transition: all 0.3s ease-in-out;*/
        transition: background 1s ease-in-out !important;
        -webkit-transition: background 1s;
        -moz-transition: background 1s;
        -o-transition: background 1s;
    }
    .login-form .left-col{
        background: url("<?php echo IMG_URL ?>login-bg.jpg") center center no-repeat;
        background-size: cover;
        loading: lazy;
    }
    .frm-effect {
        transition: all 0.3s ;
        top: 0;
        position: absolute;
        width: 100%;
        z-index: 100;
    }
    .register_login_form-wrapper .left-col .overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #2c2c2c;
        opacity: 0.6;
        z-index: -1;
        transition: opacity 0.8s ease-in-out;
    }

    .register_login_form-wrapper .left-content {
        font-size: 13px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        height: 350px;
        width: 350px;
    }
    .register_login_form-wrapper .left-content h3{
        font-size: 1.2rem;
        margin-bottom: 2rem;
    }
    .register_login_form-wrapper .left-content button {
        text-transform: uppercase;
        width: 100%;
        font-weight: 500;
    }
    .register_login_form-wrapper .left-content ul.checked-list {
        margin-bottom: 2rem;
    }
    .register_login_form-wrapper .left-content ul li {
        margin-bottom: 15px;
    }
    .register_login_form-wrapper .right-content {
        padding: 3rem;
    }
    .register_login_form-wrapper .right-content .input-group-text {
        background: transparent;
        border-left: 0;
    }
    .register_login_form-wrapper .input-group input {
        border-right: 0;
    }
    .login-with-social {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    .login-with-social a {
        border: 1px solid var(--bs-gray-200);
        padding: 5px 10px;
        border-radius: 5px;
        text-decoration: none;
        display: flex;
        justify-content: center;
        font-size: 14px;
        color:var(--bs-text-color);
        transition: all 0.3s ease-in-out;
    }
    .login-with-social a:hover{
        box-shadow: 1px 1px 5px #ddd;
    }
    .login-with-social a img{
        width: 25px;
    }
    .register-form .left-col {
        background: url("<?php echo IMG_URL ?>register-bg.jpg") center center no-repeat;
        background-size: cover;
        loading: lazy;
    }

    @keyframes fadeout {
        0% { opacity: 0.6; }
        100% { opacity: 1; }
    }
    @keyframes fadein {
        0% { opacity: 1; }
        100% { opacity: 0.6; }
    }
    .form-title{
        transition: all 0.4s;
    }
    .title-move-down{
        transform: translate(320px,250px);
        opacity: 0.2;
    }
    .form-footer {
        width: 100%;
        position: relative;
        bottom: 0;
        /*transform: translateY(-100%);*/
    }
</style>

<?php
get_footer(); ?>
