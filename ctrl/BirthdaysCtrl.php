<?php

namespace Ctrl;

use Wrk\WrkBirthdays;

require_once ROOT . "/wrk/WrkBirthdays.php";

/**
 * Contrôleur pour la gestion des anniversaires.
 *
 * Ce contrôleur gère les actions liées aux anniversaires telles que la récupération de ceux-ci.
 *
 * @since 05.2024
 * @author Noé Henchoz
 */
class BirthdaysCtrl {

    private WrkBirthdays $wrkBirthdays;

    /**
     * Constructeur de la classe BirthdaysCtrl.
     *
     * Initialise l'instance du Worker.
     */
    public function __construct() {
        $this->wrkBirthdays = new WrkBirthdays();
    }

    /**
     * Récupère la liste des collaborateurs ayant leur anniveraire de naissance aujourd'hui.
     *
     * @return void
     */
    public function getTodaysBirthdays(): void {
        $this->wrkBirthdays->getTodaysBirthdays();
    }

    /**
     * Récupère la liste des collaborateurs ayant leur anniveraire de naissance dans le mois en cours.
     *
     * @return void
     */
    public function getMonthsBirthdays(): void {
        $this->wrkBirthdays->getMonthsBirthdays();
    }

    /**
     * Récupère la liste des collaborateurs ayant leur anniveraire de naissance dans le mois en cours (pour les administrateurs).
     *
     * @return void
     */
    public function getAdminMonthsBirthdays(): void {
        $this->wrkBirthdays->getAdminMonthsBirthdays();
    }

    /**
     * Récupère la liste des collaborateurs, leur(s) département(s), ainsi que les communications de ceux-ci,
     * pour anticiper un anniversaire spécial.
     *
     * @return void
     */
    public function getSpecialBirthdays(): void {
        $this->wrkBirthdays->getSpecialBirthdays();
    }

}