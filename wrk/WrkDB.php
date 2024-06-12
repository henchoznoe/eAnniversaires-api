<?php

namespace Wrk;

use HTTP\HTTPResponses;
use PDO;
use PDOException;

/**
 * Classe de gestion de la base de données.
 *
 * Cette classe permet d'établir une connexion à la base de données MariaDB et d'exécuter des requêtes SQL.
 * Elle utilise l'extension PDO pour la gestion des requêtes et des transactions.
 *
 * @since 05.2024
 * @author Noé Henchoz
 */
class WrkDB {

    private PDO $pdo;

    /**
     * Constructeur de la classe WrkDB.
     *
     * Initialise la connexion à la base de données MariaDB.
     */
    public function __construct() {
        try {
            $dbUrl = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
            $this->pdo = new PDO($dbUrl, DB_USER, DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch ( PDOException $ex ) {
            HTTPResponses::error(500, $ex->getMessage());
        }
    }

    /**
     * Exécute une requête SQL de récupération.
     *
     * @param string $query La requête SQL à exécuter.
     * @param array $params Les paramètres de la requête.
     */
    public function select(string $query, array $params = [], bool $fetchAll = false): array|bool {
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        return $fetchAll ? $statement->fetchAll(PDO::FETCH_ASSOC) : $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Exécute une requête SQL d'insertion, de mise à jour ou de suppression.
     *
     * @param string $query La requête SQL à exécuter.
     * @param array $params Les paramètres de la requête.
     */
    public function execute(string $query, array $params = []): bool {
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Démarre une transaction.
     *
     * @return void
     */
    public function beginTransaction(): void {
        $this->pdo->beginTransaction();
    }

    /**
     * Valide une transaction.
     *
     * @return void
     */
    public function commit(): void {
        $this->pdo->commit();
    }

    /**
     * Annule une transaction.
     *
     * @return void
     */
    public function rollBack(): void {
        $this->pdo->rollBack();
    }

    /**
     * Retourne l'identifiant de la dernière ligne insérée.
     *
     * @return bool|string L'identifiant de la dernière ligne insérée, ou faux si aucune ligne n'a été insérée.
     */
    public function lastInsertId(): bool|string {
        return $this->pdo->lastInsertId();
    }

}