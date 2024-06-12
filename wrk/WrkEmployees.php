<?php

namespace Wrk;

use DateTime;
use Exception;
use HTTP\HTTPResponses;

/**
 * Classe de gestion des collaborateurs.
 *
 * Cette classe gère les collaborateurs et permet de les récupérer, d'en ajouter, d'en modifier et de les supprimer.
 *
 * @since 05.2024
 * @author Noé Henchoz
 */
class WrkEmployees {

    // Constantes pour les validations de données
    private const string REGEX_EMPLOYEE_FIRST_NAME = "/^[a-zA-ZÀ-ÿ\s'-]{1,32}$/";
    private const string REGEX_EMPLOYEE_LAST_NAME = "/^[a-zA-ZÀ-ÿ\s'-]{1,32}$/";
    private const string REGEX_EMPLOYEE_MAIL = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
    private const string REGEX_EMPLOYEE_TEL_NUMBER = "/^\+41[0-9]{9}$/";
    private const string REGEX_EMPLOYEE_DATE_OF_BIRTH = "/^\d{4}-\d{2}-\d{2}$/";
    private const string REGEX_EMPLOYEE_DATE_OF_HIRE = "/^\d{4}-\d{2}-\d{2}$/";

    // Constantes pour les messages de succès
    private const string GET_EMPLOYEES_SUCCESS = "La liste des collaborateurs a été récupérée avec succès";
    private const string ADD_EMPLOYEE_SUCCESS = "Le collaborateur a été ajouté avec succès";
    private const string UPDATE_EMPLOYEE_SUCCESS = "Le collaborateur a été modifié avec succès";
    private const string DELETE_EMPLOYEE_SUCCESS = "Le collaborateur a été supprimé avec succès";

    // Constantes pour les messages d'erreur
    private const string GET_EMPLOYEES_ERROR = "La liste des collaborateurs n'a pas pu être récupérée";
    private const string ADD_EMPLOYEE_ERROR = "Le collaborateur n'a pas pu être ajouté";
    private const string UPDATE_EMPLOYEE_ERROR = "Le collaborateur n'a pas pu être ajouté";
    private const string DELETE_EMPLOYEE_ERROR = "Le collaborateur n'a pas pu être supprimé";

    private const string ADD_EMPLOYEE_DATA_INVALID = "Le prénom, le nom, le mail, le numéro de téléphone, la date de naissance et la date d'engagement doivent être spécifiées";
    private const string UPDATE_EMPLOYEE_DATA_INVALID = "La clé primaire, le prénom, le nom, le mail, le numéro de téléphone, la date de naissance et la date d'engagement doivent être spécifiées";
    private const string DELETE_EMPLOYEE_DATA_INVALID = "La clé primaire doit être spécifiée";

    private const string EMPLOYEE_PK_FORMAT_INVALID = "La clé primaire doit être un nombre";
    private const string EMPLOYEE_FIRST_NAME_FORMAT_INVALID = "Le prénom doit contenir uniquement des caractères alphabétiques et avoir une longueur comprise entre 1 et 32 caractères";
    private const string EMPLOYEE_LAST_NAME_FORMAT_INVALID = "Le nom doit contenir uniquement des caractères alphabétiques et avoir une longueur comprise entre 1 et 32 caractères";
    private const string EMPLOYEE_MAIL_FORMAT_INVALID = "Le mail ne respecte pas le bon format";
    private const string EMPLOYEE_TEL_NUMBER_FORMAT_INVALID = "Le numéro de téléphone doit respecter le format suivant : +4179xxxxxxx";
    private const string EMPLOYEE_DATE_OF_BIRTH_FORMAT_INVALID = "La date de naissance doit respecter le format suivant : 1970-12-30";
    private const string EMPLOYEE_DATE_OF_HIRE_FORMAT_INVALID = "La date d'engagement doit respecter le format suivant : 1970-12-30";
    private const string EMPLOYEE_DEPARTMENTS_FORMAT_INVALID = "Les départements doivent être sous forme de tableau et au moins contenir un département";
    private const string EMPLOYEE_DEPARTMENTS_CONTENT_INVALID = "Les départements dont l'employé fait partie doivent être fournis sous forme d'objets avec leur clé primaire";
    private const string EMPLOYEE_DEPARTMENTS_VALUE_INVALID = "La clé primaire doit être un nombre";
    private const string EMPLOYEE_DATE_OF_HIRE_BEFORE_DATE_OF_BIRTH = "La d'engagement ne peut pas être antérieur à la date de naissance";

