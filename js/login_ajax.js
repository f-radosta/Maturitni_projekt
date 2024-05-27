window.onload = function() {
    let urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('insufficient_role') === 'true') {
        alert('Chyba: nedostatečná práva.');
    }
    if(urlParams.get('login_failed') === 'true') {
        alert('Chyba: automatické přihlášení selhalo.');
    }
};

document.querySelector('#loginForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const captchaResponse = grecaptcha.getResponse();

    if (!captchaResponse.length > 0) {
        document.querySelector('#errorMsg').innerText = 'Vyplňte prosím captcha test.';
        document.querySelector('#errorMsg').style.display = 'block';
    } else {
        const formData = new FormData(this);

        fetch('php/login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = "user_page.html";
            } else {
                document.querySelector('#errorMsg').innerText = data.message;
                document.querySelector('#errorMsg').style.display = 'block';
                document.querySelector('#password').value = '';
                grecaptcha.reset();
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Chyba, zkuste to znovu.");
        });
    }
});