<?php
declare(strict_types=1);
session_start();

if (isset($_SESSION['user_name'])) {
    echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8');
} else {
    echo '';
}