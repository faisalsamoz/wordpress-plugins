jQuery(document).ready(function ($) {
    $('#surveySaveProgressBtn').on('click', function (e) {
       $('#survey_status').val('In Progress');
        $('#questionnaire-form').submit();
    });
    $('#surveyCompleteBtn').on('click', function (e) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You want to complete this survey?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#survey_status').val('Submitted');
                $('#questionnaire-form').submit();
            } else {
                $('#survey_status').val('In Progress');
            }
        });
    });
    $('#questionnaire-form').on('submit', function (e) {
        e.preventDefault();

        let form = $(this);
        let formData = new FormData(this);

        // Disable the submit button and show a message
        form.find('button[type="button"]').prop('disabled', true).text('Saving Data...');

        $.ajax({
            url: myPluginAjax.ajaxurl+"?action=save_questionnaire",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Data submitted successfully!',
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: response.data.message,
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'An unexpected error occurred.',
                });
            },
            complete: function () {
                // Re-enable the submit button
                $('#surveySaveProgressBtn').prop('disabled', false).text('ave Progress');
                $('#surveyCompleteBtn').prop('disabled', false).text('Complete Survey');
            }
        });
    });
});
