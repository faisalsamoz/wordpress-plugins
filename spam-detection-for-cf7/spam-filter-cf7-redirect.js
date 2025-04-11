document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('wpcf7submit', function (event) {
        if (event.detail.apiResponse && event.detail.apiResponse.redirect_url) {
            window.location.href = event.detail.apiResponse.redirect_url;
        }
    }, false);
});
