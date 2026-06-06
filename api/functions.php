<?php
require_once __DIR__ . '/../config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// ============================================================
//  HELPERS GENERICI
// ============================================================

function checkExisting($conn, $column, $value)
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE $column = ? LIMIT 1");
    $stmt->execute([$value]);
    return $stmt->fetch() !== false;
}


// ============================================================
//  ANTI-BRUTEFORCE
// ============================================================

function registerLoginAttempt($conn, $ip, $email, $success)
{
    $stmt = $conn->prepare(
        "INSERT INTO login_attempts (ip, email, success) VALUES (?, ?, ?)"
    );
    $stmt->execute([$ip, $email, $success ? 1 : 0]);
}

function getFailedAttempts($conn, $ip, $email, $minutes)
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE ip = ?
           AND email = ?
           AND success = 0
           AND attempt_time >= (NOW() - INTERVAL ? MINUTE)"
    );
    $stmt->execute([$ip, $email, $minutes]);
    return (int) $stmt->fetchColumn();
}

function isIpBlocked($conn, $ip)
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE ip = ?
           AND success = 0
           AND attempt_time >= (NOW() - INTERVAL 2 MINUTE)"
    );
    $stmt->execute([$ip]);
    return ((int) $stmt->fetchColumn()) >= 10;
}

function isEmailBlocked($conn, $email)
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE email = ?
           AND success = 0
           AND attempt_time >= (NOW() - INTERVAL 15 MINUTE)"
    );
    $stmt->execute([$email]);
    return ((int) $stmt->fetchColumn()) >= 5;
}

function applySlowdown($failedAttempts)
{
    if ($failedAttempts <= 0) {
        return;
    }
    sleep(min(pow(2, $failedAttempts - 1), 8));
}

function checkBruteforce($conn, $ip, $email)
{
    if (isIpBlocked($conn, $ip)) {
        return "Troppi tentativi da questo indirizzo IP. Attendi qualche minuto.";
    }
    if (isEmailBlocked($conn, $email)) {
        return "Troppi tentativi falliti su questo account. Attendi qualche minuto.";
    }
    $failedAttempts = getFailedAttempts($conn, $ip, $email, 15);
    applySlowdown($failedAttempts);
    return null;
}


// ============================================================
//  VALIDAZIONE LOGIN
// ============================================================

function validateLoginData($data)
{
    if (empty($data['email']) || empty($data['password'])) {
        return "Email e password sono richiesti!";
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return "Formato email non valido!";
    }
    return null;
}


// ============================================================
//  LOGIN
// ============================================================

function loginUser($conn, $data)
{
    $error = validateLoginData($data);
    if ($error !== null) {
        echo json_encode(["status" => false, "message" => $error]);
        return;
    }

    $email = strtolower(trim($data['email']));
    $ip = $_SERVER['REMOTE_ADDR'];
    $response = processLogin($conn, $email, $data['password'], $ip);

    echo json_encode($response);
}

function processLogin($conn, $email, $password, $ip)
{
    $bruteforceError = checkBruteforce($conn, $ip, $email);
    if ($bruteforceError !== null) {
        return ["status" => false, "message" => $bruteforceError];
    }
    return performDbLogin($conn, $email, $password, $ip);
}

function performDbLogin($conn, $email, $password, $ip)
{
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return handleLoginResult($conn, $user, $password, $email, $ip);
    } catch (PDOException $e) {
        return ["status" => false, "message" => "Errore del database durante il login"];
    }
}

