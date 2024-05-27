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

    $username = isset($_POST['username']) ? $_POST['username'] : null;
    if ($username === null) throw new Exception("Neplatné údaje.", 400);
    $email = $_POST['email'];
    $password_or_hash = $_POST['password'];
    $password_or_hash_confirmation = $_POST['passwordConfirmation'];

    $validationResult = validate($username, $email, $password_or_hash, $password_or_hash_confirmation);
    if ($validationResult !== true) {
        echo json_encode($validationResult);
        exit;
    }

    try {

        if (isset($_POST['user_id'])) { // edit user
            $userId = $_POST['user_id'];
            $passwordHash = $_POST['passwordHash'];

            if ($password_or_hash == $passwordHash) { // zmena bez hesla
                $stmt = $pdo->prepare("UPDATE users SET user_name = ?, email = ? WHERE id = ?");
                $result = $stmt->execute([$username, $email, $userId]);
            } else { // zmena s heslem
                $password_hash = password_hash($password_or_hash, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET user_name = ?, email = ?, password_hash = ? WHERE id = ?");
                $result = $stmt->execute([$username, $email, $password_hash, $userId]);
            }

            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Chyba při aktualizaci uživatele.']);
            }
            
        } else { // add user
            $password_hash = password_hash($password_or_hash, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (user_name, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $password_hash]);
            $userId = $pdo->lastInsertId();
            $defaultRoleId = 2;
            $stmtRole = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmtRole->execute([$userId, $defaultRoleId]);

            echo json_encode(['success' => true]);
        }

    } catch(PDOException $e) {
        if ($e->getCode() == 23000 && strpos($e->getMessage(), '1062 Duplicate entry') !== false) {
            echo json_encode(['error' => 'Tento email je již registrován, prosím použijte jiný.']);
        } else {
            echo json_encode(['error' => 'Chyba: ' . $e->getMessage()]);
        }
    }

} catch(Exception $exc) {
    echo json_encode(['error' => 'Chyba: ' . $exc->getMessage()]);
}


function validate($username, $email, $password_or_hash, $password_or_hash_confirmation) {
    if (empty($username)) {
        return ['error' => 'Jméno je vyžadováno'];
    }

    if (strlen($username) > 12) {
        return ['error' => 'Jméno je moc dlouhé'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['error' => 'Je vyžadován validní email'];
    }
    
    if (strlen($password_or_hash) < 8) {
        return ['error' => 'Heslo musí obsahovat alsepoň osm znaků'];
    }
    
    if (!preg_match("/[a-z]/i", $password_or_hash)) {
        return ['error' => 'Heslo musí obsahovat alsepoň jedno písmeno'];
    }
    
    if (!preg_match("/[0-9]/", $password_or_hash)) {
        return ['error' => 'Heslo musí obsahovat alsepoň jedno číslo'];
    }
    
    if ($password_or_hash !== $password_or_hash_confirmation) {
        return ['error' => 'Hesla se musí shodovat'];
    }

    return true;
}
