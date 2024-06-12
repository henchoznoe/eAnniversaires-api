<?php

/**
 * Fichier de requêtes SQL.
 *
 * Ce fichier contient des constantes représentant des requêtes SQL préparées pour récupérer, insérer, modifier ou supprimer
 * des données dans les tables de la base de données.
 *
 * @since 05.2024
 * @author Noé Henchoz
 */

// Gestion de l'authentification
const GET_ADMIN_BY_MAIL = <<< SQL
    SELECT 
        pk_admin, 
        mail, 
        password 
    FROM 
        t_admin 
    WHERE 
        mail = ?;
SQL;
// Gestion de l'authentification

// Gestion des départements
const GET_DEPARTMENTS = <<< SQL
    SELECT 
        d.pk_department, 
        d.name, 
        d.notify_by_sms, 
        d.notify_by_mail,
        e.pk_employee AS manager_pk_employee,
        e.first_name AS manager_first_name, 
        e.last_name AS manager_last_name,
        c.pk_communication AS communication_pk_communication,
        c.description AS communication_description,
        emp.pk_employee AS emp_pk_employee,
        emp.first_name AS emp_first_name,
        emp.last_name AS emp_last_name
    FROM 
        t_department d
    INNER JOIN 
        t_employee e ON d.fk_manager = e.pk_employee
    INNER JOIN 
        t_communication c ON d.fk_communication = c.pk_communication
    LEFT JOIN 
        tr_department_employee de ON d.pk_department = de.fk_department
    LEFT JOIN 
        t_employee emp ON de.fk_employee = emp.pk_employee
    ORDER BY
        d.name;
SQL;

const GET_DEPARTMENT_BY_NAME = <<< SQL
    SELECT 
        *
    FROM 
        t_department
    WHERE 
        name = ?;
SQL;

const GET_DEPARTMENT_BY_NAME_EXCEPT_PK = <<< SQL
    SELECT
        *
    FROM
        t_department
    WHERE
        name = ? AND pk_department != ?;
SQL;

const INSERT_DEPARTMENT = <<< SQL
    INSERT INTO 
        t_department (name, notify_by_sms, notify_by_mail, fk_manager, fk_communication)
    VALUES 
        (?, ?, ?, ?, ?);
SQL;

const INSERT_DEPARTMENT_EMPLOYEE = <<< SQL
    INSERT INTO 
        tr_department_employee (fk_department, fk_employee)
    VALUES 
        (?, ?);
SQL;

const GET_EXISTING_EMPLOYEE_BY_PK = <<< SQL
    SELECT 
        pk_employee,
        first_name,
        last_name
    FROM 
        t_employee
    WHERE 
        pk_employee = ?;
SQL;

const GET_DEPARTMENT_BY_PK = <<< SQL
    SELECT 
        d.pk_department, 
        d.name, 
        d.notify_by_sms, 
        d.notify_by_mail,
        e.pk_employee AS manager_pk_employee,
        e.first_name AS manager_first_name, 
        e.last_name AS manager_last_name,
        c.pk_communication AS communication_pk_communication,
        c.description AS communication_description,
        emp.pk_employee AS emp_pk_employee,
        emp.first_name AS emp_first_name,
        emp.last_name AS emp_last_name
    FROM 
        t_department d
    INNER JOIN 
        t_employee e ON d.fk_manager = e.pk_employee
    INNER JOIN 
        t_communication c ON d.fk_communication = c.pk_communication
    LEFT JOIN 
        tr_department_employee de ON d.pk_department = de.fk_department
    LEFT JOIN 
        t_employee emp ON de.fk_employee = emp.pk_employee
    WHERE
        d.pk_department = ?
    ORDER BY
        d.pk_department, emp.pk_employee;
SQL;

const GET_EMPLOYEE_DEPARTMENTS = <<< SQL
    SELECT 
        fk_department 
    FROM 
        tr_department_employee 
    WHERE fk_employee = ?;
SQL;

