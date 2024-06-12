<?php

namespace Wrk;

use Exception;
use HTTP\HTTPResponses;

/**
 * Classe de gestion des départements.
 *
 * Cette classe gère les départements et permet de les récupérer, d'en ajouter, d'en modifier et de les supprimer.
 *
 * @since 05.2024
 * @author Noé Henchoz
 */
class WrkDepartments {

    // Constantes pour les validations de données
    private const string REGEX_DEPARTMENT_NAME = "/^[a-zA-ZÀ-ÿ\s'-]{1,32}$/";

    // Constantes pour les messages de succès
    private const string GET_DEPARTMENTS_SUCCESS = "La liste des départements a été récupérée avec succès";
    private const string ADD_DEPARTMENT_SUCCESS = "Le département a été ajouté avec succès";
    private const string UPDATE_DEPARTMENT_SUCCESS = "Le département a été modifié avec succès";
    private const string DELETE_DEPARTMENT_SUCCESS = "Le département a été supprimé avec succès";

    // Constantes pour les messages d'erreur
    private const string GET_DEPARTMENTS_ERROR = "La liste des départements n'a pas pu être récupérée";
    private const string ADD_DEPARTMENT_ERROR = "Le département n'a pas pu être ajouté";
    private const string UPDATE_DEPARTMENT_ERROR = "Le département n'a pas pu être modifié";
    private const string DELETE_DEPARTMENT_ERROR = "Le département n'a pas pu être supprimé";

    private const string ADD_DEPARTMENT_DATA_INVALID = "Le nom, les notifications par Mail et/ou SMS, le responsable, la communication et les collaborateurs doivent être spécifiés";
    private const string UPDATE_DEPARTMENT_DATA_INVALID = "La clé primaire, le nom, les notifications par Mail et/ou SMS, le responsable, la communication et les collaborateurs doivent être spécifiés";
    private const string DELETE_DEPARTMENT_DATA_INVALID = "La clé primaire doit être spécifiée";

    private const string DEPARTMENT_PK_FORMAT_INVALID = "La clé primaire doit être un nombre";
    private const string DEPARTMENT_NAME_FORMAT_INVALID = "Le nom doit contenir uniquement des caractères alphabétiques et avoir une longueur comprise entre 1 et 32 caractères";
    private const string DEPARTMENT_NOTIFY_SMS_FORMAT_INVALID = "La notification par SMS doit être un booléen";
    private const string DEPARTMENT_NOTIFY_MAIL_FORMAT_INVALID = "La notification par SMS doit être un booléen";
    private const string DEPARTMENT_MANAGER_FORMAT_INVALID = "Le responsable doit être un objet avec sa clé primaire";
    private const string DEPARTMENT_COMMUNICATION_FORMAT_INVALID = "La communication doit être un objet avec sa clé primaire";
    private const string DEPARTMENT_EMPLOYEES_FORMAT_INVALID = "Les collaborateurs doivent être sous forme d'un tableau mais qui peut être vide";
    private const string DEPARTMENT_MANAGER_CONTENT_INVALID = "Le responsable doit être fourni sous forme d'objet avec sa clé primaire";
    private const string DEPARTMENT_COMMUNICATION_CONTENT_INVALID = "La communication doit être fournie sous forme d'objet avec sa clé primaire";
    private const string DEPARTMENT_EMPLOYEES_CONTENT_INVALID = "Les employés doivent être fournis sous forme d'objets avec leur clé primaire";
    private const string DEPARTMENT_MANAGER_VALUE_INVALID = "La clé primaire pour le responsable doit être un nombre";
    private const string DEPARTMENT_COMMUNICATION_VALUE_INVALID = "La clé primaire pour la communication doit être un nombre";
    private const string DEPARTMENT_EMPLOYEES_VALUE_INVALID = "Les clés primaire pour les collaborateurs doivent être des nombres";

    private const string DEPARTMENT_EMPLOYEE_DOESNT_EXIST = "Le collaborateur fourni n'existe pas : ";
    private const string DEPARTMENT_COMMUNICATION_DOESNT_EXIST = "La communication n'existe pas : ";
    private const string DEPARTMENT_HAS_EMPLOYEES = "Le département ne peut pas être supprimé, il contient encore des collaborateurs";
    private const string EMPLOYEE_CANNOT_BE_REMOVED = "Le département ne peut pas être modifié car un employé ne serait plus dans aucun département : ";
    private const string DEPARTMENT_ALREADY_EXISTS = "Le département existe déjà";
    private const string DEPARTMENT_DOESNT_EXIST = "Le département n'existe pas";

