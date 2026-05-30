<?php
// password_reset_form.php
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Recupero Password</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <form id="resetForm" action="password_reset.php" method="POST" class="form-box">
            <h2>Recupero Password</h2>

            <?php if (!empty($_GET['error'])): ?>
                <p class="message" style="color:red;">
                    <?= htmlspecialchars($_GET['error']) ?>
                </p>
            <?php endif; ?>

            <p id="js-message" class="message"></p>

            <input type="hidden" name="action" value="reset_password">

            <input type="email" name="email" placeholder="Email registrata" required>
            <input type="password" name="password" placeholder="Nuova Password" required>
            <input type="password" name="confirm_password" placeholder="Conferma Password" required>

            <button type="submit">Aggiorna Password</button>

            <p>
                Ricordi la password?
                <a href="login.php">Torna al login</a>
            </p>
        </form>
    </div>

    <!-- Validazione JS esterna -->
    <script src="./javascript/reset_validation.js"></script>
</body>

</html>
