<?php
// 1. Inizializza la sessione per poterla chiudere
session_start();

// 2. Rimuove tutte le variabili di sessione
$_SESSION = array();

// 3. Se desideri eliminare anche il cookie di sessione (molto professionale)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Distrugge definitivamente la sessione sul server
session_destroy();

// 5. Reindirizza l'utente alla pagina di login o alla index
header("Location: login.php");
exit();
