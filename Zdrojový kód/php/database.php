<?php
declare(strict_types=1);

$host = "db.mp.spse-net.cz";
$db = "radostfi20_1";
$user = "radostfi20";
$pass = "*****";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    exit("Chyba při připojování k databázi: " . $e->getMessage());
}

return $pdo;