const UPDATE_DEPARTMENT = <<< SQL
    UPDATE 
        t_department
    SET 
        name = ?, 
        notify_by_sms = ?,
        notify_by_mail = ?, 
        fk_manager = ?, 
        fk_communication = ?
    WHERE 
        pk_department = ?;
SQL;

const DELETE_DEPARTMENT_EMPLOYEES = <<< SQL
    DELETE FROM 
        tr_department_employee
    WHERE 
        fk_department = ?;
SQL;

const DELETE_DEPARTMENT_BY_PK = <<< SQL
    DELETE FROM 
        t_department
    WHERE 
        pk_department = ?;
SQL;

const GET_DEPARTMENT_EMPLOYEES = <<< SQL
    SELECT 
        * 
    FROM 
        tr_department_employee 
    WHERE 
        fk_department = ?;
SQL;
// Gestion des départements


// Gestion des collaborateurs
const GET_EMPLOYEES = <<< SQL
    SELECT 
        e.pk_employee, 
        e.first_name, 
        e.last_name, 
        e.mail, 
        e.tel_number, 
        e.date_of_birth, 
        e.date_of_hire,
        d.pk_department AS department_pk_department, 
        d.name AS department_name
    FROM 
        t_employee e
    INNER JOIN 
        tr_department_employee tde ON e.pk_employee = tde.fk_employee
    INNER JOIN 
        t_department d ON tde.fk_department = d.pk_department
    ORDER BY
        e.first_name, e.last_name;
SQL;

const GET_EMPLOYEE_BY_MAIL = <<< SQL
    SELECT 
        * 
    FROM 
        t_employee 
    WHERE 
        mail = ?;
SQL;

const GET_EMPLOYEE_BY_MAIL_EXCEPT_PK = <<< SQL
    SELECT 
        * 
    FROM 
        t_employee 
    WHERE 
        mail = ? AND pk_employee != ?;
SQL;

const GET_EMPLOYEE_BY_TEL_NUMBER = <<< SQL
    SELECT 
        * 
    FROM 
        t_employee 
    WHERE 
        tel_number = ?
SQL;

const GET_EMPLOYEE_BY_TEL_NUMBER_EXCEPT_PK = <<< SQL
    SELECT 
        * 
    FROM 
        t_employee 
    WHERE 
        tel_number = ? AND pk_employee != ?;
SQL;

const INSERT_EMPLOYEE = <<< SQL
    INSERT INTO 
        t_employee (first_name, last_name, mail, tel_number, date_of_birth, date_of_hire)
    VALUES 
        (?, ?, ?, ?, ?, ?);
SQL;

const INSERT_EMPLOYEE_DEPARTMENT = <<< SQL
    INSERT INTO 
        tr_department_employee (fk_department, fk_employee)
    VALUES 
        (?, ?);
SQL;

const GET_EXISTING_DEPARTMENT_BY_PK = <<< SQL
    SELECT 
        pk_department
    FROM 
        t_department
    WHERE 
        pk_department = ?
SQL;

const GET_EMPLOYEE_BY_PK = <<< SQL
    SELECT 
        e.pk_employee, 
        e.first_name, 
        e.last_name, 
        e.mail, 
        e.tel_number, 
        e.date_of_birth, 
        e.date_of_hire,
        d.pk_department AS department_pk_department, 
        d.name AS department_name
    FROM 
        t_employee e
    INNER JOIN 
        tr_department_employee tde ON e.pk_employee = tde.fk_employee
    INNER JOIN 
        t_department d ON tde.fk_department = d.pk_department
    WHERE 
        e.pk_employee = ?;
SQL;

const UPDATE_EMPLOYEE = <<< SQL
    UPDATE 
        t_employee
    SET 
        first_name = ?,
        last_name = ?, 
        mail = ?, 
        tel_number = ?,
        date_of_birth = ?, 
        date_of_hire = ?
    WHERE 
        pk_employee = ?;
SQL;

const DELETE_EMPLOYEE_DEPARTMENTS = <<< SQL
    DELETE FROM 
        tr_department_employee
    WHERE 
        fk_employee = ?;
SQL;

