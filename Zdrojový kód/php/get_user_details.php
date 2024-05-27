<?php
declare(strict_types=1);

require __DIR__ . "/database.php";
require __DIR__ . "/session_check.php";

header('Content-Type: application/json');

if (!checkSessionAndRole('admin')) {
    echo json_encode(['error' => 'Nedostatečná práva']);
    exit;
}

if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode($user);
    } else {
        echo json_encode(['error' => 'Uživatel nebyl nalezen']);
    }
} else {
    echo json_encode(['error' => 'Chybí uživatelské ID']);
}
