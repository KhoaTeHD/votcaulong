<?php
/**
 * Template Name: Password Reset - page
 */

get_header();

$key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
$login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';

$user = check_password_reset_key($key, $login);

$errors = new WP_Error();

if (is_wp_error($user)) {
    $errors = $user;
}

// If the form is submitted
if (isset($_POST['reset_password'])) {
    $new_password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (empty($new_password) || empty($confirm_password)) {
        $errors->add('password_empty', __('Please enter your new password twice.', LANG_ZONE));
    }

    if ($new_password !== $confirm_password) {
        $errors->add('password_mismatch', __('The passwords do not match.', LANG_ZONE));
    }

    if (strlen($new_password) < 6) {
        $errors->add('password_too_short', __('New password must be at least 6 characters long.', LANG_ZONE));
    }

    if (empty($errors->get_error_codes())) {
        // Reset password
        wp_set_password($new_password, $user->ID);

        // Log the user in and redirect to home or account page
        wp_set_auth_cookie($user->ID);
        wp_redirect(home_url('/')); // Or your account page URL
        exit;
    }
}

?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg p-4">
                <h3 class="card-title text-center mb-4"><?php _e('Reset Your Password', LANG_ZONE) ?></h3>

                <?php if ($errors->has_errors()) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?php foreach ($errors->get_error_messages() as $message) : ?>
                            <p><?php echo $message; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!is_wp_error($user)) : // Only show form if key is valid ?>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="password" class="form-label"><?php _e('New Password', LANG_ZONE) ?></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <span class="input-group-text togglePassword"><i class="bi bi-eye-slash" role="button"></i></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label"><?php _e('Confirm New Password', LANG_ZONE) ?></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <span class="input-group-text togglePassword"><i class="bi bi-eye-slash" role="button"></i></span>
                            </div>
                        </div>
                        <button type="submit" name="reset_password" class="btn btn-primary w-100"><?php _e('Set New Password', LANG_ZONE) ?></button>
                    </form>
                <?php else : $login_url = get_field('register_and_login','options'); ?>
                    <p class="text-center text-danger"><?php _e('Your password reset link is invalid or has expired. Please try again.', LANG_ZONE) ?></p>
                    <p class="text-center"><a href="<?php echo $login_url; ?>" class="btn btn-secondary"><?php _e('Back to Login/Register', LANG_ZONE) ?></a></p>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php
get_footer();
?>