<?php

namespace HTTP;

/**
 * Classe HTTPResponses pour la gestion des réponses HTTP.
 *
 * @author Noé Henchoz
 * @since 05.2024
 */
class HTTPResponses {

    /**
     * Envoie une réponse HTTP avec le code de statut spécifié, le statut de succès, le message et les données (optionnelles).
     *
     * @param int $code Le code de statut HTTP.
     * @param bool $status Le statut de succès de la réponse.
     * @param string $message Le message de la réponse.
     * @param array|null $data Les données de la réponse (optionnelles).
     * @return void
     */
    public static function sendResponse(int $code, bool $status, string $message, array $data = null): void {
        $response = ['success' => $status, 'message' => $message];
        if ( !empty($data) ) {
            $response['data'] = $data;
        }
        http_response_code($code);
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * Envoie une réponse HTTP de succès avec le code de statut 200, le message et les données (optionnelles).
     *
     * @param string $message Le message de succès.
     * @param array|null $data Les données de la réponse (optionnelles).
     * @return void
     */
    public static function success(string $message, array $data = null): void {
        self::sendResponse(200, true, $message, $data);
    }

    /**
     * Envoie une réponse HTTP d'erreur avec le code de statut spécifié et le message d'erreur.
     *
     * @param int $code Le code de statut HTTP de l'erreur.
     * @param string $message Le message d'erreur.
     * @return void
     */
    public static function error(int $code, string $message): void {
        self::sendResponse($code, false, $message);
    }

}