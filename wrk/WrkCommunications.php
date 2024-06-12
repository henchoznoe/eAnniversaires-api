<?php

namespace Wrk;

use Exception;
use HTTP\HTTPResponses;

/**
 * Classe de gestion des communications.
 *
 * Cette classe gère les communications et permet de les récupérer, d'en ajouter, de les modifier et de les supprimer.
 *
 * @since 05.2024
 * @author Noé Henchoz
 */
class WrkCommunications {

    // Constantes pour les validations de données
    private const string REGEX_COMMUNICATIONS_DESCRIPTION = "/^[a-zA-ZÀ-ÿ\s'-]{1,32}$/";

    // Constantes pour les messages de succès
    private const string GET_COMMUNICATIONS_SUCCESS = "Les communications ont été récupérées avec succès";
    private const string ADD_COMMUNICATIONS_SUCCESS = "Les communications ont été ajoutées avec succès";
    private const string UPDATE_COMMUNICATIONS_SUCCESS = "Les communications ont été modifiées avec succès";
    private const string DELETE_COMMUNICATIONS_SUCCESS = "Les communications ont été supprimées avec succès";

    // Constantes pour les messages d'erreur
    private const string GET_COMMUNICATIONS_ERROR = "Les communications n'ont pas pu être récupérées";
    private const string ADD_COMMUNICATIONS_ERROR = "Les communications n'ont pas pu être ajoutées";
    private const string UPDATE_COMMUNICATIONS_ERROR = "Les communications n'ont pas pu être modifiées";
    private const string DELETE_COMMUNICATIONS_ERROR = "Les communications n'ont pas pu être supprimées";

    private const string ADD_COMMUNICATIONS_DATA_INVALID = "La description, le message d'anniversaire, le message d'anniversaire au format HTML et la durée en jour pour un rappel doivent être spécifiées";
    private const string UPDATE_COMMUNICATIONS_DATA_INVALID = "La clé primaire, la description, le message d'anniversaire, le message d'anniversaire au format HTML et la durée en jour pour un rappel doivent être spécifiées";
    private const string DELETE_COMMUNICATIONS_DATA_INVALID = "La clé primaire doit être spécifiée";

    private const string COMMUNICATIONS_PK_FORMAT_INVALID = "La clé primaire doit être un nombre";
    private const string COMMUNICATIONS_DESCRIPTION_FORMAT_INVALID = "La description doit être une chaîne de caractères alphabétique de maximum 32 caractères";
    private const string COMMUNICATIONS_BIRTHDAY_MSG_FORMAT_INVALID = "Le message d'anniversaire doit être une chaîne de caractères";
    private const string COMMUNICATIONS_HTML_BIRTHDAY_MSG_FORMAT_INVALID = "Le message d'anniversaire au format HTML doit être une chaîne de caractères";
    private const string COMMUNICATIONS_NOTIF_DAYS_BEFORE_FORMAT_INVALID = "La durée en jour pour un rappel doit être un nombre compris entre 1 et 30";

    private const string COMMUNICATIONS_DONT_EXIST = "Les communications n'existent pas";
    private const string COMMUNICATIONS_IS_USED = "Cette communication ne peut pas être supprimée, elle est utilisée par au moins un département";

    private WrkDB $wrkDB;

    /**
     * Constructeur de la classe WrkCommunications.
     *
     * Initialise l'instance de la classe WrkDB.
     */
    public function __construct() {
        $this->wrkDB = new WrkDB();
    }

    /**
     * Récupère la liste des communications.
     *
     * @return void
     */
    public function getCommunications(): void {
        try {
            $communications = $this->wrkDB->select(GET_COMMUNICATIONS, [], true);
            HTTPResponses::success(self::GET_COMMUNICATIONS_SUCCESS, $communications);
        } catch ( Exception $ex ) {
            HTTPResponses::error(500, self::GET_COMMUNICATIONS_ERROR . ' - ' . $ex->getMessage());
        }
    }

