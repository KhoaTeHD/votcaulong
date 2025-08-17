<?php
// Ensure user is logged in
if ( !is_user_logged_in()) {
    echo '<p>'.__('Please login to manage addresses.',LANG_ZONE).'</p>'; // Please log in to manage addresses.
    return;
}

// Instantiate the Customer object for the current user
$customer = get_current_customer();




?>

<div class="account-address-container"> <!-- Added a container for easier JS targeting -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="title mb-0"><?php _e('My addresses', LANG_ZONE)  ?></h5>
        <!-- Button to trigger the modal -->
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#address-modal" id="btn-add-new-address">
            <i class="fas fa-plus me-1"></i> <?php _e('Add new address', LANG_ZONE)  ?>
        </button>
    </div>
    <p class="mb-4"><?php _e('Manage your shipping address information.', LANG_ZONE)  ?></p>

    <!-- Address List -->
    <div id="customer-address-list" class="address-list">
        <?php if (!empty($addresses)) : ?>
            <?php foreach ($addresses as $address) : // Iterate through the numerically indexed sorted array
                $address_id = $address['id']; // Get the ID from the address data
                $is_default = !empty($address['is_default']);
            ?>
                <div class="card address-card mb-3 <?php echo $is_default ? 'border-primary' : ''; ?>" data-address-id="<?php echo esc_attr($address_id); ?>"
                     data-recipient-name="<?php echo esc_attr($address['recipient_name'] ?? ''); ?>"
                     data-recipient-phone="<?php echo esc_attr($address['recipient_phone'] ?? ''); ?>"
                     data-location-name="<?php echo esc_attr($address['location_name'] ?? ''); ?>"
                     data-ward-name="<?php echo esc_attr($address['ward_name'] ?? ''); ?>"
                     data-street="<?php echo esc_attr($address['street'] ?? ''); ?>"
                     data-is-default="<?php echo $is_default ? '1' : '0'; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <span class="fw-bold me-2"><?php echo esc_html($address['recipient_name'] ?? ''); ?></span> |
                                <span class="ms-2 text-muted"><?php echo esc_html($address['recipient_phone'] ?? ''); ?></span>
                                <?php if ($is_default) : ?>
                                    <span class="badge bg-primary ms-2"><?php _e('Default', LANG_ZONE)  ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <button type="button" class="btn btn-link btn-sm text-primary p-0 me-2 btn-edit-address" data-bs-toggle="modal" data-bs-target="#address-modal" title="Sửa"><?php _e('Edit', LANG_ZONE)  ?></button>
                                <?php if (!$is_default) : ?>
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0 btn-delete-address" title="Xóa"><?php _e('Delete', LANG_ZONE)  ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="card-text text-muted mb-1">
                            <?php echo esc_html($address['street'] ?? ''); ?>,
                            <?php echo esc_html($address['ward_name'] ?? ''); ?>,
                            <?php echo esc_html($address['location_name'] ?? ''); // Assumes location_name contains Province/City - District ?>
                        </p>
                        <?php if (!$is_default) : ?>
                             <button type="button" class="btn btn-outline-secondary btn-sm mt-2 btn-set-default-address"><?php _e('Set as default', LANG_ZONE)  ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p id="no-addresses-message"><?php _e('You have no saved addresses.', LANG_ZONE)  ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Address Modal (Add/Edit) -->
<div class="modal fade" id="address-modal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="address-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="addressModalLabel"><?php _e('Create new address',LANG_ZONE)  ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="address_id" name="address_id" value=""> <!-- Hidden field for address ID during edit -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="recipient_name" class="form-label"><?php _e("Recipient's name", LANG_ZONE)  ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="recipient_name" name="recipient_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="recipient_phone" class="form-label"><?php _e('Phone number', LANG_ZONE)  ?> <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="recipient_phone" name="recipient_phone" required>
                        </div>
                    </div>
                    <div class="row">
                         <!-- Use IDs consistent with checkout page if reusing JS, but ensure correct 'name' attributes -->
                        <div class="col-md-6 mb-3">
                             <label for="shipping_location" class="form-label"><?php _e('Province/City - District', LANG_ZONE)  ?> <span class="text-danger">*</span></label>
                             <select class="form-select" id="shipping_location" name="location_name" required>
                                 <option value="">-- <?php _e('Select Province/City - District', LANG_ZONE)  ?> --</option>
                                 <!-- Options loaded by JS -->
                             </select>
                             <!-- <input type="hidden" id="location_id_hidden" name="location_id"> Optional: Store ID if needed -->
                        </div>
                        <div class="col-md-6 mb-3">
                             <label for="shipping_ward_new" class="form-label"><?php _e('Ward/Commune', LANG_ZONE)  ?> <span class="text-danger">*</span></label>
                             <select class="form-select" id="shipping_ward_new" name="ward_name" required disabled>
                                 <option value="">-- <?php _e('Select Ward/Commune', LANG_ZONE)  ?> --</option>
                                  <!-- Options loaded by JS -->
                             </select>
                              <!-- <input type="hidden" id="ward_id_hidden" name="ward_id"> Optional: Store ID if needed -->
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="street" class="form-label"><?php _e('Specific address (House number, street name...)', LANG_ZONE)  ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="street" name="street" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="is_default" name="is_default"> <!-- Changed ID and name -->
                        <label class="form-check-label" for="is_default">
	                        <?php _e('Set as default', LANG_ZONE)  ?>
                        </label>
                    </div>
                     <div id="address-form-error" class="text-danger mt-2 d-none"></div> <!-- Added d-none to hide initially -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel', LANG_ZONE)  ?></button>
                    <button type="submit" class="btn btn-primary" id="btn-save-address"><?php _e('Save address', LANG_ZONE)  ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
