jQuery(document).ready(function($) {
    $('.download-word-doc').on('click', function(e) {
        e.preventDefault();
        let downloadButton = $(this);
        downloadButton.prop('disabled', true).text('Downloading...');
        var orderId = $(this).data('order-id');

        $.ajax({
            url: myPluginAjax.ajaxurl + "?action=download_order_word_doc",
            type: 'POST',
            data: {
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    var fileData = response.data.fileContent;
                    var filename = response.data.filename;

                    // Create a Blob object from the base64 string
                    var byteCharacters = atob(fileData);
                    var byteArray = new Uint8Array(byteCharacters.length);
                    for (var i = 0; i < byteCharacters.length; i++) {
                        byteArray[i] = byteCharacters.charCodeAt(i);
                    }
                    var blob = new Blob([byteArray], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' });

                    // Create a temporary URL for the Blob
                    var url = URL.createObjectURL(blob);

                    // Create a temporary link element
                    var link = document.createElement('a');
                    link.href = url;
                    link.download = filename;

                    // Simulate a click on the link to trigger the download
                    link.click();

                    // Revoke the URL to release memory
                    URL.revokeObjectURL(url);
                } else {
                    // Handle errors in the AJAX response
                    console.error(response.data);
                }
            },
            error: function(error) {
                // Handle errors, e.g., display an error message
                console.error(error);
            },
            complete: function() {
                downloadButton.prop('disabled', false).text('Download Word Doc')
            }

        });
    });
    $('.upload-survey-pdf').on('click', function() {
        var orderId = $(this).data('order-id');
        var fileInput = $('<input type="file" accept="application/pdf" />');
        var button = $(this);
        button.prop('disabled', true);

        fileInput.on('change', function() {
            button.prop('disabled', true);
            var formData = new FormData();
            formData.append('action', 'upload_survey_pdf');
            formData.append('order_id', orderId);
            formData.append('pdf_file', this.files[0]);

            $.ajax({
                url: myPluginAjax.ajaxurl + "?action=upload_survey_pdf",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('File uploaded successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                    button.prop('disabled', false);
                },
                error: function() {
                    alert('An error occurred while uploading the file.');
                    button.prop('disabled', false);
                }, complete() {
                    button.prop('disabled', false);
                }
            });
        });

        fileInput.trigger('click');
        setTimeout(function() {
            if (!fileInput[0].files.length) {
                button.prop('disabled', false);
            }
        }, 500);
    });
});
