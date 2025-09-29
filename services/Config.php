<?php

namespace services;

use Dotenv\Dotenv;

/**
 * Classe de gestion de la configuration
 *
 * Cette classe permet de charger et d'accéder aux variables d'environnement
 * définies dans un fichier .env à la racine du projet.
 */
class Config
{
    /** @var Dotenv|null Instance de Dotenv utilisée pour charger les variables d'environnement */
    private static $dotenv = null;

    /**
     * Récupère la valeur d'une variable d'environnement
     *
     * @param string $cle Nom de la variable d'environnement à récupérer
     * @return mixed Valeur de la variable d'environnement ou null si non définie
     */
    public static function get($cle)
    {
        self::load();
        return isset($_ENV[$cle]) ? $_ENV[$cle] : null;
    }

    /**
     * Charge les variables d'environnement depuis le fichier .env
     *
     * Cette méthode n'est appelée qu'une seule fois et initialise l'instance de Dotenv.
     *
     * @return void
     */
    public static function load()
    {
        if (self::$dotenv === null) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
            self::$dotenv = $dotenv;
        }
    }
}