    /**
     * Ajoute une nouvelle communication.
     *
     * @param array $requestBody Les données de la communication à ajouter.
     * @return void
     */
    public function addCommunications(array $requestBody): void {
        try {
            // Vérifie les données de la communication à ajouter
            $this->validateAddOrUpdateCommunications($requestBody);

            $description = $requestBody['description'];
            $birthdayMsg = $requestBody['birthday_msg'];
            $htmlBirthdayMsg = $requestBody['html_birthday_msg'];
            $notificationDelay = $requestBody['notification_delay'];

            // Ajoute la communication dans une transaction
            $this->wrkDB->beginTransaction();
            $this->wrkDB->execute(INSERT_COMMUNICATIONS, [$description, $birthdayMsg, $htmlBirthdayMsg, $notificationDelay]);
            $pkCommunication = $this->wrkDB->lastInsertId();
            $communications = $this->wrkDB->select(GET_EXISTING_COMMUNICATION_BY_PK, [$pkCommunication]);
            $this->wrkDB->commit();

            HTTPResponses::success(self::ADD_COMMUNICATIONS_SUCCESS, $communications);
        } catch ( Exception $ex ) {
            $this->wrkDB->rollBack();
            HTTPResponses::error(500, self::ADD_COMMUNICATIONS_ERROR . ' - ' . $ex->getMessage());
        }
    }

    /**
     * Met à jour une communication existante.
     *
     * @param array $requestBody Les données de la communication à mettre à jour.
     * @return void
     */
    public function updateCommunications(array $requestBody): void {
        try {
            // Vérifie les données de la communication à mettre à jour
            $this->validateAddOrUpdateCommunications($requestBody, true);

            $pkCommunication = $requestBody['pk_communication'];
            $description = $requestBody['description'];
            $birthdayMsg = $requestBody['birthday_msg'];
            $htmlBirthdayMsg = $requestBody['html_birthday_msg'];
            $notificationDelay = $requestBody['notification_delay'];

            // Vérifie si la communication existe par clé primaire
            if ( !$this->checkCommunicationsExistenceByPk($pkCommunication) ) {
                HTTPResponses::error(404, self::COMMUNICATIONS_DONT_EXIST);
            }

            // Met à jour la communication dans une transaction
            $this->wrkDB->beginTransaction();
            $this->wrkDB->execute(UPDATE_COMMUNICATIONS, [$description, $birthdayMsg, $htmlBirthdayMsg, $notificationDelay, $pkCommunication]);
            $communications = $this->wrkDB->select(GET_EXISTING_COMMUNICATION_BY_PK, [$pkCommunication]);
            $this->wrkDB->commit();

            HTTPResponses::success(self::UPDATE_COMMUNICATIONS_SUCCESS, $communications);
        } catch ( Exception $ex ) {
            $this->wrkDB->rollBack();
            HTTPResponses::error(500, self::UPDATE_COMMUNICATIONS_ERROR . ' - ' . $ex->getMessage());
        }
    }

    /**
     * Supprime une communication.
     *
     * @param array $params Les paramètres contenant la clé primaire de la communication à supprimer.
     * @return void
     */
    public function deleteCommunications(array $params): void {
        try {
            // Vérifie les données de la requête de suppression
            $this->validateDeleteCommunications($params);

            $pkCommunication = $params['pk_communication'];

            // Vérifie que la communication n'est plus liée à un département
            if ( $this->checkDepartmentHasThisCommunication($pkCommunication) ) {
                HTTPResponses::error(409, self::COMMUNICATIONS_IS_USED);
            }

            // Suppression de la communication dans une transaction
            $this->wrkDB->beginTransaction();
            $this->wrkDB->execute(DELETE_COMMUNICATIONS_BY_PK, [$pkCommunication]);
            $this->wrkDB->commit();

            HTTPResponses::success(self::DELETE_COMMUNICATIONS_SUCCESS);
        } catch ( Exception $ex ) {
            $this->wrkDB->rollBack();
            HTTPResponses::error(500, self::DELETE_COMMUNICATIONS_ERROR . ' - ' . $ex->getMessage());
        }
    }

