<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

$requiredRole = $_GET['role'] ?? '';
$requiredRole = strtoupper($requiredRole);
$userRoles = $_SESSION['user_roles'] ?? [];

$response = [
    'hasRole' => in_array($requiredRole, $userRoles)
];

echo json_encode($response);
