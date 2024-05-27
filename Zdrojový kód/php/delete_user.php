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

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $result = $stmt->execute([$userId]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Chyba při mazání uživatele']);
    }
} else {
    echo json_encode(['error' => 'Chybí uživatelské ID']);
}