    private const string EMPLOYEE_DEPARTMENT_MEMBER_DOESNT_EXIST = "Le département fourni n'existe pas : ";
    private const string EMPLOYEE_IS_MANAGER = "Le collaborateur ne peut pas être supprimé, il est responsable d'un département : ";
    private const string EMPLOYEE_ALREADY_EXISTS = "Le collaborateur existe déjà";
    private const string EMPLOYEE_DOESNT_EXIST = "Le collaborateur n'existe pas";

    private WrkDB $wrkDB;

    /**
     * Constructeur de la classe WrkEmployees.
     *
     * Initialise l'instance de la classe WrkDB.
     */
    public function __construct() {
        $this->wrkDB = new WrkDB();
    }

    /**
     * Récupère la liste des collaborateurs.
     *
     * @return void
     */
    public function getEmployees(): void {
        try {
            $employees = $this->wrkDB->select(GET_EMPLOYEES, [], true);
            $employeesMap = [];
            foreach ( $employees as $employee ) {
                $pk_employee = $employee['pk_employee'];
                // Si le collaborateur n'est pas déjà dans le résultat, l'ajouter
                if ( !isset($employeesMap[$pk_employee]) ) {
                    $employeesMap[$pk_employee] = [
                        'pk_employee' => $employee['pk_employee'],
                        'first_name' => $employee['first_name'],
                        'last_name' => $employee['last_name'],
                        'mail' => $employee['mail'],
                        'tel_number' => $employee['tel_number'],
                        'date_of_birth' => $employee['date_of_birth'],
                        'date_of_hire' => $employee['date_of_hire'],
                        'departments' => []
                    ];
                }
                // Ajoute les départements du collaborateur
                if ( $employee['department_pk_department'] ) {
                    $employeesMap[$pk_employee]['departments'][] = [
                        'pk_department' => $employee['department_pk_department'],
                        'name' => $employee['department_name']
                    ];
                }
            }
            // Conversion du résultat en un tableau indexé numériquement
            $result = array_values($employeesMap);
            HTTPResponses::success(self::GET_EMPLOYEES_SUCCESS, $result);
        } catch ( Exception $ex ) {
            HTTPResponses::error(500, self::GET_EMPLOYEES_ERROR . ' - ' . $ex->getMessage());
        }
    }


    /**
     * Ajoute un nouveau collaborateur.
     *
     * @param array $requestBody Les données du collaborateur à ajouter.
     * @return void
     */
    public function addEmployee(array $requestBody): void {
        try {
            // Vérifie les données du collaborateur à ajouter
            $this->validateAddOrUpdateEmployee($requestBody);

            $firstName = $requestBody['first_name'];
            $lastName = $requestBody['last_name'];
            $mail = $requestBody['mail'];
            $telNumber = $requestBody['tel_number'];
            $dateOfBirth = $requestBody['date_of_birth'];
            $dateOfHire = $requestBody['date_of_hire'];
            $departments = $requestBody['departments'];

            // Vérifie si un collaborateur avec le même mail ou numéro de téléphone existe déjà
            if ( $this->checkEmployeeExistenceByMail($mail) || $this->checkEmployeeExistenceByTelNumber($telNumber) ) {
                HTTPResponses::error(409, self::EMPLOYEE_ALREADY_EXISTS);
            }

            // Ajoute le collaborateur dans une transaction
            $this->wrkDB->beginTransaction();
            $this->wrkDB->execute(INSERT_EMPLOYEE, [$firstName, $lastName, $mail, $telNumber, $dateOfBirth, $dateOfHire]);
            $pkEmployee = $this->wrkDB->lastInsertId();
            // Insère les départements de ce collaborateur
            $this->insertDepartments($pkEmployee, $departments);
            $this->wrkDB->commit();

            // Récupère et renvoie le nouveau collaborateur
            $employee = $this->getEmployeeByPk($pkEmployee);

            HTTPResponses::success(self::ADD_EMPLOYEE_SUCCESS, $employee);
        } catch ( Exception $ex ) {
            $this->wrkDB->rollBack();
            HTTPResponses::error(500, self::ADD_EMPLOYEE_ERROR . ' - ' . $ex->getMessage());
        }
    }

