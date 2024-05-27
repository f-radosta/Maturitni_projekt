
document.addEventListener('DOMContentLoaded', function() {
    fetch('php/check_role.php?role=user')
        .then(response => response.json())
        .then(data => {
            if (!data.hasRole) {
                window.location.href = 'login.html?insufficient_role=true';
            }
        })
        .catch(error => console.error('Error:', error));

    document.querySelector('#submitButton').addEventListener('click', function() {
        let formData = new FormData();
        formData.append('category', document.querySelector('#categoryInput').value);
        formData.append('city', document.querySelector('#cityInput').value);
        formData.append('radius', document.querySelector('#radiusInput').value);
        formData.append('email', document.querySelector('#email').checked ? 1 : 0);
        formData.append('phone', document.querySelector('#phone').checked ? 1 : 0);
        formData.append('website', document.querySelector('#website').checked ? 1 : 0);
        formData.append('sameCity', document.querySelector('#sameCity').checked ? 1 : 0);

        fetch('php/processUserRequest.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            if (Object.keys(data).length == 0) {
                alert('Nebyl nalezen žádný výsledek.');
            }
            // Access the iframe document
            let iframeDocument = document.querySelector('#iframe').contentDocument;

            let tbody = iframeDocument.querySelector('tbody');
            tbody.innerHTML = '';

            // Append data rows
            data.forEach(obj => {
                let row = tbody.insertRow();
                let address = `${obj.housenumber} ${obj.street}, ${obj.postcode}, ${obj.city}, ${obj.country}, ${obj.suburb}`;
                [obj.name, obj.email, obj.phone, obj.website, obj.latitude, obj.longitude, address].forEach(text => {
                    let cell = row.insertCell();
                    cell.textContent = text;
                });
            });

            let table = iframeDocument.querySelector('table');
            table.appendChild(tbody);

            let iframeWindow = document.querySelector('#iframe').contentWindow;
            if (iframeWindow.initializeTableFeatures) {
                iframeWindow.initializeTableFeatures();
            } else {
                console.error('Funkce initializeTableFeatures není definovaná v iframe.');
            }

        })
        .catch(error => {
            console.error("Fetch error: ", error);
        });
    });
});

function suggestCategories() {
    let input = $('#categoryInput').val();
    if(input.length > 1) {
        $.ajax({
            url: 'php/suggest_categories.php',
            type: 'POST',
            data: { 'input': input },
            success: function(data) {
                $('#categoryList').html(data);
            }
        });
    }
}

function suggestCities() {
    let input = $('#cityInput').val();
    if(input.length > 1) {
        $.ajax({
            url: 'php/suggest_cities.php',
            type: 'POST',
            data: { 'input': input },
            success: function(data) {
                $('#cityList').html(data);
            }
        });
    }
}
