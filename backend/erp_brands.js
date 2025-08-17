jQuery(document).ready(function($){
    $.post(ajaxurl, {
        action: 'sync_brands_from_erp',
        nonce: MyVars.nonce
    }, function(response){
        if (response.success) {
            console.log(
                "Đồng bộ Thương hiệu thành công:",
                response.data.synced.length + " Thương hiệu"
            );
        } else {
            console.error("Lỗi đồng bộ:", response.data);
        }
    });
});
