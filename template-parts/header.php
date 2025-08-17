<header class="bg-dark-blue text-white">
	<div class="container py-2">
		<!-- Logo và Tìm kiếm -->
		<div class="row align-items-center ">
			<div class="col-4 order-md-1 col-md-2 ">
				<a href="<?php echo home_url();  ?>" class="d-block logo" id="site-logo">
					<img src="<?php echo get_field('site-logo','options');  ?>" alt="Logo" class="img-fluid">
				</a>

			</div>
			
			<div class="col-12 col-md-6 col-xxl-4 order-lg-2 order-3 d-flex d-lg-block ">
				<button class="me-3 fs-1 navbar-toggler text-light d-block-inline d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="Toggle navigation">
					<i class="bi bi-list"></i>
				</button>
				<div class="input-group search-form">
					<input type="text" class="form-control border-0" id="searchInput" placeholder="<?php _e('Search for brands, products, articles...', LANG_ZONE)  ?>">
                    
                    <button class="btn btn-secondary border-0 " type="button" id="searchBtn">
						<i class="bi bi-search"></i>
                        <span id="searchBtn-spinner" class="spinner-grow spinner-grow-sm position-absolute top-50 start-50 translate-middle" aria-hidden="true" style="display: none;" ></span>
					</button>

				</div>
				<!-- Container for search results -->
				<div id="searchResultsContainer" class="position-absolute bg-white border rounded shadow mt-1" style="z-index: 1050; display: none; max-height: 400px; overflow-y: auto;">
                    <!-- Search results will be loaded here -->
                </div>
                <?php
                $search_seggestions = get_field('search_suggestions','options');
                $kw = new Keyword_Manager();
                $top_keywords = $kw->get_top(4);

                if ($top_keywords && $search_seggestions) {
	                echo '<div class="quick-links mt-1 text-white small d-none d-lg-flex">';
	                foreach ($top_keywords as $row) {
		                echo '<a href="#" class="text-white me-2 quick-link" data-keyword="'.esc_attr($row['keyword']).'">'.esc_html($row['keyword']).'</a>';
	                }
	                echo '</div>';
                }
                ?>
				<!--<div class="quick-links mt-1 text-white small d-none d-lg-flex">
					<a href="#" class="text-white me-2">Vợt cầu lông Yonex</a>
					<a href="#" class="text-white me-2">Vợt cầu lông Lining</a>
					<a href="#" class="text-white me-2">Giày Yonex</a>
					<a href="#" class="text-white">Astrox 100zz</a>
				</div>-->
			</div>
			<div class="col-8 col-md-4 col-xxl-6  order-2 order-md-3 text-end">
				<ul class="icon-listbox big-icon icon-gradient ps-0 ps-lg-4 mb-0 mb-lg-2">
					<li>
						<a href="<?php echo get_field('store_system','options');  ?>">
							<i class="icon vcl-icon-location-store"></i>
							<span class="d-none d-xxl-block"><?php _e('Our<br>stores',LANG_ZONE)  ?></span>
						</a>
					</li>
					<li>
						<a href="<?php echo get_field('tracking_order','options');  ?>">
							<i class="icon vcl-icon-list-search"></i>
							<span class="d-none d-xxl-block"><?php _e('Order lookup<br>Warranty check',LANG_ZONE)  ?></span>
						</a>
					</li>
					<li>
						<?php
						if (is_user_logged_in() ){
							$text = __('Your<br>account', LANG_ZONE);
                            $url = get_field('user_account','options');
                        }else{
							$text = __('Register<br>Login', LANG_ZONE);
							$url = get_field('register_and_login','options');
                        }
						?>
						<a href="<?php echo $url;  ?>">
							<i class="icon vcl-icon-account"></i>
							<span class="d-none d-xxl-block"><?php echo $text  ?></span>
						</a>
					</li>
					<li>
						<a href="#">
							<i class="icon vcl-icon-bell"></i>

							<span class="d-none d-lg-block"></span>
						</a>
					</li>
					<li>
						<a class="open_cart_btn" data-bs-toggle="offcanvas" href="#shoppingCartCanvas" role="button" aria-controls="Shopping-cart">
							<i class="icon vcl-icon-cart"></i>
							<span class="d-none d-xxl-block"><?php _e('Your cart', LANG_ZONE)  ?><br>(<span id="cart-item-counter">0</span>) <?php _e('product(s)', LANG_ZONE)  ?></span>
						</a>
					</li>
				</ul>
				<ul class="icon-listbox text-light-blue call-center mb-0 d-none d-lg-flex">
					<li>
						<a href="tel:<?php the_field('header_hotline_1','options');  ?>">
							<i class="icon vcl-icon-phone-out"></i>
							<span><?php the_field('header_hotline_1_label', 'options');  ?></span>
						</a>
					</li>
					<li>
						<a href="tel:<?php the_field('header_hotline_2','options');  ?>">
							<i class="icon vcl-icon-phone-out"></i>
							<span><?php the_field('header_hotline_2_label', 'options');  ?></span>
						</a>
					</li>
					<li>
						<a href="tel:<?php the_field('header_hotline_3','options');  ?>">
							<i class="icon vcl-icon-phone-out"></i>
							<span><?php the_field('header_hotline_3_label', 'options');  ?></span>
						</a>
					</li>
				</ul>
			</div>
		</div>
	</div>


	<!-- Menu -->
	<nav class="header-nav d-none d-lg-block">
		<div class="container">
			<?php
			$args = array(
				'theme_location' => 'main-nav',
				'menu' => '',
				'menu_class' => 'main-menu',
				'container' => false,
				'add_li_class' => 'text-white text-uppercase',
				'add_a_class' => '',
				'walker' => new Custom_Walker_Nav_Menu(),
			);
			wp_nav_menu($args);
			?>
		</div>
	</nav>
	<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
		<!-- <div class="offcanvas-header"> -->
			<!-- <h5 class="offcanvas-title" id="offcanvasExampleLabel"></h5>
			<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button> -->
		<!-- </div> -->
		<div class="offcanvas-body">
			<nav>
				<div class="nav nav-tabs justify-content-center pt-3" id="nav-tab" role="tablist">
					<button class="nav-link fs-6 active" id="mainNav-tab" data-bs-toggle="tab" data-bs-target="#mobile-mainNav" type="button" role="tab" aria-controls="mobile-mainNav" aria-selected="true">Menu</button>
					<button class="nav-link fs-6" id="mobile-categoryNav-tab" data-bs-toggle="tab" data-bs-target="#mobile-categoryNav" type="button" role="tab" aria-controls="mobile-categoryNav" aria-selected="false">Danh mục sản phẩm</button>
				</div>
			</nav>
			<div class="tab-content" id="pills-tabContent">
				<div class="mobileNav tab-pane fade show active" id="mobile-mainNav" role="tabpanel">
					<?php 
					$args_mobi = array(
						'theme_location' => 'main-nav',
						'menu' => '',
						'menu_class' => 'main-menu',
						'container' => false,
						'add_li_class' => ' text-uppercase',
						'add_a_class' => '',
						'walker' => new Custom_Walker_Nav_Menu(),
					);
					wp_nav_menu($args_mobi);
					?>
				</div>
				<div class="categoryNav mobileNav tab-pane fade" id="mobile-categoryNav" role="tabpanel">
				<?php 
					$args_mobi_cat = array(
						'theme_location' => 'mobile-category-nav',
						'menu' => '',
						'menu_class' => 'main-menu',
						'container' => false,
						'add_li_class' => ' text-uppercase',
						'add_a_class' => '',
						'walker' => new Custom_Walker_Nav_Menu(),
					);
					wp_nav_menu($args_mobi_cat);
					?>
				</div>
			</div>
		</div>
		</div>
</header>