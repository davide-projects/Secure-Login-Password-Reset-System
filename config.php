<?php
$host = "localhost";
$dbname = "monsters_university";
$username = "root";
$password = "";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- CONFIGURAZIONI GLOBALI DI DATABASE ---
define('DB_NAME', 'monsters_university');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- CONFIGURAZIONI GLOBALI DI VALIDAZIONE ---
define('MIN_CHARS_NOMI', 2); // Esempio: nome e cognome di almeno 2 caratteri
define('MIN_CHARS_NICK', 3); // Esempio: nickname di almeno 3 caratteri
define('DOMINI_PERMESSI', ['gmail.com', 'outlook.it']);
define('REGEX_PASSWORD', "/^(?=(?:\D*\d){2,}).{8,}$/");
define('REGEX_NOMI', "/^[a-zA-Z\s\p{L}]+$/u");