    /**
     * Met à jour un collaborateur existant.
     *
     * @param array $requestBody Les données du collaborateur à mettre à jour.
     * @return void
     */
    public function updateEmployee(array $requestBody): void {
        try {
            // Vérifie les données du collaborateur à mettre à jour
            $this->validateAddOrUpdateEmployee($requestBody, true);

            $pkEmployee = $requestBody['pk_employee'];
            $firstName = $requestBody['first_name'];
            $lastName = $requestBody['last_name'];
            $mail = $requestBody['mail'];
            $telNumber = $requestBody['tel_number'];
            $dateOfBirth = $requestBody['date_of_birth'];
            $dateOfHire = $requestBody['date_of_hire'];
            $departments = $requestBody['departments'];

            // Vérifie si le collaborateur existe par clé primaire
            if ( !$this->checkEmployeeExistenceByPk($pkEmployee) ) {
                HTTPResponses::error(404, self::EMPLOYEE_DOESNT_EXIST);
            }
            // Vérifie si un autre collaborateur avec le même mail ou numéro de téléphone existe
            if ( $this->checkEmployeeExistenceByMail($mail, true, $pkEmployee) || $this->checkEmployeeExistenceByTelNumber($telNumber, true, $pkEmployee) ) {
                HTTPResponses::error(409, self::EMPLOYEE_ALREADY_EXISTS);
            }

            // Met à jour le collaborateur dans une transaction
            $this->wrkDB->beginTransaction();
            $this->wrkDB->execute(UPDATE_EMPLOYEE, [$firstName, $lastName, $mail, $telNumber, $dateOfBirth, $dateOfHire, $pkEmployee]);
            $this->wrkDB->execute(DELETE_EMPLOYEE_DEPARTMENTS, [$pkEmployee]);
            // Met à jour les départements de ce collaborateur
            $this->insertDepartments($pkEmployee, $departments);
            $this->wrkDB->commit();

            // Récupère et renvoie le collaborateur mis à jour
            $employee = $this->getEmployeeByPk($pkEmployee);

            HTTPResponses::success(self::UPDATE_EMPLOYEE_SUCCESS, $employee);
        } catch ( Exception $ex ) {
            $this->wrkDB->rollBack();
            HTTPResponses::error(500, self::UPDATE_EMPLOYEE_ERROR . ' - ' . $ex->getMessage());
        }
    }

    /**
     * Supprime un collaborateur existant.
     *
     * @param array $params Les paramètres de la requête de suppression.
     * @return void
     */
    public function deleteEmployee(array $params): void {
        try {
            // Vérifie les paramètres de la requête de suppression
            $this->validateDeleteEmployee($params);

            $pkEmployee = $params['pk_employee'];

            // Vérifie si le collaborateur est responsable d'un département
            $departmentName = $this->checkEmployeeIsManager($pkEmployee);
            if ( $departmentName !== false ) {
                HTTPResponses::error(409, self::EMPLOYEE_IS_MANAGER . $departmentName);
            }

            // Supprime le collaborateur dans une transaction
            $this->wrkDB->beginTransaction();
            $this->wrkDB->execute(DELETE_EMPLOYEE_BY_PK, [$pkEmployee]);
            $this->wrkDB->commit();

            HTTPResponses::success(self::DELETE_EMPLOYEE_SUCCESS);
        } catch ( Exception $ex ) {
            $this->wrkDB->rollBack();
            HTTPResponses::error(500, self::DELETE_EMPLOYEE_ERROR . ' - ' . $ex->getMessage());
        }
    }

