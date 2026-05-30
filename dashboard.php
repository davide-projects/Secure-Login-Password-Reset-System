<?php
// dashboard.php - Pagina protetta

require_once 'api/db.php';

// Controllo sicurezza
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Assicuriamoci che non ci sia altro codice PHP dopo questo punto
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h2>Benvenuto</h2>
            <h1><?= htmlspecialchars($_SESSION['nome'] . " " . $_SESSION['cognome']); ?></h1>
            <p>Nickname: <strong><?= htmlspecialchars($_SESSION['nickname']); ?></strong></p>
            <a class="logout-btn" href="logout.php">Logout</a>
        </div>
    </div>
</body>
</html>
