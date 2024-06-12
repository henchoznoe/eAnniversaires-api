<?php

namespace HTTP;

use Ctrl\AuthCtrl;
use Ctrl\BirthdaysCtrl;
use Ctrl\CommunicationsCtrl;
use Ctrl\DepartmentsCtrl;
use Ctrl\EmployeesCtrl;

require_once ROOT . "/ctrl/AuthCtrl.php";
require_once ROOT . "/ctrl/EmployeesCtrl.php";
require_once ROOT . "/ctrl/DepartmentsCtrl.php";
require_once ROOT . "/ctrl/CommunicationsCtrl.php";
require_once ROOT . "/ctrl/BirthdaysCtrl.php";

/**
 * Classe HTTPHandlers pour la gestion des requêtes HTTP.
 *
 * Cette classe gère les différentes actions à effectuer en fonction des requêtes reçues.
 *
 * @author Noé Henchoz
 * @since 05.2024
 */
class HTTPHandlers {

    // Constantes pour les actions disponibles
    private const string ACTION = "action";
    private const string A_LOGIN = "login";
    private const string A_GET_EMPLOYEES = "getEmployees";
    private const string A_ADD_EMPLOYEE = "addEmployee";
    private const string A_UPDATE_EMPLOYEE = "updateEmployee";
    private const string A_DELETE_EMPLOYEE = "deleteEmployee";
    private const string A_GET_DEPARTMENTS = "getDepartments";
    private const string A_ADD_DEPARTMENT = "addDepartment";
    private const string A_UPDATE_DEPARTMENT = "updateDepartment";
    private const string A_DELETE_DEPARTMENT = "deleteDepartment";
    private const string A_GET_COMMUNICATIONS = "getCommunications";
    private const string A_ADD_COMMUNICATIONS = "addCommunications";
    private const string A_UPDATE_COMMUNICATIONS = "updateCommunications";
    private const string A_DELETE_COMMUNICATIONS = "deleteCommunications";
    private const string A_GET_TODAYS_BIRTHDAYS = "getTodaysBirthdays";
    private const string A_GET_SPECIAL_BIRTHDAYS = "getSpecialBirthdays";
    private const string A_GET_MONTHS_BIRTHDAYS = "getMonthsBirthdays";
    private const string A_GET_ADMIN_MONTHS_BIRTHDAYS = "getAdminMonthsBirthdays";

    // Constantes pour les messages d'erreurs
    private const string UNSPECIFIED_ACTION = "L'action n'est pas spécifiée";
    private const string UNKNOWN_ACTION = "L'action n'est pas reconnue par ce serveur";
    private const string ERROR_REQUEST_BODY = "Le corps de la requête HTTP qui doit être au format JSON est mal formé";

    private AuthCtrl $authCtrl;
    private EmployeesCtrl $employeesCtrl;
    private DepartmentsCtrl $departmentsCtrl;
    private CommunicationsCtrl $communicationsCtrl;
    private BirthdaysCtrl $birthdaysCtrl;

    /**
     * Constructeur de la classe HTTPHandlers.
     *
     * Initialise les instances des différents contrôleurs.
     */
    public function __construct() {
        $this->authCtrl = new AuthCtrl();
        $this->employeesCtrl = new EmployeesCtrl();
        $this->departmentsCtrl = new DepartmentsCtrl();
        $this->communicationsCtrl = new CommunicationsCtrl();
        $this->birthdaysCtrl = new BirthdaysCtrl();
    }

    /**
     * Gère les requêtes HTTP GET.
     *
     * Effectue les actions appropriées en fonction du paramètre 'action' de la requête.
     *
     * @return void
     */
    public function GET(): void {
        if ( isset($_GET[self::ACTION]) ) {
            switch ( $_GET[self::ACTION] ) {
                case self::A_GET_MONTHS_BIRTHDAYS:
                    $this->birthdaysCtrl->getMonthsBirthdays();
                    break;
                case self::A_GET_ADMIN_MONTHS_BIRTHDAYS:
                    $this->authCtrl->checkToken();
                    $this->birthdaysCtrl->getAdminMonthsBirthdays();
                    break;
                case self::A_GET_EMPLOYEES:
                    $this->authCtrl->checkToken();
                    $this->employeesCtrl->getEmployees();
                    break;
                case self::A_GET_DEPARTMENTS:
                    $this->authCtrl->checkToken();
                    $this->departmentsCtrl->getDepartments();
                    break;
                case self::A_GET_COMMUNICATIONS:
                    $this->authCtrl->checkToken();
                    $this->communicationsCtrl->getCommunications();
                    break;
                case self::A_GET_TODAYS_BIRTHDAYS:
                    $this->authCtrl->checkToken();
                    $this->birthdaysCtrl->getTodaysBirthdays();
                    break;
                case self::A_GET_SPECIAL_BIRTHDAYS:
                    $this->authCtrl->checkToken();
                    $this->birthdaysCtrl->getSpecialBirthdays();
                    break;
                default:
                    HTTPResponses::error(400, self::UNKNOWN_ACTION);
                    break;
            }
        } else {
            HTTPResponses::error(400, self::UNSPECIFIED_ACTION);
        }
    }

