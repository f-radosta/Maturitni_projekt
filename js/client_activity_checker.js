let logoutTimer;
const TIMEOUT_IN_MS = 15 * 60 * 1000; // 10 minutes in milliseconds

function resetLogoutTimer() {
    clearTimeout(logoutTimer);
    logoutTimer = setTimeout(() => {
        window.location.href = 'php/logout.php';
    }, TIMEOUT_IN_MS);
}

document.addEventListener('mousemove', resetLogoutTimer);
document.addEventListener('keypress', resetLogoutTimer);
document.addEventListener('click', resetLogoutTimer);

resetLogoutTimer();
