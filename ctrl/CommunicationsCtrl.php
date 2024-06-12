<?php

namespace Ctrl;

use Wrk\WrkCommunications;

require_once ROOT . "/wrk/WrkCommunications.php";

/**
 * Contrôleur pour la gestion des communications.
 *
 * Ce contrôleur gère les actions liées aux communications telles que la récupération, l'ajout, la mise à jour et la suppression.
 *
 * @since 05.2024
 * @author Noé Henchoz
 */
class CommunicationsCtrl {

    private WrkCommunications $wrkCommunications;

    /**
     * Constructeur de la classe CommunicationsCtrl.
     *
     * Initialise l'instance du Worker.
     */
    public function __construct() {
        $this->wrkCommunications = new WrkCommunications();
    }

    /**
     * Récupère la liste des communications.
     *
     * @return void
     */
    public function getCommunications(): void {
        $this->wrkCommunications->getCommunications();
    }

    /**
     * Ajoute des nouvelles communications.
     *
     * @param array $requestBody Les données du département à ajouter.
     * @return void
     */
    public function addCommunications(array $requestBody): void {
        $this->wrkCommunications->addCommunications($requestBody);
    }

    /**
     * Met à jour les communcations existantes.
     *
     * @param array $requestBody Les données des nouveaux paramètres.
     * @return void
     */
    public function updateCommunications(array $requestBody): void {
        $this->wrkCommunications->updateCommunications($requestBody);
    }

    /**
     * Supprime des communications.
     *
     * @param array $params Les paramètres contenant la clé primaire des communications à supprimer.
     * @return void
     */
    public function deleteCommunications(array $params): void {
        $this->wrkCommunications->deleteCommunications($params);
    }

}