let userLoggedIn = false;
jQuery(document).ready(function ($) {
    checkLoginStatus();
    setInterval(checkLoginStatus, 300000);
    //---------- Shopping cart -----------------------------
    loadCart();
    const shoppingCartCanvas = new bootstrap.Offcanvas("#shoppingCartCanvas");
    $(document).on("click",".addCart", function () {
        // Lấy phần tử cha chứa thông tin sản phẩm
        const productItem = $(this).closest(".product-item");

        // Lấy thông tin sản phẩm từ các thuộc tính data-*

        const product = {
            id: productItem.data("id"), // ID sản phẩm
            sku: productItem.data("sku"), // SKU sản phẩm
            name: productItem.find(".title").text().trim(), // Tên sản phẩm
            price: productItem.data("price"), // Giá sản phẩm
            originalPrice: productItem.data("original-price"), // Giá gốc
            saleOff: productItem.data("saleoff"), // Giảm giá (%)
            image: productItem.find("img").attr("src"), // URL hình ảnh sản phẩm
            category: productItem.data("category"), // Danh mục sản phẩm
            quantity: 1, // Số lượng mặc định
        };
        if (!product.id || !product.price) {
            console.log("Product error");
            return;
        }

        addToCart(product);
        shoppingCartCanvas.show();
    });

    handleCartActions();
    

});
function checkLoginStatus() {
    isLoggedIn(function (loggedIn) {
        if (loggedIn !== userLoggedIn) { // Chỉ cập nhật nếu có thay đổi
            userLoggedIn = loggedIn;
            if (userLoggedIn) {
                handleUserLogin();
            } else {
                updateCartUI(getCart());
            }
        }
    });
}
const apiUrl = ThemeVars.ajaxurl; // API của WP để xử lý AJAX

// Lấy giỏ hàng từ localStorage
function getCart() {
    return JSON.parse(localStorage.getItem("cart")) || [];
}

// Lưu giỏ hàng vào localStorage
function saveCart(cart) {
    localStorage.setItem("cart", JSON.stringify(cart));
    if (userLoggedIn) {
        syncCartWithServer(cart);
    }
}

function mergeCarts(localCart, serverCart) {
    let cartMap = new Map();

    [...localCart, ...serverCart].forEach((product) => {
        if (cartMap.has(product.id)) {
            let existing = cartMap.get(product.id);
            existing.quantity = Math.max(existing.quantity, product.quantity); // Chỉ lấy số lượng lớn nhất
        } else {
            cartMap.set(product.id, { ...product });
        }
    });

    return Array.from(cartMap.values());
}


// Khi đăng nhập, đồng bộ giỏ hàng với server
function handleUserLogin() {
    jQuery.ajax({
        url: apiUrl,
        method: "POST",
        data: {
            action: "get_user_cart",
            nonce: ThemeVars.nonce, // Nonce bảo mật
        },
        success: function (response) {
            if (response.success) {
                let serverCart = response.data.cart || [];
                let localCart = getCart();
                let mergedCart = mergeCarts(localCart, serverCart);

                saveCart(mergedCart); // Lưu giỏ hàng đã gộp
                updateCartUI(mergedCart);
            }
        },
    });
}

// Đồng bộ giỏ hàng với server
function syncCartWithServer(cart) {
    jQuery.ajax({
        url: apiUrl,
        method: "POST",
        data: {
            action: "update_user_cart",
            nonce: ThemeVars.nonce,
            cart: JSON.stringify(cart),
        },
    });
}


function addToCart(product) {
    let cart = getCart();
    let existingProduct = cart.find((p) => p.id === product.id);

    if (existingProduct) {
        existingProduct.quantity += product.quantity ?? 1;
    } else {
        cart.push(product);
    }

    saveCart(cart);
    if (userLoggedIn) {
        syncCartWithServer(cart);
    }

    updateCartUI(cart);
}



