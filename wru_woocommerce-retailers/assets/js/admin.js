jQuery(document).ready(function ($) {
    // Show retailer form
    $('#add-retailer-btn').on('click', function () {
        $('#retailer-form').slideToggle();
    });

    // Upload logo
    $('#upload-logo-btn').on('click', function (e) {
        e.preventDefault();
        let mediaUploader;

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Choose Retailer Logo',
            button: { text: 'Use this logo' },
            multiple: false
        });

        mediaUploader.on('select', function () {
            let attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#retailer-logo').val(attachment.id);
            $('#logo-preview').attr('src', attachment.url);
            $('#logo-preview-container').show();
        });

        mediaUploader.open();
    });

    // Save retailer via AJAX
    $('#save-retailer-btn').on('click', function () {
        let name = $('#retailer-name').val();
        let type = $('#retailer-type').val();
        let logo = $('#retailer-logo').val();

        if (!name || !type || !logo) {
            Swal.fire({
                title: 'Error',
                text: 'All fields are required!',
                icon: 'error',
            });
            return;
        }

        $.post(ajaxurl, { action: 'wru_add_retailer', name, type, logo }, function (response) {
            if (response.success) {
                Swal.fire({
                    title: 'Success',
                    text: response.data.message,
                    icon: 'success',
                });
                location.reload();
            } else {
                Swal.fire({
                    title: 'Error',
                    text: 'Error adding retailer.',
                    icon: 'error',
                });
            }
        });
    });

    // Delete retailer via AJAX
    $('.delete-retailer-btn').on('click', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: "Are you sure?",
            text: "you want to delete this retailer?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(ajaxurl, { action: 'wru_delete_retailer', id }, function (response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Success',
                            text: response.data.message,
                            icon: 'success',
                        });
                        location.reload();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: 'Error deleting retailer.',
                            icon: 'error',
                        });
                    }
                });
            }
        });

    });


    //product meta

    $('.add-retailer').on('click', function() {
        let type = $(this).data('type');
        let container = $('#' + type + '-retailers-container');

        let newRow = `
            <div class="retailer-row" style="margin-bottom: 5px">
                <select name="_wru_${type}_retailers[]" class="retailer-select">
                    <option value="">Select a Retailer</option>
                </select>
                <input type="url" name="_wru_${type}_retailers_urls[]" placeholder="Enter Retailer URL">
                <button type="button" class="remove-retailer dashicons-before dashicons-no-alt"></button>
            </div>
        `;

        container.append(newRow);

        let firstSelect = container.find('.retailer-select:first');
        let options = firstSelect.html();
        container.find('.retailer-select:last').html(options);
    });

    $(document).on('click', '.remove-retailer', function() {
        $(this).closest('.retailer-row').remove();
    });
});
