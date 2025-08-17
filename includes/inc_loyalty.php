<?php
add_action('admin_menu', function() {
	add_submenu_page(
		'site-configs',
		'Loyalty Color',
		'Loyalty Color',
		'manage_options',
		'loyalty-rank-color',
		'lrcc_admin_page'
	);
}, 99);

// ======= PRESET MÀU SẴN =======
function lrcc_get_color_presets() {
	return [
		'luxury_diamond' => [
			'name'   => 'Diamond',
			'colors' => [
				'color' => '#208acb',
				'bar'   => '#7de2fc',
				'm1'    => '#eaf6ff',
				'm2'    => '#fafdff',
				'm3'    => '#b8eaff',
				'border'=> '#b8eaff'
			]
		],'luxury_gold' => [
			'name'   => 'Luxury Gold',
			'colors' => [
				'color'  => '#fff',
				'bar'    => '#fcb859',
				'm1'     => '#f2c14b',
				'm2'     => '#ffecb3',
				'm3'     => '#d99f1a',
				'border' => '#f2c14b',
			]
		],
		'classic_silver' => [
			'name'   => 'Classic Silver',
			'colors' => [
				'color'  => '#333',
				'bar'    => '#babec5',
				'm1'     => '#d9d9d9',
				'm2'     => '#f3f3f3',
				'm3'     => '#babec5',
				'border' => '#babec5',
			]
		],
		'classic_bronze' => [
			'name'   => 'Bronze',
			'colors' => [
				'color' => '#fff',
				'bar'   => '#e2a76f',
				'm1'    => '#cd7f32',
				'm2'    => '#e2a76f',
				'm3'    => '#a97142',
				'border'=> '#e2a76f'
			]
		],
        'modern_green' => [
			'name'   => 'Modern Green',
			'colors' => [
				'color'  => '#3a3a3a',
				'bar'    => '#92e94e',
				'm1'     => '#d7eecb',
				'm2'     => '#a4dc8a',
				'm3'     => '#6eb960',
				'border' => '#e6e6e6',
			]
		],

		// Thêm preset khác nếu muốn
	];
}

// Giá trị mặc định (nếu rank chưa set màu)
function lrcc_get_default_colors() {
	return [
		'member' => [
			'color' => '#3a3a3a',
			'bar'   => '#92e94e',
			'm1'    => '#d7eecb',
			'm2'    => '#d7eecb',
			'm3'    => '#d7eecb',
			'border'=> '#e6e6e6'
		],
		'vang' => [
			'color' => '#fff',
			'bar'   => '#fcb859',
			'm1'    => '#f2c14b',
			'm2'    => '#ffecb3',
			'm3'    => '#d99f1a',
			'border'=> '#f2c14b'
		],
		'bac' => [
			'color' => '#333',
			'bar'   => '#babec5',
			'm1'    => '#d9d9d9',
			'm2'    => '#f3f3f3',
			'm3'    => '#babec5',
			'border'=> '#babec5'
		],
		'dong' => [
			'color' => '#fff',
			'bar'   => '#e2a76f',
			'm1'    => '#cd7f32',
			'm2'    => '#e2a76f',
			'm3'    => '#a97142',
			'border'=> '#e2a76f'
		],
		'kim-cuong' => [
			'color' => '#208acb',
			'bar'   => '#7de2fc',
			'm1'    => '#eaf6ff',
			'm2'    => '#fafdff',
			'm3'    => '#b8eaff',
			'border'=> '#b8eaff'
		]
	];
}

