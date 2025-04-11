jQuery(document).ready(function($) {
    function updateTax() {
        var country = $('#bcountry').val(); // Country field
        var state = $('#bstate').val(); // State field
        var level_id = new URLSearchParams(window.location.search).get('pmpro_level'); // Get membership level ID

        if (!country || !level_id) {
            return;
        }
        $('#pmpro_btn-submit').prop('disabled', true);
        $('#tax_section_message').html('<p><strong>Please wait, calculating tax...</strong></p>');
        $.ajax({
            type: 'POST',
            url: sm_pmpro_ajax.ajax_url,
            data: {
                action: 'calculate_membership_tax',
                country: country,
                state: state,
                level_id: level_id
            },
            success: function(response) {
                if (response.success) {
                    $('#tax_amount').text(response.data.tax);
                    $('#total_price').text(response.data.total);
                    $('#tax_section_message').html('')
                    $('#pmpro_btn-submit').prop('disabled', false);
                }
            },
            error: function (error) {
                $('#tax_section_message').html('<p style="color:red;"><strong>Failed to fetch tax.</strong></p>');
                $('#pmpro_btn-submit').prop('disabled', false);
            }
        });
    }

    // Trigger when country or state changes
    $('#bcountry, #bstate').on('change', updateTax);

    // Run on page load (if state is pre-selected)
    updateTax();
});
