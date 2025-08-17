jQuery(document).ready(function($){
    // Function to handle manual sync
    function manualSyncStores() {
        var $syncButton = $('#sync-stores-button');
        $syncButton.prop('disabled', true).text('Đang đồng bộ...');

        $.post(ajaxurl, {
            action: 'sync_stores_from_erp',
            nonce: MyVars.nonce
        }, function(response){
            if (response.success) {
                console.log(
                    "✅ Đồng bộ chi nhánh thành công:",
                    response.data.synced.length + " chi nhánh"
                );
                alert("✅ Đồng bộ chi nhánh thành công:",
                    response.data.synced.length + " chi nhánh");
            } else {
                console.error("Lỗi đồng bộ chi nhánh:", response.data);
                alert("Lỗi đồng bộ chi nhánh: " + (response.data || "Vui lòng kiểm tra console."));
            }
            $syncButton.prop('disabled', false).text('Đồng bộ ngay');
            location.reload(); // Reload the page to show updated data
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX request failed:", textStatus, errorThrown);
            alert("Lỗi kết nối khi đồng bộ chi nhánh. Vui lòng thử lại.");
            $syncButton.prop('disabled', false).text('Đồng bộ ngay');
        });
    }

    // Attach click event to the new button (will be added in PHP)
    $(document).on('click', '#sync-stores-button', manualSyncStores);
});
