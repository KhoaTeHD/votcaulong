jQuery(document).ready(function($){
    // Function to handle manual sync for brands
    function manualSyncBrands() {
        var $syncButton = $('#sync-brands-button');
        $syncButton.prop('disabled', true).text('Đang đồng bộ...');

        $.post(ajaxurl, {
            action: 'sync_brands_from_erp',
            nonce: MyVars.nonce
        }, function(response){
            if (response.success) {
                console.log(
                    "✅ Đồng bộ Thương hiệu thành công:",
                    response.data.synced.length + " Thương hiệu"
                );
                alert("Đồng bộ Thương hiệu thành công!");
            } else {
                console.error("Lỗi đồng bộ Thương hiệu:", response.data);
                alert("Lỗi đồng bộ Thương hiệu: " + (response.data || "Vui lòng kiểm tra console."));
            }
            $syncButton.prop('disabled', false).text('Đồng bộ ngay');
            location.reload(); // Reload the page to show updated data
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX request failed:", textStatus, errorThrown);
            alert("Lỗi kết nối khi đồng bộ Thương hiệu. Vui lòng thử lại.");
            $syncButton.prop('disabled', false).text('Đồng bộ ngay');
        });
    }

    // Attach click event to the new button (will be added in PHP)
    $(document).on('click', '#sync-brands-button', manualSyncBrands);
});
