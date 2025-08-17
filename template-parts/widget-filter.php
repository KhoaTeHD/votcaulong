<?php
global $product_attr_name;
$filter_name = $args['name'];
$filter_data = $args['data'];
$field_name = $args['field_name'];
$unique_id = uniqid('widget-');
$has_search = ( $args['has_search'] ?? '' );
?>
<div class="widget-box my-3">
	<div class="widget-head">
		<div class="widget-title"><?php echo $filter_name  ?></div>
		<div class="widget-control">
			<button class="bg-white border-0"  type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $unique_id  ?>" aria-expanded="true" aria-controls="<?php echo $unique_id  ?>">
				<i class="bi bi-chevron-up"></i>
			</button>
		</div>
	</div>
	<div class="widget-body collapse show" id="<?php echo $unique_id  ?>">
		<div class="filter-cbox <?php echo ($has_search?'filterable-list':'')  ?>">
            <?php
            if ($has_search){
                ?>
                <div class="input-group flex-nowrap mb-2">
                    <input type="text" class="search-icon filter-input form-control form-control-sm"  placeholder="<?php _e('Quick search',LANG_ZONE)  ?>" data-debounce="500" data-case-sensitive="false" data-empty-message="<?php _e('No results found!',LANG_ZONE)  ?>" >
                    <span class="input-group-text"><i class="bi bi-search"></i> </span>
                </div>
            <?php
            }
            ?>
			<ul>
				<?php
                if (isset($filter_data) && $filter_data) {
                    foreach ($filter_data as $item) {

                        if ($field_name=='filter_price'){
                            $value = $item['min'].'_'.$item['max'];
                            $label = priceFormater($item['min']).' - '.priceFormater($item['max']);
                        }else{
                            if (is_array($item)) {
	                            $value = $item['id'];
	                            $label = $item['name'];
                            }else{
	                            $value = ($item);
	                            $label = $item;
                            }

                        }
	                    if ($value=='') continue;
                        $item_id = $field_name.'_'.$value;
                        if(FAKE_DATA){
	                        $value = sanitize_title($value);
                        }
                        ?>
                        <li <?php echo ($has_search ?'class="filter-item"': '') ?>>
                            <input type="checkbox" name="<?php echo $field_name ?>" id="<?php echo $item_id  ?>" value="<?php echo $value  ?>" class="filter-checkbox">
                            <label for="<?php echo $item_id  ?>"><?php echo $label  ?></label>
                        </li>
                        <?php
                    }
                }
				?>


			</ul>
		</div>
	</div>
</div>
