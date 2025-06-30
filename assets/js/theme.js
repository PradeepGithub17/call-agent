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