<?php
declare(strict_types=1);

set_error_handler(function ($severity, $message, $file, $line) {
    // Zkontroluje, zda je chyba varováním
    if ($severity === E_WARNING) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    // Zkontroluje, zda se jedná o chybu jinak vrati false, aby pokracoval v normálním zpracování chyb jiných typů.
    return false;
});

require __DIR__ . "/session_check.php";
$pdo = require __DIR__ . "/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    try {
        if (!checkSessionAndRole('admin')) {
            exit;
        }
        
        $lock_file = "../json_data/objects.json";

        if (file_exists($lock_file)) {
            echo "Jiné nahrávání právě probíhá, zkuste to prosím později.";
            exit;
        }

        if (isset($_FILES['jsonFile'])) {
            $fileInfo = $_FILES['jsonFile'];
            $uploadMessage = uploadJsonFile($fileInfo);
            if ($uploadMessage) {
                echo("Soubor se úspěšně nahrál.");
            } else {
                echo("Operace byla zrušena.");
                exit;
            }
        } else {
            echo "Žádný soubor nebyl nahrán.";
            exit;
        }

        if (file_exists($lock_file)) {
            backupDatabase($pdo);
            $deleteMessage = deleteAllData();
            $insertMessage = insertJsonIntoDatabase($lock_file);
            $assignMessage = assignNearestCity();
            $deleteFileMessage = deleteJson($lock_file);
            $finalMessage = "<br>" . $deleteMessage . "<br>" . $insertMessage . "<br>" . $assignMessage . "<br>" . $deleteFileMessage;
            echo $finalMessage;
        } else {
            echo "Musíte nejdříve nahrát soubor.";
        }

    } catch (Exception $e) {
        echo "Chyba: " . $e->getMessage();
        if (file_exists($lock_file)) {
            deleteJson($lock_file);
        }
        if (restoreDatabase($pdo)) {
            echo("<br>Databáze se obnovila z kopie před operací.");
        }
    } finally {
        $pdo = null;
    }
}

function backupDatabase($pdo) {
    $backupTables = ['object', 'address', 'categories'];

    foreach ($backupTables as $table) {
        $backupTable = $table . '_backup';
        $pdo->exec("DROP TABLE IF EXISTS `$backupTable`");
        $pdo->exec("CREATE TABLE `$backupTable` LIKE `$table`");
        $pdo->exec("INSERT INTO `$backupTable` SELECT * FROM `$table`");
    }
}


