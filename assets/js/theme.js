const themeToggle = document.getElementById('themeToggle');
const html = document.documentElement;
const sunIcon = document.getElementById('sunIcon');
const moonIcon = document.getElementById('moonIcon');

// Check if dark mode is enabled (default is dark)
const isDark = html.classList.contains('dark');
updateIcons(isDark);

themeToggle.addEventListener('click', () => {
    html.classList.toggle('dark');
    const isNowDark = html.classList.contains('dark');
    updateIcons(isNowDark);
});

function updateIcons(isDarkMode) {
    if (isDarkMode) {
        sunIcon.classList.add('hidden');
        moonIcon.classList.remove('hidden');
    } else {
        sunIcon.classList.remove('hidden');
        moonIcon.classList.add('hidden');
    }
}

$(document).ready(function ($) {
    // Collapsible example boxes functionality
    $('.example-toggle').on('click', function () {
        const targetId = $(this).data('target');
        const $content = $('#' + targetId);
        const $arrow = $(this).find('.arrow-icon');

        // Prevent multiple rapid clicks
        if ($content.is(':animated')) {
            return;
        }

        if ($content.hasClass('hidden')) {
            // Show content with smooth animation
            $content.removeClass('hidden').css('display', 'none').slideDown({
                duration: 500,
                easing: 'swing',
                complete: function () {
                    $(this).css('display', '');
                }
            });
            $arrow.addClass('rotate-90');
        } else {
            // Hide content with smooth animation
            $content.slideUp({
                duration: 500,
                easing: 'swing',
                complete: function () {
                    $(this).addClass('hidden');
                }
            });
            $arrow.removeClass('rotate-90');
        }
    });
});

// Alert Team Manager Modal functionality
document.addEventListener('DOMContentLoaded', function () {
    const alertTeamBtn = document.getElementById('alert-team-modal');
    const alertModal = document.getElementById('alert-modal');
    const closeModalBtn = document.getElementById('close-modal');
    const cancelAlertBtn = document.getElementById('cancel-alert');

    // Show modal when alert team button is clicked
    alertTeamBtn.addEventListener('click', function () {
        alertModal.classList.remove('hidden');
    });

    // Hide modal when close button is clicked
    closeModalBtn.addEventListener('click', function () {
        alertModal.classList.add('hidden');
    });

    // Hide modal when cancel button is clicked
    cancelAlertBtn.addEventListener('click', function () {
        alertModal.classList.add('hidden');
    });

    // Close modal when clicking outside
    alertModal.addEventListener('click', function (e) {
        if (e.target === alertModal) {
            alertModal.classList.add('hidden');
        }
    });
});