    private WrkDB $wrkDB;

    /**
     * Constructeur de la classe WrkDepartments.
     *
     * Initialise l'instance de la classe WrkDB.
     */
    public function __construct() {
        $this->wrkDB = new WrkDB();
    }

    /**
     * Récupère la liste des départements.
     *
     * @return void
     */
    public function getDepartments(): void {
        try {
            $departments = $this->wrkDB->select(GET_DEPARTMENTS, [], true);
            $departmentMap = [];
            foreach ( $departments as $department ) {
                $pk_department = $department['pk_department'];
                // Si le département n'est pas déjà dans le résultat, l'ajouter
                if ( !isset($departmentMap[$pk_department]) ) {
                    $departmentMap[$pk_department] = [
                        'pk_department' => $department['pk_department'],
                        'name' => $department['name'],
                        'notify_by_sms' => $department['notify_by_sms'],
                        'notify_by_mail' => $department['notify_by_mail'],
                        'manager' => [
                            'pk_employee' => $department['manager_pk_employee'],
                            'first_name' => $department['manager_first_name'],
                            'last_name' => $department['manager_last_name']
                        ],
                        'communication' => [
                            'pk_communication' => $department['communication_pk_communication'],
                            'description' => $department['communication_description']
                        ],
                        'employees' => []
                    ];
                }
                // Ajouter les collaborateurs du département
                if ( $department['emp_pk_employee'] ) {
                    $departmentMap[$pk_department]['employees'][] = [
                        'pk_employee' => $department['emp_pk_employee'],
                        'first_name' => $department['emp_first_name'],
                        'last_name' => $department['emp_last_name']
                    ];
                }
            }
            // Conversion du résultat en un tableau indexé numériquement
            $result = array_values($departmentMap);
            HTTPResponses::success(self::GET_DEPARTMENTS_SUCCESS, $result);
        } catch ( Exception $ex ) {
            HTTPResponses::error(500, self::GET_DEPARTMENTS_ERROR . ' - ' . $ex->getMessage());
        }
    }


    /**
     * Ajoute un nouveau département.
     *
     * @param array $requestBody Les données du département à ajouter.
     * @return void
     */
    public function addDepartment(array $requestBody): void {
        try {
            // Vérifie les données du département à ajouter
            $this->validateAddOrUpdateDepartment($requestBody);

            $name = $requestBody['name'];
            $notifyBySMS = $requestBody['notify_by_sms'];
            $notifyByMail = $requestBody['notify_by_mail'];
            $manager = $requestBody['manager'];
            $communication = $requestBody['communication'];
            $employees = $requestBody['employees'];

            // Vérifie si le département existe déjà par nom
            if ( $this->checkDepartmentExistenceByName($name) ) {
                HTTPResponses::error(409, self::DEPARTMENT_ALREADY_EXISTS);
            }

            // Ajoute le département dans une transaction
            $this->wrkDB->beginTransaction();
            $this->wrkDB->execute(INSERT_DEPARTMENT, [$name, $notifyBySMS, $notifyByMail, $manager['pk_employee'], $communication['pk_communication']]);
            $pkDepartment = $this->wrkDB->lastInsertId();
            // Insère les collaborateurs
            $this->insertEmployees($pkDepartment, $employees);
            $this->wrkDB->commit();

            // Récupère et renvoie le nouveau département
            $department = $this->getDepartmentByPk($pkDepartment);

            HTTPResponses::success(self::ADD_DEPARTMENT_SUCCESS, $department);
        } catch ( Exception $ex ) {
            $this->wrkDB->rollBack();
            HTTPResponses::error(500, self::ADD_DEPARTMENT_ERROR . ' - ' . $ex->getMessage());
        }
    }

