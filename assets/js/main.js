let is_comparePage = false;
jQuery(document).ready(function ($) {
  $('#loading-overlay').fadeOut(500);
  $(document).on('click', 'a[href="#"]', function(e) {
    e.preventDefault();
  });
  // $('form').submit(function(event) {
  //   $('#loading-overlay').fadeIn(500);
  // });
  // Scroll to top
  const scrollToTopBtn = $("#scroll-to-top");
  window.onscroll = function () {
    if (
      document.body.scrollTop > 100 ||
      document.documentElement.scrollTop > 100
    ) {
      scrollToTopBtn.fadeIn();
    } else {
      scrollToTopBtn.fadeOut();
    }
  };
  scrollToTopBtn.click(function () {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
  Shareon.init();
  const swiper_info_box = new Swiper(".banner-footer .swiper", {
    // Optional parameters
    loop: true,
    slidesPerView: 4, // or a number
    spaceBetween: 0, // Adjust spacing as needed
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
    breakpoints: {
      // when window width is >= 320px
      320: {
        slidesPerView: 1,
        spaceBetween: 0,
      },
      // when window width is >= 480px
      480: {
        slidesPerView: 2,
        spaceBetween: 0,
      },
      // when window width is >= 640px
      640: {
        slidesPerView: 3,
        spaceBetween: 0,
      },
      992: {
        slidesPerView: 4,
        spaceBetween: 0,
      },
    },
  });
  $("#new-product-filter2").initSwiperWithFilter();
  $('.category-filter-scroll').on('wheel', function(e) {
    if (e.originalEvent.deltaY !== 0) {
      e.preventDefault();
      this.scrollLeft += e.originalEvent.deltaY;
    }
  });
  $(".filterable-list").filterableList();
  displayRecentlyViewedProducts();
  let compareList = JSON.parse(localStorage.getItem("compareList")) || [];
  updateCompareListUI(compareList, true);
  $('.photo-box').on('wheel', function(e) {
    if (e.originalEvent.deltaY !== 0) {
      e.preventDefault();
      let box = $(this).find('.photo-box-item').first();
      let boxWidth = box.outerWidth(true);
      if (e.originalEvent.deltaY > 0) {
        // Scroll tới box kế tiếp
        this.scrollTo({ left: this.scrollLeft + boxWidth, behavior: 'smooth' });
      } else {
        // Scroll về box trước
        this.scrollTo({ left: this.scrollLeft - boxWidth, behavior: 'smooth' });
      }
    }
  });
  $(document).on("click", "#viewed-products .remove-viewed-btn", function (e) {
    let item = $(this).closest(".viewed-item");
    e.preventDefault();
    let product_id = $(this).data("product-id");
    if (product_id) {
      item.fadeOut();
      removeViewedProduct(product_id);
    }
    return false;
  });
  $(document).on("click", "a.product-url", function (event) {
    return;
    const href = this.getAttribute("href");

    if (href.charAt(0) !== "#" && href.charAt(0) !== "?") {
      // showLoading();
      // console.log(href.charAt(0));
    } else {
      event.preventDefault();
    }
  });
  function startCountdown(box_element) {
    let $boxEle = $(box_element);
    let endDate = $boxEle.data("enddate");
    const dayElement = $boxEle.find(".day");
    const hourElement = $boxEle.find(".hour");
    const minuteElement = $boxEle.find(".minute");
    const secondElement = $boxEle.find(".second");

    const endTime = new Date(endDate);

    function updateCountdown() {
      const now = new Date();
      const timeLeft = endTime - now;

      if (timeLeft <= 0) {
        clearInterval(interval);
        // alert("Flashsale đã kết thúc!");
        return;
      }

      const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
      const hours = Math.floor(
        (timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
      );
      const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

      dayElement.text(days.toString().padStart(2, "0"));
      hourElement.text(hours.toString().padStart(2, "0"));
      minuteElement.text(minutes.toString().padStart(2, "0"));
      secondElement.text(seconds.toString().padStart(2, "0"));
    }

    // Gọi hàm updateCountdown mỗi giây
    const interval = setInterval(updateCountdown, 1000);

    // Gọi hàm ngay lập tức để tránh độ trễ ban đầu
    updateCountdown();
  }

  if ($(".flashSale-wrapper").length > 0) {
    startCountdown(".box-count-down");
  }

  function displayRecentlyViewedProducts() {
    var viewedProducts =
      JSON.parse(localStorage.getItem("viewedProducts")) || [];
    var productList = $("#viewed-products");
    let maxItems = 5;
    var counter = 1;
    if (productList.length) {
      productList.empty(); // Xóa danh sách hiện tại
      viewedProducts.forEach(function (product) {
        if (counter <= maxItems) {
          // Tạo phần tử li cho mỗi sản phẩm (có thể thay thế bằng cách lấy thông tin từ server)
          let item = `<div class="viewed-item" data-product-info="">
                           <div class="product-image">
                               <a href="${product.url}" class="product-url"><img src="${product.image}" alt="${product.name}"></a>
                           </div>
                           <div class="product-content">
                               <h5 class="title"><a href="${product.url}" class="product-url">${product.name}</a></h5>
                               <p class="price">${product.price}</p>
                           </div>
                           <a class="btn text-black-50 remove-viewed-btn" role="button" href="#" data-product-id="${product.id}"><i class="bi bi-x-circle-fill"></i></a>
                       </div>`;
          productList.append(item);
          counter++;
        }
      });
    }
  }
  //---------
  $("#clear-viewed-products").click(function (e) {
    e.preventDefault();
    localStorage.removeItem("viewedProducts");
    $("#viewed-products").fadeOut();
  });
  function removeViewedProduct(productId) {
    let viewedProducts =
      JSON.parse(localStorage.getItem("viewedProducts")) || [];
    viewedProducts = viewedProducts.filter(function (product) {
      return product.id !== productId;
    });
    localStorage.setItem("viewedProducts", JSON.stringify(viewedProducts));
    // displayRecentlyViewedProducts();
  }
  //-----

  $("#openForm").on("click", function () {
    $("#registerForm").fadeIn();
  });

  $("#closeForm").on("click", function () {
    $("#registerForm").fadeOut();
  });

  $(document).on("click", ".favourite-btn", function (e) {
    e.preventDefault();
    var product_id = $(this).data("product-id");
    let $thisBtn = $(this);
    if ($thisBtn.hasClass("added")) {
      return;
    }
    $.ajax({
      url: ThemeVars.ajaxurl,
      type: "POST",
      data: {
        action: "add_to_wishlist",
        nonce: ThemeVars.nonce,
        product_id: product_id,
      },
      beforeSend: function () {
        toggleButtonState($thisBtn);
        $thisBtn.text("...");
      },
      success: function (response) {
        if (response.success) {
          siteNotify(response.data.message);
          $thisBtn.text(response.data.count_text);
          $thisBtn.addClass("added");
        } else {
          // alert(response.data.message);
          siteNotify(response.data.message);
        }
        toggleButtonState($thisBtn, false);
      },
    });
  });
  $(document).on("click", ".remove-favorite-btn", function (e) {
    e.preventDefault();
    var product_id = $(this).data("product-id");
    let $thisBtn = $(this);
    let $item = $(this).closest(".product-item");
    $.ajax({
      url: ThemeVars.ajaxurl,
      type: "POST",
      data: {
        action: "remove_wishlist",
        nonce: ThemeVars.nonce,
        product_id: product_id,
      },
      beforeSend: function () {
        toggleButtonState($thisBtn);
      },
      success: function (response) {
        if (response.success) {
          // alert(response.data.message);
          // $thisBtn.text(response.data.count_text);
          // $thisBtn.addClass('added');
          console.log($item);
          $item.remove();
        } else {
          // alert(response.data);
        }
      },
    });
  });
  //--------- Compare ----------------------
  let timer;
  function searchProducts(search) {
    let modalBody = $("#searchProduct_modal").find(".modal-body");
    let html = `<div id="searchProduct_grid" class="product-grid shadow-item">`;
    let loading =
      '<div class="d-flex justify-content-center w-100">\n' +
      '                    <div class="spinner-border" role="status">\n' +
      '                        <span class="visually-hidden">Loading...</span>\n' +
      "                    </div>\n" +
      "                </div>";
    $.ajax({
      url: ThemeVars.ajaxurl,
      type: "POST",
      data: {
        action: "compare_list_search",
        nonce: ThemeVars.nonce,
        search: search,
      },
      beforeSend: function () {
        modalBody.html(loading);
      },
      success: function (response) {
        if (response.success) {
          let compareList =
            JSON.parse(localStorage.getItem("compareList")) || [];
          response.data.forEach(function (product) {
            let button = !compareList.some((item) => item.sku === product.sku)
              ? `<i class="bi bi-plus-circle"></i> ${translations.compare}`
              : `<i class="bi bi-check2-circle"></i> ${translations.added}`;

            let item = `
                            <div class="product-item  product-data flex-column" ${product.meta_info}>
                                ${product.discount}
                                ${product.badge}
                                <div class="image">
                                    <a href="${product.url}" class="product-url">
                                        <img src="${product.image_url}" data-bs-toggle="tooltip" title="" loading="lazy">
                                    </a>
                                </div>
                                <div class="text-badge">${product.text_badge}</div>
                                <div class="content">
                                    <h5 class="title" data-bs-toggle="tooltip" title="">
                                        <a href="${product.url}" class="product-url">${product.title}</a>
                                    </h5>
                                    <div class="product-meta-text sku">${product.sku}</div>
                                    <div class="price">${product.htmlPrice}</div>
                                </div>
                                <div class="buttons">
                                    <a href="#" role="button" class="btn search-compare text-primary fw-bold compare-btn" onclick="addToCompare(this)" data-id="${product.sku}">
                                        ${button}
                                    </a>
                                </div>
                            </div>`;
            html += item;
          });
          modalBody.html(html + "</div>");
        } else {
          modalBody.html(
            '<p class="text-center text-danger">Không tìm thấy sản phẩm.</p>'
          );
        }
      },
    });
  }

  $("#searchProduct_modal").on("show.bs.modal", function (event) {
    let search = $("#searchProduct_compare_input").val().trim();
    searchProducts(search);
  });
  $("#searchProduct_compare_input").on("keyup", function () {
    let search = $(this).val().trim();

    clearTimeout(timer);
    timer = setTimeout(() => {
      if (search.length === 0 || search.length >= 3) {
        searchProducts(search);
      }
    }, 500); // Đợi 500ms trước khi gửi request
  });
  $("#searchProduct_modal").on("hide.bs.modal", function (event) {
    let compareList = JSON.parse(localStorage.getItem("compareList")) || [];
    if (typeof updateCompareListUI_page === "function") {
      updateCompareListUI_page(compareList);
    }
    updateCompareListUI(compareList);
  });
  updateCompareButtonStatus();

  //live search
  let searchTimeout;
  const searchInput = $("#searchInput");
  const resultsContainer = $("#searchResultsContainer");
  const minChars = 3;

  searchInput.on("input", function () {
    const query = $(this).val().trim();

    clearTimeout(searchTimeout);

    if (query.length >= minChars) {
      resultsContainer
        .html(
          '<div class="p-2 text-center text-muted small">Đang tìm kiếm...</div>'
        )
        .show(); // Show loading indicator

      searchTimeout = setTimeout(function () {
        performSearch(query);
      }, 500); // Chờ 500ms sau khi người dùng ngừng gõ
    } else {
      resultsContainer.hide().empty(); // Ẩn và xóa kết quả nếu query quá ngắn
    }
  });
  searchInput.on("keydown", function(e){
    if (e.key === "Enter") {
      e.preventDefault();
      const query = $(this).val().trim();
      if(query.length >= minChars) {
        resultsContainer
            .html(
                '<div class="p-2 text-center text-muted small">Đang tìm kiếm...</div>'
            )
            .show(); // Show loading indicator
        clearTimeout(searchTimeout);
        performSearch(query);
      }
    }
  });

  $("#searchBtn").on("click", function(e){
    e.preventDefault();
    const query = searchInput.val().trim();
    if(query.length >= minChars) {
      resultsContainer
          .html(
              '<div class="p-2 text-center text-muted small">Đang tìm kiếm...</div>'
          )
          .show();
      clearTimeout(searchTimeout);
      performSearch(query);
    }
  });

  function performSearch(query) {
    $.ajax({
      url: ThemeVars.ajaxurl,
      type: "POST",
      data: {
        action: "live_search",
        search_query: query,
        nonce: ThemeVars.nonce,
      },
      beforeSend:function (){
        $('#searchBtn-spinner').show();
        $('#searchBtn i').css('opacity',0);
      },
      success: function (response) {
        if (response.success) {
          displayResults(response.data);
        } else {
          resultsContainer
            .html(
              '<div class="p-2 text-center text-danger small">' +
                (response.data || "Có lỗi xảy ra.") +
                "</div>"
            )
            .show();
        }
        $('#searchBtn-spinner').hide();
        $('#searchBtn i').css('opacity',1);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Search AJAX error:", textStatus, errorThrown);
        resultsContainer
          .html(
            '<div class="p-2 text-center text-danger small">Lỗi kết nối. Vui lòng thử lại.</div>'
          )
          .show();
      },
    });
  }

  // Hàm hiển thị kết quả
  function displayResults(results) {
    resultsContainer.empty(); // Xóa kết quả cũ hoặc loading indicator

    if (results && results.length > 0) {
      const list = $('<ul class="list-group list-group-flush"></ul>');
      results.forEach(function (item) {
        // Tùy chỉnh cách hiển thị từng kết quả ở đây
        let itemPrice =
          item.type == "product"
            ? `<div class="d-flex gap-1 search-price" >${item.price}</div>`
            : `<div class="text-secondary">${item.type}</div>`;
        const listItem = `
                    <li class="list-group-item list-group-item-action">
                        <a href="${item.url}" data-url="${item.url}" class="d-flex align-items-center text-decoration-none search-result-item">
                            ${
                              item.thumbnail
                                ? `<img src="${item.thumbnail}" alt="" width="40" height="40" class="me-2 object-fit-contain">`
                                : ""
                            }
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark">${
                                  item.title
                                }</div>
                                ${itemPrice}
                            </div>
                        </a>
                    </li>
                `;
        list.append(listItem);
      });
      resultsContainer.append(list).show();
    } else {
      resultsContainer
        .html(
          '<div class="p-2 text-center text-muted small">Không tìm thấy kết quả nào.</div>'
        )
        .show();
    }
  }

  $(document).on("click", function (event) {
    if (
      !$(event.target).closest("#searchInput, #searchResultsContainer").length
    ) {
      resultsContainer.hide();
    }
  });

  resultsContainer.on("mousedown", function (event) {
    if (event.offsetX > $(this).width()) {
    } else {
    }
  });

  resultsContainer.on('click', '.search-result-item', function(e) {
    e.preventDefault(); // Ngăn chuyển trang ngay lập tức

    const url = $(this).data('url');
    const keyword = searchInput.val().trim();
    if(!url || !keyword) return;

    // Gửi AJAX lưu keyword
    $.post(ThemeVars.ajaxurl, {
      action: 'add_search_keyword',
      keyword: keyword,
      nonce: ThemeVars.nonce
    }, function() {
      window.location.href = url;
    });
  });

  searchInput.on('keydown', function(e) {
    const items = resultsContainer.find('.list-group-item-action:visible');
    if (!items.length) return;

    let current = items.filter('.active');
    let idx = items.index(current);

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      let nextIdx = (idx < items.length - 1) ? idx + 1 : 0;
      items.removeClass('active');
      items.eq(nextIdx).addClass('active');
      // SCROLL vào tầm nhìn:
      scrollItemIntoView(items.eq(nextIdx));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      let prevIdx = (idx > 0) ? idx - 1 : items.length - 1;
      items.removeClass('active');
      items.eq(prevIdx).addClass('active');
      scrollItemIntoView(items.eq(prevIdx));
    } else if (e.key === 'Enter') {
      if (idx !== -1) {
        e.preventDefault();
        items.eq(idx).find('.search-result-item').trigger('click');
      }
    } else {
      items.removeClass('active');
    }
  });

  function scrollItemIntoView($item) {
    if (!$item.length) return;
    const container = $item.closest('#searchResultsContainer')[0];
    const itemDom = $item[0];

    const containerTop = container.scrollTop;
    const containerBottom = containerTop + container.clientHeight;

    const itemTop = itemDom.offsetTop;
    const itemBottom = itemTop + itemDom.offsetHeight;

    if (itemTop < containerTop) {
      container.scrollTop = itemTop;
    } else if (itemBottom > containerBottom) {
      container.scrollTop = itemBottom - container.clientHeight;
    }
  }

  $('.quick-links').on('click', '.quick-link', function(e) {
    e.preventDefault();
    const keyword = $(this).data('keyword');

    if (keyword) {
      setTimeout(function() {
        typeTextEffect(searchInput, keyword.toLowerCase(), 30, function() {
          searchInput.trigger('input');
        });
        searchInput.focus();
      }, 400);
    }
  });

  $('.search-tags').on('click', '.quick-link-badge', function(e) {
    e.preventDefault();
    const keyword = $(this).data('keyword');
    if (!keyword || !searchInput.length) return;

    searchInput[0].scrollIntoView({ behavior: "smooth", block: "center" });

    setTimeout(function() {
      typeTextEffect(searchInput, keyword.toLowerCase(), 30, function() {
        searchInput.trigger('input');
      });
      searchInput.focus();
    }, 400);

    searchInput.addClass('input-highlight');
    setTimeout(function() { searchInput.removeClass('input-highlight'); }, 1000);
  });

  function typeTextEffect($input, text, speed = 50, callback) {
    $input.val('');
    let i = 0;
    function typeChar() {
      if (i <= text.length) {
        $input.val(text.substring(0, i));
        i++;
        setTimeout(typeChar, speed);
      } else if (typeof callback === 'function') {
        callback();
      }
    }
    typeChar();
  }




  const brands_swiper = new Swiper('.brand-swiper', {
    slidesPerView: 'auto',
    spaceBetween: 16,
    loop: true,
    speed: 3500,
    autoplay: {
      delay: 0,
      disableOnInteraction: false,
      pauseOnMouseEnter: true
    },
    freeMode: true,
    freeModeMomentum: false,
    grabCursor: true,
    allowTouchMove: true,
    breakpoints: {
      900: { slidesPerView: 6 },
      600: { slidesPerView: 4 },
      0:   { slidesPerView: 2 }
    }
  });


  $('#quickCompare').click(function(e){
      // e.preventDefault();

    compareList = JSON.parse(localStorage.getItem('compareList')) || [];
    let skusFromUrl = [];
    if(compareList){
      compareList.forEach((item) => {
        skusFromUrl.push(item.sku);
      });
    }
    if (skusFromUrl.length > 0 ) {
      loadProductComparison(skusFromUrl);
      hideCompare();
    }
  });




});/////////////////////////////////////////////

//---- quick compare & function
// Build comparison table
const addCompare = `<div class="cp-item-col" ><a href="#" class="cp-plus cp-plus_new"  data-bs-toggle="modal" data-bs-target="#searchProduct_modal">
                  <i class="bi bi-plus-lg"></i><p>${translations.add_product}</p>
                </a></div>`;
function buildComparisonTable(products) {
  if (!products || products.length === 0) {
    siteNotify('Không có sản phẩm để so sánh');
    return;
  }

  const comparisonFields = getAllComparisonFields(products);
  const tableHtml = generateComparisonTableHtml(products, comparisonFields);

  jQuery('#comparison-table-container').html(tableHtml);

  // Initialize table interactions
  initializeTableInteractions();
}

// Get all unique fields for comparison
function getAllComparisonFields(products) {
  const attributes = new Set();
  const specifications = new Set();

  products.forEach(product => {
    // Collect attributes
    if (product.attributes) {
      Object.keys(product.attributes).forEach(attr => {
        attributes.add(attr);
      });
    }

    // Collect specifications
    if (product.specifications) {
      Object.keys(product.specifications).forEach(spec => {
        specifications.add(spec);
      });
    }
  });

  return {
    attributes: Array.from(attributes),
    specifications: Array.from(specifications)
  };
}

// Generate comparison table HTML
function generateComparisonTableHtml(products, fields) {
  // Tối đa 3 cột, nếu thiếu thì chèn null để padding
  const maxColumns = 3;
  const paddedProducts = [...products];
  while (paddedProducts.length < maxColumns) {
    paddedProducts.push(null);
  }

  let html = '<div class="comparison-table-wrapper">';
  html += '<table class="product-comparison-table" id="comparison-table">';

  // Header row with product names
  html += '<thead>';
  html += '<tr class="product-header-row">';
  html += '<th class="field-name-header"></th>';

  paddedProducts.forEach(product => {
    if (product) {
      html += `<th class="product-header">
                <div class="product-header-content" data-sku="${product.basic_info.item_code}">
                    <div class="product-image">
                        ${getProductImageHtml(product.basic_info.image_url)}
                    </div>
                    <div class="product-name">${escapeHtml(product.basic_info.item_name)}</div>
                    <div class="product-code">${escapeHtml(product.basic_info.item_code)}</div>
                    <div class="product-price">${product.basic_info.price}</div>
                    <div class="product-brand">${escapeHtml(product.basic_info.brand)}</div>
                </div>
            </th>`;
    } else {
      html += `<th class="product-header empty-header">${addCompare}</th>`;
    }
  });

  html += '</tr>';
  html += '</thead>';

  html += '<tbody>';

  // Basic information section
  html += generateBasicInfoRows(paddedProducts);

  // Attributes section
  if (fields.attributes.length > 0) {
    html += generateAttributeRows(paddedProducts, fields.attributes);
  }

  // Specifications section
  if (fields.specifications.length > 0) {
    html += generateSpecificationRows(paddedProducts, fields.specifications);
  }

  html += '</tbody>';
  html += '</table>';
  html += '</div>';

  return html;
}

function generateComparisonTableHtml_old(products, fields) {
  let html = '<div class="comparison-table-wrapper">';
  html += '<table class="product-comparison-table" id="comparison-table">';

  // Header row with product names
  html += '<thead>';
  html += '<tr class="product-header-row">';
  html += '<th class="field-name-header"></th>';

  products.forEach(product => {
    html += `<th class="product-header">
                <div class="product-header-content" data-sku="${product.basic_info.item_code}">
                    <div class="product-image">
                        ${getProductImageHtml(product.basic_info.image_url)}
                    </div>
                    <div class="product-name">${escapeHtml(product.basic_info.item_name)}</div>
                    <div class="product-code">${escapeHtml(product.basic_info.item_code)}</div>
                    <div class="product-price">${(product.basic_info.price)}</div>
                    <div class="product-brand">${escapeHtml(product.basic_info.brand)}</div>
                </div>
            </th>`;
  });

  html += '</tr>';
  html += '</thead>';

  html += '<tbody>';

  // Basic information section
  html += generateBasicInfoRows(products);

  // Attributes section
  if (fields.attributes.length > 0) {
    html += generateAttributeRows(products, fields.attributes);
  }

  // Specifications section
  if (fields.specifications.length > 0) {
    html += generateSpecificationRows(products, fields.specifications);
  }

  html += '</tbody>';
  html += '</table>';
  html += '</div>';

  return html;
}

// Generate basic info rows
function generateBasicInfoRows(products) {
  let html = '';
  // Description row
  html += '<tr class="section-header-row"><td >Thông tin cơ bản</td><td colspan="' + (products.length) + '"></td></tr>';
  html += '<tr class="comparison-row">';
  html += '<td class="field-name">Mô tả</td>';

  products.forEach(product => {
    if (product){
      html += `<td class="field-value">${escapeHtml(product.basic_info.description || 'N/A')}</td>`;
    }else{
      html += `<td class="field-value"></td>`;
    }

  });
  html += '</tr>';

  // Item group row
  html += '<tr class="comparison-row">';
  html += '<td class="field-name">Nhóm sản phẩm</td>';
  products.forEach(product => {
    if (product) {
      html += `<td class="field-value">${escapeHtml(product.basic_info.item_group || 'N/A')}</td>`;
    }else {
      html += `<td class="field-value"></td>`;
    }
  });
  html += '</tr>';

  return html;
}

// Generate attribute rows
function generateAttributeRows(products, attributes) {
  let html = '';

  if (attributes.length > 0) {
    html += '<tr class="section-header-row"><td >Thuộc tính</td><td colspan="' + (products.length) + '"></td></tr>';

    attributes.forEach(attributeName => {
      html += '<tr class="comparison-row">';
      html += `<td class="field-name">${escapeHtml(attributeName)}</td>`;

      products.forEach(product => {
        if (product) {
          const attributeValues = product.attributes && product.attributes[attributeName]
              ? product.attributes[attributeName]
              : [];

          html += `<td class="field-value">
                        <div class="attribute-values">
                            ${attributeValues.map(value =>
              `<span class="attribute-tag">${escapeHtml(value)}</span>`
          ).join('')}
                        </div>
                    </td>`;
        }else{
          html +='<td class="field-value"></td>';
        }
      });

      html += '</tr>';
    });
  }

  return html;
}

// Generate specification rows
function generateSpecificationRows(products, specifications) {
  let html = '';

  if (specifications.length > 0) {
    // html += '<tr class="section-header-row"><td colspan="' + (products.length + 1) + '">Thông số kỹ thuật</td></tr>';
    html += '<tr class="section-header-row"><td class="title">Thông số kỹ thuật</td><td colspan="' + (products.length) + '"></td></tr>';

    specifications.forEach(specName => {
      html += '<tr class="comparison-row">';
      html += `<td class="field-name">${escapeHtml(specName)}</td>`;

      products.forEach(product => {
        if (product) {
          const specValue = product.specifications && product.specifications[specName]
              ? product.specifications[specName]
              : 'N/A';
          html += `<td class="field-value">${escapeHtml(specValue)}</td>`;
        }else{
          html += `<td class="field-value"></td>`;
        }

      });

      html += '</tr>';
    });
  }

  return html;
}

// Get product image HTML
function getProductImageHtml(imageUrls) {
  if (imageUrls ) {
    return `<img src="${escapeHtml(imageUrls)}" alt="Product Image" class="product-comparison-image">`;
  }
  return '<div class="no-image">Không có hình ảnh</div>';
}

// Initialize table interactions
function initializeTableInteractions() {
  // Sticky header
  const $table = jQuery('#comparison-table');
  const $window = jQuery(window);

  $window.on('scroll', function() {
    const scrollTop = $window.scrollTop();
    const tableTop = $table.offset().top;

    if (scrollTop > tableTop) {
      $table.addClass('sticky-header');
    } else {
      $table.removeClass('sticky-header');
    }
  });

  // Highlight differences
  highlightDifferences();

  // Add remove product functionality
  addRemoveProductButtons();
}

// Highlight differences between products
function highlightDifferences() {
  jQuery('.comparison-row').each(function() {
    const $row = jQuery(this);
    const values = [];

    $row.find('.field-value').each(function() {
      values.push(jQuery(this).text().trim());
    });

    // Check if all values are the same
    const allSame = values.every(val => val === values[0]);

    if (!allSame) {
      $row.addClass('has-differences');
    }
  });
}

// Add remove product buttons
function addRemoveProductButtons() {
  jQuery('.product-header').each(function(index) {
    const $header = jQuery(this);
    if (jQuery('.product-header').length > 1) { // Only show remove if more than 1 product
      const removeBtn = jQuery('<button class="remove-product-btn" title="Xóa sản phẩm">×</button>');
      removeBtn.on('click', function() {
        removeProductFromComparison(index);
      });
      $header.find('.product-header-content').append(removeBtn);
    }
  });
}

// Remove product from comparison
function removeProductFromComparison(productIndex) {
  const columnIndex = productIndex + 1; // +1 because first column is field names
  const sku = jQuery(`.product-header:eq(${productIndex})`).find('.product-header-content').data('sku');
  // Remove header
  // $(`.product-header:eq(${productIndex})`).remove();

  jQuery(`.product-header:eq(${productIndex})`).html(addCompare);
  // Remove all cells in this column
  jQuery('tr').each(function() {
    // $(this).find(`td:eq(${columnIndex}), th:eq(${columnIndex})`).remove();
    jQuery(this).find(`td:eq(${columnIndex})`).html('');
  });

  // Update colspan for section headers
  // $('.section-header-row td:last-child').each(function() {
  //     const currentColspan = parseInt($(this).attr('colspan'));
  //     $(this).attr('colspan', currentColspan - 1);
  // });
  let compareList = JSON.parse(localStorage.getItem('compareList')) || [];
  compareList = compareList.filter(item => item.sku !== sku);
  localStorage.setItem('compareList', JSON.stringify(compareList));
  newTitle = 'So sanh san phẩm';
  newState = compareList;
  window.history.pushState(newState, newTitle, generateCompareUrl(compareList));
  // updateCompareListUI_page(compareList);
  // Re-initialize interactions
  initializeTableInteractions();
}

// Utility functions
function showLoading() {
  jQuery('#comparison-loading').show();
  jQuery('#comparison-table-container').hide();
}

function hideLoading() {
  jQuery('#comparison-loading').hide();
  jQuery('#comparison-table-container').show();
}

function showError(message) {
  jQuery('#comparison-table-container').html(`
            <div class="comparison-error">
                <p>${message}</p>
            </div>
        `);
}

function escapeHtml(text) {
  if (typeof text !== 'string') return text;
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, function(m) { return map[m]; });
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
function loadProductComparison(skusFromUrl) {
  if (!skusFromUrl) {
    console.error('No product SKUs provided');
    return;
  }
  showLoading();
  jQuery.ajax({
    url: ThemeVars.ajaxurl,
    type: 'POST',
    data: {
      action: 'load_product_comparison',
      product_skus: skusFromUrl,
      nonce: ThemeVars.nonce
    },
    success: function (response) {
      hideLoading();
      if (response.success) {
        buildComparisonTable(response.data);
      } else {
        siteNotify('Không thể tải dữ liệu sản phẩm');
      }
    },
    error: function () {
      hideLoading();
      siteNotify('Lỗi kết nối, vui lòng thử lại');
    }
  });
};

//--------------------------------------------
function toggleButtonState(button, isDisabled = true) {
  let spinner =
    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" ></span> ';
  const $button = jQuery(button);
  const oldText = $button.data("button-text") || $button.text();
  if (!oldText) {
    $button.data("button-text", oldText);
  }
  const newContent = isDisabled ? spinner + oldText : oldText;
  $button.prop("disabled", isDisabled);
  $button.html(newContent);
}

function showLoading() {
  jQuery("#loading-overlay").show();
}

function hideLoading() {
  jQuery("#loading-overlay").fadeOut(); // Ẩn layout loading
}
function hideCompare() {
  jQuery("#compare-list").fadeOut();
  jQuery("#compare-popup-btn").fadeIn();
}
function showCompare() {
  jQuery("#compare-list").fadeIn();
  jQuery("#compare-popup-btn").fadeOut();
}
let addCompare_item =
  '            <a href="#" class="cp-plus cp-plus_new"  data-bs-toggle="modal" data-bs-target="#searchProduct_modal">\n' +
  '                <i class="bi bi-plus-lg"></i><p>${translations.add_product}</p></a>\n';
function removeCompare_old(sku) {
  jQuery('li[data-sku="' + sku + '"]')
    .html(addCompare_item)
    .attr("data-sku", "");
  // jQuery('#compare-list .listcompare')
}
function addToCompare(thisBtn) {
  let $thisBtn = jQuery(thisBtn);
  let comparePage = (typeof is_comparePage !== "undefined") ? is_comparePage : false;
  let name = $thisBtn.closest(".product-data").data("name");
  let sku = $thisBtn.closest(".product-data").data("sku");
  let image_url = $thisBtn.closest(".product-data").data("image");
  let url = $thisBtn.closest(".product-data").data("url");

  let compareList = JSON.parse(localStorage.getItem("compareList")) || [];
  if (compareList.length >= 3) {
    siteNotify(translations.max_compare);
    return;
  }
  // console.log(comparePage);
  if (compareList.length < 3 && !compareList.some((item) => item.sku === sku)) {
    compareList.push({ name, sku, image_url, url });
    localStorage.setItem("compareList", JSON.stringify(compareList));
    updateCompareListUI(compareList);
    showCompare();
  } else {
    siteNotify(translations.added_compare);
  }
  if (comparePage){
    window.location.reload();
  }
}

function removeCompare(sku) {
  console.log('remove',sku);
  let compareList = JSON.parse(localStorage.getItem("compareList")) || [];
  compareList = compareList.filter(item => String(item.sku) !== String(sku));
  localStorage.setItem("compareList", JSON.stringify(compareList));
  updateCompareListUI(compareList);
}

function updateCompareListUI(compareList, first_load = false) {
  let $listcompare = jQuery("#compare-list .listcompare");
  $listcompare.empty();
  compareList.forEach((item) => {
    let compare_item = `<li><a href="${item.url}" class=""><img src="${item.image_url}" alt=""><h3>${item.name}</h3></a><span class="remove-ic-compare" onclick="removeCompare('${item.sku}');"><i class="bi bi-x-lg"></i></span></li>`;
    $listcompare.append(compare_item);
  });

  for (let i = compareList.length; i < 3; i++) {
    $listcompare.append(`
      <li class="formsg">
        <a href="#" class="cp-plus cp-plus_new"  data-bs-toggle="modal" data-bs-target="#searchProduct_modal">
          <i class="bi bi-plus-lg"></i><p>${translations.add_product}</p>
        </a>
      </li>
    `);
  }

  let $compareButton = jQuery("#compare-list .closecompare .btn");
  if (compareList.length > 0) {
    $compareButton.prop("disabled", false);
    jQuery("#gotoCompare").attr("href", generateCompareUrl(compareList));
    // jQuery('#compare-list').fadeOut();
    // jQuery('#compare-popup-btn').fadeIn();
    jQuery("#compare-popup-btn #count-compare-item").text(
      "(" + compareList.length + ")"
    );
  } else {
    $compareButton.prop("disabled", true);
    jQuery("#compare-list").fadeOut();
    jQuery("#compare-popup-btn #count-compare-item").text("");
  }
  if (first_load && compareList.length > 0) {
    hideCompare();
  }
  updateCompareButtonStatus();
}

function RemoveAllIdCompare() {
  localStorage.removeItem("compareList");
  updateCompareListUI([]);
}

function generateCompareUrl(compareList) {
  if (!compareList || compareList.length === 0) {
    return "#";
  }

  let urlParts = ["/so-sanh/"];
  let productParts = [];

  compareList.forEach((item) => {
    productParts.push(`${createSlug(item.name)}.${item.sku}`);
  });

  // Thêm domain vào đầu URL
  let url = window.location.origin + urlParts.join("") + productParts.join(";");

  return url;
}
function siteNotify(message) {
  jQuery("#siteNotify .toast-body").text(message);
  var toast = new bootstrap.Toast(jQuery("#siteNotify")[0]);
  toast.show();
}
function isProductInCompareList(sku) {
  let compareList = JSON.parse(localStorage.getItem("compareList")) || [];
  return compareList.some((item) => item.sku === sku);
}
function updateCompareButtonStatus() {
  // console.log('updateCompareButtonStatus');
  let compareList = JSON.parse(localStorage.getItem("compareList")) || [];
  jQuery(document)
    .find(".compare-btn")
    .each(function () {
      let $thisBtn = jQuery(this);
      let sku = $thisBtn.closest(".product-data").data("sku"); // Lấy sku từ data attribute của product-data
      if (isProductInCompareList(sku)) {
        $thisBtn.html(
          `<i class="bi bi-check2-circle"></i> ${translations.added}`
        );
      } else {
        $thisBtn.html(
          `<i class="bi bi-plus-circle"></i> ${translations.compare}`
        );
      }
    });
}
function createSlug(name) {
  if (!name) {
    return "";
  }

  let slug = name.toLowerCase(); // Chuyển đổi thành chữ thường
  slug = slug.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); // Loại bỏ dấu
  slug = slug.replace(/[^a-z0-9]+/g, "-"); // Thay thế khoảng trắng và loại bỏ ký tự đặc biệt
  slug = slug.replace(/^-+|-+$/g, ""); // Loại bỏ dấu gạch ngang ở đầu và cuối chuỗi

  return slug;
}
