<?php
declare(strict_types=1);

session_start();

function checkSessionAndRole($requiredRole = null) {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 900)) { // 15 mins
        session_destroy();
        header('Location: logout.html');
        return false;
    } else {
        $_SESSION['LAST_ACTIVITY'] = time();
    }

    if ($requiredRole && (empty($_SESSION['user_roles']) || !in_array(strtoupper($requiredRole), $_SESSION['user_roles']))) {
        header('Location: ../login.html?insufficient_role=true');
        return false;
    }
    return true;
}
?>