    /**
     * Met à jour un département existant.
     *
     * @param array $requestBody Les données du département à mettre à jour.
     * @return void
     */
    public function updateDepartment(array $requestBody): void {
        try {
            // Vérifie les données du département à mettre à jour
            $this->validateAddOrUpdateDepartment($requestBody, true);

            $pkDepartment = $requestBody['pk_department'];
            $name = $requestBody['name'];
            $notifyBySMS = $requestBody['notify_by_sms'];
            $notifyByMail = $requestBody['notify_by_mail'];
            $manager = $requestBody['manager'];
            $communication = $requestBody['communication'];
            $employees = $requestBody['employees'];

            // Vérifie si le département existe par clé primaire
            if ( !$this->checkDepartmentExistenceByPk($pkDepartment) ) {
                HTTPResponses::error(404, self::DEPARTMENT_DOESNT_EXIST);
            }
            // Vérifie si un autre département avec le même nom existe
            if ( $this->checkDepartmentExistenceByName($name, true, $pkDepartment) ) {
                HTTPResponses::error(409, self::DEPARTMENT_ALREADY_EXISTS);
            }

            // Récupérer les employés actuellement affectés au département
            $currentEmployeesInDepartment = $this->wrkDB->select(GET_DEPARTMENT_EMPLOYEES, [$pkDepartment], true);
            $currentPkEmployees = array_map(fn($emp) => $emp['fk_employee'], $currentEmployeesInDepartment);

            // Déterminer les employés supprimés
            $newPkEmployees = array_map(fn($emp) => $emp['pk_employee'], $employees);
            $removedPkEmployees = array_diff($currentPkEmployees, $newPkEmployees);

            // Vérifier que chaque employé retiré est encore dans au moins un autre département
            foreach ( $removedPkEmployees as $finalPkEmployee ) {
                $departments = $this->wrkDB->select(GET_EMPLOYEE_DEPARTMENTS, [$finalPkEmployee], true);
                $employeeInfo = $this->wrkDB->select(GET_EXISTING_EMPLOYEE_BY_PK, [$finalPkEmployee]);
                if ( count($departments) <= 1 ) {
                    $firstName = $employeeInfo['first_name'];
                    $lastName = $employeeInfo['last_name'];
                    HTTPResponses::error(409, self::EMPLOYEE_CANNOT_BE_REMOVED . $firstName . ' ' . $lastName);
                }
            }

            // Met à jour le département dans une transaction
            $this->wrkDB->beginTransaction();
            $this->wrkDB->execute(UPDATE_DEPARTMENT, [$name, $notifyBySMS, $notifyByMail, $manager['pk_employee'], $communication['pk_communication'], $pkDepartment]);
            $this->wrkDB->execute(DELETE_DEPARTMENT_EMPLOYEES, [$pkDepartment]);
            // Met à jour les collaborateurs
            $this->insertEmployees($pkDepartment, $employees);
            $this->wrkDB->commit();

            // Récupère et renvoie le département mis à jour
            $department = $this->getDepartmentByPk($pkDepartment);

            HTTPResponses::success(self::UPDATE_DEPARTMENT_SUCCESS, $department);
        } catch ( Exception $ex ) {
            $this->wrkDB->rollBack();
            HTTPResponses::error(500, self::UPDATE_DEPARTMENT_ERROR . ' - ' . $ex->getMessage());
        }
    }

    /**
     * Supprime un département.
     *
     * @param array $params Les paramètres contenant la clé primaire du département à supprimer.
     * @return void
     */
    public function deleteDepartment(array $params): void {
        try {
            // Vérifie les données du département à supprimer
            $this->validateDeleteDepartment($params);

            $pkDepartment = $params['pk_department'];

            // Vérifie que le départment ne contient plus de collaborateurs
            if ( $this->checkDepartmentHasEmployees($pkDepartment) ) {
                HTTPResponses::error(409, self::DEPARTMENT_HAS_EMPLOYEES);
            }

            // Supprime le département dans une transaction
            $this->wrkDB->beginTransaction();
            $this->wrkDB->execute(DELETE_DEPARTMENT_BY_PK, [$pkDepartment]);
            $this->wrkDB->commit();

            HTTPResponses::success(self::DELETE_DEPARTMENT_SUCCESS);
        } catch ( Exception $ex ) {
            $this->wrkDB->rollBack();
            HTTPResponses::error(500, self::DELETE_DEPARTMENT_ERROR . ' - ' . $ex->getMessage());
        }
    }

