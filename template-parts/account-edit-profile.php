<?php
if ($args){
	extract($args);
	$fullname = $first_name . ' ' . $last_name;
	$avatar = ($avatar_url!=''?$avatar_url:IMG_URL.'No_Image_Available.jpg');
}
?>

<h5 class="title"><?php _e('My profile',LANG_ZONE)  ?></h5>
<p ><?php _e('Manage profile information to keep your account secure',LANG_ZONE)  ?></p>
<form action="" class="" id="update-account-form">
	<div class="row">
		<div class="col-md-9">

			<div class="mb-2 row">
				<label for="fullname" class="col-sm-3 col-form-label text-end" data-erp-name="<?php echo esc_attr($erp_name); ?>"><?php _e('Fullname',LANG_ZONE)  ?></label>
				<div class="col-sm-9">
					<input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo esc_attr($fullname); ?>">
				</div>
			</div>
			<div class="mb-2 row">
				<label for="inputPassword" class="col-sm-3 col-form-label text-end"><?php _e('Gender',LANG_ZONE)  ?></label>
				<div class="col-sm-9 col-form-label">
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="gender" id="male" value="male" <?php checked($gender, 'male'); ?>>
						<label class="form-check-label" for="male"><?php _e('Male', LANG_ZONE)  ?></label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="gender" id="female" value="female" <?php checked($gender, 'female'); ?>>
						<label class="form-check-label" for="female"><?php _e('Female', LANG_ZONE)  ?></label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="gender" id="other" value="other" <?php checked($gender, 'other'); ?>>
						<label class="form-check-label" for="other"><?php _e('Other', LANG_ZONE)  ?></label>
					</div>
				</div>
			</div>
			<div class="mb-2 row">
				<label for="email" class="col-sm-3 col-form-label text-end">Email</label>
				<div class="col-sm-9">
					<input type="text" class="form-control" name="email" id="email" value="<?php echo esc_attr($user_email); ?>" <?php echo $user_email?'readonly':'' ?>>
				</div>
			</div>
			<div class="mb-2 row">
				<label for="phone_number" class="col-sm-3 col-form-label text-end"><?php _e('Phone number', LANG_ZONE)  ?></label>
				<div class="col-sm-9">
					<input type="text" class="form-control" id="phone_number" name="phone_number"  value="<?php echo esc_attr($phone_number); ?>" >
				</div>
			</div>
			<div class="mb-2 row align-items-center">
				<label class="col-sm-3 col-form-label text-end "><?php _e('Day of birth', LANG_ZONE)  ?></label>
				<div class="col-sm-9 col-form-label form-dob-select">
					<div class=" form-check-inline">
						<select class="form-select" name="birthdate_day" aria-label="Ngày">
							<option value="" selected><?php _e('Day', LANG_ZONE)  ?></option>
							<?php for ($i = 1; $i <= 31; $i++) { ?>
								<option value="<?php echo $i ?>" <?php selected(date('d', strtotime($dob)), $i); ?>><?php echo $i ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-check form-check-inline">
						<select class="form-select" name="birthdate_month"  aria-label="Tháng">
							<option value="" selected><?php _e('Month', LANG_ZONE);  ?></option>
							<?php for ($i = 1; $i <= 12; $i++) {
								$months = array(
									1 => __('January',LANG_ZONE),
									2 => __('February', LANG_ZONE),
									3 => __('March',LANG_ZONE),
									4 => __('April',LANG_ZONE),
									5 => __('May', LANG_ZONE),
									6 => __('June', LANG_ZONE),
									7 => __('July', LANG_ZONE),
									8 => __('August', LANG_ZONE),
									9 => __('September', LANG_ZONE),
									10 => __('October', LANG_ZONE),
									11 => __('November', LANG_ZONE),
									12 => __('December', LANG_ZONE)
								);
								?>
								<option value="<?php echo $i ?>" <?php selected(date('n', strtotime($dob)), $i); ?>><?php echo $months[$i] ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="form-check form-check-inline">
						<select class="form-select" name="birthdate_year"  aria-label="Năm">
							<option value="" selected><?php _e('Year', LANG_ZONE)  ?></option>
							<?php for ($i = 1900; $i <= date("Y"); $i++) { ?>
								<option value="<?php echo $i ?>" <?php selected(date('Y', strtotime($dob)), $i); ?>><?php echo $i ?></option>
							<?php } ?>
						</select>
					</div>
					<input type="hidden" name="birthdate" value="<?php echo $dob; ?>">
					<input type="hidden" name="action" value="update_account_info">
				</div>
			</div>

			

			<div class="mb-2 row align-items-center">
				<div class="col-md-9 offset-md-3 ">
					<button type="submit" class="btn btn-primary "><?php _e('Save', LANG_ZONE)  ?></button>
				</div>
			</div>
		</div>
		<div class="col-md-3 d-flex flex-column  align-items-center">
			<div class="avatar mx-0 my-3">
				<img src="<?php echo $avatar;  ?>" id="avatar-preview" class="rounded-circle img-thumbnail" alt="...">
			</div>
			<label class=" btn btn-primary" role="button" for="avatar-input"><?php _e('Select', LANG_ZONE)  ?></label>
			<input type="file" class="form-control d-none" name="avatar" id="avatar-input">
		</div>
		<div class="col-md-12">
		<hr class="my-4"> <!-- Add a separator -->
            <h6 class="mb-3">Địa chỉ thanh toán</h6>

            <!-- Billing Address Fields -->
            
            <div class="mb-2 row">
                <label for="billing_company" class="col-sm-3 col-form-label text-end"><?php _e('Company (Optional)', LANG_ZONE)  ?></label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="billing_company" name="billing_company" value="<?php echo esc_attr($billing_address['company']); ?>">
                </div>
            </div>

            <!-- Location Dropdown -->
            <div class="mb-2 row">
                <label for="billing_location" class="col-sm-3 col-form-label text-end"><?php _e('Province/City', LANG_ZONE)  ?></label>
                <div class="col-sm-9">
                    <select class="form-select" id="billing_location" name="billing_location" disabled>
                        <option value="">-- <?php _e('Select Province/City - District', LANG_ZONE)  ?> --</option>
                        <!-- Options will be populated by JavaScript -->
                    </select>
                </div>
            </div>

            <!-- Ward Dropdown -->
            <div class="mb-2 row">
                <label for="billing_ward" class="col-sm-3 col-form-label text-end"><?php _e('Ward/Commune', LANG_ZONE)  ?></label>
                <div class="col-sm-9">
                    <select class="form-select" id="billing_ward" name="billing_ward" disabled>
                        <option value="">-- <?php _e('Select Ward/Commune', LANG_ZONE)  ?> --</option>
                        <!-- Options will be populated by JavaScript -->
                    </select>
                </div>
            </div>

            <!-- Street Address -->
            <div class="mb-2 row">
                <label for="billing_address_1" class="col-sm-3 col-form-label text-end"><?php _e('Address', LANG_ZONE)  ?></label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="billing_address_1" name="billing_address_1" placeholder="<?php _e('House number, street name', LANG_ZONE)  ?>" value="<?php echo esc_attr($billing_address['address_1']); ?>">
                </div>
            </div>

            <!-- Postcode (Optional) -->
            <div class="mb-2 row">
                <label for="billing_postcode" class="col-sm-3 col-form-label text-end"><?php _e('Postal Code (Optional)', LANG_ZONE)  ?></label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="billing_postcode" name="billing_postcode" value="<?php echo esc_attr($billing_address['postcode']); ?>">
                </div>
            </div>
            <!-- End Billing Address Fields -->
            <div class="mb-2 row align-items-center">
                <div class="col-md-9 offset-md-3 ">
                    <button type="submit" class="btn btn-primary "><?php _e('Save', LANG_ZONE)  ?></button>
                </div>
            </div>
		</div>
	</div>
</form>
<?php 
$AccountProfileVars = [
	'savedBillingLocation' => $billing_address['city'],
	'savedBillingWard' => $billing_address['ward'],
];
?>
<script type="text/javascript">
	const AccountProfileVars = <?php echo wp_json_encode($AccountProfileVars); ?>;
</script>