// Xóa sản phẩm khỏi giỏ hàng
function removeCartItem(productId) {
    let cart = getCart().filter((product) => product.id !== productId);
    saveCart(cart);
    updateCartUI(cart);
}
function isLoggedIn(callback) {
    jQuery.ajax({
        url: apiUrl, // Đổi thành URL API thực tế của bạn
        method: "POST",
        data: {
            action: "check_user_login",
        },
        success: function (response) {
            if (typeof callback === "function") {
                callback(response.success);
            }
        },
        error: function () {
            if (typeof callback === "function") {
                callback(false);
            }
        }
    });
}

function updateCartUI(cart) {
    let shoppingCart = jQuery("#shopping-cart");
    let itemCount_label = jQuery("#cart-item-counter");
    let cartItems = jQuery("#cart-items");
    let cartTotal = jQuery("#cart-total");
    let lineTotal = 0;
    let total = 0;
    let itemsQty = 0;

    shoppingCart.fadeOut();
    cartItems.empty();

    cart.forEach((product) => {
        lineTotal = product.price * product.quantity; // Tính tổng tiền
        total += lineTotal; // Tính tổng tiền
        itemsQty += product.quantity;
        let product_attr = '';
        if (product.attributes) {
            for (const [key, value] of Object.entries(product.attributes)) {
                product_attr += `<div>${key}: ${value}</div>`;
            }
        }
        cartItems.append(`
          <li class="cart-item">
                  <img src="${product.image}" alt="${
            product.name
        }" width="50" height="50" style="object-fit: cover;">
                  <div class="cart-item-details">
                    <p class="cart-item-name">${product.name}</p>
                    <p class="cart-item-sku">${product.sku}</p>
                    <div class="cart-item-attrs">${product_attr}</div>
                    <p class="cart-item-price">${product.price.toLocaleString()}<sup>₫</sup> x ${
            product.quantity
        } = <span class="line-total">${lineTotal.toLocaleString()}<sup>₫</sup></span></p>
                  </div>
                  <button class="remove-from-cart btn btn-danger btn-sm" data-product-id="${
            product.id
        }"><i class="bi bi-x-circle"></i></button>
                </li>
              `);
    });
    cartTotal.html(total.toLocaleString() + "<sup>₫</sup>");
    itemCount_label.text(itemsQty);

    if (!itemsQty) {
        shoppingCart.hide();
        jQuery("#empty-cart").fadeIn();
    } else {
        jQuery("#empty-cart").hide();
        shoppingCart.fadeIn();
    }
    if (jQuery("#shoppingCart-page").length > 0) {
        cartPageLoad();
    }
}
function loadCart() {

    if (userLoggedIn) {
        jQuery.ajax({
            url: apiUrl,
            method: "POST",
            data: {
                action: "get_user_cart",
                nonce: ThemeVars.nonce,
            },
            success: function (response) {
                if (response.success) {
                    let serverCart = response.data.cart || [];
                    let localCart = getCart();
                    let mergedCart = [];
                    // Chỉ gộp nếu localCart chưa có dữ liệu
                    if (serverCart.length && localCart.length) {
                        // mergedCart = serverCart;
                        mergedCart = mergeCarts(localCart, serverCart);
                    }
                    if(serverCart.length && !localCart.length){
                        mergedCart =  serverCart
                    }else{
                        mergedCart = localCart;
                    }
                    saveCart(mergedCart);
                    updateCartUI(mergedCart);
                    // if (jQuery("#shoppingCart-page").length > 0) {
                    //     // cartPageLoad();
                    //     // handleCartActions();
                    // }
                }
            }
        });
    } else {
        updateCartUI(getCart());
    }

}

function cartPageLoad() {
    const cart = getCart();
    if (cart.length === 0) {
        jQuery("#shoppingCart-page").html(`<p>${translations.cart_no_item}</p>`);
        return;
    }
    jQuery.ajax({
        url: ThemeVars.ajaxurl,
        method: "POST",
        data: {
            action: "get_cart_products",
            cart: JSON.stringify(cart),
            nonce: ThemeVars.nonce,
        },
        beforeSend: function () {
            showLoading();
        },
        success: function (response) {
            if (response.success) {
                jQuery("#shoppingCart-page #cartPage-items").html(response.data.html);
                jQuery("#shoppingCart-page #cartPage-subtotal").html(response.data.subtotal_html);
                jQuery("#shoppingCart-page #cartPage-total").html(response.data.total_html);
            } else {
                console.error("Lỗi:", response.data);
                jQuery("#shoppingCart-page").html(`<p>${translations.cart_error}</p>`);
            }
            hideLoading();
            // loadCart(); //reload minicart
        },
        error: function (error) {
            console.error("Lỗi khi tải thông tin sản phẩm:", error);
            jQuery("#shoppingCart-page").html(`<p>${translations.cart_error}</p>`);
            hideLoading();
        },
    });
}