const DELETE_EMPLOYEE_BY_PK = <<< SQL
    DELETE FROM 
        t_employee 
    WHERE 
        pk_employee = ?;
SQL;

const CHECK_EMPLOYEE_IS_MANAGER = <<< SQL
    SELECT 
        name AS department_name
    FROM 
        t_department
    WHERE 
        fk_manager = ?;
SQL;
// Gestion des collaborateurs

// Gestion des communications
const GET_EXISTING_COMMUNICATION_BY_PK = <<< SQL
    SELECT 
        pk_communication,
        description, 
        birthday_msg, 
        html_birthday_msg, 
        notification_delay
    FROM 
        t_communication
    WHERE 
        pk_communication = ?;
SQL;

const INSERT_COMMUNICATIONS = <<< SQL
    INSERT INTO
        t_communication (
            description, 
            birthday_msg, 
            html_birthday_msg, 
            notification_delay
        ) 
    VALUES 
        (?, ?, ?, ?);
SQL;

const GET_COMMUNICATIONS = <<< SQL
    SELECT 
        pk_communication, 
        description,
        birthday_msg, 
        html_birthday_msg,
        notification_delay
    FROM 
        t_communication;
SQL;

const UPDATE_COMMUNICATIONS = <<< SQL
    UPDATE 
        t_communication
    SET 
        description = ?,
        birthday_msg = ?, 
        html_birthday_msg = ?, 
        notification_delay = ?
    WHERE 
        pk_communication = ?;
SQL;

const DELETE_COMMUNICATIONS_BY_PK = <<< SQL
    DELETE FROM 
        t_communication
    WHERE 
        pk_communication = ?;
SQL;

const CHECK_DEPARTMENT_HAS_THIS_COMMUNICATION = <<< SQL
    SELECT 
        COUNT(*) AS count
    FROM 
        t_department
    WHERE 
        fk_communication = ?;
SQL;
// Gestion des communications

// Gestion des anniversaires
const GET_TODAYS_BIRTHDAYS = <<< SQL
    SELECT 
        e.pk_employee,
        e.first_name,
        e.last_name, 
        e.mail, 
        e.tel_number, 
        e.date_of_birth,
        d.name AS department_name, 
        c.birthday_msg,
        c.html_birthday_msg, 
        d.notify_by_sms, 
        d.notify_by_mail
    FROM 
        t_employee e
    INNER JOIN 
        tr_department_employee de ON e.pk_employee = de.fk_employee
    INNER JOIN 
        t_department d ON de.fk_department = d.pk_department
    INNER JOIN 
        t_communication c ON d.fk_communication = c.pk_communication
    WHERE 
        DATE_FORMAT(e.date_of_birth, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d');
SQL;

const GET_MONTHS_BIRTHDAYS = <<<SQL
    SELECT 
        pk_employee,
        first_name,
        last_name,
        birthday_date,
        birthday_type
    FROM (
        SELECT 
            pk_employee,
            e.first_name,
            e.last_name,
            DATE_FORMAT(e.date_of_birth, '%d-%m') AS birthday_date,
            CASE 
                WHEN MOD(YEAR(CURDATE()) - YEAR(e.date_of_birth), 10) = 0 THEN 'Anniversaire important'
                ELSE 'Anniversaire de naissance'
            END AS birthday_type
        FROM 
            t_employee e
        WHERE 
            MONTH(e.date_of_birth) = MONTH(CURDATE())

        UNION ALL

        SELECT 
            pk_employee,
            e.first_name,
            e.last_name,
            DATE_FORMAT(e.date_of_hire, '%d-%m') AS birthday_date,
            'Anniversaire d\'ancienneté' AS birthday_type
        FROM 
            t_employee e
        WHERE 
            MONTH(e.date_of_hire) = MONTH(CURDATE())
            AND MOD(YEAR(CURDATE()) - YEAR(e.date_of_hire), 5) = 0
        ) AS birthdays
    ORDER BY 
        MONTH(STR_TO_DATE(birthday_date, '%d-%m')), DAY(STR_TO_DATE(birthday_date, '%d-%m'));
SQL;


const GET_ADMIN_MONTHS_BIRTHDAYS = <<<SQL
    SELECT 
        pk_employee,
        first_name,
        last_name,
        mail,
        tel_number,
        birthday_date,
        birthday_type
    FROM (
        SELECT 
            pk_employee,
            e.first_name,
            e.last_name,
            e.mail,
            e.tel_number,
            DATE_ADD(e.date_of_birth, INTERVAL (YEAR(CURDATE()) - YEAR(e.date_of_birth)) YEAR) AS birthday_date,
            CASE 
                WHEN MOD(YEAR(CURDATE()) - YEAR(e.date_of_birth), 10) = 0 THEN 
                    CONCAT('Anniversaire important (', YEAR(CURDATE()) - YEAR(e.date_of_birth), ' ans)')
                ELSE 
                    CONCAT('Anniversaire de naissance (', YEAR(CURDATE()) - YEAR(e.date_of_birth), ' ans)')
            END AS birthday_type
        FROM 
            t_employee e
        WHERE 
            DATE_ADD(e.date_of_birth, INTERVAL (YEAR(CURDATE()) - YEAR(e.date_of_birth)) YEAR) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 180 DAY)

        UNION ALL

        SELECT 
            pk_employee,
            e.first_name,
            e.last_name,
            e.mail,
            e.tel_number,
            DATE_ADD(e.date_of_hire, INTERVAL (YEAR(CURDATE()) - YEAR(e.date_of_hire)) YEAR) AS birthday_date,
            CONCAT('Anniversaire d\'ancienneté (', YEAR(CURDATE()) - YEAR(e.date_of_hire), ' ans)') AS birthday_type
        FROM 
            t_employee e
        WHERE 
            DATE_ADD(e.date_of_hire, INTERVAL (YEAR(CURDATE()) - YEAR(e.date_of_hire)) YEAR) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 180 DAY)
            AND MOD(YEAR(CURDATE()) - YEAR(e.date_of_hire), 5) = 0
        ) AS birthdays
    ORDER BY 
        MONTH(birthday_date), DAY(birthday_date);