function handleLoginResult($conn, $user, $password, $email, $ip)
{
    if (!$user) {
        registerLoginAttempt($conn, $ip, $email, false);
        return ["status" => false, "message" => "Questa email non è registrata nei nostri sistemi."];
    }

    if (!password_verify($password, $user['password'])) {
        registerLoginAttempt($conn, $ip, $email, false);
        return ["status" => false, "message" => "La password inserita è errata. Riprova."];
    }

    registerLoginAttempt($conn, $ip, $email, true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nickname'] = $user['nickname'];
    $_SESSION['nome'] = $user['nome'];
    $_SESSION['cognome'] = $user['cognome'];

    return ["status" => true, "message" => "Login effettuato! Benvenuto " . $user['nickname']];
}


// ============================================================
//  VALIDAZIONE REGISTRAZIONE
// ============================================================

function validateUserData($conn, $data, $dominio)
{
    return validateUserFields($data)
        ?? validateUserFormats($data, $dominio)
        ?? validateUserUniqueness($conn, $data);
}

function validateUserFields($data)
{
    $error = null;

    if (empty($data['nickname']) || empty($data['nome']) || empty($data['cognome']) || empty($data['email']) || empty($data['password'])) {
        $error = "Tutti i campi sono obbligatori";

    } elseif (mb_strlen($data['nickname']) < MIN_CHARS_NICK) {
        $error = "Il Nickname deve essere di almeno " . MIN_CHARS_NICK . " caratteri!";

    } elseif (mb_strlen($data['nome']) < MIN_CHARS_NOMI || !preg_match(REGEX_NOMI, $data['nome'])) {
        $error = "Il Nome deve avere almeno " . MIN_CHARS_NOMI . " caratteri e contenere solo lettere!";

    } elseif (mb_strlen($data['cognome']) < MIN_CHARS_NOMI || !preg_match(REGEX_NOMI, $data['cognome'])) {
        $error = "Il Cognome deve avere almeno " . MIN_CHARS_NOMI . " caratteri e contenere solo lettere!";
    }

    return $error;
}

function validateUserFormats($data, $dominio)
{
    $error = null;

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Formato email non valido";

    } elseif (!in_array($dominio, DOMINI_PERMESSI)) {
        $error = "DOMINIO_NON_AUTORIZZATO";

    } elseif (!preg_match(REGEX_PASSWORD, $data['password'])) {
        $error = "La password deve contenere almeno 8 caratteri e almeno 2 numeri!";
    }

    return $error;
}

function validateUserUniqueness($conn, $data)
{
    if (checkExisting($conn, 'nickname', $data['nickname'])) {
        return "Nickname già esistente";
    }
    if (checkExisting($conn, 'email', $data['email'])) {
        return "Email già esistente";
    }
    return null;
}


// ============================================================
//  VALIDAZIONE RESET PASSWORD
// ============================================================

function validateResetEmail($email)
{
    $error = null;
    $dominio = substr(strrchr($email, "@"), 1);

    if ($email === "") {
        $error = "Email obbligatoria";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato email non valido";

    } elseif (!in_array($dominio, DOMINI_PERMESSI)) {
        $error = "Dominio email non autorizzato";
    }

    return $error;
}

function validateResetPassword($password, $confirm)
{
    $error = null;

    if ($password === "" || $confirm === "") {
        $error = "Password e conferma obbligatorie";

    } elseif (!preg_match(REGEX_PASSWORD, $password)) {
        $error = "La password deve contenere almeno 8 caratteri e almeno 2 numeri";

    } elseif ($password !== $confirm) {
        $error = "Le password non coincidono";
    }

    return $error;
}

function userExistsForReset($conn, $email)
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}


// ============================================================
//  RESET PASSWORD
// ============================================================

