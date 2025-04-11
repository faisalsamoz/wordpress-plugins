jQuery(document).ready(function ($) {

    var rewardsLink = $('a').filter(function () {
        return $(this).attr('href') && $(this).attr('href').includes("rewards");
    });

    // Reward page popup
    $(".su-rewards-page-popup").on("click", function (e) {
        e.preventDefault();
        $('#su-tag-id').val(stu_ajax.reward_tag);
        $("#su-product-image").hide().attr("src", "");

        setTimeout(function () {
            if (typeof stu_ajax.stu_reward_popup_image === "string" &&
                stu_ajax.stu_reward_popup_image.trim() !== "" &&
                /^https?:\/\/.+/.test(stu_ajax.stu_reward_popup_image)) {

                $("#su-product-image").attr("src", stu_ajax.stu_reward_popup_image).fadeIn();
            }
        }, 100);

        $("#su-popup-title").html(stu_ajax.stu_reward_popup_title);
        $("#su-popup-description").html(stu_ajax.stu_reward_popup_description);
        $("#su-popup").fadeIn();
        $('.su-popup-error').html('');
        $('.su-popup-message').html('');
        $('.su-popup-success').html('');
        $("#su-email").val('');
    });

    $("#su-close").on("click", function () {
        $("#su-popup").fadeOut();
        $('.su-popup-error').html('');
        $('.su-popup-message').html('');
        $('.su-popup-success').html('');
        $.ajax({
            url: stu_ajax.ajaxurl,
            type: "POST",
            data: {
                action: "set_popup_cookie"
            }
        });
    });

    // Product Popup Button Click
    $(".stu-popup-trigger").on("click", function (e) {
        e.preventDefault();
        var tagId = $(this).data("tag-id") || '';
        var productImage = $(this).data("image") || "";
        var productTitle= $(this).data("title") || stu_ajax.stu_default_popup_title;

        $("#su-tag-id").val(tagId);
        $("#su-product-image").hide().attr("src", "");

        setTimeout(function () {
            if (typeof productImage === "string" &&
                productImage.trim() !== "" &&
                /^https?:\/\/.+/.test(productImage)) {
                $("#su-product-image").attr("src", productImage).fadeIn();
            }
        }, 100);

        $("#su-popup-title").html(productTitle);
        $("#su-popup-description").html(stu_ajax.stu_default_popup_description);

        $("#su-popup").fadeIn();
        $('.su-popup-error').html('');
        $('.su-popup-message').html('');
        $('.su-popup-success').html('');
    });


    // ✅ Handle form submission with SweetAlert
    $("#su-submit").on("click", function () {
        let email = $("#su-email").val().trim();
        let su_tag_id = $("#su-tag-id").val().trim();
        $('.su-popup-error').html('');
        $('.su-popup-message').html('');
        $('.su-popup-success').html('');

        if (!email) {
            $('.su-popup-error').html('Please enter your email!');
            return false;
        }

        $.ajax({
            url: stu_ajax.ajaxurl,
            type: "POST",
            data: {
                action: "stu_submit_email",
                email: email,
                su_tag_id: su_tag_id,
            },
            beforeSend: function () {
                $('.su-popup-message').html('Please wait while we subscribe you...');
            },
            success: function (response) {
                if (response.success) {
                    $('.su-popup-error').html('');
                    $('.su-popup-message').html('');
                    $('.su-popup-success').html('');
                    if(response.data.login_redirect) {
                        $('.su-popup-success').html(response.data.message);
                    } else if(response.data.redirect){
                        $('.su-popup-success').html(response.data.message);
                    } else {
                        $('.su-popup-success').html(response.data.message);
                    }
                } else {
                    $('.su-popup-message').html('');
                    $('.su-popup-success').html('');
                    $('.su-popup-error').html(response.data.message);
                }
            },
            error: function () {
                $('.su-popup-message').html('');
                $('.su-popup-success').html('');
                $('.su-popup-error').html('Something went wrong. Please try again later.');
            },
        });
    });

    //footer form submit
    $("#su-footer-submit").on("click", function () {
        let footer_email = $("#su-footer-email").val().trim();
        let footer_su_tag_id = $("#su-footer-tag-id").val().trim();

        $('.su-popup-footer-error').html('');
        $('.su-popup-footer-message').html('');
        $('.su-popup-footer-success').html('');

        if (!footer_email) {
            $('.su-popup-footer-error').html('Please enter your email!')
            return false;
        }

        $.ajax({
            url: stu_ajax.ajaxurl,
            type: "POST",
            data: {
                action: "stu_submit_email",
                email: footer_email,
                su_tag_id: footer_su_tag_id,
            },
            beforeSend: function () {
                $('.su-popup-footer-message').html('Please wait while we subscribe you...');
            },
            success: function (response) {
                if (response.success) {
                    $('.su-popup-footer-error').html('');
                    $('.su-popup-footer-message').html('');
                    $('.su-popup-footer-success').html('');
                    if(response.data.login_redirect) {
                        $('.su-popup-footer-success').html(response.data.message);
                    } else if(response.data.redirect){
                        $('.su-popup-footer-success').html(response.data.message);
                    } else {
                        $('.su-popup-footer-success').html(response.data.message);
                    }
                } else {
                    $('.su-popup-footer-message').html('');
                    $('.su-popup-footer-success').html('');
                    $('.su-popup-footer-error').html(response.data.message);
                }
            },
            error: function () {
                $('.su-popup-footer-message').html('');
                $('.su-popup-footer-success').html('');
                $('.su-popup-footer-error').html('Something went wrong. Please try again later.');
            },
        });
    });

    setTimeout(function () {
        // Check if the cookie is not set and if the current URL does not contain 'rewards'
        if (document.cookie.indexOf("stu_popup_closed=1") === -1 && window.location.href.indexOf("shop") === -1) {
            $("#su-popup").fadeIn();
        }
    }, 5000);

});
