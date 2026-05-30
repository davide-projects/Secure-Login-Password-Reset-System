<?php
// password_reset.php

define('RESET_ERROR_REDIRECT', 'Location: password_reset_form.php?error=');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header(RESET_ERROR_REDIRECT . urlencode("Richiesta non valida."));
    exit;
}

$data = [
    "action" => $_POST['action'] ?? '',
    "email" => $_POST['email'] ?? '',
    "password" => $_POST['password'] ?? '',
    "confirm_password" => $_POST['confirm_password'] ?? ''
];

// Chiamata API
$ch = curl_init("http://login-register.test/api/users.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

// Validazione risposta API
if (!is_array($result)) {
    header(RESET_ERROR_REDIRECT . urlencode("Errore interno: risposta non valida dal server."));
    exit;
}

if (!($result['status'] ?? false)) {
    $message = $result['message'] ?? 'Errore sconosciuto';
    header(RESET_ERROR_REDIRECT . urlencode($message));
    exit;
}

// Caso successo → mostra pagina
header("refresh:3;url=login.php");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Esito Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h2>Esito Reset Password</h2>

            <p class="message" style="color: green;">
                <?= htmlspecialchars($result['message']) ?>
            </p>

            <p>Verrai reindirizzato alla pagina di login...</p>
        </div>
    </div>
</body>
</html>
