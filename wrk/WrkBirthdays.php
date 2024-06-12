<?php

namespace Wrk;

use Exception;
use HTTP\HTTPResponses;

	/**
	 * Classe de gestion des anniversaires.  Cette classe gère les anniversaires et
	 * permet de les récupérer.
	 * @since 05.2024
	 * @author Noé Henchoz
	 * @version 1.0
	 * @updated 02-juin-2024 13:48:58
	 */
class WrkBirthdays {

    // Constantes pour les messages de succès
    private const string GET_TODAYS_BIRTHDAYS_SUCCESS = "La liste des anniversaires d'aujourd'hui a été récupérée avec succès";
    private const string GET_MONTHS_BIRTHDAYS_SUCCESS = "La liste des anniversaires du mois a été récupérée avec succès";
    private const string GET_SPECIAL_BIRTHDAYS_SUCCESS = "La liste des anniversaires spéciaux a été récupérée avec succès";

    // Constantes pour les messages d'erreur
    private const string GET_TODAYS_BIRTHDAYS_ERROR = "La liste des anniversaires d'aujourd'hui n'a pas pu été récupérée";
    private const string GET_MONTHS_BIRTHDAYS_ERROR = "La liste des anniversaires du mois n'a pas pu été récupérée";
    private const string GET_SPECIAL_BIRTHDAYS_ERROR = "La liste des anniversaires spéciaux n'a pas pu été récupérée";

    private WrkDB $wrkDB;

    /**
     * Constructeur de la classe WrkBirthdays.
     *
     * Initialise l'instance de la classe WrkDB.
     */
    public function __construct() {
        $this->wrkDB = new WrkDB();
    }

    /**
     * Récupère la liste des anniversaires de naissance d'aujourd'hui.
     *
     * @return void
     */
    public function getTodaysBirthdays(): void {
        try {
            $birthdays = $this->wrkDB->select(GET_TODAYS_BIRTHDAYS, [], true);
            $result = [];
            foreach ( $birthdays as $birthday ) {
                $pkEmployee = $birthday['pk_employee'];
                // Si le collaborateur n'est pas déjà dans le résultat, l'ajouter
                if ( !isset($result[$pkEmployee]) ) {
                    $result[$pkEmployee] = [
                        'pk_employee' => $birthday['pk_employee'],
                        'first_name' => $birthday['first_name'],
                        'last_name' => $birthday['last_name'],
                        'mail' => $birthday['mail'],
                        'tel_number' => $birthday['tel_number'],
                        'date_of_birth' => $birthday['date_of_birth'],
                        'departments' => []
                    ];
                }
                // Ajout du département du collaborateur à sa liste de départements
                $result[$pkEmployee]['departments'][] = [
                    'name' => $birthday['department_name'],
                    'notify_by_sms' => $birthday['notify_by_sms'],
                    'notify_by_mail' => $birthday['notify_by_mail'],
                    'birthday_msg' => $birthday['birthday_msg'],
                    'html_birthday_msg' => $birthday['html_birthday_msg']
                ];
            }
            // Conversion du résultat en un tableau indexé numériquement
            $result = array_values($result);
            HTTPResponses::success(self::GET_TODAYS_BIRTHDAYS_SUCCESS, $result);
        } catch ( Exception $ex ) {
            HTTPResponses::error(500, self::GET_TODAYS_BIRTHDAYS_ERROR . ' - ' . $ex->getMessage());
        }
    }

    /**
     * Récupère la liste des anniversaires du mois.
     *
     * Les anniversaires sont triés par ordre croissant de date.
     *
     * @return void
     */
    public function getMonthsBirthdays(): void {
        try {
            $birthdays = $this->wrkDB->select(GET_MONTHS_BIRTHDAYS, [], true);

            // Tri des anniversaires par ordre croissant de date
            usort($birthdays, function ($a, $b) {
                $dayA = intval(substr($a['birthday_date'], 0, 2)); // Jour de l'anniversaire A
                $dayB = intval(substr($b['birthday_date'], 0, 2)); // Jour de l'anniversaire B
                $monthA = intval(substr($a['birthday_date'], 3, 2)); // Mois de l'anniversaire A
                $monthB = intval(substr($b['birthday_date'], 3, 2)); // Mois de l'anniversaire B
                // Calcul de la différence entre les dates en utilisant un format numérique (MMJJ)
                return ($monthA * 100 + $dayA) - ($monthB * 100 + $dayB);
            });

            HTTPResponses::success(self::GET_MONTHS_BIRTHDAYS_SUCCESS, $birthdays);
        } catch ( Exception $ex ) {
            HTTPResponses::error(500, self::GET_MONTHS_BIRTHDAYS_ERROR . ' - ' . $ex->getMessage());
        }
    }

    /**
     * Récupère la liste des anniversaires du mois pour l'administrateur.
     *
     * Les anniversaires sont triés par ordre croissant de date.
     *
     * @return void
     */
    public function getAdminMonthsBirthdays(): void {
        try {
            $birthdays = $this->wrkDB->select(GET_ADMIN_MONTHS_BIRTHDAYS, [], true);

            usort($birthdays, function ($a, $b) {
                // Extraction du jour et du mois de l'anniversaire A
                $monthDayA = intval(substr($a['birthday_date'], 5, 2)) * 100 + intval(substr($a['birthday_date'], 8, 2));
                // Extraction du jour et du mois de l'anniversaire B
                $monthDayB = intval(substr($b['birthday_date'], 5, 2)) * 100 + intval(substr($b['birthday_date'], 8, 2));
                // Comparaison des jours et mois extraits pour le tri
                return $monthDayA - $monthDayB;
            });

            HTTPResponses::success(self::GET_MONTHS_BIRTHDAYS_SUCCESS, $birthdays);
        } catch ( Exception $ex ) {
            HTTPResponses::error(500, self::GET_MONTHS_BIRTHDAYS_ERROR. ' - '. $ex->getMessage());
        }
    }

    /**
     * Récupère la liste des anniversaires spéciaux.
     *
     * @return void
     */
    public function getSpecialBirthdays(): void {
        try {
            $specialBirthdays = $this->wrkDB->select(GET_SPECIAL_BIRTHDAYS, [], true);
            HTTPResponses::success(self::GET_SPECIAL_BIRTHDAYS_SUCCESS, $specialBirthdays);
        } catch ( Exception $ex ) {
            HTTPResponses::error(500, self::GET_SPECIAL_BIRTHDAYS_ERROR . ' - ' . $ex->getMessage());
        }
    }

}