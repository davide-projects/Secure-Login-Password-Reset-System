<?php
session_start();
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
        <form id="registerForm" class="form-box">
            <h2>Crea Account</h2>

            <p id="message" class="message"></p>

            <input type="text" id="nickname" placeholder="Nickname" minlength="3" required>
            <input type="text" id="nome" placeholder="Nome" minlength="2" required>
            <input type="text" id="cognome" placeholder="Cognome" minlength="2" required>
            <input type="email" id="email" placeholder="Email" required>
            <input type="password" id="password" placeholder="Password" minlength="8" required>

            <button type="submit">Registrati</button>
            <p>Hai già un account? <a href="login.php">Login</a></p>
        </form>
    </div>

    <script>
        document.getElementById("registerForm").addEventListener("submit", async function (e) {
            e.preventDefault();

            const nickname = document.getElementById("nickname").value.trim();
            const nome = document.getElementById("nome").value.trim();
            const cognome = document.getElementById("cognome").value.trim();
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value.trim();
            const messageBox = document.getElementById("message");

            const response = await fetch("http://login-register.test/api/users.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    nickname: nickname,
                    nome: nome,
                    cognome: cognome,
                    email: email,
                    password: password
                })
            });

            const result = await response.json();
            messageBox.textContent = result.message;

            if (result.status === true) {
                setTimeout(() => {
                    window.location.href = "login.php";
                }, 2000);
            }
        });
    </script>

</body>
</html>
