jQuery(document).ready(function($){
    $.post(ajaxurl, {
        action: 'sync_stores_from_erp',
        nonce: MyVars.nonce
    }, function(response){
        if (response.success) {
            console.log(
                "✅ Đồng bộ chi nhánh thành công:",
                response.data.synced.length + " chi nhánh"
            );
        } else {
            console.error("Lỗi đồng bộ chi nhánh:", response.data);
        }
    });
});
