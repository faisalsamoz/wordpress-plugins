jQuery(document).ready(function($) {
    var $grid = $('.yarpp-related-posts');
    $grid.masonry({
        itemSelector: '.masonry-item',
        columnWidth: '.masonry-sizer',
        percentPosition: true,
        horizontalOrder: false,
        fitWidth: false
    });

    $grid.imagesLoaded().progress(function() {
        $grid.masonry('layout');
    });
    let msnry = $grid.data('masonry');
    // Recalculate layout on window resize
    $(window).resize(function() {
        msnry.layout();  // Recalculate and re-layout the items
    });

    // var $container = $('.yarpp-related-posts');
    var postId = $('#load-more-yarpp').data('postid');
    var fallback = false; // Track whether we're in fallback mode
    var fallbackSwitched = false; // Track if fallback has already been switched

    $grid.infiniteScroll({
        path: function () {
            var currentPage = parseInt($('#load-more-yarpp').data('page'));
            return (
                yarpp_ajax_obj.ajax_url +
                '?action=load_more_yarpp&post_id=' +
                postId +
                '&page=' +
                currentPage +
                '&fallback=' +
                fallback
            );
        },
        outlayer: msnry,
        // append: '.masonry-item',
        append: false,
        history: false,
        status: '.infinite-scroll-status',
        loadOnScroll: true,
        scrollThreshold: 200,
        responseBody: 'json',
        checkLastPage: true
    });

    $grid.on('load.infiniteScroll', function (event, response) {
        var button = $('#load-more-yarpp');
        var newPage = button.data('page') + 1;
        button.data('page', newPage);

        if(response.success) {
            let $items = $( response.data.html );
            $items.imagesLoaded( function() {
                $grid.append( $items ).masonry( 'appended', $items );
            })
        } else {
            $('.scroll-last').show();
            let $items = $(response.data);
            $items.imagesLoaded( function() {
                $grid.append( $items ).masonry( 'appended', $items );
            });
        }
        if (response.success && response.data.fallback == "true" && !fallbackSwitched) {
            fallback = true; // Switch to loading all posts
            fallbackSwitched = true; // Prevent further resets
            button.data('page', 1); // Reset the page only once
        }
    });

    $grid.on('last.infiniteScroll', function (event, body, path) {
        $('.infinite-scroll-request').hide();
    });
});