    /**
     * Valide les données pour ajouter ou mettre à jour un département.
     *
     * @param array $data Les données à valider.
     * @param bool $isUpdate Indique s'il s'agit d'une mise à jour.
     * @return void
     */
    private function validateAddOrUpdateDepartment(array $data, bool $isUpdate = false): void {
        // Vérifie si la clé primaire du département est spécifiée lorsque c'est une mise à jour
        if ( $isUpdate && !isset($data['pk_department']) ) {
            HTTPResponses::error(400, self::UPDATE_DEPARTMENT_DATA_INVALID);
        }
        if ( !isset($data['name']) ||
            !isset($data['notify_by_sms']) ||
            !isset($data['notify_by_mail']) ||
            !isset($data['manager']) ||
            !isset($data['communication']) ||
            !isset($data['employees']) ) {
            HTTPResponses::error(400, self::ADD_DEPARTMENT_DATA_INVALID);
        }
        if ( $isUpdate ) $pkDepartment = $data['pk_department'];
        $name = $data['name'];
        $notifyBySMS = $data['notify_by_sms'];
        $notifyByMail = $data['notify_by_mail'];
        $manager = $data['manager'];
        $communication = $data['communication'];
        $employees = $data['employees'];
        if ( $isUpdate && !is_numeric($pkDepartment) ) {
            HTTPResponses::error(400, self::DEPARTMENT_PK_FORMAT_INVALID);
        }
        if ( !preg_match(self::REGEX_DEPARTMENT_NAME, $name) ) {
            HTTPResponses::error(400, self::DEPARTMENT_NAME_FORMAT_INVALID);
        }
        if ( !is_bool($notifyBySMS) ) {
            HTTPResponses::error(400, self::DEPARTMENT_NOTIFY_SMS_FORMAT_INVALID);
        }
        if ( !is_bool($notifyByMail) ) {
            HTTPResponses::error(400, self::DEPARTMENT_NOTIFY_MAIL_FORMAT_INVALID);
        }
        if ( !is_array($manager) ) {
            HTTPResponses::error(400, self::DEPARTMENT_MANAGER_FORMAT_INVALID);
        }
        if ( !is_array($communication) ) {
            HTTPResponses::error(400, self::DEPARTMENT_COMMUNICATION_FORMAT_INVALID);
        }
        if ( !is_array($employees) ) {
            HTTPResponses::error(400, self::DEPARTMENT_EMPLOYEES_FORMAT_INVALID);
        }
        // Validation du responsable, de la communication et les collaborateurs
        $this->validateManager($manager);
        $this->validateCommunication($communication);
        $this->validateEmployees($employees);
    }

    /**
     * Valide les données pour supprimer un département.
     *
     * @param array $params Les paramètres à valider.
     * @return void
     */
    private function validateDeleteDepartment(array $params): void {
        // Vérifie si la clé primaire du département est spécifiée et est numérique
        if ( !isset($params['pk_department']) || !is_numeric($params['pk_department']) ) {
            HTTPResponses::error(400, self::DELETE_DEPARTMENT_DATA_INVALID);
        }
        $pkDepartment = $params['pk_department'];
        // Vérifie que le département existe par sa clé primaire
        if ( !$this->checkDepartmentExistenceByPk($pkDepartment) ) {
            HTTPResponses::error(404, self::DEPARTMENT_DOESNT_EXIST);
        }
    }

    /**
     * Vérifie les données du responsable.
     *
     * @param array $manager Les données à valider.
     * @return void
     */
    private function validateManager(array $manager): void {
        // Vérifie que la clé primaire du responsable est spécifiée
        if ( !isset($manager['pk_employee']) ) {
            HTTPResponses::error(400, self::DEPARTMENT_MANAGER_CONTENT_INVALID);
        }
        // Vérifie que la clé primaire du responsable est numérique
        if ( !is_numeric($manager['pk_employee']) ) {
            HTTPResponses::error(400, self::DEPARTMENT_MANAGER_VALUE_INVALID);
        }
        $pkEmployee = $manager['pk_employee'];
        // Vérifie que le responsable existe par sa clé primaire
        $existingEmployee = $this->wrkDB->select(GET_EXISTING_EMPLOYEE_BY_PK, [$pkEmployee], true);
        if ( !$existingEmployee ) {
            HTTPResponses::error(400, self::DEPARTMENT_EMPLOYEE_DOESNT_EXIST . $pkEmployee);
        }
    }

    /**
     * Vérifie les données de la communication.
     *
     * @param array $communication Les données à valider.
     * @return void
     */
    private function validateCommunication(array $communication): void {
        // Vérifie que la clé primaire de la communication est spécifiée
        if ( !isset($communication['pk_communication']) ) {
            HTTPResponses::error(400, self::DEPARTMENT_COMMUNICATION_CONTENT_INVALID);
        }
        // Vérifie que la clé primaire de la communication est numérique
        if ( !is_numeric($communication['pk_communication']) ) {
            HTTPResponses::error(400, self::DEPARTMENT_COMMUNICATION_VALUE_INVALID);
        }
        $pkCommunication = $communication['pk_communication'];
        // Vérifie que la communication existe par sa clé primaire
        $existingCommunication = $this->wrkDB->select(GET_EXISTING_COMMUNICATION_BY_PK, [$pkCommunication], true);
        if ( !$existingCommunication ) {
            HTTPResponses::error(400, self::DEPARTMENT_COMMUNICATION_DOESNT_EXIST . $pkCommunication);
        }
    }

