<?php
declare(strict_types=1);
header('Content-Type: application/json');

$secretKey = "*****";
$response = [];

$name = $_POST["name"];
$email = $_POST["email"];
$password = $_POST["password"];
$password_confirmation = $_POST["password_confirmation"];
$recaptchaResponse = $_POST['g-recaptcha-response'];

if (empty($name)) {
    echo json_encode(['error' => 'Jméno je vyžadováno']);
    exit;
}

if (strlen($name) > 12) {
    echo json_encode(['error' => 'Jméno je moc dlouhé']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Je vyžadován validní email']);
    exit;
}

if (strlen($_POST["password"]) < 8) {
    echo json_encode(['error' => 'Heslo musí obsahovat alsepoň osm znaků']);
    exit;
}

if (!preg_match("/[a-z]/i", $password)) {
    echo json_encode(['error' => 'Heslo musí obsahovat alsepoň jedno písmeno']);
    exit;
}

if (!preg_match("/[0-9]/", $password)) {
    echo json_encode(['error' => 'Heslo musí obsahovat alsepoň jedno číslo']);
    exit;
}

if ($password !== $password_confirmation) {
    echo json_encode(['error' => 'Hesla se musí shodovat']);
    exit;
}

$postData = http_build_query([
    'secret' => $secretKey,
    'response' => $recaptchaResponse,
    'remoteip' => $_SERVER['REMOTE_ADDR']
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'content' => $postData
    ]
]);

$url = 'https://www.google.com/recaptcha/api/siteverify';
$captchaResponse = file_get_contents($url, false, $context);
$captchaResponseData = json_decode($captchaResponse);
if (!($captchaResponseData->success)) {
    echo json_encode(['success' => false, 'message' => 'Captcha verifikace selhala']);
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$pdo = require __DIR__ . "/database.php";

try {
    $stmt = $pdo->prepare("INSERT INTO users (user_name, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $password_hash]);

    $userId = $pdo->lastInsertId();

    $defaultRoleId = 2; // user role

    $stmtRole = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
    $stmtRole->execute([$userId, $defaultRoleId]);

    $loginResponse = [
        "success" => false,
        "message" => "Přihlášení selhalo."
    ];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $user_id = $user['id'];
            $stmt = $pdo->prepare("SELECT r.role_name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?");
            $stmt->execute([$user_id]);
            $user_roles = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            if (password_verify($password, $user["password_hash"])) {
                session_start();
                $_SESSION["loggedInUserId"] = $user["id"];
                $_SESSION['user_roles'] = $user_roles;
                $_SESSION['user_name'] = $user['user_name'];
                $_SESSION['last_activity'] = time();
                $loginResponse["success"] = true;
                $loginResponse["message"] = "Přihlášení proběhlo úspěšně";
            }
        } else {
            $loginResponse["message"] = "Email nebyl nalezen";
        }

    } catch(PDOException $e) {
        $loginResponse["message"] = "Chyba: " . $e->getMessage();
    };

    echo json_encode(['success' => true, 'successLogin' => $loginResponse['success'], 'messageLogin' => $loginResponse['message']]);
} catch(PDOException $e) {
    if ($e->getCode() == 23000 && strpos($e->getMessage(), '1062 Duplicate entry') !== false) {
        echo json_encode(['error' => 'Tento email je již registrován, prosím použijte jiný.']);
    } else {
        echo json_encode(['error' => 'Chyba: ' . $e->getMessage()]);
    }
}


