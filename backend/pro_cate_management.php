
<div class="wrap">
<h1 class="wp-heading-inline">Product category</h1>
<?php
//$erp_api = new ERP_API_Handler(false);
//$cate_list = $erp_api->list_all_item_groups(true);
//my_debug($cate_list);

?>
    <hr>
    <button class="button button-primary" id="sync_with_erp"><?php _e('Sync with ERP', LANG_ZONE)  ?></button>
<table id="proCate-list" class="display" style="width:100%">
    <thead>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Parent</th>
        <th>Full Path</th>
        <th>Desc</th>
        <th>Thumb</th>
        <th>Action</th>
    </tr>
    </thead>
    <tbody>


    </tbody>

</table>
<script type="text/javascript">
    jQuery(document).ready(function ($) {

        let table_proCate;

        // Khởi tạo DataTables trực tiếp khi DOM sẵn sàng
        table_proCate = $('#proCate-list').DataTable({
            "paging": true,
            "serverSide": true, // Bật chế độ xử lý phía server
            "processing": true, // Hiển thị thông báo "Processing..."
            "ajax": {
                url: MyVars.ajaxurl, // URL AJAX của WordPress
                type: "POST",
                data: function (d) {
                    // d là đối tượng chứa các tham số mặc định của DataTables (length, start, search...)
                    d.action = 'get_pro_cate_table';
                    d.nonce = MyVars.nonce;
                    // d.page = (d.start / d.length) + 1; // Có thể bỏ dòng này nếu PHP dùng trực tiếp start/length
                },
                dataSrc: function (json) {
                    // dataSrc được dùng để trích xuất dữ liệu từ response JSON
                    if (!json.success || !json.data || !json.data.data) {
                        console.error("Dữ liệu trả về không hợp lệ:", json);
                        return []; // Trả về mảng trống để DataTable không hiển thị gì
                    }

                    // DataTables mong đợi 'recordsTotal' và 'recordsFiltered' ở cấp cao nhất của JSON.
                    // PHP của bạn trả về chúng trong `json.data`. Cần điều chỉnh lại mapping.
                    json.recordsTotal = json.data.recordsTotal; // Tổng số bản ghi (cho DataTable)
                    json.recordsFiltered = json.data.recordsFiltered; // Tổng số bản ghi sau khi lọc (nếu có)

                    return json.data.data; // Dữ liệu hiển thị (mảng các đối tượng)
                }
            },
            "columns": [
                { "data": "id" },
                {   "data": "name" ,
                    "render": function (data,type,row){
                        return `<a href="${row.url}" target="_blank">${data}</a>`;
                    }
                },
                { "data": "parent_name", "defaultContent": "" },
                { "data": "full_path", "defaultContent": "" },
                { "data": "description" },
                {
                    "data": "thumbnail_url",
                    "render": function (data, type, row) {
                        if (data) {
                            return '<img width="30" src="' + data + '" alt="Thumbnail">';
                        }
                        return '';
                    }
                },
                {
                    "data": null,
                    "render": function (data, type, row) {
                        return '<button type="button" class="btn button-primary edit-cate-btn" data-procate-data=\'' + JSON.stringify(row) + '\' ?><?php _e('Edit', LANG_ZONE) ?></button>';
                    }
                }
            ],
            "columnDefs": [
                { "orderable": false, "targets": [5, 6] } // Tắt sắp xếp cho cột Thumbnail và Action
            ]
        });


        $('#sync_with_erp').on('click', function(e) {
            e.preventDefault();
            $(this).prop('disabled', true).text('<?php _e('Syncing...', LANG_ZONE) ?>');

            $.ajax({
                url: MyVars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sync_pro_cate_from_erp',
                    nonce: MyVars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php _e('Synchronization successful!', LANG_ZONE) ?>');
                        table_proCate.ajax.reload(); // Reload DataTables to show updated data
                    } else {
                        alert('<?php _e('Synchronization failed:', LANG_ZONE) ?> ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    alert('<?php _e('AJAX Error during sync:', LANG_ZONE) ?> ' + error);
                    console.error(xhr.responseText);
                },
                complete: function() {
                    $('#sync_with_erp').prop('disabled', false).text('<?php _e('Sync with ERP', LANG_ZONE) ?>');
                }
            });
        });
            


        $(document).on('click', '.close-modal, #close-modal', function () {
            $('#edit-cate-modal').fadeOut();
            $('.modal-backdrop').fadeOut(function () {
                $(this).remove();
            });
        });

        $(document).on('click','.edit-cate-btn',function(e){
            e.preventDefault();
            let cate_data = $(this).data("procate-data");
            var cateID = cate_data.id;
            var cate_name_for_display = cate_data.original_name || cate_data.name;

            if(cateID) {
                console.log(cateID);
                $('.clear-thumb').hide();
                $('#newchapter_group').show();

                $('#modal-cate-name').text(cate_name_for_display); // Hiển thị tên trong modal
                tinymce.get('cate_content_editor').setContent(cate_data.description);
                $('#edit-cate-modal').find('img.preview-img').attr('src',cate_data.thumbnail_url);
                $('#procate-thumbnail-id').val(cate_data.pro_cate_thumbnail_id); // Set hidden ID
                $('#edit-cate-modal').data('cate-id', cateID).fadeIn();
            }
        });

        // WordPress Media Uploader
        let mediaUploader;
        $('#select-media-button').on('click', function(e) {
            e.preventDefault();
            // If the uploader object has already been created, reopen the dialog
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            // Extend the wp.media object
            mediaUploader = wp.media({
                title: '<?php _e('Select Category Image', LANG_ZONE) ?>',
                button: {
                    text: '<?php _e('Use this image', LANG_ZONE) ?>'
                },
                multiple: false // Set to true to allow multiple files to be selected
            });

            // When a file is selected, grab the URL and set it as the text field's value
            mediaUploader.on('select', function() {
                let attachment = mediaUploader.state().get('selection').first().toJSON();
                $('.cate-thumbnail-box').data('old-thumb',$('img.preview-img').attr('src'));
                $('img.preview-img').attr('src', attachment.url);
                $('#procate-thumbnail-id').val(attachment.id); // Store attachment ID
                $('#procate-input').val(''); // Clear file input if media is selected
                $('.clear-thumb').show();
            });

            // Open the uploader dialog
            mediaUploader.open();
        });

        $('#procate-input').change(function(e) {
            let file = e.target.files[0];
            let reader = new FileReader();

            reader.onload = function(e) {
                $('.cate-thumbnail-box').data('old-thumb',$('img.preview-img').attr('src'));
                $('img.preview-img').attr('src', e.target.result);
                $('#procate-thumbnail-id').val(''); // Clear hidden ID if new file is uploaded
                $('.clear-thumb').show();
            }

            reader.readAsDataURL(file);
        });

        $('.clear-thumb').click(function(e){
            e.preventDefault();
            $('img.preview-img').attr('src', $('.cate-thumbnail-box').data('old-thumb'));
            $('#procate-input').val('');
            $('#procate-thumbnail-id').val(''); // Clear hidden ID
            $(this).hide();
        });

        $('#save-chapter-content').click(function (e) {
            e.preventDefault();

            let cateID = $('#edit-cate-modal').data('cate-id');
            let description = tinymce.get('cate_content_editor').getContent();
            let fileInput = $('#procate-input')[0];
            let thumbnailID = $('#procate-thumbnail-id').val(); // Get attachment ID
            let formData = new FormData();

            formData.append('cate_id', cateID);
            formData.append('description', description);
            formData.append('nonce', MyVars.nonce);
            formData.append('action', 'update_pro_cate');

            if (fileInput.files.length > 0) {
                formData.append('thumbnail', fileInput.files[0]);
            } else if (thumbnailID) {
                formData.append('thumbnail_id', thumbnailID); // Send attachment ID if selected from media
            }

            $.ajax({
                url: MyVars.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        $('#edit-cate-modal').fadeOut();
                        $('.modal-backdrop').fadeOut(function () {
                            $(this).remove();
                        });
                        $('#cate-edit-form')[0].reset();
                        tinymce.get('cate_content_editor').setContent('');
                        $('#procate-input').val('');
                        $('.clear-thumb').hide();
                        $('img.preview-img').attr('src', '');
                        $('.cate-thumbnail-box').data('old-thumb', '');

                        alert('Cập nhật thành công!');
                        table_proCate.ajax.reload(); // Tải lại DataTables sau khi cập nhật
                    } else {
                        alert('Lỗi cập nhật: ' + response.data);
                        console.error(response);
                    }
                },
                error: function (error) {
                    alert('Lỗi AJAX: ' + error.message);
                    console.error(error);
                }
            });
        });

    });
