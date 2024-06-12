<?php

namespace Wrk;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use HTTP\HTTPResponses;
use UnexpectedValueException;

/**
 * Classe de gestion de l'authentification.
 *
 * Cette classe gère la connexion administrateur en générant un token JWT et vérifie également sa validité.
 *
 * @since 05.2024
 * @author Noé Henchoz
 */
class WrkAuth {

    // Constantes pour les validations de données
    private const string REGEX_LOGIN_MAIL = "/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
    private const string REGEX_LOGIN_PASSWORD = "/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{12,64}$/";

    // Constantes pour les messages de succès
    private const string LOGIN_SUCCESS = "Vous êtes désormais authentifié !";

    // Constantes pour les messages d'erreur
    private const string LOGIN_DATA_INVALID = "L'adresse mail et le mot de passe doivent être spécifiés";
    private const string LOGIN_MAIL_FORMAT_INVALID = "L'adresse mail ne respecte pas le bon format";
    private const string LOGIN_PASSWORD_FORMAT_INVALID = "Le mot de passe ne respecte pas le bon format. Le mot de passe doit contenir au moins une lettre minuscule, une lettre majuscule, et avoir une longueur comprise entre 12 et 64 caractères";
    private const string INVALID_CREDENTIALS = "Les identifiants d'authentification sont invalides";
    private const string JWT_EXPIRED = "Le token JWT a expiré";
    private const string JWT_NOT_VALID_YET = "Le token JWT n'est pas encore valide";
    private const string JWT_INVALID_SIGNATURE = "La signature du token JWT est invalide";
    private const string JWT_UNEXPECTED_VALUE = "Valeur inattendue dans le token JWT";
    private const string JWT_ERROR = "Erreur lors de la validation du token JWT";
    private const string JWT_INVALID_FORMAT = "Erreur dans le format du token JWT";
    private const string JWT_UNSPECIFIED = "Token JWT non fourni";

    private WrkDB $wrkDB;

    /**
     * Constructeur de la classe WrkAuth.
     *
     * Initialise l'instance de la classe WrkDB.
     */
    public function __construct() {
        $this->wrkDB = new WrkDB();
    }

    /**
     * Gère la connexion de l'utilisateur administrateur.
     *
     * Génère un token JWT valide si les identifiants sont corrects.
     *
     * @param array $requestBody Les données de la requête de connexion.
     * @return void
     */
    public function login(array $requestBody): void {
        // Vérification des données de connexion
        if ( !isset($requestBody['mail']) || !isset($requestBody['password']) ) {
            HTTPResponses::error(400, self::LOGIN_DATA_INVALID);
        }
        $mail = $requestBody['mail'];
        $password = $requestBody['password'];
        // Validation des données de connexion selon les regex
        $validations = [
            'mail' => [self::REGEX_LOGIN_MAIL, self::LOGIN_MAIL_FORMAT_INVALID],
            'password' => [self::REGEX_LOGIN_PASSWORD, self::LOGIN_PASSWORD_FORMAT_INVALID]
        ];
        foreach ( $validations as $field => $validation ) {
            if ( !preg_match($validation[0], $requestBody[$field]) ) {
                HTTPResponses::error(400, $validation[1]);
            }
        }
        // Vérification de l'existence de l'administrateur et de la validité du mot de passe
        $existingAdmin = $this->checkAdminExistence($mail);
        if ( !$existingAdmin || !password_verify($password, $existingAdmin['password']) ) {
            HTTPResponses::error(403, self::INVALID_CREDENTIALS);
        }
        // Génération du token JWT
        $payload = [
            'iss' => JWT_ISSUER,
            'aud' => JWT_AUD,
            'iat' => time(),
            'exp' => time() + JWT_EXPIRES_IN,
            'data' => [
                'pk_admin' => $existingAdmin['pk_admin'],
                'mail' => $existingAdmin['mail']
            ]
        ];
        // Encodage du token JWT
        $token = JWT::encode($payload, JWT_SECRET, JWT_ALG);
        // Renvoi des données de connexion et le token JWT
        $data = array('mail' => $existingAdmin['mail'], 'token' => $token, 'expiresAt' => $payload['exp']);
        HTTPResponses::success(self::LOGIN_SUCCESS, $data);
    }

    /**
     * Vérifie la validité du token JWT fourni dans l'en-tête HTTP.
     *
     * Vérifie si le token JWT est valide, expiré ou a une signature invalide.
     *
     * @return void
     */
    public function checkToken(): void {
        // Récupération des données de l'en-tête HTTP
        $headers = apache_request_headers();
        // Vérification de l'existence de l'en-tête Authorization
        if ( isset($headers['Authorization']) ) {
            $authHeader = $headers['Authorization'];
            // Vérification de la présence du mot-clé Bearer dans l'en-tête Authorization'
            if ( preg_match('/Bearer\s(\S+)/', $authHeader, $matches) ) {
                $token = $matches[1];
                try {
                    // Vérification du token JWT
                    JWT::decode($token, new Key(JWT_SECRET, JWT_ALG));
                    return;
                } catch ( ExpiredException $ex ) {
                    HTTPResponses::error(401, self::JWT_EXPIRED);
                } catch ( BeforeValidException $ex ) {
                    HTTPResponses::error(401, self::JWT_NOT_VALID_YET);
                } catch ( SignatureInvalidException $ex ) {
                    HTTPResponses::error(401, self::JWT_INVALID_SIGNATURE);
                } catch ( UnexpectedValueException $ex ) {
                    HTTPResponses::error(401, self::JWT_UNEXPECTED_VALUE);
                } catch ( Exception $ex ) {
                    HTTPResponses::error(401, self::JWT_ERROR);
                }
            } else {
                HTTPResponses::error(401, self::JWT_INVALID_FORMAT);
            }
        } else {
            HTTPResponses::error(401, self::JWT_UNSPECIFIED);
        }
    }

    /**
     * Vérifie l'existence de l'administrateur avec l'adresse e-mail donnée.
     *
     * @param string $mail L'adresse e-mail de l'administrateur.
     * @return array|bool Les données de l'administrateur si elle existe, sinon false.
     */
    private function checkAdminExistence(string $mail): array|bool {
        return $this->wrkDB->select(GET_ADMIN_BY_MAIL, [$mail]);
    }

}
