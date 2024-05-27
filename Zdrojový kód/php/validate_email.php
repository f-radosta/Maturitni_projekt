<?php
declare(strict_types=1);
require __DIR__ . "/database.php";

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$_GET["email"]]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$is_available = count($result) == 0;

header("Content-Type: application/json");

echo json_encode(["available" => $is_available]);