    /**
     * Gère les requêtes HTTP POST.
     *
     * Effectue les actions appropriées en fonction du paramètre 'action' du corps de la requête.
     *
     * @return void
     */
    public function POST(): void {
        $requestBody = $this->checkRequestBody();
        if ( isset($requestBody[self::ACTION]) ) {
            switch ( $requestBody[self::ACTION] ) {
                case self::A_LOGIN:
                    $this->authCtrl->login($requestBody);
                    break;
                case self::A_ADD_EMPLOYEE:
                    $this->authCtrl->checkToken();
                    $this->employeesCtrl->addEmployee($requestBody);
                    break;
                case self::A_ADD_DEPARTMENT:
                    $this->authCtrl->checkToken();
                    $this->departmentsCtrl->addDepartment($requestBody);
                    break;
                case self::A_ADD_COMMUNICATIONS:
                    $this->authCtrl->checkToken();
                    $this->communicationsCtrl->addCommunications($requestBody);
                    break;
                default:
                    HTTPResponses::error(400, self::UNKNOWN_ACTION);
                    break;
            }
        } else {
            HTTPResponses::error(400, self::UNSPECIFIED_ACTION);
        }
    }

    /**
     * Gère les requêtes HTTP PUT.
     *
     * Effectue les actions appropriées en fonction du paramètre 'action' du corps de la requête.
     *
     * @return void
     */
    public function PUT(): void {
        $requestBody = $this->checkRequestBody();
        if ( isset($requestBody[self::ACTION]) ) {
            switch ( $requestBody[self::ACTION] ) {
                case self::A_UPDATE_EMPLOYEE:
                    $this->authCtrl->checkToken();
                    $this->employeesCtrl->updateEmployee($requestBody);
                    break;
                case self::A_UPDATE_DEPARTMENT:
                    $this->authCtrl->checkToken();
                    $this->departmentsCtrl->updateDepartment($requestBody);
                    break;
                case self::A_UPDATE_COMMUNICATIONS:
                    $this->authCtrl->checkToken();
                    $this->communicationsCtrl->updateCommunications($requestBody);
                    break;
                default:
                    HTTPResponses::error(400, self::UNKNOWN_ACTION);
                    break;
            }
        } else {
            HTTPResponses::error(400, self::UNSPECIFIED_ACTION);
        }
    }

    /**
     * Gère les requêtes HTTP DELETE.
     *
     * Effectue les actions appropriées en fonction du paramètre 'action' de la requête.
     *
     * @return void
     */
    public function DELETE(): void {
        if ( isset($_GET[self::ACTION]) ) {
            $requestParams = $this->checkRequestParams();
            switch ( $_GET[self::ACTION] ) {
                case self::A_DELETE_EMPLOYEE:
                    $this->authCtrl->checkToken();
                    $this->employeesCtrl->deleteEmployee($requestParams);
                    break;
                case self::A_DELETE_DEPARTMENT:
                    $this->authCtrl->checkToken();
                    $this->departmentsCtrl->deleteDepartment($requestParams);
                    break;
                case self::A_DELETE_COMMUNICATIONS:
                    $this->authCtrl->checkToken();
                    $this->communicationsCtrl->deleteCommunications($requestParams);
                    break;
                default:
                    HTTPResponses::error(400, self::UNKNOWN_ACTION);
                    break;
            }
        } else {
            HTTPResponses::error(400, self::UNSPECIFIED_ACTION);
        }
    }

    /**
     * Vérifie et extrait les paramètres de la requête.
     *
     * @return array Les paramètres de la requête.
     */
    private function checkRequestParams(): array {
        $requestParams = array();
        foreach ( $_GET as $key => $value ) {
            if ( $key !== self::ACTION ) {
                $requestParams[$key] = $value;
            }
        }
        return $requestParams;
    }

    /**
     * Vérifie et extrait le corps de la requête.
     *
     * @return array|null Le corps de la requête en tant que tableau associatif, ou null en cas d'erreur de format.
     */
    private function checkRequestBody(): ?array {
        $requestBody = json_decode(file_get_contents("php://input"), true);
        if ( $requestBody === null && json_last_error() !== JSON_ERROR_NONE ) {
            HTTPResponses::error(400, self::ERROR_REQUEST_BODY);
        }
        return $requestBody;
    }

}