<?php
/**
 * Template Name: Store_system
 **/
get_header();
?>
<div class="container">
    <div class="section-header">
            <h3 class="title"><?php the_title();  ?></h3>
        </div>  
<div class="store-list-container post-content bg-white p-3">
    <div class="row">
        <div class="col-12 col-md-6">
            <button id="findNearestStoreBtn" class="btn btn-primary mb-3">Tìm cửa hàng gần nhất</button>
            <div id="geolocationMessage" class="alert alert-info" style="display: none;"></div>

            <div class="store-all-list ">
            <ul>
                <?php
                $select_option ='';
                $args = array(
                    'post_type'      => 'store_system',
                    'posts_per_page' => -1,  // Lấy tất cả cửa hàng
                    'orderby'        => 'date',
                    'order'          => 'ASC'
                );
                $query = new WP_Query($args);

                if ($query->have_posts()) :
                    while ($query->have_posts()) : $query->the_post();
                        $store_address = get_post_meta(get_the_ID(), 'store_address', true);
                        $store_phone   = get_post_meta(get_the_ID(), 'store_phone', true);
//                        $store_map     = get_post_meta(get_the_ID(), 'store_google_map', true);
	                    $html = get_post_meta( get_the_ID(), 'store_google_map', true );

	                    $allowed = wp_kses_allowed_html( 'post' );
	                    $allowed['iframe'] = array(
		                    'src'             => true,
		                    'width'           => true,
		                    'height'          => true,
		                    'style'           => true,
		                    'frameborder'     => true,
		                    'allow'           => true,
		                    'allowfullscreen' => true,
		                    'loading'         => true,
		                    'referrerpolicy'  => true,
	                    );

	                    $store_map = wp_kses( $html, $allowed );
                        ?>
                        <?php
                        $store_lat_long = get_post_meta(get_the_ID(), 'store_lat_long', true);
                        ?>
                        <li
                            data-storeid="<?php the_ID(); ?>"
                            data-mapembed="<?php echo esc_attr($store_map); ?>"
                            data-permalink="<?php echo esc_attr(get_permalink()); ?>"
                            data-storename="<?php echo esc_attr(get_the_title()); ?>"
                            data-latlong="<?php echo esc_attr($store_lat_long); ?>">
                            <h6><a href="<?php echo esc_url(get_permalink()); ?>" ><i class="bi bi-box-arrow-up-right"></i> <?php the_title(); ?></a></h6>
                            <i class="bi bi-geo-alt-fill"></i> <?php echo esc_html($store_address); ?><br>
                            <i class="bi bi-telephone-fill"></i> <?php echo esc_html($store_phone); ?>
                        </li>
                        <?php
                        $select_option .= '<option value="'.get_the_ID().'">'.get_the_title().'</option>';
                    endwhile;
                    wp_reset_postdata();
                else :
                    echo "<p>Không có cửa hàng nào.</p>";
                endif;
                ?>
            </ul>

            </div>
	        <?php
	        if ($select_option) {
		        echo '<select name="store_address_mobile" id="store_address_mobile" class="form-control d-none mb-3">';
		        echo $select_option;
		        echo '</select>';
	        }
	        ?>
        </div>

        <div class="store-map col-12 col-md-6">
            <h2>Bản đồ</h2>

            <div id="googleMapEmbed" style="border:0;width: 100%;height: 400px; position: relative;">
                <iframe id="googleMapFrame" style="width: 100%; height: 100%; border: 0; position: absolute; top: 0; left: 0; opacity: 0; transition: opacity 0.3s ease-in-out;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                <div class="map-loading-spinner" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center;text-align: center; background: rgba(255,255,255,0.8); z-index: 1;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải bản đồ...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        let initialMap = '';
        let activeStoreElement = null;
        const mapTitleElement = $('.store-map h2');
        const baseMapTitle = 'Bản đồ';

        function updateMapAndTitle(mapHtml, storeName) {
            const mapEmbedContainer = $('#googleMapEmbed');
            const mapFrame = $('#googleMapFrame');
            const loadingSpinner = mapEmbedContainer.find('.map-loading-spinner');

            mapFrame.css('opacity', '0');
            loadingSpinner.show();

            const tempDiv = $('<div>').html(mapHtml);
            const newSrc = tempDiv.find('iframe').attr('src');

            if (newSrc) {
                mapFrame.attr('src', newSrc);
                mapFrame.off('load').on('load', function() {
                    $(this).css('opacity', '1');
                    loadingSpinner.hide();
                });
            } else {
                mapFrame.attr('src', '');
                loadingSpinner.hide();
            }

            mapTitleElement.text(baseMapTitle + (storeName ? ': ' + storeName : ''));
        }

        function changeMap(mapHtml, storeName) {
            updateMapAndTitle(mapHtml, storeName);
        }

        function resetMap() {
            if (activeStoreElement) {
                updateMapAndTitle($(activeStoreElement).attr('data-mapembed'), $(activeStoreElement).attr('data-storename'));
            } else {
                updateMapAndTitle(initialMap, ''); // Revert to base title if no active store
            }
        }

        // Haversine formula to calculate distance between two lat/long points
        function getDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radius of Earth in kilometers
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = 
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const distance = R * c;
            return distance; // Distance in kilometers
        }

        function findNearestStore(userLat, userLon, storeItems) {
            let nearestStore = null;
            let minDistance = Infinity;

            storeItems.each(function() {
                const storeLatLong = $(this).data('latlong');
                if (storeLatLong) {
                    const [storeLat, storeLon] = storeLatLong.split(',').map(Number);
                    if (!isNaN(storeLat) && !isNaN(storeLon)) {
                        const distance = getDistance(userLat, userLon, storeLat, storeLon);
                        if (distance < minDistance) {
                            minDistance = distance;
                            nearestStore = this;
                        }
                    }
                }
            });
            return nearestStore;
        }

        const storeListItems = $(".store-all-list ul li");
        const storeListUl = $(".store-all-list ul");
        const geolocationMessage = $("#geolocationMessage");

        // Initial load: set the first store as active by default
        if (storeListItems.length > 0) {
            activeStoreElement = storeListItems[0];
            $(activeStoreElement).addClass("active");
            initialMap = $(activeStoreElement).attr("data-mapembed");
            updateMapAndTitle(initialMap, $(activeStoreElement).attr("data-storename"));
        }

        $("#findNearestStoreBtn").on("click", function() {
            geolocationMessage.hide().removeClass("alert-danger alert-success").text("");
            if (navigator.geolocation) {
                geolocationMessage.text("Đang tìm vị trí của bạn...").show();
                navigator.geolocation.getCurrentPosition(function(position) {
                    geolocationMessage.hide();
                    const userLat = position.coords.latitude;
                    const userLon = position.coords.longitude;

                    const storesWithDistance = [];
                    storeListItems.each(function() {
                        const storeLatLong = $(this).data("latlong");
                        if (storeLatLong) {
                            const [storeLat, storeLon] = storeLatLong.split(",").map(Number);
                            if (!isNaN(storeLat) && !isNaN(storeLon)) {
                                const distance = getDistance(userLat, userLon, storeLat, storeLon);
                                storesWithDistance.push({ element: this, distance: distance });
                            } else {
                                storesWithDistance.push({ element: this, distance: Infinity }); // Stores without valid lat/long go to end
                            }
                        } else {
                            storesWithDistance.push({ element: this, distance: Infinity }); // Stores without lat/long go to end
                        }
                    });

                    // Sort stores by distance
                    storesWithDistance.sort((a, b) => a.distance - b.distance);

                    // Re-append sorted items to the list
                    storeListUl.empty();
                    storesWithDistance.forEach(item => {
                        storeListUl.append($(item.element));
                    });

                    // Set the first (nearest) store as active
                    if (storesWithDistance.length > 0) {
                        if (activeStoreElement) {
                            $(activeStoreElement).removeClass("active");
                        }
                        activeStoreElement = storesWithDistance[0].element;
                        $(activeStoreElement).addClass("active");
                        initialMap = $(activeStoreElement).attr("data-mapembed"); // Update initialMap to nearest store's map
                        updateMapAndTitle($(activeStoreElement).attr("data-mapembed"), $(activeStoreElement).attr("data-storename"));
                        let storeID = $(activeStoreElement).attr("data-storeid");
                        if (storesWithDistance[0].distance !== Infinity) {
                            $('#store_address_mobile').val(storeID).change();
                            geolocationMessage.text(`Cửa hàng gần nhất: ${$(activeStoreElement).data("storename")}`).addClass("alert-success").show();
                        } else {
                            geolocationMessage.text("Không tìm thấy cửa hàng với thông tin vị trí hợp lệ.").addClass("alert-warning").show();
                        }

                    } else {
                        geolocationMessage.text("Không có cửa hàng nào để sắp xếp.").addClass("alert-info").show();
                    }

                }, function(error) {
                    let errorMessage = 'Không thể lấy vị trí của bạn.';
                    if (error.code === error.PERMISSION_DENIED) {
                        errorMessage += ' Vui lòng cho phép truy cập vị trí.';
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        errorMessage += ' Thông tin vị trí không khả dụng.';
                    } else if (error.code === error.TIMEOUT) {
                        errorMessage += ' Hết thời gian chờ lấy vị trí.';
                    }
                    geolocationMessage.text(errorMessage).addClass("alert-danger").show();
                });
            } else {
                geolocationMessage.text("Trình duyệt của bạn không hỗ trợ định vị.").addClass("alert-danger").show();
            }
        });

        storeListUl.on("click", "li", function() {
            if (activeStoreElement) {
                $(activeStoreElement).removeClass("active");
            }
            $(this).addClass("active");
            activeStoreElement = this;
            updateMapAndTitle($(this).attr("data-mapembed"), $(this).attr("data-storename"));
        });

        storeListUl.on("mouseover", "li", function() {
            changeMap($(this).attr("data-mapembed"), $(this).attr("data-storename"));
        });

        storeListUl.on("mouseout", "li", function() {
            resetMap();
        });
        $('#store_address_mobile').on('change', function(){
            let storeID = $(this).val();
            storeListItems.each(function(){
                let thisStore = $(this);
                if (thisStore.data("storeid")==storeID){
                    thisStore.trigger('click');
                }
            })
        })
    });
</script>
</div>
<?php
get_footer();