    /**
     * Vérifie les données des collaborateurs.
     *
     * @param array $data Les données à valider.
     * @return void
     */
    private function validateEmployees(array $data): void {
        foreach ( $data as $item ) {
            // Vérifie que la clé primaire de l'employé est spécifiée
            if ( !isset($item['pk_employee']) ) {
                HTTPResponses::error(400, self::DEPARTMENT_EMPLOYEES_CONTENT_INVALID);
            }
            // Vérifie que la clé primaire de l'employé est numérique
            if ( !is_numeric($item['pk_employee']) ) {
                HTTPResponses::error(400, self::DEPARTMENT_EMPLOYEES_VALUE_INVALID);
            }
            $pkEmployee = $item['pk_employee'];
            // Vérifie que l'employé existe par sa clé primaire
            $existingEmployee = $this->wrkDB->select(GET_EXISTING_EMPLOYEE_BY_PK, [$pkEmployee], true);
            if ( !$existingEmployee ) {
                HTTPResponses::error(400, self::DEPARTMENT_EMPLOYEE_DOESNT_EXIST . $pkEmployee);
            }
        }
    }

    /**
     * Récupère un département par clé primaire.
     *
     * @param int $pkDepartment La clé primaire du département.
     * @return array Les informations du département trouvé.
     */
    public function getDepartmentByPk(int $pkDepartment): array {
        $results = $this->wrkDB->select(GET_DEPARTMENT_BY_PK, [$pkDepartment], true);
        $department = [
            'pk_department' => $results[0]['pk_department'],
            'name' => $results[0]['name'],
            'notify_by_sms' => $results[0]['notify_by_sms'],
            'notify_by_mail' => $results[0]['notify_by_mail'],
            'manager' => [
                'pk_employee' => $results[0]['manager_pk_employee'],
                'first_name' => $results[0]['manager_first_name'],
                'last_name' => $results[0]['manager_last_name']
            ],
            'communication' => [
                'pk_communication' => $results[0]['communication_pk_communication'],
                'description' => $results[0]['communication_description']
            ],
            'employees' => []
        ];
        // Ajouter les collaborateurs
        foreach ( $results as $row ) {
            if ( !is_null($row['emp_pk_employee']) ) {
                $department['employees'][] = [
                    'pk_employee' => $row['emp_pk_employee'],
                    'first_name' => $row['emp_first_name'],
                    'last_name' => $row['emp_last_name']
                ];
            }
        }
        return $department;
    }

    /**
     * Insère les collaborateurs et responsables dans la base de données.
     *
     * @param int $pkDepartment La clé primaire du département.
     * @param array $employees Les collaborateurs à insérer.
     * @return void
     */
    private function insertEmployees(int $pkDepartment, array $employees): void {
        // Pour chaque collaborateur, insérer le lien avec le département
        foreach ( $employees as $employee ) {
            $this->wrkDB->execute(INSERT_DEPARTMENT_EMPLOYEE, [$pkDepartment, $employee['pk_employee']]);
        }
    }

    /**
     * Vérifie l'existence d'un département par clé primaire.
     *
     * @param int $pkDepartment La clé primaire du département.
     * @return bool Vrai si le département existe, faux sinon.
     */
    private function checkDepartmentExistenceByPk(int $pkDepartment): bool {
        return !empty($this->wrkDB->select(GET_DEPARTMENT_BY_PK, [$pkDepartment]));
    }

    /**
     * Vérifie l'existence d'un département par nom.
     *
     * @param string $name Le nom du département.
     * @return bool Vrai si le département existe, faux sinon.
     */
    private function checkDepartmentExistenceByName(string $name, bool $isUpdate = false, int $pkEmployee = null): bool {
        if ( $isUpdate ) {
            return !empty($this->wrkDB->select(GET_DEPARTMENT_BY_NAME_EXCEPT_PK, [$name, $pkEmployee]));
        } else {
            return !empty($this->wrkDB->select(GET_DEPARTMENT_BY_NAME, [$name]));
        }
    }

    /**
     * Vérifie si un département contient des collaborateurs.
     *
     * @param int $pkDepartment La clé primaire du département.
     * @return bool Vrai si le département contient des collaborateurs, faux sinon.
     */
    private function checkDepartmentHasEmployees(int $pkDepartment): bool {
        return !empty($this->wrkDB->select(GET_DEPARTMENT_EMPLOYEES, [$pkDepartment]));
    }

}