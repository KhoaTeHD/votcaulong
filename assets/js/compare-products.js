jQuery(document).ready(function ($) {
    let compareList = [];
    // Phân tích URL và lấy danh sách SKU
    let skusFromUrl = getSkusFromUrl();

    if (skusFromUrl.length > 0) {
        $.ajax({
            url: ThemeVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'load_product_comparison',
                product_skus: skusFromUrl,
                nonce: ThemeVars.nonce
            },
            success: function(response) {
                // hideLoading();
                if (response.success) {
                    // buildComparisonTable(response.data);
                } else {
                    siteNotify('Không thể tải dữ liệu sản phẩm');
                }
            },
            error: function() {
                // hideLoading();
                siteNotify('Lỗi kết nối, vui lòng thử lại');
            }
        });
        // getProductDetailsFromSkus(skusFromUrl)
        //     .then(products => {
        //         if (products && Array.isArray(products) && products.every(product => product.sku && product.title)) {
        //             localStorage.setItem('compareList', JSON.stringify(products));
        //             compareList = products;
        //             updateCompareListUI_page(compareList);
        //         } else {
        //             console.error('Dữ liệu sản phẩm không hợp lệ.');
        //         }
        //     })
        //     .catch(error => {
        //         console.error('Lỗi khi lấy thông tin sản phẩm:', error);
        //     });
    } else {
        // Nếu URL không hợp lệ, lấy dữ liệu từ localStorage (nếu có)
        compareList = JSON.parse(localStorage.getItem('compareList')) || [];
        updateCompareListUI_page(compareList);
    }
    updateCompareListUI_page(compareList);
    $(window).scroll(function() {
        let compareHeaderTop = $('#compare-header').offset().top + ($('#compare-header').height() /2);
        let scrollTop = $(window).scrollTop();

        if (scrollTop > compareHeaderTop) {
            $('#compareHead-top').show().addClass('sticky');
        } else {
            $('#compareHead-top').removeClass('sticky').hide();
        }
    });

});
let compare_title_list = [];
let compare_products_data = [];
async function updateCompareListUI_page(compareList){
    let $listcompare = jQuery('#cmpProduct-item');
    let $fullCompareHead = jQuery('#fullCompare-head');
    let $compareAttributes = jQuery('#compare-attributes');
    let checkdiff = `<div class="stick-df" onclick="">
                <input type="checkbox" class="checkdiff-cb" id="checkdiff-cb2">
                <label for="checkdiff-cb2">${translations.only_difference}</label>
            </div>`;
    $listcompare.empty();
    $fullCompareHead.empty();
    $compareAttributes.empty();
    compare_title_list = [];
    compare_products_data = []
    await Promise.all(compareList.map(async (item) => {
        await loadProductDetails(item, $listcompare, $fullCompareHead);
    }));
    jQuery('.fullCompare-title').html(compare_title_list.join('<h6>&</h6>')+ checkdiff);
    createCompareAttributesTable(compare_products_data, $compareAttributes);
    for (let i = compareList.length; i < 3; i++) {
        $listcompare.append(`
              <li class="formsg ">
                <a href="#" class="cp-plus cp-plus_new"  data-bs-toggle="modal" data-bs-target="#searchProduct_modal">
                  <i class="bi bi-plus-lg"></i><p>${translations.add_product}</p>
                </a>
              </li>
            `);
        $fullCompareHead.append(`<div class="cp-item-col" ><a href="#" class="cp-plus cp-plus_new"  data-bs-toggle="modal" data-bs-target="#searchProduct_modal">
                  <i class="bi bi-plus-lg"></i><p>${translations.add_product}</p>
                </a></div>`);
    }
    newTitle = 'So sanh san phẩm';
    newState = compareList;
    window.history.pushState(newState, newTitle, generateCompareUrl(compareList));

}
//--------------
function removeComparePage(sku) {
    let compareList = JSON.parse(localStorage.getItem('compareList')) || [];
    compareList = compareList.filter(item => item.sku !== sku);
    localStorage.setItem('compareList', JSON.stringify(compareList));
    updateCompareListUI_page(compareList);
}
//------------------
function loadProductDetails(item, $listcompare, $fullCompareHead) {
    return new Promise((resolve, reject) => {
        jQuery.ajax({
            url: ThemeVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_product_by_sku',
                sku: item.sku,
                nonce: ThemeVars.nonce
            },
            success: function (response) {
                if (response.success) {
                    let product = response.data;
                    compare_products_data.push(product);
                    let sale_title = product.discount!='' ? `<div class="sale-title">${product.discount}%</div>` : '';
                    let sold_qty = (product.sold!='' ? `<div class="qty-sold">Đã bán ${product.sold}</div>` : '');

                    let html = `
                        <li>
                            <img src="${product.image_url}" alt="${product.title}">
                            <div>
                                <a href="${item.url}" class="text-decoration-none text-body"><h3>${product.title}</h3></a>
                                <div class="price">${product.html_price}</div>
                            </div>
                            <span class="remove-ic-compare" onclick="removeComparePage('${product.sku}');"><i class="bi bi-x-lg"></i></span>
                            ${sale_title}
                        </li>
                    `;

                    let head = `
                        <div class="cp-item-col" >
                            <img src="${product.image_url}" alt="${product.title}" class="mb-3">
                            <a href="${item.url}" class="text-decoration-none text-body"><h6>${product.title}</h6></a>
                            <div class="product-meta-text sku">${product.sku}</div>
                            <div class="price">${product.html_price}</div>
                            <span class="remove-ic-compare" onclick="removeComparePage('${product.sku}');"><i class="bi bi-x-lg"></i></span>
                            ${sale_title}${sold_qty}
                        </div>
                    `;

                    $listcompare.append(html);
                    $fullCompareHead.append(head);
                    compare_title_list.push(`<h5>${product.title}</h5>`);
                    resolve();
                } else {
                    console.error('Lỗi: ' + response.data);
                    reject('Lỗi: ' + response.data);
                }
            },
            error: function () {
                console.error('Lỗi AJAX');
                reject('Lỗi AJAX');
            }
        });
    });
}
function createCompareAttributesTable(products, $container) {
    $container.empty();

    function getSpecValue(product, attributeName) {
        if (product.specifications && Array.isArray(product.specifications)) {
            const spec = product.specifications.find(s => s.spec_name === attributeName);
            return spec ? spec.spec_value : '-';
        }
        return '-';
    }
    let allAttributes = {};
    products.forEach(product => {
        if (product.specifications && Array.isArray(product.specifications)) { 
            product.specifications.forEach(attr_obj => { 
                if (attr_obj.spec_name) {
                    allAttributes[attr_obj.spec_name] = true;
                }
            });
        }
    });
    
    let showDifferencesOnly = jQuery('#checkdiff-cb1').is(':checked') || jQuery('#checkdiff-cb2').is(':checked'); 

    Object.keys(allAttributes).forEach(attrName => { // attrName is the spec_name string
        let values = products.map(product => getSpecValue(product, attrName));
        
        let allValuesEqual = values.length > 0 && values.every(val => val === values[0]);

        if (!showDifferencesOnly || !allValuesEqual) {
            let $row = jQuery('<div class="attribute-row"></div>');
            $row.append(`<div class="attribute-name">${attributeTranslations[attrName] || attrName}</div>`);
            products.forEach(product => {
                let value = getSpecValue(product, attrName);
                $row.append(`<div class="attribute-value">${value||'-'}</div>`);
            });
            let maxProductColumns = 3; // Assuming you want to align to a layout that can hold up to 3 products
            let emptyValueColumns = maxProductColumns - products.length;
            for (let i = 0; i < emptyValueColumns; i++) {
                $row.append('<div class="attribute-value empty-column"></div>');
            }

            $container.append($row);
        }
    });
}
jQuery(document).on('change', '.checkdiff-cb', function(e) {
    if (compare_products_data.length <2) {
        jQuery(this).prop('checked', false);
        return;
    }
    let isChecked = jQuery(this).is(':checked');
    createCompareAttributesTable(compare_products_data, jQuery('#compare-attributes'));
    syncCheckboxes(isChecked);
});
function syncCheckboxes(isChecked) {
    jQuery('.checkdiff-cb').prop('checked', isChecked);
    createCompareAttributesTable(compare_products_data, jQuery('#compare-attributes'));
}
function getSkusFromUrl() {
    let path = window.location.pathname;
    let parts = path.split('/');
    let skus =[];

    if (parts.length >= 3 && parts[1] === 'so-sanh') {
        let productParts = parts[2].split(';');
        productParts.forEach(part => {
            let sku = part.split('.').pop();
            skus.push(sku);
        });
    }

    return skus;
}
function getProductDetailsFromSkus(skus) {
    return new Promise((resolve, reject) => {
        let promises = skus.map(sku => {
            return new Promise((resolve, reject) => {
                jQuery.ajax({
                    url: ThemeVars.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_product_by_sku',
                        sku: sku,
                        nonce: ThemeVars.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject('Lỗi: ' + response.data);
                        }
                    },
                    error: function () {
                        reject('Lỗi AJAX');
                    }
                });
            });
        });

        Promise.all(promises)
            .then(products => {
                resolve(products);
            })
            .catch(error => {
                reject(error);
            });
    });
}