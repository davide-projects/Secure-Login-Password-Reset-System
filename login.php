<?php
// login.php
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
        <form id="loginForm" class="form-box">
            <h2>Login</h2>

            <p id="message" class="message"></p>

            <input type="email" id="email" placeholder="Indirizzo Email" required>
            <input type="password" id="password" placeholder="Password" required>

            <button type="submit">Login</button>

            <p>
                Hai dimenticato la password?
                <a href="password_reset_form.php">Recuperala qui</a>
            </p>
            <p>
                Non hai un account?
                <a href="register.php">Registrati</a>
            </p>

        </form>
    </div>

    <script>
        document.getElementById("loginForm").addEventListener("submit", async function (e) {
            e.preventDefault();

            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value.trim();
            const messageBox = document.getElementById("message");

            const response = await fetch("http://login-register.test/api/users.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    action: "login",
                    email: email,
                    password: password
                })
            });

            const result = await response.json();
            messageBox.textContent = result.message;

            if (result.status === true) {
                window.location.href = "dashboard.php";
            }
        });
    </script>

</body>
</html>
