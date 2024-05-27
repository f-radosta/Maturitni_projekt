
const validation = new JustValidate('#signup', {validateBeforeSubmitting: true});

validation
    .addField('#name', [
        {
            rule: 'required'
        },
        {
            validator: (value) => {
                return value.length <= 12;
            },
            errorMessage: 'Jméno musí mít maximálně 12 znaků.'
        }
    ])
    .addField('#email', [
        {
            rule: "required",
            errorMessage: "Email je povinný."
        },
        {
            rule: "email",
            errorMessage: "Email má nesprávný formát."
        },
        {
            validator: (value) => () => {
                return fetch("php/validate_email.php?email=" + 
                encodeURIComponent(value))
                        .then(function(response) {
                            return response.json();
                        })
                        .then(function(json) {
                            return json.available;
                        });
            },
            errorMessage: "Email je obsazený."
        }
    ])
    .addField('#password', [
        {
            rule: "required",
            errorMessage: "Heslo je povinné."
        },
        {
            rule: "password",
            errorMessage: "Heslo musí obsahovat minimálně 8 znaků a z toho alespoň jedno písmeno a číslo."
        }
    ])
    .addField('#password_confirmation', [
        {
            validator: (value, fields) => {
                return value === fields['#password'].elem.value;
            },
            errorMessage: "Hesla se musí shodovat"
        }
    ])
    .onSuccess((event) => {
        event.preventDefault();

        const signupForm = document.querySelector("#signup");
        const formData = new FormData(signupForm);
        const captchaResponse = grecaptcha.getResponse();

        if (!captchaResponse.length > 0) {
            document.querySelector('#errorMsg').innerText = 'Vyplňte prosím captcha test.';
            document.querySelector('#errorMsg').style.display = 'block';
        } else {
            fetch('php/signup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.successLogin) {
                    window.location.href = "user_page.html";
                } else if (data.success && !data.successLogin) {
                    window.location.href = 'login.html?login_failed=true';
                } else {
                    document.querySelector('#errorMsg').innerText = 'Chyba: ' + data.message;
                    document.querySelector('#errorMsg').style.display = 'block';
                }
            })
            .catch(error => {
                console.error("Error:", error.message);
                document.querySelector('#errorMsg').innerText = 'Chyba, zkuste to znovu.';
                document.querySelector('#errorMsg').style.display = 'block';
            });
        }
    });
