document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.add-recipient-button').forEach(function (button) {
        button.addEventListener('click', function () {
            const orderId = button.getAttribute('data-order-id');
            const surveyType = button.getAttribute('data-survey-type');
            const message = button.getAttribute('data-survey-message');
            let maxRecipients = surveyType === 'duo' ? 2 : 1;
            let purchaser_will_fill_the_survey = 0;

            Swal.fire({
                title: 'Add Recipient',
                html: `
                    <form id="recipient-form">
                        ${
                    surveyType === 'duo'
                        ? `
                                <div style="margin-bottom: 10px;">
                                    <label>
                                        <input type="checkbox" value="1" name="purchaser_will_fill_the_survey" id="fill-survey-checkbox"> I will fill out the survey
                                    </label>
                                </div>
                                `
                        : ''
                }
                        <div id="recipients-fields">
                            ${Array.from({ length: maxRecipients }, (_, index) => `
                                <input type="text" name="name[${index}]" placeholder="Recipient ${index + 1} Name" required><br>
                                <input type="email" name="email[${index}]" placeholder="Recipient ${index + 1} Email" required><br>
                            `).join('')}
                        </div>
                        <div style="margin-top: 5px">
                            <label>Message</label> <br>
                            <textarea name="message">${message}</textarea>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                focusConfirm: false,
                didOpen: () => {
                    if (surveyType === 'duo') {
                        const checkbox = document.querySelector('#fill-survey-checkbox');
                        checkbox.addEventListener('change', function () {
                            const recipientsFields = document.querySelector('#recipients-fields');
                            if (checkbox.checked) {
                                purchaser_will_fill_the_survey = 1;
                                maxRecipients = 1;
                                recipientsFields.innerHTML = `
                                    <input type="text" name="name[0]" placeholder="Recipient 1 Name" required><br>
                                    <input type="email" name="email[0]" placeholder="Recipient 1 Email" required><br>
                                `;
                            } else {
                                maxRecipients = 2;
                                recipientsFields.innerHTML = `
                                    ${Array.from({ length: 2 }, (_, index) => `
                                        <input type="text" name="name[${index}]" placeholder="Recipient ${index + 1} Name" required><br>
                                        <input type="email" name="email[${index}]" placeholder="Recipient ${index + 1} Email" required><br>
                                    `).join('')}
                                `;
                            }
                        });
                    }
                },
                preConfirm: () => {
                    const recipients = [];
                    for (let i = 0; i < maxRecipients; i++) {
                        const name = document.querySelector(`[name="name[${i}]"]`)?.value;
                        const email = document.querySelector(`[name="email[${i}]"]`)?.value;
                        if (name && email) {
                            recipients.push({ name, email });
                        } else {
                            Swal.showValidationMessage('Please fill out all recipient fields.');
                            return false;
                        }
                    }
                    const message = document.querySelector(`[name="message"]`).value;
                    return fetch(`${myPluginAjax.ajaxurl}?action=save_recipient`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ order_id: orderId, recipients: JSON.stringify(recipients), message: message, purchaser_will_fill_the_survey: purchaser_will_fill_the_survey }),
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Success', data.data.message, 'success');
                                window.location.reload();
                            } else {
                                Swal.fire('Error', data.data.message, 'error');
                            }
                        })
                        .catch(() => {
                            Swal.fire('Error', 'An unexpected error occurred.', 'error');
                        });
                },
            });
        });
    });

    //send survey reminder

    document.querySelectorAll('.send-survey-reminder').forEach(function (button) {
        button.addEventListener('click', function () {
            var orderId = button.getAttribute('data-order-id');
            var name = button.getAttribute('data-recipient-name');
            var email = button.getAttribute('data-recipient-email');
            Swal.fire({
                title: 'Send Survey Reminder',
                html: `
                    <form id="recipient-form">
                        <div style="margin-top: 5px">
                            <label>Message</label> <br>
                            <textarea name="recipient_message"></textarea>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                focusConfirm: false,
                preConfirm: () => {
                    var message = document.querySelector(`[name="recipient_message"]`).value;
                    if(!message) {
                        Swal.showValidationMessage('Please fill out all recipient fields.');
                    }
                    return fetch(`${myPluginAjax.ajaxurl}?action=send_survey_reminder`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ order_id: orderId, message: message, name: name, email: email}),
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Success', data.data.message, 'success');
                                window.location.reload();
                            } else {
                                Swal.fire('Error', data.data.message, 'error');
                            }
                        })
                        .catch(() => {
                            Swal.fire('Error', 'An unexpected error occurred.', 'error');
                        });
                },
            });
        });
    });
});