function lrcc_admin_page() {

	$defaults = lrcc_get_default_colors();
	$ranks = lrcc_get_ranks_from_api();
	$colors = get_option('loyalty_rank_colors');
	if (!is_array($colors)) $colors = [];
	$selected = isset($_GET['rank']) ? sanitize_text_field($_GET['rank']) : $ranks[0]['key'];
	$rank_colors = isset($colors[$selected]) ? wp_parse_args($colors[$selected], $defaults[$selected] ?? $defaults['member']) : ($defaults[$selected] ?? $defaults['member']);
	$presets = lrcc_get_color_presets();
	?>
    <div class="wrap">
        <h1 style="margin-bottom: 20px;">Loyalty Rank Color Settings</h1>
        <form method="get" style="margin-bottom:20px;">
            <input type="hidden" name="page" value="loyalty-rank-color">
            <label><strong>Chọn hạng muốn chỉnh:</strong></label>
            <select name="rank" onchange="this.form.submit()">
				<?php foreach($ranks as $rk): ?>
                    <option value="<?php echo esc_attr($rk['key']); ?>" <?php selected($selected, $rk['key']); ?>>
						<?php echo esc_html($rk['name']); ?>
                    </option>
				<?php endforeach; ?>
            </select>
        </form>
        <form method="post" id="lrcc-form">
			<?php wp_nonce_field('lrcc_save','lrcc_nonce'); ?>
            <input type="hidden" name="rank" value="<?php echo esc_attr($selected); ?>">
            <table class="form-table color-table">
                <tr>
                    <th>Theme màu</th>
                    <td colspan="5">
                        <select name="preset_theme" id="preset_theme" style="min-width:180px;" onchange="lrcc_applyPreset(this.value)">
                            <option value="">-- Chọn theme màu sẵn --</option>
							<?php foreach ($presets as $key => $preset): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($preset['name']); ?></option>
							<?php endforeach; ?>
                        </select>
                        <span id="preset_preview" style="margin-left:12px;"></span>
                    </td>
                </tr>
                <tr>
                    <th>Gradient Colors</th>
                    <th>Text Color</th>
                    <th>Progress Bar</th>
                    <th>Border</th>
                    <th>Preview</th>
<!--                    <th>Mặc định</th>-->
                </tr>
                <tr>
                    <td>
                        <div style="display:flex;gap:8px;flex-direction: column;">
                            <div style="display: flex;gap:8px;align-items: center;">
                                <label style="font-size:12px;opacity:.7;">Màu 1</label><br>
                                <input type="color" class="lrcc-c" name="colors[m1]" value="<?php echo esc_attr($rank_colors['m1']); ?>">
                            </div>
                            <div style="display: flex;gap:8px;align-items: center;">
                                <label style="font-size:12px;opacity:.7;">Màu 2</label><br>
                                <input type="color" class="lrcc-c" name="colors[m2]" value="<?php echo esc_attr($rank_colors['m2']); ?>">
                            </div>
                            <div style="display: flex;gap:8px;align-items: center;">
                                <label style="font-size:12px;opacity:.7;">Màu 3</label><br>
                                <input type="color" class="lrcc-c" name="colors[m3]" value="<?php echo esc_attr($rank_colors['m3']); ?>">
                            </div>
                        </div>
                    </td>
                    <td><input type="color" class="lrcc-c" name="colors[color]" value="<?php echo esc_attr($rank_colors['color']); ?>"></td>
                    <td><input type="color" class="lrcc-c" name="colors[bar]" value="<?php echo esc_attr($rank_colors['bar']); ?>"></td>
                    <td><input type="color" class="lrcc-c" name="colors[border]" value="<?php echo esc_attr($rank_colors['border']); ?>"></td>
                    <td>
                        <div class="lrcc-preview" style="max-width: 370px;">
                        </div>
                    </td>
                    <!--<td>
                        <label>
                            <input type="checkbox" name="reset_default" value="1" class="lrcc-reset">
                            Đặt về mặc định
                        </label>
                    </td>-->
                </tr>
            </table>
            <p><button class="button button-primary" type="submit">Lưu thay đổi</button></p>
            <div style="opacity:.75;font-size:13px;">
                * Chọn 3 màu để tự động tạo gradient nền cho hạng.<br>
                * Chọn theme màu preset để tự động đổ màu, chỉnh lại từng màu nếu muốn.
            </div>
        </form>
		<?php
		$text_colors = get_option('loyalty_rank_text_colors');
		$default_text = lrcc_get_default_text_colors();
		$text_colors = wp_parse_args((array)$text_colors, $default_text);
		?>
        <hr>
        <h2>Cài đặt màu chữ mặc định</h2>
        <form method="post" style="margin-bottom:28px;">
			<?php wp_nonce_field('lrcc_text_save','lrcc_text_nonce'); ?>
            <table class="form-table" style="max-width:460px;">
                <tr>
                    <th>Text Chính (Duy trì hạng)</th>
                    <td><input type="color" name="text_colors[progress_label]" value="<?php echo esc_attr($text_colors['progress_label']); ?>"></td>
                </tr>
                <tr>
                    <th style="width:180px">Text phụ</th>
                    <td><input type="color" name="text_colors[main]" value="<?php echo esc_attr($text_colors['main']); ?>"></td>
                </tr>

                <tr>
                    <th>Text (mục tiêu đã đạt)</th>
                    <td><input type="color" name="text_colors[progress_number]" value="<?php echo esc_attr($text_colors['progress_number']); ?>"></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <button class="button button-primary" type="submit">Lưu màu text</button>
                        <button class="button" type="submit" name="reset_text_color" value="1">Về mặc định</button>
                    </td>
                </tr>
            </table>
        </form>
        <hr>
    </div>
    <script>
        window.lrcc_presets = <?php echo json_encode($presets); ?>;
        window.lrcc_text_colors = <?php echo json_encode($text_colors); ?>;
        window.lrcc_rank_label = <?php
		$rank_label_map = [];
		foreach($ranks as $rk) $rank_label_map[$rk['key']] = $rk['name'];
		echo json_encode($rank_label_map);
		?>;

        function lrcc_applyPreset(presetKey) {
            if (!presetKey || !window.lrcc_presets[presetKey]) return;
            var colors = window.lrcc_presets[presetKey]['colors'];
            for (var k in colors) {
                var input = document.querySelector('input[name="colors['+k+']"]');
                if (input) input.value = colors[k];
            }
            updateAllPreviews();
            var prev = document.getElementById('preset_preview');
            if (prev) {
                prev.innerHTML = '<span style="display:inline-block;width:50px;height:20px;border-radius:6px;vertical-align:middle;box-shadow:0 2px 6px #0002;background:linear-gradient(150deg,'+colors.m1+' 0%,'+colors.m2+' 50%,'+colors.m3+' 100%)"></span>';
            }
        }

        function renderCardPreview(form) {
            var m1 = form.querySelector('input[name="colors[m1]"]').value;
            var m2 = form.querySelector('input[name="colors[m2]"]').value;
            var m3 = form.querySelector('input[name="colors[m3]"]').value;
            var color = form.querySelector('input[name="colors[color]"]').value;
            var bar = form.querySelector('input[name="colors[bar]"]').value;
            var border = form.querySelector('input[name="colors[border]"]').value;
            var selected = form.querySelector('input[name="rank"]') ? form.querySelector('input[name="rank"]').value : '<?php echo $ranks[0]['key']; ?>';
            var txt_main = window.lrcc_text_colors?.main || '#5d5f63';
            var txt_label = window.lrcc_text_colors?.progress_label || '#dc3545';
            var txt_num = window.lrcc_text_colors?.progress_number || '#CF432C';
            var rank_name = window.lrcc_rank_label[selected] || selected;
            var html = `
    <div class="loyalty-card ${selected}" style="max-width:370px;border-radius:18px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,0.13);background:linear-gradient(150deg,${m1} 0%,${m2} 50%,${m3} 100%);color:${color};border:2.5px solid ${border};margin-bottom:10px;">
        <div class="loyalty-card-header" style="padding:18px 24px 0 24px;display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
                <div class="loyalty-card-title" style="font-size:22px;font-weight:bold;letter-spacing:1px;">${rank_name}</div>
                <div class="loyalty-card-user" style="padding:0 24px 15px 0;font-size:15px;opacity:0.93;font-weight:500;">Tên khách</div>
            </div>
            <div class="loyalty-card-benefit" style="font-size:14px;opacity:0.95;font-weight:600;"><a href="#">Ưu đãi mỗi thứ hạng ›</a></div>
        </div>
        <div class="loyalty-card-main" style="background:#fff;color:${txt_main};border-radius:14px;margin:0 16px;box-shadow:0 2px 8px #f4a33813;padding:18px 16px 8px 16px;margin-bottom:14px;position:relative;font-weight:500;">
            <div class="loyalty-card-progress-label" style="color:${txt_label};font-size:14px;font-weight:600;margin-bottom:9px;">Duy trì thứ hạng thẻ</div>
            <div class="loyalty-card-progress" style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:16px;">
                <div class="loyalty-card-progress-item" style="width:46%;">
                    <div>Đơn hàng</div>
                    <div><span style="font-size:15px;font-weight:700;color:${txt_num};">2</span>/20</div>
                    <div class="loyalty-card-progress-bar-bg" style="width:100%;height:7px;background-color:#e0e0e0;border-radius:6px;margin-top:4px;overflow:hidden;">
                        <div class="loyalty-card-progress-bar" style="width:40%;height:100%;background:${bar};border-radius:6px;"></div>
                    </div>
                </div>
                <div class="loyalty-card-progress-item" style="width:46%;">
                    <div>Chi tiêu</div>
                    <div><span style="font-size:15px;font-weight:700;color:${txt_num};">5tr</span>/20tr</div>
                    <div class="loyalty-card-progress-bar-bg" style="width:100%;height:7px;background-color:#e0e0e0;border-radius:6px;margin-top:4px;overflow:hidden;">
                        <div class="loyalty-card-progress-bar" style="width:15%;height:100%;background:${bar};border-radius:6px;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="loyalty-card-footer" style="font-size:13px;color:${txt_main};padding:2px 22px 18px 22px;display:flex;justify-content:space-between;align-items:center;">
            <div>Thứ hạng sẽ được cập nhật lại sau 31.12.2025.</div>
            <a href="#" style="color:${txt_main};font-weight:500;font-size:13px;">Chi Tiết ></a>
        </div>
    </div>`;
            form.querySelector('.lrcc-preview').innerHTML = html;
        }

        function updateAllPreviews() {
            document.querySelectorAll('form#lrcc-form').forEach(renderCardPreview);
        }

        document.addEventListener('DOMContentLoaded',function(){
            document.querySelectorAll('.form-table input[type="color"], .form-table input[type="text"]').forEach(function(input){
                input.addEventListener('input', updateAllPreviews);
            });
            var textColorForm = document.querySelector('form[action=""] input[name^="text_colors"]');
            if(textColorForm) {
                document.querySelector('form[action=""]').addEventListener('submit', function(){
                    setTimeout(function(){ location.reload(); }, 150);
                });
            }
            updateAllPreviews();
        });
    </script>
    <style>
        .lrcc-preview .loyalty-card {margin-top: 0;}
        .lrcc-preview a { text-decoration: none;color: #000;}
        .lrcc-c { width:40px; height:40px; padding:0; border:none;cursor: pointer; }
        .form-table label { display:block; text-align:center; }
        .color-table th {
            width: auto;
        }
    </style>
	<?php
}

// ====== XỬ LÝ LƯU DỮ LIỆU =======
add_action('admin_init', function(){
	if (
		isset($_POST['colors']) && isset($_POST['rank']) &&
		check_admin_referer('lrcc_save','lrcc_nonce')
	) {
		$defaults = lrcc_get_default_colors();
		$colors = get_option('loyalty_rank_colors');
		if (!is_array($colors)) $colors = [];
		$rank = sanitize_text_field($_POST['rank']);
		if (!empty($_POST['reset_default']) && !empty($defaults[$rank])) {
			$colors[$rank] = $defaults[$rank];
		} else {
			foreach(['m1','m2','m3','color','bar','border'] as $field){
				if(isset($_POST['colors'][$field])) {
					$colors[$rank][$field] = sanitize_text_field($_POST['colors'][$field]);
				}
			}
		}
		update_option('loyalty_rank_colors', $colors);
		add_action('admin_notices', function(){
			echo '<div class="notice notice-success is-dismissible"><p>Lưu màu thành công!</p></div>';
		});
	}
});
add_action('admin_init', function(){
	// Lưu màu text mặc định
	if (isset($_POST['text_colors']) && check_admin_referer('lrcc_text_save','lrcc_text_nonce')) {
		if (!empty($_POST['reset_text_color'])) {
			update_option('loyalty_rank_text_colors', lrcc_get_default_text_colors());
		} else {
			$save = [];
			foreach(['main','progress_label','progress_number'] as $field){
				$save[$field] = sanitize_text_field($_POST['text_colors'][$field]);
			}
			update_option('loyalty_rank_text_colors', $save);
		}
		add_action('admin_notices', function(){
			echo '<div class="notice notice-success is-dismissible"><p>Lưu màu chữ thành công!</p></div>';
		});
	}
});
function lrcc_get_default_text_colors() {
	return [
		'main'        => '#2A2E2EFF',     // var(--theme-gray2) hoặc tuỳ chỉnh
		'progress_label' => '#ff0000',  // var(--bs-danger)
		'progress_number' => '#CF432C'
	];
}
function lrcc_get_ranks_from_api() {
	$erp = new ERP_API_Client();

//    $rules = [
//		[
//			"name" => "Ưu đãi tháng 8",
//			"collection_rules" => [
//				[
//					"tier_name" => "Đồng",
//					"min_spent" => 100000.0,
//					"custom_minimum_total_orders" => 1,
//					"custom_rate" => 1.0,
//					"collection_factor" => 0.0,
//				],
//				[
//					"tier_name" => "Bạc",
//					"min_spent" => 500000.0,
//					"custom_minimum_total_orders" => 10,
//					"custom_rate" => 1.0,
//					"collection_factor" => 0.0,
//				],
//				[
//					"tier_name" => "Vàng",
//					"min_spent" => 1000000.0,
//					"custom_minimum_total_orders" => 50,
//					"custom_rate" => 2.0,
//					"collection_factor" => 0.0,
//				],
//				[
//					"tier_name" => "Kim Cương",
//					"min_spent" => 5000000.0,
//					"custom_minimum_total_orders" => 100,
//					"custom_rate" => 2.0,
//					"collection_factor" => 0.0,
//				],
//			],
//		],
//		[
//			"name" => "Ưu đãi tháng 7",
//			"collection_rules" => [
//				[
//					"tier_name" => "Đồng",
//					"min_spent" => 1000000.0,
//					"custom_minimum_total_orders" => 2,
//					"custom_rate" => 1.0,
//					"collection_factor" => 10000.0,
//				],
//				[
//					"tier_name" => "Bạc",
//					"min_spent" => 5000000.0,
//					"custom_minimum_total_orders" => 5,
//					"custom_rate" => 1.0,
//					"collection_factor" => 10000.0,
//				],
//				[
//					"tier_name" => "Vàng",
//					"min_spent" => 10000000.0,
//					"custom_minimum_total_orders" => 10,
//					"custom_rate" => 1.0,
//					"collection_factor" => 10000.0,
//				],
//			],
//		],
//	];
    $rules = $erp->get_all_loyalty_rank();
    $ranks = [];
    foreach ($rules as $rule){
        if (is_array($rule) && isset($rule['collection_rules'])){
            foreach ($rule['collection_rules'] as $rank_rule) {
                $key = sanitize_title($rank_rule['tier_name']);
                $name =  $rank_rule['tier_name'];
	            $ranks[$key] = ['key' =>$key, 'name' => $name];
            }
        }

    }
    return $ranks;
	/*return [
		[ 'key' => 'member',     'name' => 'Thành viên' ],
		[ 'key' => 'dong',       'name' => 'Đồng' ],
		[ 'key' => 'bac',        'name' => 'Bạc' ],
		[ 'key' => 'vang',       'name' => 'Vàng' ],
		[ 'key' => 'kim-cuong',  'name' => 'Kim Cương' ]
	];*/
}

// In CSS ra front-end
add_action('wp_head', function(){
	$colors = get_option('loyalty_rank_colors');
	if (!$colors) return;
	?>
    <style id="lrcc-custom-css">
        <?php foreach ($colors as $rank => $color): ?>
        .loyalty-card.<?php echo esc_attr($rank); ?> {
            background: linear-gradient(150deg, <?php
            echo esc_attr($color['m1']); ?> 0%, <?php
            echo esc_attr($color['m2']); ?> 50%, <?php
            echo esc_attr($color['m3']); ?> 100%) !important;
            color: <?php echo esc_attr($color['color']); ?> !important;
            border: 2px solid <?php echo esc_attr($color['border']); ?> !important;
        }
        .loyalty-card.<?php echo esc_attr($rank); ?> .loyalty-card-progress-bar {
            background: <?php echo esc_attr($color['bar']); ?> !important;
        }
        <?php endforeach; ?>
    </style>
	<?php
});
add_action('wp_head', function(){
	$text = get_option('loyalty_rank_text_colors');
	$text = wp_parse_args((array)$text, lrcc_get_default_text_colors());
	?>
    <style id="lrcc-custom-text-css">
        .loyalty-card-main {
            color: <?php echo esc_attr($text['main']); ?> !important;
        }
        .loyalty-card-progress-label {
            color: <?php echo esc_attr($text['progress_label']); ?> !important;
        }
        .loyalty-card-progress-item span {
            color: <?php echo esc_attr($text['progress_number']); ?> !important;
        }
    </style>
	<?php
});
