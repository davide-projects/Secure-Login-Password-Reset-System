<?php
require_once 'api/db.php';        // Carica la connessione
require_once 'api/functions.php'; // Carica la validazione e le costanti

$message = "";

if (isset($_POST['register'])) {
    // 1. RECUPERO E PULIZIA DEI DATI
    // Impacchettiamo i dati in un array per la funzione di validazione
    $userData = [
        'nickname' => trim($_POST['nickname'] ?? ''),
        'nome' => ucwords(strtolower(trim($_POST['nome'] ?? ''))),
        'cognome' => ucwords(strtolower(trim($_POST['cognome'] ?? ''))),
        'email' => strtolower(trim($_POST['email'] ?? '')),
        'password' => $_POST['password'] ?? ''
    ];

    // Estrazione dominio
    $dominioEstratto = substr(strrchr($userData['email'], "@"), 1);

    // 2. VALIDAZIONE CENTRALIZZATA
    // Questa singola riga sostituisce tutti i vecchi if e le query checkNick/checkEmail
    $validationError = validateUserData($conn, $userData, $dominioEstratto);

    if ($validationError) {
        // Gestione messaggio per dominio non autorizzato
        if ($validationError === "DOMINIO_NON_AUTORIZZATO") {
            $message = "Spiacenti, puoi registrarti solo con i domini: " . implode(", ", DOMINI_PERMESSI);
        } else {
            $message = $validationError;
        }
    } else {
        // 3. INSERIMENTO FINALE (Eseguito solo se validationError è null)
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users(nickname, nome, cognome, email, password) VALUES(?,?,?,?,?)");

        if (
            $stmt->execute([
                $userData['nickname'],
                $userData['nome'],
                $userData['cognome'],
                $userData['email'],
                $passwordHash
            ])
        ) {
            $message = "Iscrizione completata con successo! Benvenuto, " . $userData['nome'] . ".";
        } else {
            $message = "Errore durante l'iscrizione. Riprova più tardi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Monsters University</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <form method="POST" class="form-box">
            <h2>Crea Account</h2>

            <!-- Mostra il messaggio del backend (se presente) -->
            <?php if (!empty($message)): ?>
                <p class="message"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <!-- Nickname con lunghezza minima dinamica -->
            <input type="text" name="nickname" placeholder="Nickname" minlength="<?= MIN_CHARS_NICK ?>" required>

            <!-- Nome e Cognome con lunghezza minima dinamica -->
            <input type="text" name="nome" placeholder="Nome" minlength="<?= MIN_CHARS_NOMI ?>" required>

            <input type="text" name="cognome" placeholder="Cognome" minlength="<?= MIN_CHARS_NOMI ?>" required>

            <input type="email" name="email" placeholder="Email"
                title="Domini accettati: <?= implode(', ', DOMINI_PERMESSI) ?>" required>

            <!-- Password: qui potresti anche aggiungere il pattern della regex se vuoi essere estremo -->
            <input type="password" name="password" placeholder="Password" minlength="8" required>

            <button type="submit" name="register">Registrati</button>
            <p>Hai già un account? <a href="login.php">Login</a></p>
        </form>
    </div>
    <script src="script.js"></script>
</body>

</html>
