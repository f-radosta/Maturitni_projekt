<?php
declare(strict_types=1);

$secretKey = "*****";

$pdo = require __DIR__ . "/database.php";
$email = $_POST["email"];
$password = $_POST["password"];
$recaptchaResponse = $_POST['g-recaptcha-response'];

$response = [
    "success" => false,
    "message" => "Chybny email nebo heslo"
];

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
            $response["success"] = true;
            $response["message"] = "Přihlášení proběhlo úspěšně";
        }
    } else {
        $response["message"] = "Email nebyl nalezen";
    }

} catch(PDOException $e) {
    $response["message"] = "Chyba: " . $e->getMessage();
};

echo json_encode($response);

