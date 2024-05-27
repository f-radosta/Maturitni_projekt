
$(document).ready(function() {
    fetch('php/check_role.php?role=admin')
        .then(response => response.json())
        .then(data => {
            if (!data.hasRole) {
                window.location.href = 'login.html?insufficient_role=true';
            }
        })
        .catch(error => console.error('Error:', error));

    $('.wheel').addClass('disabled');

    $("#loadBtn").click(function() {
        $('.wheel').removeClass('disabled');
        let formData = new FormData($('#uploadForm')[0]);
        $.ajax({
            url: 'php/db_manager.php',
            type: 'POST',
            data: formData,
            success: function(data) {
                $('#feedback').html(data);
                $('.wheel').addClass('disabled');
            },
            cache: false,
            contentType: false,
            processData: false
        });
    });

});

document.addEventListener('DOMContentLoaded', function () {
    const fetchUsers = () => {
        fetch('php/get_users.php')
            .then(response => response.json())
            .then(users => {
                const tbody = document.querySelector('#userTable tbody');
                tbody.innerHTML = '';
                users.forEach(user => {
                    const row = tbody.insertRow();
                    row.innerHTML = `
                        <td>${user.user_name}</td>
                        <td>${user.email}</td>
                        <td>
                            <button class="editBtn" data-user-id="${user.id}">Upravit</button>
                            <button class="deleteBtn" data-user-id="${user.id}">Smazat</button>
                        </td>`;
                });

                document.querySelectorAll('.editBtn').forEach(button => {
                    button.addEventListener('click', function() {
                        const userId = this.getAttribute('data-user-id');

                        fetch(`php/get_user_details.php?user_id=${userId}`)
                            .then(response => response.json())
                            .then(user => {
                                document.querySelector('#editUserId').value = user.id;
                                document.querySelector('#passwordHash').value = user.password_hash;
                                document.querySelector('#editUsername').value = user.user_name;
                                document.querySelector('#editEmail').value = user.email;
                                document.querySelector('#editPassword').value = user.password_hash;
                                document.querySelector('#editPasswordConfirmation').value = user.password_hash;
                            })
                            .catch(error => console.error('Error:', error));
                    });
                });
                

                document.querySelectorAll('.deleteBtn').forEach(button => {
                    button.addEventListener('click', function() {
                        const userId = this.getAttribute('data-user-id');
                
                        if (confirm('Opravdu chcete smazat tohoto uživatele?')) {
                            fetch(`php/delete_user.php?user_id=${userId}`, { method: 'POST' })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        fetchUsers();
                                    } else {
                                        alert('Chyba při mazání uživatele.');
                                    }
                                })
                                .catch(error => console.error('Error:', error));
                        }
                    });
                });
                
            })
            .catch(error => console.error('Error:', error));
    };

    setInterval(fetchUsers, 60000);
    fetchUsers();

    document.querySelector('#editUserForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);

        let username = formData.get('username');
        let email = formData.get('email');
        let passwordHash = formData.get('passwordHash');
        let password = formData.get('password');
        let passwordUnchanged = areStringsEqual(passwordHash, password);
        let message = `Nová úprava.
            Uživatelské jméno: ${username}
            Email: ${email}
            ${passwordUnchanged ? "Heslo se měnit nebude." : "Heslo se změní.\nOpravdu chcete pokračovat?"}`;

        if (confirm(message)) {

            fetch('php/update_user.php', {
            method: 'POST',
            body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('#errorMsgEdit').style.display = 'none';
                    alert('Uživatel byl úspěšně upraven.');
                    fetchUsers();
                } else {
                    document.querySelector('#errorMsgEdit').innerText = data.error;
                    document.querySelector('#errorMsgEdit').style.display = 'block';
                }
            })
            .catch(error => console.error('Error:', error));
        }
    });

    document.querySelector('#addUserForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);

        if (
            confirm("Nový uživatel.\nUživatelské jméno: " + formData.get('username') + 
            "\nEmail: " + formData.get('email') + 
            "\nHeslo: " + formData.get('password') + 
            "\nOpravdu chcete pokračovat?"
            )
        ) {
            fetch('php/update_user.php', {
            method: 'POST',
            body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('#errorMsgAdd').style.display = 'none';
                    alert('Uživatel byl úspěšně přidán.');
                    fetchUsers();
                } else {
                    document.querySelector('#errorMsgAdd').innerText = data.error;
                    document.querySelector('#errorMsgAdd').style.display = 'block';
                }
            })
            .catch(error => console.error('Error:', error));
        }
    });
    
});

function areStringsEqual(str1, str2) {
    if (str1.length !== str2.length) {
        return false;
    }
    for (let i = 0; i < str1.length; i++) {
        if (str1.charCodeAt(i) !== str2.charCodeAt(i)) {
            return false;
        }
    }
    return true;
}