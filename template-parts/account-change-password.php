<?php

?>

<h5 class="title mb-5"><?php _e('Change password',LANG_ZONE)  ?></h5>
<form action="" class="" id="change-password-form">
	<div class="row my-3">
		<div class="col-md-10">

			<div class="mb-2 row">
				<label for="current_password" class="col-sm-3 col-form-label text-end"><?php _e('Current Password',LANG_ZONE)  ?></label>
				<div class="col-sm-9">
					<div class="input-group">
						<input type="password" class="form-control" id="current_password" name="current_password" required>
						<span class="input-group-text togglePassword"><i class="bi bi-eye-slash"></i></span>
					</div>
				</div>
			</div>

			<div class="mb-2 row">
				<label for="new_password" class="col-sm-3 col-form-label text-end"><?php _e('New Password',LANG_ZONE)  ?></label>
				<div class="col-sm-9">
					<div class="input-group">
						<input type="password" class="form-control" id="new_password" name="new_password" required>
						<span class="input-group-text togglePassword"><i class="bi bi-eye-slash"></i></span>
					</div>
				</div>
			</div>

			<div class="mb-2 row">
				<label for="confirm_new_password" class="col-sm-3 col-form-label text-end"><?php _e('Confirm New Password',LANG_ZONE)  ?></label>
				<div class="col-sm-9">
					<div class="input-group">
						<input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
						<span class="input-group-text togglePassword"><i class="bi bi-eye-slash"></i></span>
					</div>
				</div>
			</div>

			<div class="mb-2 row align-items-center">
				<div class="col-md-9 offset-md-3 ">
					<button type="submit" class="btn btn-primary "><?php _e('Change Password', LANG_ZONE)  ?></button>
				</div>
			</div>
		</div>
	</div>
</form>