function resetPassword($conn, $data)
{
    $email = strtolower(trim($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    $confirm = $data['confirm_password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];

    $response = processPasswordReset($conn, $email, $password, $confirm, $ip);

    echo json_encode($response);
}

function processPasswordReset($conn, $email, $password, $confirm, $ip)
{
    $bruteforceError = checkBruteforce($conn, $ip, $email);
    if ($bruteforceError !== null) {
        return ["status" => false, "message" => $bruteforceError];
    }
    return validateAndReset($conn, $email, $password, $confirm, $ip);
}

function validateAndReset($conn, $email, $password, $confirm, $ip)
{
    $error = validateResetEmail($email) ?? validateResetPassword($password, $confirm);

    if ($error !== null) {
        registerLoginAttempt($conn, $ip, $email, false);
        return ["status" => false, "message" => $error];
    }

    if (!userExistsForReset($conn, $email)) {
        registerLoginAttempt($conn, $ip, $email, false);
        return ["status" => false, "message" => "Email non trovata o utente eliminato"];
    }

    return performPasswordUpdate($conn, $email, $password, $ip);
}

function performPasswordUpdate($conn, $email, $password, $ip)
{
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?");
    $success = $stmt->execute([$hashed, $email]);

    registerLoginAttempt($conn, $ip, $email, $success);

    return [
        "status" => $success,
        "message" => $success
            ? "Password aggiornata con successo!"
            : "Errore durante l'aggiornamento"
    ];
}


// ============================================================
//  VALIDAZIONE UPDATE UTENTE
// ============================================================

function validateUpdateUserData($id, $nickname, $nome, $cognome, $email)
{
    if ($id === "" || $nickname === "" || $nome === "" || $cognome === "" || $email === "") {
        return "ID, nickname, nome, cognome e email sono richiesti";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Formato email non valido!";
    }
    return null;
}

function checkUpdateUserUniqueness($conn, $id, $nickname, $email)
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE nickname = ? AND id <> ?");
    $stmt->execute([$nickname, $id]);
    if ($stmt->rowCount() > 0) {
        return "Nickname già esistente!";
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
    $stmt->execute([$email, $id]);
    if ($stmt->rowCount() > 0) {
        return "Email già esistente!";
    }

    return null;
}


// Configurazione STMP (Per mail personalizzata)
function getSmtpConfig(): array
{
    return [
        'host' => 'localhost',
        'username' => '',
        'password' => '',
        'port' => 1025,
        'secure' => '',
        'from_email' => 'davide.barbieri1@outlook.it',
        'from_name' => 'Monsters University'
    ];
}


function sendWelcomeEmail(string $email, string $nickname): bool
{
    require_once __DIR__ . "/lib/PHPMailer/PHPMailer.php";
    require_once __DIR__ . "/lib/PHPMailer/SMTP.php";
    require_once __DIR__ . "/lib/PHPMailer/Exception.php";

    $config = getSmtpConfig();
    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';

        $mail->isSMTP();
        $mail->Host       = 'localhost';
        $mail->SMTPAuth   = false;
        $mail->SMTPSecure = false;
        $mail->Port       = 1025;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Benvenuto su Monsters University!';
        $mail->Body    = getWelcomeEmailTemplate($nickname);

        $result = $mail->send();

        if (!$result) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
        }

        return $result;

    } catch (Exception $e) {
        error_log("Mailer Exception: " . $e->getMessage());
        return false;
    }
}



function getWelcomeEmailTemplate(string $nickname): string
{
    return "
    <div style='margin:0; padding:0; background-color:#F4F6F8; font-family:Arial, sans-serif;'>
        <div style='max-width:600px; margin:0 auto; padding:40px 20px;'>

            <!-- HEADER -->
            <div style='background:linear-gradient(135deg, #2C3E50, #3B5998); border-radius:12px 12px 0 0;
                        padding:35px 30px; text-align:center;'>
                <h1 style='color:white; margin:0; font-size:26px; letter-spacing:1px;'>
                    Monsters University
                </h1>
                <p style='color:rgba(255,255,255,0.75); margin:8px 0 0; font-size:14px;'>
                    Secure Login System
                </p>
            </div>

            <!-- BODY -->
            <div style='background:white; padding:35px 30px; border-left:1px solid #E0E0E0;
                        border-right:1px solid #E0E0E0;'>

                <h2 style='color:#2C3E50; margin-top:0; font-size:22px;'>
                    Benvenuto, {$nickname}!
                </h2>

                <p style='font-size:16px; color:#555; line-height:1.6;'>
                    Il tuo account e' stato creato con successo. Ora fai ufficialmente parte
                    della nostra community e puoi accedere alla tua area riservata.
                </p>

                <p style='font-size:16px; color:#555; line-height:1.6;'>
                    Inizia la tua avventura nel mondo della sicurezza informatica, in stile Monsters!
                </p>

                <!-- CTA -->
                <div style='text-align:center; margin:30px 0;'>
                    <a href='http://login-register.test/login.php'
                       style='background:#3B5998; color:white; padding:14px 32px; border-radius:6px;
                              text-decoration:none; font-size:16px; font-weight:bold; display:inline-block;'>
                        Accedi ora
                    </a>
                </div>

                <!-- TIP BOX -->
                <div style='background:#F8F9FA; border-left:4px solid #3B5998; border-radius:4px;
                            padding:15px 20px; margin:25px 0;'>
                    <strong style='color:#2C3E50; font-size:14px;'>Consiglio di sicurezza</strong><br>
                    <span style='color:#555; font-size:14px; line-height:1.6;'>
                        Mantieni sempre sicure le tue credenziali e non condividere mai la tua password
                        con nessuno, nemmeno con il nostro staff.
                    </span>
                </div>

                <p style='font-size:14px; color:#555; line-height:1.6;'>
                    Grazie per esserti registrato!<br>
                    <strong style='color:#2C3E50;'>Il team di Monsters University</strong>
                </p>
            </div>

            <!-- FOOTER -->
            <div style='background:#F4F6F8; border:1px solid #E0E0E0; border-top:none;
                        border-radius:0 0 12px 12px; padding:20px 30px; text-align:center;'>
                <p style='font-size:12px; color:#999; margin:0; line-height:1.6;'>
                    Questa e' un'email automatica, ti preghiamo di non rispondere a questo messaggio.<br>
                    &copy; 2026 Monsters University – Secure Login System
                </p>
            </div>

        </div>
    </div>
    ";
}
