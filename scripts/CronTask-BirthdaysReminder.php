<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constants.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Europe/Zurich');

// Création du fichier de log si inexistant
if ( !file_exists(REMINDER_LOG_FILE) ) {
    file_put_contents(REMINDER_LOG_FILE, '');
}
// Vérification du hash si l'accès restreint est activé
if ( RESTRICTED_ACCESS ) verifyHash();
// Récupération du token après une authentification
$token = login();
// Récupération des anniversaires spéciaux méritant un rappel au responsable du/des département(s)
$specialBirthdays = getSpecialBirthdays($token);
// Traitement des anniversaires spéciaux et envoi des notifications au responsable du/des département(s) des collaborateurs avant un anniveraire spécial
sendNotifications($specialBirthdays);


// Vérification du hash fourni dans l'URL
function verifyHash(): void {
    writeLog("Vérification du hash fourni dans l'URL");
    $hash = hash('sha256', SECRET);
    $receivedHash = $_GET['hash'] ?? '';
    if ( $receivedHash !== $hash ) {
        http_response_code(403);
        echo "Accès non autorisé à ce script !";
        writeLog("=> Le hash n'a pas pu être vérifié");
        writeLog("Exécution du script terminée");
        exit;
    } else {
        writeLog("=> Le hash a été vérifié avec succès");
    }
}

// Authentification dans le but d'obtenir un token JWT
function login(): string {
    writeLog("Tentative d'authentification auprès du serveur");
    $data = ['action' => 'login', 'mail' => LOGIN_MAIL, 'password' => LOGIN_PASSWORD];
    $response = sendHttpRequest(SERVER_URL, 'POST', $data);
    if ( $response ) {
        $responseData = json_decode($response, true);
        writeLog("=> " . $responseData['message']);
        if ( $responseData['success'] ) {
            return $responseData['data']['token'];
        } else {
            writeLog("Exécution du script terminée");
            exit;
        }
    } else {
        writeLog("=> Échec de la requête d'authentification");
        writeLog("Exécution du script terminée");
        exit;
    }
}

// Récupération des anniversaires spéciaux méritant un rappel au responsable du/des département(s)
function getSpecialBirthdays(string $token): array {
    writeLog("Tentative de récupération des anniversaires spéciaux");
    $url = SERVER_URL . '?action=getSpecialBirthdays';
    $response = sendHttpRequest($url, 'GET', [], $token);
    if ( $response ) {
        $responseData = json_decode($response, true);
        writeLog("=> " . $responseData['message']);
        if ( $responseData['success'] ) {
            if ( isset($responseData['data']) ) {
                return $responseData['data'];
            } else {
                writeLog("=> Il n'y a pas d'anniversaires spéciaux méritant un rappel au responsable du/des département(s)");
                writeLog("Exécution du script terminée");
                exit;
            }
        } else {
            writeLog("Exécution du script terminée");
            exit;
        }
    } else {
        writeLog("=> Échec de la requête de récupération des anniversaires spéciaux");
        writeLog("Exécution du script terminée");
        exit;
    }
}

// Envoi de notification au responsbale du/des département(s)
function sendNotifications(array $specialBirthdays): void {
    foreach ( $specialBirthdays as $birthday ) {
        $subject = "eAnniversaire - " . $birthday['birthday_type'] . " prochainement";

        if ( $birthday['birthday_type'] === 'Anniversaire important' ) {
            $age = calculateAge($birthday['date_of_birth']);
            $info = "On fête ses $age ans !!";
        } else if ( $birthday['birthday_type'] === 'Anniversaire d\'ancienneté' ) {
            $years = calculateYears($birthday['date_of_hire']);
            $info = "On fête ses $years ans au sein de l'entreprise !!";
        }

        $imageData = base64_encode(file_get_contents('../assets/logo.png'));
        $imageSrc = 'data:image/png;base64,' . $imageData;
        $body = "Bonjour " . $birthday['manager_first_name'] . " " . $birthday['manager_last_name'] . ",<br><br>"
            . "Nous souhaitons vous informer que votre collaborateur "
            . $birthday['first_name'] . " " . $birthday['last_name']
            . " du département " . $birthday['department_name']
            . " aura un " . strtolower($birthday['birthday_type'])
            . " dans " . $birthday['notification_delay'] . " jours ! 🥳<br><br>"
            . "$info<br><br>"
            . "Coordonnées :<br>"
            . "Email: " . $birthday['mail'] . "<br>"
            . "Téléphone: " . $birthday['tel_number'] . "<br><br>"
            . "Cordialement,<br>"
            . "eAnniversaires<br><br>"
            . "<img src='" . $imageSrc . "' alt='Logo eAnniversaires'/>";

        sendMail($birthday['manager_mail'], $subject, $body);
    }
    writeLog("Toutes les notifications ont été envoyées");
    writeLog("Exécution du script terminée");
}

// Fonction pour calculer l'âge d'une personne
function calculateAge(string $dateOfBirth): int {
    $birthDate = new DateTime($dateOfBirth);
    $currentDate = new DateTime();
    return $currentDate->diff($birthDate)->y + 1;
}

// Fonction pour calculer les années d'ancienneté
function calculateYears(string $dateOfHire): int {
    $hireDate = new DateTime($dateOfHire);
    $currentDate = new DateTime();
    return $currentDate->diff($hireDate)->y + 1;
}

// Envoi de mail avec PHPMailer
function sendMail(string $to, string $subject, string $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->CharSet = "UTF-8";
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(MAIL_FROM, 'eAnniversaires');
        $mail->addAddress($to);
        $mail->isHTML();
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        writeLog("=> E-mail envoyé avec succès à $to");
    } catch ( Exception $e ) {
        writeLog("=> Échec de l'envoi de l'e-mail à $to. Erreur: {$mail->ErrorInfo}");
    }
}

// Envoie de requêtes HTTP
function sendHttpRequest(string $url, string $method, array $data = [], string $token = ''): ?string {
    $ch = curl_init();
    if ( $method === 'GET' ) {
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        $payload = json_encode($data);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    if ( $token ) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    if ( $response === false ) {
        return null;
    }
    return $response;
}

// Ecriture de log dans le fichier prévu à cet effet
function writeLog(string $message): void {
    $date = date('Y-m-d H:i:s');
    $logMessage = "[$date] $message\n";
    file_put_contents(REMINDER_LOG_FILE, $logMessage, FILE_APPEND);
}