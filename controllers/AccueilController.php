<?php

namespace controllers;

use services\Config;
use services\NoteDeFrais;

/**
 * Contrôleur de la page d'accueil
 *
 * Cette classe gère l'affichage de la page d'accueil qui présente les différentes
 * notes de frais de l'utilisateur, classées par statut.
 */
class AccueilController
{
    /** @var NoteDeFrais Service de gestion des notes de frais */
    private NoteDeFrais $NoteDeFrais;

    /**
     * Constructeur du contrôleur d'accueil
     *
     * Initialise le service NoteDeFrais nécessaire pour récupérer les données.
     */
    public function __construct()
    {
        $this->NoteDeFrais = new NoteDeFrais();
    }

    /**
     * Gère les requêtes POST vers la page d'accueil
     *
     * Traite les actions comme la suppression des notes de frais.
     *
     * @return void
     */
    public function post()
    {
        // Traitement de la suppression si demandée
        if (($_POST['action'] ?? '') === 'supprimer_note') {
            $noteId = (int)($_POST['note_id'] ?? 0);
            if ($noteId > 0) {
                try {
                    $this->NoteDeFrais->supprimerNoteDeFrais($noteId);
                    $_SESSION['flash_success'] = 'Note de frais supprimée avec succès.';
                } catch (\Exception $e) {
                    $_SESSION['flash_error'] = 'Erreur lors de la suppression : ' . $e->getMessage();
                }
                // Redirection pour éviter la resoumission du formulaire
                header('Location: ' . Config::get("APP_URL"));
                exit;
            }
        }

        $this->get();
    }

    /**
     * Affiche la page d'accueil avec les notes de frais de l'utilisateur
     *
     * Récupère les informations de l'utilisateur depuis la configuration et
     * charge les notes de frais selon leur statut (en cours, terminées, refusées).
     *
     * @return void
     */
    public function get()
    {
        if (!isset($_SESSION['id']) || !isset($_SESSION['LibelleUtilisateur'])) {
            header('Location: ' . Config::get("APP_URL") . 'login');
            exit;
        }
        $libelleUtilisateur = $_SESSION['LibelleUtilisateur'];
        $notesDeFraisEnCours = array_merge(
            $this->NoteDeFrais->getNotesDeFraisByStatut($libelleUtilisateur, 'En cours de validation'),
            $this->NoteDeFrais->getNotesDeFraisByStatut($libelleUtilisateur, 'En cours de traitement comptable'),
            $this->NoteDeFrais->getNotesDeFraisByStatut($libelleUtilisateur, 'En cours de saisie')
        );
        $notesDeFraisTerminees = $this->NoteDeFrais->getNotesDeFraisByStatut($libelleUtilisateur, 'Terminée');
        $notesDeFraisRefusees = $this->NoteDeFrais->getNotesDeFraisByStatut($libelleUtilisateur, 'Refusée');
        $nbNotesCeMois = $this->NoteDeFrais->countNotesDeFraisForCurrentMonth($libelleUtilisateur);
        require __DIR__ . '/../views/accueil.php';
    }
}