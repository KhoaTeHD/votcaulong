jQuery(document).ready(function($) {

    const addCompare = `<div class="cp-item-col" ><a href="#" class="cp-plus cp-plus_new"  data-bs-toggle="modal" data-bs-target="#searchProduct_modal">
                  <i class="bi bi-plus-lg"></i><p>${translations.add_product}</p>
                </a></div>`;
    compareList = JSON.parse(localStorage.getItem('compareList')) || [];
    let skusFromUrl = [];
    if(compareList){
        compareList.forEach((item) => {
            skusFromUrl.push(item.sku);
        });
    }else{
        skusFromUrl = getSkusFromUrl();
    }
    // Main function to load product comparison

    if (skusFromUrl.length > 0) {
        loadProductComparison(skusFromUrl);
    }

    // Export function for external use
    // window.ProductComparison = {
    //     load: loadProductComparison,
    //     remove: removeProductFromComparison
    // };
});
