<?php
declare(strict_types=1);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    include 'database.php';

    $secretKey = "*****";
    $recaptchaResponse = $_POST['g-recaptcha-response'];

    if (!empty($recaptchaResponse)) {
        $postData = http_build_query([
            'secret' => $secretKey,
            'response' => $recaptchaResponse,
        ]);

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded",
                'content' => $postData,
            ],
        ];
        $context = stream_context_create($opts);
        $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        $result = json_decode($response);

        $resetPassword = 'palacinky6';
        $inputPassword = $_POST['password'];

        if ($result->success) {
            if ($inputPassword === $resetPassword) {
                try {
                    $dbName = 'radostfi20_1';
                    $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
                    $pdo->exec("CREATE DATABASE `$dbName`");
                    $pdo->exec("USE `$dbName`");

                    $sqlDump = file_get_contents("../sql/radostfi20_1.sql");
                    $pdo->exec($sqlDump);

                    $folderPath = '../json_data/';
                    $files = glob($folderPath . '*', GLOB_MARK);
                    foreach($files as $file) {
                        if(is_file($file)) {
                            unlink($file);
                        }
                    }

                    $error = "Databaze byla resetovana. Nahravaci/zamykaci slozka vycistena.";
                } catch (PDOException $e) {
                    $error = "Chyba pri resetovani databaze: " . $e->getMessage();
                }
            } else {
                $error = "Chybne heslo.";
            }
        } else {
            $error = "Captcha verifikace selhala.";
        }
    } else {
        $error = "Captcha chybi.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Database</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <h2>Reset Database</h2>
    <form action="hardreset.php" method="post">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <div class="g-recaptcha" data-sitekey="6LeLG1QpAAAAAEBuKsb-RaFSAkTsgq2gTWIdOFMZ"></div>
        <button type="submit" name="submit">Reset</button>
    </form>
    <?php if (!empty($error)) echo "<p>$error</p>"; ?>
</body>
</html>
