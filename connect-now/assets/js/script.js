jQuery(document).ready(function ($) {
    $('.calendar').pignoseCalendar({
        format: 'YYYY-MM-DD',
        select: function (date, content) {
            if (date[0] !== null) {
                $('.selected-date-show').html(date[0].format('DD-MMMM-YYYY'));
                $('#selected_date').val(date[0].format('YYYY-MM-DD'));
            }
        }
    });

    // Open Modal
    $('#open-modal').on('click', function () {
        document.body.classList.add('modal-open');
        $("#msform fieldset").hide().removeAttr("style");
        $("#msform fieldset[data-step='1']").show();
        $('#form-modal').fadeIn();
    });

    // Close Modal
    $('.close-modal').on('click', function () {
        document.body.classList.remove('modal-open');
        $('#form-modal').fadeOut();
    });
});

function showAlert() {
    alert("Form Submitted Successfully!");
    return false;
}
jQuery(document).ready(function ($) {
    var current_fs, next_fs, previous_fs;
    var left, opacity, scale;
    var animating;

    $(".next").click(function () {
        var currentStep = $(this).data('step');
        if(formValidated(currentStep)) {
            if (animating) return false;
            animating = true;

            if(currentStep == 2) {
                $('.selected-time-zone-show').html($('#timezone').val());
            }

            current_fs = $(this).closest('fieldset');
            next_fs = current_fs.next('fieldset');

            // Activate the next step on the progress bar
            $("#progressbar li").eq($("fieldset").index(next_fs)).addClass("active");

            // Show the next fieldset
            next_fs.show();
            // Hide the current fieldset with style
            current_fs.animate({
                opacity: 0
            }, {
                step: function (now, mx) {
                    // Handle animations
                    scale = 1 - (1 - now) * 0.2;
                    left = (now * 50) + "%";
                    opacity = 1 - now;
                    current_fs.css({'transform': 'scale(' + scale + ')'});
                    next_fs.css({'left': left, 'opacity': opacity});
                },
                duration: 800,
                complete: function () {
                    current_fs.hide();
                    animating = false;
                },
                easing: 'easeInOutBack'
            });
        }
    });


    $(".previous").click(function () {

        if (animating) return false;
        animating = true;

        // Get the current and previous fieldsets correctly
        current_fs = $(this).closest('fieldset');
        previous_fs = current_fs.prev('fieldset'); // Get the previous fieldset

        // Deactivate the current step on the progress bar
        $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("active");

        // Show the previous fieldset
        previous_fs.show();

        // Hide the current fieldset with animation
        current_fs.animate({
                opacity: 0
        }, {
           step: function (now, mx) {
               // Animation logic
               scale = 0.8 + (1 - now) * 0.2; // Scale effect
               left = ((1 - now) * 50) + "%"; // Slide effect
               opacity = 1 - now; // Opacity transition
               current_fs.css({'left': left});
               previous_fs.css({'transform': 'scale(' + scale + ')', 'opacity': opacity});
           },
            duration: 800,
            complete: function () {
                current_fs.hide();
                animating = false;
            },
            easing: 'easeInOutBack'
            });
    });
    function formValidated(step) {
        var validated = true;
        $('.error').remove();
        if(step == 1) {
            if($('input[name="date"]').val() == '') {
                $('.calendar').after('<span class="error">Date is required</span>');
                validated = false;
            }
        }
        if(step == 2) {
            if($('#timezone').val() == '') {
                $('#timezone').after('<span class="error">Timezone is required</span>');
                validated = false;
            }
            if(!$('input[name="time_slot"]:checked').val()) {
                $('.time-slot-container').after('<span class="error">Timeslot is required</span>');
                validated = false;
            }
        }
        if(step == 3) {
            if($('input[name="name"]').val() == '') {
                $('input[name="name"]').after('<span class="error">First name is required</span>');
                validated = false;
            }
            if($('input[name="email"]').val() == '') {
                $('input[name="email"]').after('<span class="error">Email is required</span>');
                validated = false;
            }
            if($('input[name="phone"]').val() == '') {
                $('input[name="phone"]').after('<span class="error">Phone is required</span>');
                validated = false;
            }
            if($('input[name="cname"]').val() == '') {
                $('input[name="cname"]').after('<span class="error">Company Name is required</span>');
                validated = false;
            }
            if($('textarea[name="address"]').val() == '') {
                $('textarea[name="address"]').after('<span class="error">Address is required</span>');
                validated = false;
            }
        }
        return validated;
    }

    $('#cnow_form').on('submit', function (e) {
        e.preventDefault();
        $('.cnow-submit-btn').prop('disabled', true).text('Please wait....');
        if(formValidated(3)) {
           var formData = $(this).serialize();
           $('.error').remove();
           $.ajax({
               method: 'POST',
               url: myPluginAjax.ajaxurl+'?action=save_cnow_data',
               data: formData,
               success: function (response){
                   if(response.success) {
                        Swal.fire({
                           title: 'Success',
                           text: response.data.message,
                           icon: 'success'
                        });
                       $('#form-modal').fadeOut();
                   } else {
                       if(response.data.errors) {
                           $.each(response.data.errors, function(key, value) {
                               $(`input[name="${key}"]`).after(`<span class="error">${value}</span>`);
                               $(`textarea[name="${key}"]`).after(`<span class="error">${value}</span>`);
                               $(`select[name="${key}"]`).after(`<span class="error">${value}</span>`);
                           });
                       }
                       if(response.data.error) {
                           Swal.fire({
                               title: 'Success',
                               text: response.data.error,
                               icon: 'danger'
                           });
                       }
                   }
               }, error: function (xhr, status, error) {
                   console.error('AJAX Error: ', status, error);
               }, complete: function (e) {
                   $('.cnow-submit-btn').prop('disabled', false).text('Submit');
               }
           });
        } else {
            $('.cnow-submit-btn').prop('disabled', false).text('Submit');
        }
    });
});

  
// Function to generate 24-hour time slots with 30 minutes gap
function generateTimeSlots() {
    const timeSlotsContainer = document.getElementById("time-slots");
    const startHour = 0; // Start from 00:00
    const endHour = 24; // End at 24:00
    const intervalMinutes = 15; // 15-minute interval

    for (let hour = startHour; hour < endHour; hour++) {
        for (let minute = 0; minute < 60; minute += intervalMinutes) {
            // Convert to 12-hour format
            let displayHour = hour % 12;
            if (displayHour === 0) displayHour = 12; // Handle midnight and noon

            const ampm = hour < 12 ? 'AM' : 'PM'; // Determine AM or PM

            // Format time in 12-hour format (hh:mm AM/PM)
            const formattedTime = `${displayHour.toString().padStart(2, "0")}:${minute
                .toString()
                .padStart(2, "0")} ${ampm}`;

            // Create a radio button and label dynamically
            const radioItem = document.createElement("label");
            radioItem.className = "radio-item";

            const radioInput = document.createElement("input");
            radioInput.type = "radio";
            radioInput.name = "time_slot";
            radioInput.value = formattedTime;

            const radioText = document.createElement("span");
            radioText.className = "radio-text";
            radioText.textContent = formattedTime;

            radioItem.appendChild(radioInput);
            radioItem.appendChild(radioText);
            timeSlotsContainer.appendChild(radioItem);
        }
    }
}
generateTimeSlots();
