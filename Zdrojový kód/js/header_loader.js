
document.addEventListener('DOMContentLoaded', function() {
    fetch('header.html')
        .then(response => response.text())
        .then(data => {
            document.querySelector('#header-placeholder').innerHTML = data;
            loadMobileNav();
            fetch('php/get_username.php')
                .then(response => response.text())
                .then(username => {
                    if (username != '') {
                        document.querySelector('#username-placeholder').textContent += username;

                        fetch('php/check_role.php?role=admin')
                            .then(response => response.json())
                            .then(data => {
                                if (data.hasRole) {
                                    document.querySelector('nav > ul').innerHTML += '<li><a href="admin_page.html">Admin panel</a></li>';
                                }
                            })
                            .catch(error => console.error('Error:', error));
                            
                    } else {
                        document.querySelector('#username-placeholder').textContent = '';

                        if (
                            window.location.pathname.endsWith('index.html') || window.location.pathname === '/' ||
                            window.location.pathname.endsWith('login.html') || window.location.pathname.endsWith('login') ||
                            window.location.pathname.endsWith('signup.html') || window.location.pathname.endsWith('signup') ||
                            window.location.pathname.endsWith('logout.html') || window.location.pathname.endsWith('logout')
                        ) {
                            modifyHeaderForIndex();
                        }
                    }
                });
        })
        .catch(error => console.error('Error loading the header:', error));
});

function modifyHeaderForIndex() {
    document.querySelector('#nav1').innerHTML = '<a href="login.html">Přihlásit se</a>';
    document.querySelector('#nav2').innerHTML = '<a href="signup.html">Registrovat se</a>';
}

function loadMobileNav() {
    const menu = document.querySelector('.menu');
    const close = document.querySelector('.close');
    const nav = document.querySelector('nav');

    menu.addEventListener('click', () => {
        nav.classList.add('open-nav');
    });
    close.addEventListener('click', () => {
        nav.classList.remove('open-nav');
    });
}