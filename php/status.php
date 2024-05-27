<?php
declare(strict_types=1);
session_start();

$response = [
    'isLoggedIn' => false
];

if (isset($_SESSION["loggedInUserId"])) {
    $response['isLoggedIn'] = true;
}

echo json_encode($response);
