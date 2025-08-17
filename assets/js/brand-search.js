jQuery(document).ready(function($) {
    var searchInput = $('#quickSearch_brand_input');
    var searchResults = $('#quickSearch_brand_results');
    var typingTimer;
    var doneTypingInterval = 300; // time in ms (300ms)
    var nonce = vcl_brand_search.nonce;

    // On keyup, start the countdown
    searchInput.on('keyup', function() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(performSearch, doneTypingInterval);
    });

    // On keydown, clear the countdown
    searchInput.on('keydown', function() {
        clearTimeout(typingTimer);
    });

    // User is "finished typing," do something
    function performSearch() {
        var query = searchInput.val().trim();

        if (query.length < 2) {
            searchResults.hide().empty();
            return;
        }

        searchResults.show().html('<li>' + vcl_brand_search.searching_text + '</li>');

        $.ajax({
            url: vcl_brand_search.ajax_url,
            type: 'POST',
            data: {
                action: 'vcl_brand_quick_search',
                s: query,
                nonce: nonce
            },
            success: function(response) {
                if (response) {
                    searchResults.html(response);
                } else {
                    searchResults.html('<li>' + vcl_brand_search.no_results_text + '</li>');
                }
            },
            error: function() {
                searchResults.html('<li>' + vcl_brand_search.error_text + '</li>');
            }
        });
    }

    // Hide results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.brand_search').length) {
            searchResults.hide().empty();
        }
    });
});
