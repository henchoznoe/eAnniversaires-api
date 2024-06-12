<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constants.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Europe/Zurich');

// Création du fichier de log si inexistant
if ( !file_exists(WISH_LOG_FILE) ) {
    file_put_contents(WISH_LOG_FILE, '');
}
// Vérification du hash si l'accès restreint est activé
if ( RESTRICTED_ACCESS ) verifyHash();
// Récupération du token après une authentification
$token = login();
// Récupération des anniversaires du jour
$birthdays = getTodaysBirthdays($token);
// Traitement des anniversaires du jour et envoi des notifications
sendNotifications($birthdays);


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

// Récupération des anniversaires du jour avec une requête GET
function getTodaysBirthdays(string $token): array {
    writeLog("Tentative de récupération des anniversaires du jour auprès du serveur");
    $url = SERVER_URL . '?action=getTodaysBirthdays';
    $response = sendHttpRequest($url, 'GET', [], $token);
    if ( $response ) {
        $responseData = json_decode($response, true);
        writeLog("=> " . $responseData['message']);
        if ( $responseData['success'] ) {
            if ( isset($responseData['data']) ) {
                return $responseData['data'];
            } else {
                writeLog("=> Il n'y a pas d'anniversaire aujourd'hui");
                writeLog("Exécution du script terminée");
                exit;
            }
        } else {
            writeLog("Exécution du script terminée");
            exit;
        }
    } else {
        writeLog("=> Échec de la requête de récupération des anniversaires du jour");
        writeLog("Exécution du script terminée");
        exit;
    }
}

// Envoi de notifications
function sendNotifications(array $birthdays): void {
    foreach ( $birthdays as $birthday ) {
        foreach ( $birthday['departments'] as $department ) {

            if ( $department['notify_by_mail'] == 1 ) {
                $imageData = base64_encode(file_get_contents('../assets/logo.png'));
                $imageSrc = 'data:image/png;base64,' . $imageData;

                $subject = "eAnniversaires - Joyeux Anniversaire ! 🥳";
                $body = $department['html_birthday_msg']
                    . "<br><br>Cordialement,<br>"
                    . "eAnniversaires<br><br>"
                    . "<img src='" . $imageSrc . "' alt='Logo eAnniversaires'/>";
                $emailSent = sendMail($birthday['mail'], $subject, $body);
                if ( $emailSent ) {
                    writeLog("Un email a été envoyé à " . $birthday['first_name'] . " " . $birthday['last_name'] . " pour le département " . $department['name']);
                }
            } else {
                writeLog("Le département " . $department['name'] . " n'a pas autorisé la notification par mail");
            }

            if ( $department['notify_by_sms'] == 1 ) {
                $message = $department['birthday_msg'];
                $smsSent = sendSMS($birthday['tel_number'], $message);
                if ( $smsSent ) {
                    writeLog("Un SMS a été envoyé à " . $birthday['first_name'] . " " . $birthday['last_name'] . " pour le département " . $department['name']);
                }
            } else {
                writeLog("Le département " . $department['name'] . " n'a pas autorisé la notification par SMS");
            }
        }
    }
    writeLog("Toutes les notifications ont été envoyées");
    writeLog("Exécution du script terminée");
}

// Envoi de mail avec PHPMailer
function sendMail(string $to, string $subject, string $body): bool {
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
        return true;
    } catch ( Exception $e ) {
        writeLog("=> Échec de l'envoi de l'e-mail à $to. Erreur: {$mail->ErrorInfo}");
        return false;
    }
}

// Envoi de SMS
function sendSMS(string $to, string $message): bool {
    // Controle du statut de l'envoi des SMS
    if ( !SEND_SMS ) {
        writeLog("Le SMS aurait dû être envoyé à $to mais l'envoi est désactivé");
        return false;
    }
    // Remplacement des + par des 00 pour que le numéro de téléphone puisse être envoyé
    $to = str_replace('+', '00', $to);
    $SMSPostParms = "ACCOUNT=" . SMS_ACCOUNT_USERNAME;
    $SMSPostParms .= "&PASSWORD=" . SMS_ACCOUNT_PASSWORD;
    $SMSPostParms .= "&NUMBER=" . $to;
    $SMSPostParms .= "&MESSAGE=" . urlencode($message);
    $SMSPostParms .= "&ORIGIN=" . SMS_ORIGIN;
    $SMSPostParms .= "&CMD=SENDMESSAGE";

    $curlRequest = curl_init();
    curl_setopt($curlRequest, CURLOPT_URL, SMS_WEBSERVICE_URL);
    curl_setopt($curlRequest, CURLOPT_POST, 1);
    curl_setopt($curlRequest, CURLOPT_POSTFIELDS, $SMSPostParms);
    curl_setopt($curlRequest, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curlRequest, CURLOPT_USERAGENT, "TrueSenses.com Gateway/1.01");
    curl_setopt($curlRequest, CURLOPT_HEADER, 0);
    curl_setopt($curlRequest, CURLOPT_TIMEOUT, 10);
    curl_setopt($curlRequest, CURLOPT_ENCODING, SMS_CHARSET_NAME);
    curl_setopt($curlRequest, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded'
    ));

    $store = curl_exec($curlRequest);

    if ( curl_errno($curlRequest) ) {
        writeLog("=> Erreur lors de l'envoi du SMS à $to. Erreur: " . curl_error($curlRequest));
        curl_close($curlRequest);
        return false;
    }

    curl_close($curlRequest);
    if ( preg_match('/^(\d{2})\s(.+)$/', $store, $matches) === 1 ) {
        $trueSensesCodeValue = $matches[1];
        $trueSensesCodeDetails = $matches[2];
        if ( $trueSensesCodeValue == '01' ) {
            writeLog("=> SMS envoyé avec succès à $to");
            return true;
        } else {
            writeLog("=> Erreur lors de l'envoi du SMS à $to. Erreur: $trueSensesCodeDetails");
            return false;
        }
    } else {
        writeLog("=> Erreur lors de l'envoi du SMS à $to. Erreur: $store");
        return false;
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
    file_put_contents(WISH_LOG_FILE, $logMessage, FILE_APPEND);
}