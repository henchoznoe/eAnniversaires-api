<?php

namespace Ctrl;

use Wrk\WrkDepartments;

require_once ROOT . "/wrk/WrkDepartments.php";

/**
 * Contrôleur pour la gestion des départements.
 *
 * Ce contrôleur gère les actions liées aux départements telles que la récupération, l'ajout, la mise à jour et la suppression.
 *
 * @since 05.2024
 * @author Noé Henchoz
 */
class DepartmentsCtrl {

    private WrkDepartments $wrkDepartments;

    /**
     * Constructeur de la classe DepartmentsCtrl.
     *
     * Initialise l'instance du Worker.
     */
    public function __construct() {
        $this->wrkDepartments = new WrkDepartments();
    }

    /**
     * Récupère la liste des départements.
     *
     * @return void
     */
    public function getDepartments(): void {
        $this->wrkDepartments->getDepartments();
    }

    /**
     * Ajoute un nouveau département.
     *
     * @param array $requestBody Les données du département à ajouter.
     * @return void
     */
    public function addDepartment(array $requestBody): void {
        $this->wrkDepartments->addDepartment($requestBody);
    }

    /**
     * Met à jour un département existant.
     *
     * @param array $requestBody Les données du département à mettre à jour.
     * @return void
     */
    public function updateDepartment(array $requestBody): void {
        $this->wrkDepartments->updateDepartment($requestBody);
    }

    /**
     * Supprime un département.
     *
     * @param array $params Les paramètres contenant la clé primaire du département à supprimer.
     * @return void
     */
    public function deleteDepartment(array $params): void {
        $this->wrkDepartments->deleteDepartment($params);
    }

}