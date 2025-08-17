# Votcaulong-shop WordPress Theme Structure Overview

This document outlines the key files and their primary functionalities within the `votcaulong-shop` WordPress theme.

## 1. Root Directory (`/`)

Contains core WordPress template files and theme configuration.

*   **`404.php`**: Template for displaying the 404 "Not Found" error page.
*   **`category.php`**: Template for displaying posts belonging to a specific category.
*   **`footer.php`**: Contains the footer section of the theme, typically including closing `<body>` and `<html>` tags, and enqueuing footer scripts.
*   **`functions.php`**: **Core theme functionality file.**
    *   Defines constants (e.g., `LANG_ZONE`, `IMG_URL`, `PAYOO_USERNAME`, `DEV_MODE`).
    *   Enqueues all CSS stylesheets (Bootstrap, Swiper, RateIt, ShareOn, custom theme styles) and JavaScript files (Popper, Bootstrap, ShareOn, Swiper, custom libs, main, account, shopping cart, checkout).
    *   Registers navigation menus (`footer-menu1` to `footer-menu5`).
    *   Includes various PHP files from the `includes/` directory for modularity (e.g., `class_ProductUrlGenerator.php`, `class_Breadcrumb.php`, `class_Customer.php`, and other `*.php` files).
    *   Localizes scripts with dynamic data (e.g., `ThemeVars`, `ThemeVarsCheckout`).
*   **`header-product-cate.php`**: A specialized header template, likely used for product category pages.
*   **`header.php`**: Contains the header section of the theme, including the `<!DOCTYPE>`, `<html>`, `<head>`, and opening `<body>` tags, and enqueuing header scripts/styles.
*   **`index.php`**: The main fallback template file for WordPress.
*   **`page.php`**: Default template for displaying static WordPress pages.
*   **`page-account.php`**: Template for the user account page.
*   **`page-contact.php`**: Template for the contact page.
*   **`page-discount.php`**: Template for discount-related content.
*   **`page-guide.php`**: Template for a guide or informational page.
*   **`page-he-thong-cua-hang.php`**: Template for displaying the store system (likely a list of physical stores).
*   **`page-introduce.php`**: Template for the "About Us" or introduction page.
*   **`page-list-brands.php`**: Template for listing product brands.
*   **`page-list-store_system.php`**: Another template for listing store systems.
*   **`page-news.php`**: Template for displaying news articles or blog posts.
*   **`page-product.php`**: Template for displaying product listings.
*   **`page-register.php`**: Template for user registration.
*   **`page-review.php`**: Template for product reviews or general reviews.
*   **`page-service_dan_vot.php`**: Template for a specific service related to "dan vot" (racket stringing).
*   **`page-service_thu_vot.php`**: Template for a specific service related to "thu vot" (racket testing).
*   **`page-shop.php`**: Main shop page template.
*   **`page-shopping-cart.php`**: **Shopping cart and checkout page template.** This is where the cart items, subtotal, customer information, shipping methods, payment methods, and checkout buttons are displayed. (Recently enhanced with Select2 for location/ward search).
*   **`screenshot.png`**: Screenshot of the theme, displayed in the WordPress admin.
*   **`single.php`**: Default template for displaying a single post.
*   **`single-brands.php`**: Template for displaying a single brand post type.
*   **`single-store_system.php`**: Template for displaying a single store system post type.
*   **`style.css`**: Main stylesheet for the theme, containing theme information (name, author, version) and basic CSS.

## 2. `assets/` Directory

Contains all static assets like CSS, JavaScript, and images.

*   **`assets/css/`**:
    *   `theme.css`: Main custom theme styles.
    *   `responsive.css`: Styles for responsive design.
    *   `account.css`, `shopping-cart.css`, `single-product.css`, `post_content.css`, `icons.css`: Specific styles for different sections/components.
    *   `fonts/`: Custom fonts used in the theme.
*   **`assets/images/`**:
    *   Various images used throughout the theme (banners, logos, product placeholders, icons, etc.). Organized into subdirectories like `360/`, `achievements/`, `bank_logo/`, `brands/`, `icons/`, `san-pham/`.
*   **`assets/js/`**:
    *   `main.js`: Core JavaScript functionalities for the theme.
    *   `account.js`: JavaScript for user account related interactions.
    *   `shopping-cart.js`: Handles shopping cart logic (add, remove, update quantities, sync with server).
    *   `checkout.js`: **Handles checkout process logic.** (Recently modified for Select2 integration and automatic shipping method selection). Manages location/ward loading, shipping cost calculation, and order submission.
    *   `category.js`: JavaScript for category-specific interactions.
    *   `compare-products.js`, `compare.js`: Logic for product comparison features.
    *   `custom-libs.js`: Custom utility JavaScript functions.
    *   `product-slider.js`: Logic for product carousels/sliders.
    *   `single-product.js`: JavaScript for single product page interactions.
    *   `swiper-init.js`: Initialization script for Swiper.js sliders.
*   **`assets/rateit/`**: Contains files for the jQuery RateIt plugin (CSS, JS, images for stars).
*   **`assets/shareon/`**: Contains files for the Shareon social sharing library (JS, CSS).
*   **`assets/swiper/`**: Contains Swiper.js library files (CSS, JS) for creating touch sliders.

## 3. `backend/` Directory

Contains files primarily for the WordPress admin area, related to ERPNext integration and custom post types.

