<?php
declare(strict_types=1);

require __DIR__ . "/database.php";
require __DIR__ . "/session_check.php";

header('Content-Type: application/json');

if (!checkSessionAndRole('admin')) {
    echo json_encode(['error' => 'Nedostatečná práva']);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT u.id, u.user_name, u.email 
        FROM users u
        WHERE NOT EXISTS (
            SELECT 1
            FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = u.id AND r.role_name = 'ADMIN'
        )
    ");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]);
}
?>
