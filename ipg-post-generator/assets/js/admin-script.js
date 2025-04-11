jQuery(document).ready(function($) {
    var mediaUploader;

    $('#openImagePopup').on('click', function() {
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }


        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Select Image'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            var imageUrl = attachment.url;
            var imageID = attachment.id;

            // Display the selected image
            $('#selectedImageContainer').html('<img src="' + imageUrl + '" style="max-width: 200px; height: auto;">');
            $('#generatePostButton').data('image-id', imageID);
            $('#generatePostButton').data('image-url', imageUrl);
            $('#generatePostButton').show();
            $('.input-tags-group').show();
            $('.errors').html('');
        });

        mediaUploader.open();
    });

    $('#generatePostButton').on('click', function() {
        var imageUrl = $(this).data('image-url');
        var imageId = $(this).data('image-id');
        var imageTags = $('#input_tags').val();
        $('#generatePostButton').prop('disabled', true).text('Generating Post');
        $('#close_popup_button').prop('disabled', true);
        $('#openImagePopup').prop('disabled', true);
        $('.ipg-message').show();
        $('#ipg_post_loader').show();
        $('.errors').html('');
        if (imageUrl && imageId) {
            // Send AJAX request to create a post
            $.ajax({
                url: myPluginAjax.ajaxurl+"?action=generate_post_from_image",
                type: 'POST',
                data: {
                    action: 'generate_post_from_image',
                    image_url: imageUrl,
                    image_id: imageId,
                    input_tags: imageTags
                },
                success: function(response) {
                    console.log(response);
                    if (response.success) {
                        $('#image_post_generator_popup').fadeOut();
                        Swal.fire({
                            text: response.data.message,
                            icon: 'success',
                            showCancelButton: true,
                            showDenyButton: true,
                            cancelButtonText: 'Continue',
                            confirmButtonText: 'Check Logs',
                            denyButtonText: 'Close',
                            reverseButtons: false,
                            customClass: {
                                actions: 'ipg-swal-actions',
                                cancelButton: 'order-1',
                                confirmButton: 'order-2',
                                denyButton: 'order-3',
                            },
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = myPluginAjax.ipg_logs_url;
                            } else if (result.dismiss === Swal.DismissReason.cancel) {
                                $('#generatePostButton').hide();
                                $('.input-tags-group').hide();
                                $('.errors').html('');
                                $('#input_tags').val("");
                                $('#selectedImageContainer').html('');
                                $('#image_post_generator_popup').fadeIn();
                                Swal.close();
                            } else if (result.isDenied) {
                                $('#generatePostButton').hide();
                                $('.input-tags-group').hide();
                                $('.errors').html('');
                                $('#input_tags').val("");
                                $('#selectedImageContainer').html('');
                                $('#image_post_generator_popup').fadeOut();
                                $('.ipg-message').hide();
                                $('#ipg_post_loader').hide();
                            }
                        });
                    } else {
                        if(response.data.errors) {
                            $.each(response.data.errors, function(key, value) {
                                $('.errors').append(`<li>${value}</li>`);
                            });
                        }
                        if(response.data.error) {
                            $('.errors').append(`<li>${response.data.error}</li>`);
                        }
                    }
                },
                error: function(error) {
                    Swal.fire('Error', 'There was an error generating the post.', 'error');
                    console.error(error);
                },
                complete: function () {
                    $('#generatePostButton').prop('disabled', false).text('Generate Post');
                    $('#close_popup_button').prop('disabled', false);
                    $('#openImagePopup').prop('disabled', false);
                    $('.ipg-message').hide();
                    $('#ipg_post_loader').hide();
                }
            });
        } else {
            alert('Please select an image first.');
        }
    });

    // Show the popup
    $('#image_post_generator_button').on('click', function() {
        $('#image_post_generator_popup').fadeIn();
    });

    // Close the popup
    $('#close_popup_button').on('click', function() {
        $('#generatePostButton').hide();
        $('.input-tags-group').hide();
        $('.errors').html('');
        $('#input_tags').val("");
        $('#selectedImageContainer').html('');
        $('#image_post_generator_popup').fadeOut();
        $('.ipg-message').hide();
        $('#ipg_post_loader').hide();
    });
});