function loadCartUI(cart) {
    if (cart.length === 0) {
        jQuery("#shoppingCart-page").html(`<p>${translations.cart_no_item}</p>`);
        return;
    }

    jQuery.ajax({
        url: ThemeVars.ajaxurl,
        method: "POST",
        data: {
            action: "get_cart_products",
            cart: JSON.stringify(cart),
            nonce: ThemeVars.nonce,
        },
        beforeSend: function () {
            showLoading();
        },
        success: function (response) {
            if (response.success) {
                jQuery("#shoppingCart-page #cartPage-items").html(response.data.html);
                jQuery("#shoppingCart-page #cartPage-subtotal").html(
                    response.data.subtotal_html
                );



                jQuery(".remove-from-cart").on("click", function () {
                    const productId = jQuery(this).data("product-id");
                    removeCartItem(productId);
                });
            } else {
                console.error("Lỗi:", response.data);
                jQuery("#shoppingCart-page").html(`<p>${translations.cart_error}</p>`);
            }
            hideLoading();
        },
        error: function (error) {
            console.error("Lỗi khi tải thông tin sản phẩm:", error);
            jQuery("#shoppingCart-page").html(`<p>${translations.cart_error}</p>`);
            hideLoading();
        },
    });
}
function updateCartItemTotal(item) {
    const productId = item.data("id");
    const price = item.data("price");
    const quantity = parseInt(item.find(".item-qty").val());
    const total = price * quantity;
    // item.find(".item-total").text(`${total.toLocaleString()}đ`);
    updateCartQuantity(productId, quantity);
    // loadCart(); //reload minicart
}


function updateCartQuantity(productId, newQty) {
    let cart = getCart();
    // Tìm sản phẩm trong giỏ hàng
    // console.log(productId, cart)
    let itemIndex = cart.findIndex(item => item.id === productId);
    // console.log('index',itemIndex)
    if (itemIndex !== -1) {
        cart[itemIndex].quantity = newQty; // Cập nhật số lượng
        saveCart(cart);
        updateCartUI(cart);
        // cartPageLoad();
        // loadCart();
    }


}


function handleCartActions() {
    jQuery(document).on("click", ".qty-plus", function () {
        const qtyInput = jQuery(this).siblings(".item-qty");
        let qty = parseInt(qtyInput.val()) + 1;
        qtyInput.val(qty).trigger('change');
        const productId = jQuery(this).closest(".cartPage-item").data("product-id");
        // updateCartQuantity(productId, qty);
        // updateCartItemTotal(jQuery(this).closest(".cartPage-item"));
    });

    jQuery(document).on("click", ".qty-minus", function () {
        const qtyInput = jQuery(this).siblings(".item-qty");
        let qty = parseInt(qtyInput.val());
        if (qty > 1) {
            qty -= 1;
            qtyInput.val(qty).trigger('change');
            const productId = jQuery(this).closest(".cartPage-item").data("product-id");
            // updateCartQuantity(productId, qty);
            // updateCartItemTotal(jQuery(this).closest(".cartPage-item"));
        }
    });

    jQuery(document).on("change", ".item-qty", function () {
        const $thisItem = jQuery(this).closest('.cartPage-item');
        let qty = parseInt(jQuery(this).val());
        if (qty < 1) {
            jQuery(this).val(1);
            qty = 1;
        }
        const productId = $thisItem.data('variation-sku') || $thisItem.data('id');
        updateCartQuantity(productId, qty);
        updateCartItemTotal(jQuery(this).closest(".cartPage-item"));
    });
    jQuery(document).on("click", ".remove-from-cart", function () {
        const productId = jQuery(this).data("product-id");
        removeCartItem(productId);
    });

}