SQL;


const GET_SPECIAL_BIRTHDAYS = <<< SQL
    SELECT 
        e.pk_employee,
        e.first_name,
        e.last_name,
        e.mail,
        e.tel_number,
        e.date_of_birth,
        e.date_of_hire,
        d.pk_department,
        d.name AS department_name,
        m.first_name AS manager_first_name,
        m.last_name AS manager_last_name,
        m.mail AS manager_mail,
        c.notification_delay,
        CASE 
            WHEN DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL c.notification_delay DAY), '%m-%d') = DATE_FORMAT(e.date_of_birth, '%m-%d')
                 AND MOD(YEAR(CURDATE()) - YEAR(e.date_of_birth), 10) = 0 THEN 'Anniversaire important'
            WHEN DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL c.notification_delay DAY), '%m-%d') = DATE_FORMAT(e.date_of_hire, '%m-%d')
                 AND MOD(YEAR(CURDATE()) - YEAR(e.date_of_hire), 5) = 0 THEN 'Anniversaire d\'ancienneté'
        END AS birthday_type
    FROM 
        t_employee e
    INNER JOIN 
        tr_department_employee de ON e.pk_employee = de.fk_employee
    INNER JOIN 
        t_department d ON de.fk_department = d.pk_department
    INNER JOIN 
        t_employee m ON d.fk_manager = m.pk_employee
    INNER JOIN 
        t_communication c ON d.fk_communication = c.pk_communication
    WHERE 
        (
            DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL c.notification_delay DAY), '%m-%d') = DATE_FORMAT(e.date_of_birth, '%m-%d')
            AND MOD(YEAR(CURDATE()) - YEAR(e.date_of_birth), 10) = 0
        ) OR (
            DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL c.notification_delay DAY), '%m-%d') = DATE_FORMAT(e.date_of_hire, '%m-%d')
            AND MOD(YEAR(CURDATE()) - YEAR(e.date_of_hire), 5) = 0
        )
SQL;
// Gestion des anniversaires