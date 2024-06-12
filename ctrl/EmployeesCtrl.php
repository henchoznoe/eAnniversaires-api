<?php

namespace Ctrl;

use Wrk\WrkEmployees;

require_once ROOT . "/wrk/WrkEmployees.php";

/**
 * Contrôleur pour la gestion des collaborateurs.
 *
 * Ce contrôleur gère les actions liées aux collaborateurs telles que la récupération, l'ajout, la mise à jour et la suppression.
 *
 * @since 05.2024
 * @author Noé Henchoz
 */
class EmployeesCtrl {

    private WrkEmployees $wrkEmployees;

    /**
     * Constructeur de la classe EmployeesCtrl.
     *
     * Initialise l'instance du Worker.
     */
    public function __construct() {
        $this->wrkEmployees = new WrkEmployees();
    }

    /**
     * Récupère la liste des collaborateurs.
     *
     * @return void
     */
    public function getEmployees(): void {
        $this->wrkEmployees->getEmployees();
    }

    /**
     * Ajoute un nouveau collaborateur.
     *
     * @param array $requestBody Les données du collaborateur à ajouter.
     * @return void
     */
    public function addEmployee(array $requestBody): void {
        $this->wrkEmployees->addEmployee($requestBody);
    }

    /**
     * Met à jour un collaborateur existant.
     *
     * @param array $requestBody Les données du collaborateur à mettre à jour.
     * @return void
     */
    public function updateEmployee(array $requestBody): void {
        $this->wrkEmployees->updateEmployee($requestBody);
    }

    /**
     * Supprime un collaborateur.
     *
     * @param array $params Les paramètres contenant la clé primaire du collaborateur à supprimer.
     * @return void
     */
    public function deleteEmployee(array $params): void {
        $this->wrkEmployees->deleteEmployee($params);
    }

}