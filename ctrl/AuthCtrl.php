<?php

namespace Ctrl;

use Wrk\WrkAuth;

require_once ROOT . "/wrk/WrkAuth.php";

/**
 * Contrôleur pour la gestion de l'authentification.
 *
 * Ce contrôleur gère les opérations liées à l'authentification, telles que la connexion et la vérification du token.
 *
 * @since 05.2024
 * @author Noé Henchoz
 */
class AuthCtrl {

    private WrkAuth $wrkAuth;

    /**
     * Constructeur de la classe AuthCtrl.
     *
     * Initialise l'instance du Worker.
     */
    public function __construct() {
        $this->wrkAuth = new WrkAuth();
    }

    /**
     * Gère la connexion d'un administrateur.
     *
     * @param array $requestBody Les données de la requête de connexion.
     * @return void
     */
    public function login(array $requestBody): void {
        $this->wrkAuth->login($requestBody);
    }

    /**
     * Vérifie la validité du token d'un administrateur.
     *
     * @return void
     */
    public function checkToken(): void {
        $this->wrkAuth->checkToken();
    }

}
