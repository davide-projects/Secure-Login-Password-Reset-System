<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://login-register.test/*");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$input = file_get_contents("php://input");
define("RAW_INPUT", json_decode($input, true) ?? []);

require_once 'db.php';
require_once 'functions.php';

// 1. IMPORTANTE: Leggi i dati JSON inviati da Postman
$input = json_decode(file_get_contents('php://input'), true);


$database = new Database();
$conn = $database->connect();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getSingleUser($conn, $_GET['id']);
        } else {
            getAllUsers($conn);
        }
        break;

    case 'POST':
        // Verifichiamo se è un LOGIN
        if (isset($input['action']) && $input['action'] === 'login') {
            loginUser($conn, $input); // Passiamo i dati decodificati alla funzione di login
            return;
        } // RESET PASSWORD
        if (isset($input['action']) && $input['action'] === 'reset_password') {
            resetPassword($conn, $input);
            return;
        }

        // Verifichiamo se è un UPDATE tramite POST (tipico dei form)
        elseif (!empty($input['id']) || !empty($_POST['id'])) {
            updateUser($conn);
            return;
        }
        // Altrimenti è una REGISTRAZIONE standard
        else {
            insertUser($conn);
        }
        break;

    case 'PUT':
        updateUser($conn);
        break;

    case 'DELETE':
        deleteUser($conn);
        break;

    default:
        echo json_encode([
            "status" => false,
            "message" => "Metodo di richiesta non valido!"
        ]);
        break;
}

function getAllUsers($conn)
{
    // Aggiunti nome e cognome nella SELECT
    $stmt = $conn->prepare("SELECT id, nickname, nome, cognome, email, created_at FROM users");
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "data" => $users
    ]);
}

function getSingleUser($conn, $id)
{
    // Aggiunti nome e cognome nella SELECT
    $stmt = $conn->prepare("SELECT id, nickname, nome, cognome, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$id]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            "status" => true,
            "data" => $user
        ]);
    } else {
        echo json_encode([
            "status" => false,
            "message" => "Utente non trovato"
        ]);
    }
}

// La funzione insertUser ora utilizza la funzione di validazione centralizzata
function insertUser($conn)
{
    $data = RAW_INPUT; // Utilizziamo RAW_INPUT per ottenere i dati JSON decodificati

    // 1. PULIZIA DATI
    $cleanData = [
        'nickname' => trim($data['nickname'] ?? ''),
        'nome' => ucwords(strtolower(trim($data['nome'] ?? ''))),
        'cognome' => ucwords(strtolower(trim($data['cognome'] ?? ''))),
        'email' => strtolower(trim($data['email'] ?? '')),
        'password' => $data['password'] ?? ''
    ];

    $dominio = substr(strrchr($cleanData['email'], "@"), 1);
    $validationError = validateUserData($conn, $cleanData, $dominio);

    // Rimuoviamo l'assegnazione iniziale inutile e dichiariamo la variabile
    $response = [];

    if ($validationError) {
        if ($validationError === "DOMINIO_NON_AUTORIZZATO") {
            $response = [
                "status" => false,
                "message" => "Spiacenti, dominio non autorizzato.",
                "domini_accettati" => DOMINI_PERMESSI
            ];
        } else {
            $response = ["status" => false, "message" => $validationError];
        }
    } else {
        // 3. ESECUZIONE (Se non ci sono errori)
        $hashedPassword = password_hash($cleanData['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users(nickname, nome, cognome, email, password) VALUES(?,?,?,?,?)");
        $success = $stmt->execute([
            $cleanData['nickname'],
            $cleanData['nome'],
            $cleanData['cognome'],
            $cleanData['email'],
            $hashedPassword
        ]);

        $response = [
            "status" => $success,
            "message" => $success ? "Utente creato con successo!" : "Errore durante la creazione!"
        ];
    }

    echo json_encode($response);
}


function updateUser($conn)
{
    $response = ["status" => false, "message" => ""];
    $source = !empty($_POST) ? $_POST : RAW_INPUT;

    $id = trim($source['id'] ?? '');
    $nickname = trim($source['nickname'] ?? '');
    $nome = trim($source['nome'] ?? '');
    $cognome = trim($source['cognome'] ?? '');
    $email = strtolower(trim($source['email'] ?? ''));

    // 1. Validazione base
    $error = validateUpdateUserData($id, $nickname, $nome, $cognome, $email);
    if ($error !== "") {
        $response["message"] = $error;
        echo json_encode($response);
        return;
    }

    // 2. Controllo unicità
    $error = checkUpdateUserUniqueness($conn, $id, $nickname, $email);
    if ($error !== "") {
        $response["message"] = $error;
        echo json_encode($response);
        return;
    }

    // 3. UPDATE
    $stmt = $conn->prepare(
        "UPDATE users
         SET nickname = ?, nome = ?, cognome = ?, email = ?, updated_at = NOW()
         WHERE id = ?"
    );

    $success = $stmt->execute([$nickname, $nome, $cognome, $email, $id]);

    if (!$success) {
        $response["message"] = "Errore durante l'aggiornamento dei dati utente.";
        echo json_encode($response);
        return;
    }

    // 4. Successo
    $response["status"] = true;
    $response["message"] = "Dati utente aggiornati con successo!";
    echo json_encode($response);
}

function deleteUser($conn)
{
    $data = RAW_INPUT;

    // 1. Controllo se l'ID è presente
    if (empty($data['id'])) {
        echo json_encode([
            "status" => false,
            "message" => "ID utente richiesto!"
        ]);
        return;
    }

    $id = $data['id'];

    // 2. Eseguo la DELETE
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    // 3. Controllo REALE: quante righe sono state cancellate?
    if ($stmt->rowCount() > 0) {
        // Se rowCount > 0, l'utente esisteva ed è stato eliminato
        echo json_encode([
            "status" => true,
            "message" => "Utente con ID $id eliminato con successo!"
        ]);
    } else {
        // Se rowCount è 0, la query è OK ma l'ID non c'era nel DB
        echo json_encode([
            "status" => false,
            "message" => "Errore: l'utente con ID $id non esiste!"
        ]);
    }
}