    /**
     * Valide les données pour l'ajout ou la mise à jour d'un collaborateur.
     *
     * @param array $data Les données du collaborateur.
     * @param bool $isUpdate Indique s'il s'agit d'une mise à jour.
     * @return void
     */
    private function validateAddOrUpdateEmployee(array $data, bool $isUpdate = false): void {
        // Vérifie si la clé primaire du collaborateur est spécifiée lorsque c'est une mise à jour
        if ( $isUpdate && !isset($data['pk_employee']) ) {
            HTTPResponses::error(400, self::UPDATE_EMPLOYEE_DATA_INVALID);
        }
        if ( !isset($data['first_name']) ||
            !isset($data['last_name']) ||
            !isset($data['mail']) ||
            !isset($data['tel_number']) ||
            !isset($data['date_of_birth']) ||
            !isset($data['date_of_hire']) ||
            !isset($data['departments']) ) {
            HTTPResponses::error(400, self::ADD_EMPLOYEE_DATA_INVALID);
        }
        if ( $isUpdate ) $pkEmployee = $data['pk_employee'];
        $firstName = $data['first_name'];
        $lastName = $data['last_name'];
        $mail = $data['mail'];
        $telNumber = $data['tel_number'];
        $dateOfBirth = $data['date_of_birth'];
        $dateOfHire = $data['date_of_hire'];
        $departments = $data['departments'];
        if ( $isUpdate && !is_numeric($pkEmployee) ) {
            HTTPResponses::error(400, self::EMPLOYEE_PK_FORMAT_INVALID);
        }
        if ( !preg_match(self::REGEX_EMPLOYEE_FIRST_NAME, $firstName) ) {
            HTTPResponses::error(400, self::EMPLOYEE_FIRST_NAME_FORMAT_INVALID);
        }
        if ( !preg_match(self::REGEX_EMPLOYEE_LAST_NAME, $lastName) ) {
            HTTPResponses::error(400, self::EMPLOYEE_LAST_NAME_FORMAT_INVALID);
        }
        if ( !preg_match(self::REGEX_EMPLOYEE_MAIL, $mail) ) {
            HTTPResponses::error(400, self::EMPLOYEE_MAIL_FORMAT_INVALID);
        }
        if ( !preg_match(self::REGEX_EMPLOYEE_TEL_NUMBER, $telNumber) ) {
            HTTPResponses::error(400, self::EMPLOYEE_TEL_NUMBER_FORMAT_INVALID);
        }
        if ( !preg_match(self::REGEX_EMPLOYEE_DATE_OF_BIRTH, $dateOfBirth) || !$this->validateDate($dateOfBirth) ) {
            HTTPResponses::error(400, self::EMPLOYEE_DATE_OF_BIRTH_FORMAT_INVALID);
        }
        if ( !preg_match(self::REGEX_EMPLOYEE_DATE_OF_HIRE, $dateOfHire) || !$this->validateDate($dateOfHire) ) {
            HTTPResponses::error(400, self::EMPLOYEE_DATE_OF_HIRE_FORMAT_INVALID);
        }
        if ( $dateOfHire < $dateOfBirth ) {
            HTTPResponses::error(400, self::EMPLOYEE_DATE_OF_HIRE_BEFORE_DATE_OF_BIRTH);
        }
        if ( !is_array($departments) || count($departments) == 0 ) {
            HTTPResponses::error(400, self::EMPLOYEE_DEPARTMENTS_FORMAT_INVALID);
        }
        // Validation des départements
        $this->validateDepartments($departments);
    }

    /**
     * Valide les données pour la suppression d'un collaborateur.
     *
     * @param array $params Les paramètres de la requête de suppression.
     * @return void
     */
    private function validateDeleteEmployee(array $params): void {
        // Vérifie si la clé primaire du collaborateur est spécifiée et qu'elle est numérique
        if ( !isset($params['pk_employee']) || !is_numeric($params['pk_employee']) ) {
            HTTPResponses::error(400, self::DELETE_EMPLOYEE_DATA_INVALID);
        }
        $pkEmployee = $params['pk_employee'];
        // Vérifie si le collaborateur existe
        if ( !$this->checkEmployeeExistenceByPk($pkEmployee) ) {
            HTTPResponses::error(404, self::EMPLOYEE_DOESNT_EXIST);
        }
    }

    /**
     * Valide les départements auxquels le collaborateur appartient.
     *
     * @param array $data Les données des départements.
     * @return void
     */
    public function validateDepartments(array $data): void {
        foreach ( $data as $item ) {
            // Vérifie si la clé primaire du département est spécifiée
            if ( !isset($item['pk_department']) ) {
                HTTPResponses::error(400, self::EMPLOYEE_DEPARTMENTS_CONTENT_INVALID);
            }
            // Vérifie si la clé primaire du département est numérique
            if ( !is_numeric($item['pk_department']) ) {
                HTTPResponses::error(400, self::EMPLOYEE_DEPARTMENTS_VALUE_INVALID);
            }
            $pkDepartment = $item['pk_department'];
            // Vérifie si le département existe
            $existingDepartment = $this->wrkDB->select(GET_EXISTING_DEPARTMENT_BY_PK, [$pkDepartment], true);
            if ( !$existingDepartment ) {
                HTTPResponses::error(400, self::EMPLOYEE_DEPARTMENT_MEMBER_DOESNT_EXIST . $pkDepartment);
            }
        }
    }

