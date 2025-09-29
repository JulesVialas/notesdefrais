<?php

namespace services;

use PDO;
use PDOException;

/**
 * Classe de gestion de la connexion à la base de données
 *
 * Cette classe implémente le pattern Singleton pour fournir un accès unique
 * à la connexion PDO vers la base de données de l'application.
 */
class Database
{
    /** @var PDO|null Instance unique de la connexion PDO à la base de données */
    private static $pdo;

    /**
     * Constructeur privé pour empêcher l'instanciation directe
     *
     * Conforme au pattern Singleton, initialise la connexion PDO.
     */
    private function __construct()
    {
        $this->initialiserPDO();
    }

    /**
     * Initialise la connexion PDO à la base de données
     *
     * Utilise les variables d'environnement pour configurer la connexion
     * et définit les options PDO recommandées pour l'application.
     *
     * @return void
     * @throws PDOException Si la connexion à la base de données échoue
     */
    private function initialiserPDO()
    {
        try {
            $host = Config::get('DB_HOST');
            $dbname = Config::get('DB_NAME');
            $charset = Config::get('DB_CHARSET');
            $user = Config::get('DB_USER');
            $password = Config::get('DB_PASSWORD');
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            self::$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $password, $options);
            self::$pdo->exec("SET CHARACTER SET utf8");
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    /**
     * Récupère l'instance unique de PDO
     *
     * Si l'instance n'existe pas encore, elle est créée.
     *
     * @return PDO Instance de la connexion PDO à la base de données
     */
    public static function getPDO()
    {
        if (self::$pdo === null) {
            new Database();
        }
        return self::$pdo;
    }
}