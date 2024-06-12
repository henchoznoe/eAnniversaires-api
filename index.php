<?php

use HTTP\HTTPHandlers;
use HTTP\HTTPResponses;

// Dossier racine de l'API
const ROOT = __DIR__;

require_once ROOT . "/vendor/autoload.php";
require_once ROOT . "/constants/global.php";
require_once ROOT . "/constants/http-headers.php";
require_once ROOT . "/http/HTTPHandlers.php";
require_once ROOT . "/http/HTTPResponses.php";
require_once ROOT . "/wrk/WrkDB.php";
require_once ROOT . "/constants/db-queries.php";

// Définit le fuseau horaire par défaut à l'Europe/Zurich
date_default_timezone_set('Europe/Zurich');

// Contrôle de l'existence de la méthode HTTP utilisée lors d'une requête
if ( isset($_SERVER["REQUEST_METHOD"]) ) {
    $http = new HTTPHandlers();
    // Séparation des requêtes selon la méthode HTTP utilisée
    switch ( $_SERVER["REQUEST_METHOD"] ) {
        case "GET":
            $http->GET();
            break;
        case "POST":
            $http->POST();
            break;
        case "PUT":
            $http->PUT();
            break;
        case "DELETE":
            $http->DELETE();
            break;
        case "OPTIONS":
            // Pour laisser passer les requêtes CORS préliminaires
            break;
        default:
            // Méthode HTTP non autorisée par cette API
            HTTPResponses::error(405, UNAUTHORIZED_HTTP_METHOD);
            break;
    }
} else {
    // Méthode HTTP non spécifiée
    HTTPResponses::error(405, UNSPECIFIED_HTTP_METHOD);
}