</script>
    <div id="edit-cate-modal" >
        <div class="modal-content">
            <button class="close-modal" aria-label="Close">&times;</button>
            <h2><?php _e('Edit Product category', LANG_ZONE)  ?></h2>
            <div class="modal-header">
                <h4><strong><?php _e('Name', LANG_ZONE)  ?>:</strong> <span id="modal-cate-name"></span></h4>
                <div class="cate-thumbnail-box">
                    <button class="clear-thumb" aria-label="Clear">&times;</button>
                    <img src="" class="thumbnail-image preview-img">
                    <label class=" button button-primary" role="button" for="procate-input"><?php _e('Upload New', LANG_ZONE)  ?></label>
                    <input type="file" class="form-control d-none" name="procate-image" id="procate-input">
                    <button type="button" class="button button-secondary" id="select-media-button"><?php _e('Select from Media', LANG_ZONE) ?></button>
                    <input type="hidden" id="procate-thumbnail-id" name="procate_thumbnail_id" value="">
                </div>
            </div>
            <form id="cate-edit-form">
				<?php
				// Tạo vùng editor với `wp_editor`
				wp_editor('', 'cate_content_editor', [
					'textarea_name' => 'cate_description',
					'textarea_rows' => 20,
					'editor_class' => 'chapter-editor',
					'wpautop' => true, // Kích hoạt tự động ngắt dòng
					'media_buttons' => false, // Không cần nút Add Media
					'quicktags' => false,        // Kích hoạt thẻ QuickTags
					'tinymce' => [
						'height' => 300,                  // Chiều cao
						'toolbar1' => 'bold italic | alignleft aligncenter alignright', // Thanh công cụ
					],
				]);
				?>
            </form>
            <div class="modal-footer">
                <button id="save-chapter-content" class="button button-primary"><?php _e('Save', LANG_ZONE); ?></button>
                <button id="close-modal" class="button button-cancel"><?php _e('Close', LANG_ZONE); ?></button>
            </div>

        </div>
    </div>

    <style>
        .button, button, input, select {
            border-radius: 5px!important;
        }
        .dt-input {
            line-height: 1em!important;
        }
        .dt-input[name="chapters-table_length"]{
            padding-right: 1.5em!important;

        }
        #filter-form{
            padding-bottom: 30px;
            position: relative;
            max-width: 70%;
        }
        .suggestions-list {
            position: absolute;
            z-index: 1000;
            list-style: none;
            background: #fff;
            /*border: 1px solid #ccc;*/
            max-height: 150px;
            overflow-y: auto;
            margin: 0;
            /*padding: 5px;*/
            width: calc(100% - 2px);
            box-shadow: 0px 4px 6px;
            max-width: 500px;
        }
        .suggestions-list li {
            padding: 8px;
            cursor: pointer;
        }
        .suggestions-list li:hover {
            background: #f0f0f0;
        }
        .inline-input {
            width: 100%;
        }
        #edit-cate-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border: 1px solid #ccc;
            padding: 20px;
            z-index: 1000;
            display: none;
            box-shadow:10px 15px 100px rgba(0, 0, 0, 0.9);
            height: 80vh; /* Chiều cao modal chiếm 80% chiều cao viewport */
            max-height: 90vh; /* Đảm bảo không vượt quá chiều cao viewport */
        }

        #edit-cate-modal .modal-content {
            width: 800px;
            max-width: 100%;
            height: 100%;
        }
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        .close-modal {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            cursor: pointer;
        }

        .close-modal:hover {
            color: #000;
            text-shadow: 0 0 3px #ccc;
        }
        .modal-header {
            margin-bottom: 20px;
            font-size: 16px;
            display: flex;
            justify-content: space-between;
        }
        .modal-header img.preview-img {
            max-width: 100px;
            padding: 5px;
            border:1px solid #ddddff;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .modal-header .cate-thumbnail-box {
            display: flex;
            flex-direction: column;
            text-align: center;
            position: relative;
        }
        .modal-header .cate-thumbnail-box .clear-thumb {
            position: absolute;
            top:-5px;
            right: 0;
            background: none;
            border: none;
            font-size: 24px;
            font-weight: bold;
            color: #d40a0a;
            cursor: pointer;
            line-height: 1em;
            padding: 0;
            margin: 0;
            display: none;
        }
        .modal-header .cate-thumbnail-box input{
            display: none;
        }
        .modal-header p {
            margin: 0 0 10px;
            line-height: 1.5;
        }

        #modal-book-title,
        #modal-chapter-title {
            font-weight: bold;
            color: #555;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 20px;
            margin-top: 15px;
        }
        .book-title-filter {
            padding: 5px 0px;
            cursor: pointer;
        }
        .book-title-filter:hover{
            font-weight: bold;
        }
        .editable .editable-field {
            display: block;
            min-width: 50px;
            max-width: 100%;
            min-height: 1em;
            cursor: pointer;

        }
        .editable .editable-field:hover{
            text-decoration: underline;
        }
    </style>
</div>