    /**
     * Valide les données de la requête d'ajout ou de mise à jour des communications.
     *
     * @param array $data Les données de la requête contenant les paramètres à valider.
     * @param bool $isUpdate Indique s'il s'agit d'une mise à jour.
     * @return void
     */
    private function validateAddOrUpdateCommunications(array $data, bool $isUpdate = false): void {
        // Vérifie si la clé primaire de la communication est spécifiée lorsque c'est une mise à jour
        if ( $isUpdate && !isset($data['pk_communication']) ) {
            HTTPResponses::error(400, self::UPDATE_COMMUNICATIONS_DATA_INVALID);
        }
        if ( !isset($data['description']) ||
            !isset($data['birthday_msg']) ||
            !isset($data['html_birthday_msg']) ||
            !isset($data['notification_delay']) ) {
            HTTPResponses::error(400, self::ADD_COMMUNICATIONS_DATA_INVALID);
        }
        if ( $isUpdate ) $pkCommunication = $data['pk_communication'];
        $description = $data['description'];
        $birthdayMsg = $data['birthday_msg'];
        $htmlBirthdayMsg = $data['html_birthday_msg'];
        $notificationDelay = $data['notification_delay'];
        if ( $isUpdate && !is_numeric($pkCommunication) ) {
            HTTPResponses::error(400, self::COMMUNICATIONS_PK_FORMAT_INVALID);
        }
        if ( !preg_match(self::REGEX_COMMUNICATIONS_DESCRIPTION, $description) ) {
            HTTPResponses::error(400, self::COMMUNICATIONS_DESCRIPTION_FORMAT_INVALID);
        }
        if ( !is_string($birthdayMsg) ) {
            HTTPResponses::error(400, self::COMMUNICATIONS_BIRTHDAY_MSG_FORMAT_INVALID);
        }
        if ( !is_string($htmlBirthdayMsg) ) {
            HTTPResponses::error(400, self::COMMUNICATIONS_HTML_BIRTHDAY_MSG_FORMAT_INVALID);
        }
        if ( !is_numeric($notificationDelay) || $notificationDelay < 0 || $notificationDelay > 30 ) {
            HTTPResponses::error(400, self::COMMUNICATIONS_NOTIF_DAYS_BEFORE_FORMAT_INVALID);
        }
    }

    /**
     * Valide les paramètres de la requête de suppression des communications.
     *
     * @param array $params Les données de la requête contenant les paramètres à valider.
     * @return void
     */
    public function validateDeleteCommunications(array $params): void {
        // Vérifie que la clé primaire de la communication est spécifiée et qu'elle est numérique
        if ( !isset($params['pk_communication']) || !is_numeric($params['pk_communication']) ) {
            HTTPResponses::error(400, self::DELETE_COMMUNICATIONS_DATA_INVALID);
        }
        $pkCommunication = $params['pk_communication'];
        // Vérifie si la communication existe par clé primaire
        if ( !$this->checkCommunicationsExistenceByPk($pkCommunication) ) {
            HTTPResponses::error(404, self::COMMUNICATIONS_DONT_EXIST);
        }
    }

    /**
     * Vérifie l'existence d'une communication par clé primaire.
     *
     * @param int $pkCommunications La clé primaire de la communication.
     * @return bool Retourne vrai si la communications existe, faux sinon.
     */
    private function checkCommunicationsExistenceByPk(int $pkCommunications): bool {
        return !empty($this->wrkDB->select(GET_EXISTING_COMMUNICATION_BY_PK, [$pkCommunications]));
    }

    /**
     * Vérifie que la communication n'est pas liée à un département
     *
     * @param int $pkCommunication La clé primaire de la communication.
     * @return bool Retourne vrai si la communication n'est pas liée à un département, faux sinon.
     */
    private function checkDepartmentHasThisCommunication(int $pkCommunication): bool {
        return $this->wrkDB->select(CHECK_DEPARTMENT_HAS_THIS_COMMUNICATION, [$pkCommunication])['count'] > 0;
    }

}