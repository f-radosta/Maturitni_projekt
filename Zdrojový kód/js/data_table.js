
function run() {

    const search = document.querySelector('.input-group input'),
        table_rows = document.querySelectorAll('tbody tr'),
        table_headings = document.querySelectorAll('thead th');

    // hledani
    search.addEventListener('input', searchTable);

    function searchTable() {
        table_rows.forEach((row, rowIndex) => {
            const tableData = row.textContent.toLowerCase();
            const searchData = search.value.toLowerCase();

            // podminka pro viditelnost
            const isVisible = tableData.includes(searchData);
            row.classList.toggle('hide', !isVisible);
           
            row.style.setProperty('--delay', `${rowIndex / 25}s`);
        });

        // zmena pozadi aby bylo kazdy druhy tmavsi
        document.querySelectorAll('tbody tr:not(.hide)').forEach((visibleRow, visibleIndex) => {
            const backgroundColor = (visibleIndex % 2 === 0) ? 'transparent' : '#0000000b';
            visibleRow.style.backgroundColor = backgroundColor;
        });
    }

    table_headings.forEach((heading, index) => {
        let sortAscending = true;
    
        heading.onclick = () => {
            // reset
            table_headings.forEach(head => head.classList.remove('active'));
            heading.classList.add('active');
    
            // vsude active pryc
            document.querySelectorAll('td').forEach(td => td.classList.remove('active'));
            // active pod aktivnim headingem
            table_rows.forEach(row => {
                row.querySelectorAll('td')[index].classList.add('active');
            });
    
            heading.classList.toggle('asc', sortAscending);
            sortAscending = !heading.classList.contains('asc');
            sortTable(index, sortAscending);
        };
    });

    function sortTable(column, sort_asc) {
        [...table_rows].sort((a, b) => {
            let first_row = a.querySelectorAll('td')[column].textContent.toLowerCase(),
                second_row = b.querySelectorAll('td')[column].textContent.toLowerCase();

            return sort_asc ? (first_row < second_row ? 1 : -1) : (first_row < second_row ? -1 : 1);
        })
            .map(sorted_row => document.querySelector('tbody').appendChild(sorted_row));
    }

    // PDF
    const tlacitkoPDF = document.querySelector('#toPDF');
    const tabulkaDat = document.querySelector('#data_table');

    const vytvorPDF = function (tabulka) {
        // HTML kód pro nové okno, včetně stylů
        const htmlKod = `
            <!DOCTYPE html>
            <html lang="cs">
            <head>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/light.css">
                <link rel="stylesheet" href="css/header.css">
                <link rel="stylesheet" type="text/css" href="css/table.css">
            </head>
            <body>
                <main class="table" id="data_table">${tabulka.innerHTML}</main>
            </body>
            </html>`;

        // Otevření nového okna a vložení HTML kódu
        const noveOkno = window.open();
        noveOkno.document.write(htmlKod);

        // Vytisknutí obsahu okna po krátké pauze a následné zavření
        setTimeout(() => {
            noveOkno.print();
            noveOkno.close();
        }, 400);
    }

    tlacitkoPDF.onclick = () => {
        vytvorPDF(tabulkaDat);
    }

    // JSON

    const tlacitkoJson = document.querySelector('#toJSON');

    const toJson = function (tabulka) {
        let dataTabulky = [],
            hlavickyTab = [],
        
            hlavicky = tabulka.querySelectorAll('th'),
            radky = tabulka.querySelectorAll('tbody tr');
        
        for (let hlavicka of hlavicky) {
            let aktualniHlavicka = hlavicka.textContent.trim().split(' ');
        
            // Odstranění šipky z názvu sloupce a přidání do hlavickyTab
            hlavickyTab.push(aktualniHlavicka.splice(0, aktualniHlavicka.length - 1).join(' ').toLowerCase());
        }
        
        radky.forEach(radek => {
            const objektRadku = {},
                bunky = radek.querySelectorAll('td');
        
            // Přiřazení hodnot buněk do objektu podle hlaviček sloupců
            bunky.forEach((bunka, indexBunky) => {
                objektRadku[hlavickyTab[indexBunky]] = bunka.textContent.trim();
            })
            dataTabulky.push(objektRadku);
        })

        return JSON.stringify(dataTabulky, null, 4);
    }
        
    tlacitkoJson.onclick = () => {
        const json = toJson(data_table);
        downloadFile(json, 'json', 'tabulka_JSON');
    }

    //CSV

    const tlacitkoCSV = document.querySelector('#toCSV');

    const prevodNaCSV = function (tabulka) {
        // Získání nadpisů sloupců z <th> elementů
        const nadpisy = Array.from(tabulka.querySelectorAll('th')).map(hlavicka => {
            return hlavicka.textContent.trim().split(' ').slice(0, -1).join(' ').toLowerCase();
        }).join(',');

        // Zpracování řádků tbody a převod buněk na CSV formát
        const dataTabulky = Array.from(tabulka.querySelectorAll('tbody tr')).map(radek => {
            return Array.from(radek.querySelectorAll('td'))
                .map(bunka => bunka.textContent.replace(/,/g, '.').trim()).join(',');
        }).join('\n');

        return nadpisy + '\n' + dataTabulky;
    }

    tlacitkoCSV.onclick = () => {
        const csvData = prevodNaCSV(data_table);
        downloadFile(csvData, 'csv', 'tabulka_CSV');
    }

    // EXCEL

    const excel_btn = document.querySelector('#toEXCEL');

    const toExcel = function (table) {
        const t_heads = table.querySelectorAll('th'),
              tbody_rows = table.querySelectorAll('tbody tr');
    
        // Vytvoření záhlaví pro Excel soubor bez 'image name'
        const headings = [...t_heads].map(head => head.textContent.trim().split(' ').slice(0, -1).join(' ').toLowerCase()).join('\t');
    
        // Příprava dat z řádků tabulky
        const table_data = [...tbody_rows].map(row => {
            return Array.from(row.querySelectorAll('td')).map(cell => cell.textContent.trim()).join('\t');
        }).join('\n');
    
        return headings + '\n' + table_data;
    }
    
    excel_btn.onclick = () => {
        const excelData = toExcel(data_table);
        downloadFile(excelData, 'excel', 'tabulka_EXCEL');
    }
    
    const downloadFile = function (data, fileType, fileName = '') {
        const a = document.createElement('a');
        a.download = fileName;
        const mime_types = {
            'json': 'application/json',
            'csv': 'text/csv',
            'excel': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        }
        a.href = `data:${mime_types[fileType]};charset=utf-8,${encodeURIComponent(data)}`;
        document.body.appendChild(a);
        a.click();
        a.remove();
    }
    

}

run();

function initializeTableFeatures() {

    run();

}

window.initializeTableFeatures = initializeTableFeatures;

