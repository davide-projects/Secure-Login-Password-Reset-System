<?php
// db.php
require_once __DIR__ . '/../config.php';

// Verifichiamo se la connessione esiste già per evitare duplicati
if (!isset($conn)) {
    try {
        // Usiamo le COSTANTI definite in config.php
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS
        );
        
        // Configurazione errori e fetch predefinito
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Se siamo in un'API, rispondiamo in JSON, altrimenti con un die
        if (strpos($_SERVER['REQUEST_METHOD'], 'POST') !== false && !isset($_POST['login'])) {
            header('Content-Type: application/json');
            echo json_encode(["status" => false, "message" => "Errore DB: " . $e->getMessage()]);
            exit;
        } else {
            die("Connessione fallita: " . $e->getMessage());
        }
    }
}

class Database {
    // Rimuoviamo le variabili private "hardcoded" e usiamo quelle di config.php
    public $conn;

    public function connect() {
        // Usiamo le variabili definite nel tuo config.php
        // Nota: se nel tuo config.php le variabili si chiamano $host, $dbname, etc.
        // dobbiamo renderle disponibili qui.
        global $host, $dbname, $username, $password;

        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8",
                $username,
                $password
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch(PDOException $error) {
            echo json_encode([
                "status" => false,
                "code" => $error->getCode(),
                "message" => "Errore di connessione al database"
            ]);
            exit; // Fermiamo l'esecuzione se il DB non risponde
        }

        return $this->conn;
    }
}
