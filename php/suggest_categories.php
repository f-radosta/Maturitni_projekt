<?php
declare(strict_types=1);

require __DIR__ . "/database.php";
require __DIR__ . "/session_check.php";

if (!checkSessionAndRole('user')) {
    exit;
}

$input = $_POST['input'];

$suggestions = searchCategories($pdo, $input);

foreach($suggestions as $suggestion) {
    echo "<option value='" . htmlspecialchars($suggestion[0]) . "'>";
}

function searchCategories($pdo, $input) {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE name LIKE :input LIMIT 10");
    $stmt->execute(['input' => "%$input%"]);
    return $stmt->fetchAll();
}

