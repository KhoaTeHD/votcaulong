# Project Overview: Votcaulong-shop WordPress Theme

This project is a WordPress theme for an e-commerce site.

## Key Information

- **Platform:** WordPress
- **Purpose:** E-commerce website for "Votcaulong-shop".
- **Core Feature:** Integration with ERPNext via API.
- **Data Synchronization:** The theme handles data exchange with ERPNext for key data points, including:
    - Products
    - Customers
    - Orders

## Instructions for Gemini
- Mỗi lần khởi động, đọc luôn cấu trúc dự án trong @THEME_STRUCTURE.md (On every startup, always read the project structure in @THEME_STRUCTURE.md).

## Recent Enhancements

- **Enhanced Location/Ward Selection (Checkout Page):**
    - Integrated **Select2** library (`functions.php`, `assets/js/checkout.js`) for `#shipping_location` (Province/City) and `#shipping_ward_new` (Ward/Commune) select boxes on the shopping cart/checkout page (`page-shopping-cart.php`).
    - Provides quick search functionality within the dropdowns for improved user experience.
    - Ensured compatibility with existing form submission logic by not altering the underlying `<select>` element structure.
    - Fixed issues with Select2 not displaying pre-filled default addresses by adjusting initialization order and triggering change events (`assets/js/checkout.js`).
    - Added a check to prevent `select2('destroy')` errors when Select2 is not yet initialized on an element (`assets/js/checkout.js`).
- **Automatic Shipping Method Selection:**
    - Modified `assets/js/checkout.js` (`loadShippingMethodsAndCost` function) to automatically select and apply the first available shipping method and its cost immediately after shipping options are loaded and displayed.