    /**
     * Récupère un collaborateur par clé primaire.
     *
     * @param int $pkEmployee La clé primaire du collaborateur.
     * @return array Les informations du collaborateur trouvé.
     */
    public function getEmployeeByPk(int $pkEmployee): array {
        $results = $this->wrkDB->select(GET_EMPLOYEE_BY_PK, [$pkEmployee], true);
        $employee = [
            'pk_employee' => $results[0]['pk_employee'],
            'first_name' => $results[0]['first_name'],
            'last_name' => $results[0]['last_name'],
            'mail' => $results[0]['mail'],
            'tel_number' => $results[0]['tel_number'],
            'date_of_birth' => $results[0]['date_of_birth'],
            'date_of_hire' => $results[0]['date_of_hire'],
            'departments' => []
        ];
        // Ajouter les départements du collaborateur
        foreach ( $results as $row ) {
            if ( !is_null($row['department_pk_department']) ) {
                $employee['departments'][] = [
                    'pk_department' => $row['department_pk_department'],
                    'name' => $row['department_name'],
                ];
            }
        }
        return $employee;
    }

    /**
     * Insère les départements pour un collaborateur donné.
     *
     * @param int $pkEmployee La clé primaire du collaborateur.
     * @param array $departments Les départements auxquels le collaborateur appartient.
     * @return void
     */
    private function insertDepartments(int $pkEmployee, array $departments): void {
        // Pour chaque département, insère le lien entre le collaborateur et le département
        foreach ( $departments as $department ) {
            $this->wrkDB->execute(INSERT_EMPLOYEE_DEPARTMENT, [$department['pk_department'], $pkEmployee]);
        }
    }

    /**
     * Vérifie l'existence d'un collaborateur par sa clé primaire.
     *
     * @param int $pkEmployee La clé primaire du collaborateur.
     * @return bool True si le collaborateur existe, sinon False.
     */
    private function checkEmployeeExistenceByPk(int $pkEmployee): bool {
        return !empty($this->wrkDB->select(GET_EMPLOYEE_BY_PK, [$pkEmployee]));
    }

    /**
     * Vérifie l'existence d'un collaborateur par son adresse e-mail.
     *
     * @param string $mail Le mail du collaborateur.
     * @param bool $isUpdate Indique s'il s'agit d'une mise à jour.
     * @param int|null $pkEmployee La clé primaire du collaborateur.
     * @return bool Vrai si le collaborateur existe, faux sinon.
     */
    private function checkEmployeeExistenceByMail(string $mail, bool $isUpdate = false, int $pkEmployee = null): bool {
        if ( $isUpdate ) {
            return !empty($this->wrkDB->select(GET_EMPLOYEE_BY_MAIL_EXCEPT_PK, [$mail, $pkEmployee]));
        } else {
            return !empty($this->wrkDB->select(GET_EMPLOYEE_BY_MAIL, [$mail]));
        }
    }

    /**
     * Vérifie l'existence d'un collaborateur par son numéro de téléphone.
     *
     * @param string $telNumber Le numéro de téléphone du collaborateur.
     * @param bool $isUpdate Indique s'il s'agit d'une mise à jour.
     * @param int|null $pkEmployee La clé primaire du collaborateur.
     * @return bool Vrai si le collaborateur existe, faux sinon.
     */
    private function checkEmployeeExistenceByTelNumber(string $telNumber, bool $isUpdate = false, int $pkEmployee = null): bool {
        if ( $isUpdate ) {
            return !empty($this->wrkDB->select(GET_EMPLOYEE_BY_TEL_NUMBER_EXCEPT_PK, [$telNumber, $pkEmployee]));
        } else {
            return !empty($this->wrkDB->select(GET_EMPLOYEE_BY_TEL_NUMBER, [$telNumber]));
        }
    }

    /**
     * Vérifie qu'un collaborateur n'est pas manager d'un département.
     *
     * @param int $pkEmployee La clé primaire du collaborateur.
     * @return string|bool Le nom du département ou False si le collaborateur n'est pas manager
     */
    private function checkEmployeeIsManager(int $pkEmployee): string|bool {
        $result = $this->wrkDB->select(CHECK_EMPLOYEE_IS_MANAGER, [$pkEmployee]);
        return $result ? $result['department_name'] : false;
    }

    /**
     * Vérifie si une date est valide.
     *
     * @param string $date La date à vérifier.
     * @return bool Vrai si la date est valide, faux sinon.
     */
    private function validateDate($date): bool {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date && $d->format('Y') >= 1900 && $d->format('Y') <= date('Y');
    }

}