function restoreDatabase($pdo) {
    $backupTables = ['object', 'address', 'categories'];

    try {
        foreach ($backupTables as $table) {
            $backupTable = $table . '_backup';
            $pdo->exec("TRUNCATE TABLE `$table`");
            $pdo->exec("INSERT INTO `$table` SELECT * FROM `$backupTable`");
            $pdo->exec("DROP TABLE `$backupTable`");
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function deleteJson($file_pointer) {
    if (!unlink($file_pointer)) { 
        throw new Exception("$file_pointer nemůže být kvůli chybě smazán."); 
    } 
    else { 
        return "$file_pointer byl úspěšně smazán."; 
    } 
}

function uploadJsonFile($fileInfo) {
    $target_dir = "../json_data/";
    $temporary_file = $target_dir . basename($fileInfo["name"]);
    $lock_file = $target_dir . "upload.lock";
    $final_file = $target_dir . "objects.json";
    
    // Zkontroluje další probíhající nahrávání
    if (file_exists($lock_file)) {
        echo "Jiné nahrávání právě probíhá, zkuste to prosím později.";
        return false;
    }

    // Vytvoří soubor zámek, který značí probíhající nahrávání.
    file_put_contents($lock_file, "locked");

    try {
        $fileMimeType = mime_content_type($fileInfo["tmp_name"]);

        // zkontroluje ze soubor je zip
        if ($fileMimeType != 'application/zip') {
            echo "Povoleny jsou pouze ZIP soubory.";
            unlink($lock_file); // smaze lock file
            return false;
        }

        // nahraje nahrany soubor na docasnou lokaci
        if (move_uploaded_file($fileInfo["tmp_name"], $temporary_file)) {
            echo "ZIP soubor se nahrál.";

            $zip = new ZipArchive;
            if ($zip->open($temporary_file) === TRUE) {
                $zip->extractTo($target_dir);
                $zip->close();

                $foundJson = false;
                $extractedFiles = scandir($target_dir);
                foreach ($extractedFiles as $file) {
                    if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'json') {
                        if (rename($target_dir . $file, $final_file)) {
                            echo "JSON soubor byl extrahován."; //objects.json
                            $foundJson = true;
                            break;
                        }
                    }
                }

                if (!$foundJson) {
                    echo "JSON nebyl v ZIP archivu nalezen.";
                    unlink($lock_file); // smaze lock file
                    cleanupDirectory($target_dir, $final_file);
                    return false;
                }
            } else {
                echo "Nepodařilo se otevřít ZIP soubor.";
                unlink($lock_file);
                cleanupDirectory($target_dir, $final_file);
                return false;
            }
        } else {
            echo "Nastala chyba při nahrávání souboru.";
            unlink($lock_file);
            cleanupDirectory($target_dir, $final_file);
            return false;
        }

        // vycisti adresar
        cleanupDirectory($target_dir, $final_file);

        return true;

    } catch (\Throwable $th) {
        echo("Nahrávání selhalo.");
        unlink($lock_file); // zmaze lock file
        return false;
    }
}

function cleanupDirectory($dir, $keepFile) {
    $files = glob($dir . '*');
    foreach ($files as $file) {
        if ($file != $keepFile) {
            unlink($file);
        }
    }
}


function insertJsonIntoDatabase($jsonString) {
    global $pdo;
    try {
        $file = file_get_contents($jsonString);
        $data = json_decode($file, true);

        foreach ($data as $category => $objects) {
            // Kontrola, zda řetězec kategorie obsahuje více kategorií
            if (strpos($category, ';') !== false) {
                $category = explode(';', $category)[0];
            }
            // Vložení kategorie do tabulky kategorií, pokud neexistuje
            $stmtCategory = $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
            $stmtCategory->execute([$category]);
            $categoryId = $pdo->lastInsertId();

            foreach ($objects as $object) {
                // Vyhledání city_id na základě názvu města z JSONu
                $stmtCity = $pdo->prepare("SELECT id FROM cities WHERE name = ?");
                $stmtCity->execute([$object['address']['city']]);
                $cityId = $stmtCity->fetchColumn();
                if (!$cityId) {
                    $cityId = 0;
                }

                // Zkontroluje, zda je adresa URL webové stránky příliš dlouhá, pokud ano, nastavte ji na NULL.
                $website = strlen($object['website']) > 191 ? NULL : $object['website'];

                // najpve vlozi do tabulky objektu
                $stmtObject = $pdo->prepare("INSERT INTO object (category_id, name, email, phone, website, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmtObject->execute([$categoryId, $object['name'], $object['email'], $object['phone'], $website, $object['coordinates']['lat'], $object['coordinates']['lon']]);
                $objectId = $pdo->lastInsertId();

                // pak do tabulky adres s novym objektem
                $stmtAddress = $pdo->prepare("INSERT INTO address (object_id, city_id, street, postcode, suburb, housenumber, country) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmtAddress->execute([$objectId, $cityId, $object['address']['street'], $object['address']['postcode'], $object['address']['suburb'], $object['address']['housenumber'], $object['address']['country']]);

                $addressId = $pdo->lastInsertId();

                // updatuje object s address_id
                $stmtUpdateObject = $pdo->prepare("UPDATE object SET address_id = ? WHERE id = ?");
                $stmtUpdateObject->execute([$addressId, $objectId]);
            }
        }

    } catch(Throwable $e) {
        throw new Exception("Chyba při vkládání JSON souboru do databáze: " . $e->getMessage());
    }
    return "JSON byl úspěšně vložen do databáze.";
}

function deleteAllData() {
    global $pdo;
    try {
        $pdo->exec("DELETE FROM object");
        $pdo->exec("DELETE FROM address");
        $pdo->exec("DELETE FROM categories");
    } catch(Throwable $e) {
        throw new Exception("Chyba při mazání dat z databáze: " . $e->getMessage());
    }
    return "Data v databázi byla úspěšně vymazána.";
}


function assignNearestCity() {
    global $pdo;
    
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch all cities and their coordinates
        $stmtCities = $pdo->prepare("SELECT id, name, latitude, longitude FROM cities");
        $stmtCities->execute();
        $cities = $stmtCities->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all addresses without a city_id
        $stmtAddresses = $pdo->prepare("SELECT a.id, o.latitude, o.longitude FROM address a JOIN object o ON a.object_id = o.id WHERE a.city_id IS NULL OR a.city_id = 0");
        $stmtAddresses->execute();
        $addresses = $stmtAddresses->fetchAll(PDO::FETCH_ASSOC);

        if (sizeof($cities) == 0) {
            throw new Exception("Chyba při získávání měst z databáze: ");
        }
        if (sizeof($addresses) == 0) {
            throw new Exception("Chyba při získávání adres z databáze: ");
        }

        foreach ($addresses as $address) {
            $smallestDistance = PHP_INT_MAX;
            $nearestCityId = null;

            foreach ($cities as $city) {
                // vypocita kartezskou vzdalenost (zemeplocha)
                $distance = sqrt(pow($address['latitude'] - $city['latitude'], 2) + pow($address['longitude'] - $city['longitude'], 2));
                if ($distance < $smallestDistance) {
                    $smallestDistance = $distance;
                    $nearestCityId = $city['id'];
                }
            }

            // Aktualizace city_id přidružené adresy pro tuto adresu
            $stmtUpdate = $pdo->prepare("UPDATE address SET city_id = :city_id WHERE id = :address_id");
            $stmtUpdate->execute(['city_id' => $nearestCityId, 'address_id' => $address['id']]);
        }

    } catch (Throwable $e) {
        throw new Exception("Chyba při přiřazování měst do objektů: " . $e->getMessage());
    }
    return "Města byla úspěšně přiřazena do adres objektů.";
}
