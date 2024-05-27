<?php
declare(strict_types=1);

$pdo = require __DIR__ . "/database.php";
require __DIR__ . "/session_check.php";
function fetchResults($pdo, $categoryName, $city, $radius, $emailRequired, $phoneRequired, $websiteRequired, $sameCityRequired) {

    $stmt = $pdo->prepare("SELECT id, latitude, longitude FROM cities WHERE name = :city");
    $stmt->bindParam(':city', $city);
    $stmt->execute();
    $cityData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cityData) {
        return ['error' => 'Město nebylo nalezeno.'];
    }

    $cityId = $cityData['id'];
    $cityLat = $cityData['latitude'];
    $cityLon = $cityData['longitude'];
    $delta = $radius / 111.0;  // Roughly converted km to degrees

    $sql = "SELECT o.name, o.email, o.phone, o.website, o.latitude, o.longitude, 
                   a.housenumber, a.street, a.postcode, c.name as city, a.country, a.suburb 
            FROM object o 
            JOIN address a ON o.address_id = a.id 
            JOIN cities c ON a.city_id = c.id
            JOIN categories cat ON o.category_id = cat.id
            WHERE cat.name = :categoryName
            AND o.latitude BETWEEN (:cityLat - :delta) AND (:cityLat + :delta)
            AND o.longitude BETWEEN (:cityLon - :delta) AND (:cityLon + :delta)";

    // Filters
    if ($emailRequired) {
        $sql .= " AND o.email IS NOT NULL AND o.email <> ''";
    }
    if ($phoneRequired) {
        $sql .= " AND o.phone IS NOT NULL AND o.phone <> ''";
    }
    if ($websiteRequired) {
        $sql .= " AND o.website IS NOT NULL AND o.website <> ''";
    }
    if ($sameCityRequired) {
        $sql .= " AND c.id = :cityId";
    }

    $query = $pdo->prepare($sql);
    $query->bindParam(':categoryName', $categoryName, PDO::PARAM_STR);
    $query->bindParam(':cityLat', $cityLat, PDO::PARAM_STR);
    $query->bindParam(':cityLon', $cityLon, PDO::PARAM_STR);
    $query->bindParam(':delta', $delta, PDO::PARAM_STR);

    if ($sameCityRequired) {
        $query->bindParam(':cityId', $cityId, PDO::PARAM_INT);
    }

    $query->execute();

    return $query->fetchAll(PDO::FETCH_ASSOC);
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!checkSessionAndRole('user')) {
        header('Location: login.html?insufficient_role=true');
        exit();
    }

    $category = $_POST['category'];
    $city = $_POST['city'];
    $radius = (int)$_POST['radius'];
    $emailRequired = $_POST['email'] == '1';
    $phoneRequired = $_POST['phone'] == '1';
    $websiteRequired = $_POST['website'] == '1';
    $sameCityRequired = $_POST['sameCity'] == '1';

    try {
        $data = fetchResults($pdo, $category, $city, $radius, $emailRequired, $phoneRequired, $websiteRequired, $sameCityRequired);
        echo json_encode($data);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Chyba: ' . $e->getMessage()]);
    }
}
