<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://login-register.test/*");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$input = file_get_contents("php://input");
define("RAW_INPUT", json_decode($input, true) ?? []);

require_once 'db.php';
require_once 'functions.php';

$database = new Database();
$conn = $database->connect();

$method = $_SERVER['REQUEST_METHOD'];
$data = RAW_INPUT;

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getSingleUser($conn, $_GET['id']);
        } else {
            getAllUsers($conn);
        }
        break;

    case 'POST':
        if (isset($data['action']) && $data['action'] === 'login') {
            loginUser($conn, $data);
            return;
        }
        if (isset($data['action']) && $data['action'] === 'reset_password') {
            resetPassword($conn, $data);
            return;
        }
        elseif (!empty($data['id'])) {
            updateUser($conn);
            return;
        }
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

function insertUser($conn)
{
    $data = RAW_INPUT;

    $cleanData = [
        'nickname' => trim($data['nickname'] ?? ''),
        'nome'     => ucwords(strtolower(trim($data['nome'] ?? ''))),
        'cognome'  => ucwords(strtolower(trim($data['cognome'] ?? ''))),
        'email'    => strtolower(trim($data['email'] ?? '')),
        'password' => $data['password'] ?? ''
    ];

    $dominio = substr(strrchr($cleanData['email'], "@"), 1);
    $validationError = validateUserData($conn, $cleanData, $dominio);

    $response = [];

    if ($validationError) {
        if ($validationError === "DOMINIO_NON_AUTORIZZATO") {
            $response = [
                "status"         => false,
                "message"        => "Spiacenti, dominio non autorizzato.",
                "domini_accettati" => DOMINI_PERMESSI
            ];
        } else {
            $response = ["status" => false, "message" => $validationError];
        }
    } else {
        $hashedPassword = password_hash($cleanData['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users(nickname, nome, cognome, email, password) VALUES(?,?,?,?,?)");
        $success = $stmt->execute([
            $cleanData['nickname'],
            $cleanData['nome'],
            $cleanData['cognome'],
            $cleanData['email'],
            $hashedPassword
        ]);

        if ($success) {
            file_put_contents('C:/laragon/www/login-register/debug.txt', date('H:i:s') . " - sendWelcomeEmail chiamata per: " . $cleanData['email'] . "\n", FILE_APPEND);
            sendWelcomeEmail($cleanData['email'], $cleanData['nickname']);
        }

        $response = [
            "status"  => $success,
            "message" => $success ? "Utente creato con successo!" : "Errore durante la creazione!"
        ];
    }

    echo json_encode($response);
}

function updateUser($conn)
{
    $response = ["status" => false, "message" => ""];
    $source = !empty($_POST) ? $_POST : RAW_INPUT;

    $id       = trim($source['id'] ?? '');
    $nickname = trim($source['nickname'] ?? '');
    $nome     = trim($source['nome'] ?? '');
    $cognome  = trim($source['cognome'] ?? '');
    $email    = strtolower(trim($source['email'] ?? ''));

    $error = validateUpdateUserData($id, $nickname, $nome, $cognome, $email);
    if ($error !== "") {
        $response["message"] = $error;
        echo json_encode($response);
        return;
    }

    $error = checkUpdateUserUniqueness($conn, $id, $nickname, $email);
    if ($error !== "") {
        $response["message"] = $error;
        echo json_encode($response);
        return;
    }

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

    $response["status"]  = true;
    $response["message"] = "Dati utente aggiornati con successo!";
    echo json_encode($response);
}

function deleteUser($conn)
{
    $data = RAW_INPUT;

    if (empty($data['id'])) {
        echo json_encode([
            "status"  => false,
            "message" => "ID utente richiesto!"
        ]);
        return;
    }

    $id = $data['id'];

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status"  => true,
            "message" => "Utente con ID $id eliminato con successo!"
        ]);
    } else {
        echo json_encode([
            "status"  => false,
            "message" => "Errore: l'utente con ID $id non esiste!"
        ]);
    }
}
