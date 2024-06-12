<?php

/**
 * Fichier de définition des en-têtes CORS et du type de contenu pour les réponses HTTP.
 *
 * Ce fichier définit les en-têtes CORS nécessaires pour autoriser les requêtes cross-origin et le type de contenu pour
 * les réponses HTTP. Ces en-têtes sont ajoutés aux réponses HTTP envoyées par l'API pour permettre une communication
 * sécurisée entre différentes origines de domaine.
 *
 * @since 05.2024
 * @author Noé Henchoz
 */

// Définition des en-têtes CORS
header('Access-Control-Allow-Origin: ' . HEADERS_CORS_HOST);
header('Access-Control-Allow-Headers: ' . HEADERS_CORS_HEADERS);
header('Access-Control-Allow-Methods: ' . HEADERS_CORS_HTTP_METHODS);

// Définition du type de contenu pour les réponses HTTP
header('Content-Type: ' . HEADERS_CONTENT_TYPE);