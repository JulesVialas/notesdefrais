<?php

namespace controllers;

use services\Config;
use services\NoteDeFrais;

/**
 * Contrôleur pour gérer l'affichage des notes de frais archivées
 *
 * @package controllers
 */
class NotesDeFraisArchiveesController
{
    private NoteDeFrais $noteDeFrais;

    public function __construct()
    {
        $this->noteDeFrais = new NoteDeFrais();
    }

    /**
     * Traite les requêtes POST pour exporter les notes de frais archivées
     */
    public function post()
    {

        // Vérifier si une action d'export est demandée
        if (isset($_POST['action']) && $_POST['action'] === 'export') {
            if (isset($_POST['ids']) && !empty($_POST['ids'])) {
                $ids = explode(',', $_POST['ids']);
                $notesDeFraisToExport = [];

                foreach ($ids as $id) {
                    $noteDeFrais = $this->noteDeFrais->getNoteDeFraisById((int)$id);
                    if ($noteDeFrais && $noteDeFrais['Statut'] === 'Terminée') {
                        $notesDeFraisToExport[] = $noteDeFrais;
                    }
                }

                // Exporter les notes de frais
                if (!empty($notesDeFraisToExport)) {
                    $this->noteDeFrais->exportation($notesDeFraisToExport);
                }
            }
        }

        // Si on arrive ici, c'est que l'export n'a pas été déclenché ou a échoué
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    /**
     * Traite les requêtes GET pour afficher la liste des notes de frais archivées
     */
    public function get()
    {
        if (!isset($_SESSION['id']) || !isset($_SESSION['LibelleUtilisateur'])) {
            header('Location: ' . Config::get("APP_URL") . 'login');
            exit;
        }
        // Récupérer les filtres depuis les paramètres GET
        $filterNom = isset($_GET['nom']) ? trim($_GET['nom']) : '';
        $filterDate = isset($_GET['date']) ? trim($_GET['date']) : '';

        // Passer les filtres au service
        $notesDeFrais = $this->noteDeFrais->getNotesDeFraisByStatut(
            $_SESSION['LibelleUtilisateur'],
            "Terminée",
            true,
            $filterNom,
            $filterDate
        );

        // Afficher la vue avec les données
        require 'views/notes-de-frais-archivees.php';
    }
}