*   **`admin_script.js`**: General JavaScript for the admin panel.
*   **`erp_brands.js`**: JavaScript for managing brands synchronized with ERPNext in the admin.
*   **`erp_store_system.js`**: JavaScript for managing store systems synchronized with ERPNext in the admin.
*   **`pro_cate_management.php`**: PHP file for product category management in the admin.
*   **`product-reviews-admin.php`**: PHP file for managing product reviews in the admin.
*   **`backend/css/`**: Admin-specific stylesheets (`admin-style.css`, `login-style.css`).
*   **`backend/datatables/`**: Contains DataTables library files (CSS, JS) for enhanced table functionalities in the admin.
*   **`backend/img/`**: Images used in the admin area (e.g., `admin-logo.png`, `login-bg.jpg`).

## 4. `data/` Directory

Stores static JSON data used by the theme, likely for frontend display or initial data loading.

*   `branches.json`: Branch information.
*   `brands.json`: Brand data.
*   `categories.json`: Product category data.
*   `flashsale.json`: Flash sale event data.
*   `locations.json`: Location data (provinces/cities).
*   `products_complete.json`: Complete product data.
*   `vn_data.json`: General Vietnam-specific data.
*   `wards.json`: Ward/commune data.
*   `tinh-thanh/`: Likely contains more granular location data.

## 5. `includes/` Directory

Contains PHP classes and functions that provide the core backend logic and integration with ERPNext. This is a critical directory for the theme's functionality.

*   **`class_Breadcrumb.php`**: Class for generating breadcrumb navigation.
*   **`class_Customer.php`**: Class representing customer data and operations.
*   **`class_CustomerManager.php`**: Manages customer-related operations.
*   **`class_ERP_API_Client.php`**: **Handles communication with the ERPNext API.** This is a central component for data synchronization (products, customers, orders).
*   **`class_Keyword_Manager.php`**: Manages keywords, possibly for search or SEO.
*   **`class_payoo_handler.php`**: Handles payment processing logic specifically for Payoo.
*   **`class_Product.php`**: Class representing product data and operations.
*   **`class_ProductManager.php`**: Manages product-related operations.
*   **`class_ProductUrlGenerator.php`**: Generates SEO-friendly URLs for products.
*   **`class_vcl_order.php`**: Class for handling order data and operations within the Votcaulong theme.
*   **`class_widget.php`**: Base class or utility for custom WordPress widgets.
*   **`inc_ajax_locations.php`**: Handles AJAX requests related to locations (provinces, districts, wards).
*   **`inc_ajax_orders.php`**: Handles AJAX requests related to orders.
*   **`inc_ajaxcall.php`**: General AJAX handler for various frontend requests.
*   **`inc_backend.php`**: Functions and hooks specific to the WordPress admin backend.
*   **`inc_create_db.php`**: Script for creating or updating custom database tables used by the theme.
*   **`inc_ERP_API_health.php`**: Checks and reports on the health/status of the ERPNext API connection.
*   **`inc_loyalty.php`**: Logic for loyalty programs.
*   **`inc_megamenu.php`**: Logic for mega menus.
*   **`inc_product_reviews.php`**: Handles product review functionality.
*   **`inc_products.php`**: Functions related to product display and management.
*   **`inc_rate.php`**: Logic for rating systems.
*   **`inc_rewrite.php`**: Custom rewrite rules for WordPress URLs.
*   **`inc_shortcodes.php`**: Registers custom shortcodes for use in content.
*   **`inc_siteconfig.php`**: Site-wide configuration settings.
*   **`inc_theme_hooks.php`**: Custom WordPress hooks used by the theme.
*   **`inc_theme_support.php`**: Declares theme support for various WordPress features.
*   **`inc_widgets.php`**: Registers custom WordPress widgets.
*   **`libs.php`**: Contains various utility functions or third-party library integrations.
*   **`posttype_brands.php`**: Registers the custom post type for "Brands".
*   **`posttype_cms_block.php`**: Registers a custom post type for CMS blocks (reusable content blocks).
*   **`posttype_store_system.php`**: Registers the custom post type for "Store System" (physical stores).
*   **`template-store-system.php`**: Template for displaying a single store system.

## 6. `languages/` Directory

Contains translation files for internationalization.

*   `vi.mo`, `vi.po`: Vietnamese translation files.

## 7. `template-parts/` Directory

Contains reusable template partials used across different pages.

*   `account-address.php`, `account-edit-profile.php`, `account-loyalty-gift.php`, `account-loyalty-member.php`, `account-orders.php`, `account-your-favorite.php`: Partials for different sections of the user account page.
*   `brand-item.php`: Template for a single brand item.
*   `breadcrumbs-bar.php`: Template for displaying breadcrumbs.
*   `cart-item.php`: Template for a single item in the shopping cart.
*   `compare-products.php`: Template for product comparison display.
*   `footer-widget.php`: Template for footer widget areas.
*   `header.php`: A generic header partial (might be different from the root `header.php`).
*   `not-found-page.php`: Partial for 404 content.
*   `order-received.php`: Partial for the order received confirmation.
*   `page-title.php`: Template for displaying page titles.
*   `product-brand.php`, `product-category.php`, `product-detail.php`, `product-flashsale.php`, `product-item.php`, `product-listing.php`: Partials for various product-related displays.
*   `product-review-form.php`, `product-reviews.php`: Partials for product review forms and display.
*   `widget-filter.php`: Partial for a product filter widget.
